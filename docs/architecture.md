# 架构说明

## 分层

- 页面层：`p_*.php` 负责接收请求、渲染视图、调用服务函数
- API 层：`api.php` 提供 JSON 接口，统一处理登录态、CSRF 与错误响应
- 代理层：`ai_chat_api.php` 对接外部大模型服务，隔离密钥与流式协议
- 实时层：`p_sse.php` 提供 SSE 长连接，推送未读通知变化
- 服务层：`lib/*.php` 封装业务逻辑，函数全局可用
- 数据层：统一通过 `dbConnect()` 使用 MySQLi 预处理语句
- 配置层：`p_dbInfo.php` 读取环境变量与 `.env`，`config/` 存放业务配置

## 请求流程

```text
浏览器 -> p_*.php
       -> lib/bootstrap.php (安全 Session、响应头、致命错误日志)
       -> lib/autoload.php (加载模块)
       -> 业务函数
       -> MySQL

浏览器 -> api.php?action=health
       -> JSON 健康状态

浏览器 -> p_sse.php (登录后)
       -> text/event-stream
       -> unread 事件

浏览器 -> p_aiChat.php
       -> ai_chat_api.php (学生会话用 DIFY_API_KEY / 管理员会话用 DIFY_ADMIN_API_KEY)
       -> Dify Agent (streaming)
       -> SSE 分片回传，实体卡片标记在服务端补齐
```

## 核心模块

| 模块 | 职责 |
|------|------|
| bootstrap.php | Session 安全、安全响应头、统一致命错误日志注册、多标签页会话隔离 |
| logger.php | 结构化应用日志，写入 var/logs/app.log |
| core.php | 数据库连接、CSRF、迁移入口 |
| migrations.php | 版本化 SQL 迁移与性能索引 |
| auth.php | 登录、可配置限流、密码找回、活动会话令牌 |
| captcha.php | 算术验证码生成与校验 |
| student.php | 注册、资料、标签、推荐 |
| dynamic.php | 动态、点赞、评论、编辑、分页搜索 |
| notification.php | 站内通知与未读统计 |
| prefs.php | 通知偏好 |
| settings.php | 隐私设置与会话设置 |
| follow.php | 关注关系 |
| message.php | 站内私信、会话、审计查询 |
| block.php | 拉黑关系 |
| rate_limit.php | 发布/评论/私信频控 |
| moderation.php | 敏感词过滤与词库管理 |
| report.php | 举报审核 |
| upload.php | 图片校验、重编码、多尺寸缩略图 |
| audit.php | 管理员审计 |
| stats.php | 后台统计与活跃看板 |
| analytics.php | 日报与趋势 |
| export.php | CSV 导出 |
| feedback.php | 意见反馈闭环 |
| announcement.php | 校园公告 |
| maintenance.php | 过期数据清理 |

## 登录态与会话隔离

- Session 启用 strict mode、HttpOnly、SameSite，HTTPS 下启用 Secure。
- 多标签页会话隔离：`lib/bootstrap.php` 的 `tabToken()` / `tabSessionId()` 一旦拿到 8-64 位 token（`?tab=` 参数、`TAB_SESSION` cookie 或 `X-Tab-Session` 头），就把 `session_id` 改写成 `HMAC(tab, CAMPUS_TAB_SECRET)`。同一浏览器开多个标签页登录不同账号不会互相顶掉。
- 活动会话令牌：`user.active_token` 与 `$_SESSION['auth_token']` 双向校验，后登录的设备会顶掉先登录的设备，改密与登出同时让旧令牌失效。
- 学生端与管理员端使用独立会话状态，切换身份时旋转 Session 并生成新的活动会话令牌。

## AI 助手链路

- `p_aiChat.php` 自建对话页替代官方 iframe 气泡，AI 页隐藏气泡入口。
- `ai_chat_api.php` 作为服务端代理：密钥只存在服务端，按会话类型分发到学生应用（`DIFY_API_KEY`）或管理员应用（`DIFY_ADMIN_API_KEY`）。
- 上游是 Dify Agent 应用，只支持流式协议；代理逐片转发并拼接增量文本，同时剥离推理模型回答里的 `<think>` 段。
- 后端对回答做实体链接化：命中站内同学姓名的片段转成【同学|pk|姓名】标记，前端渲染成可点击跳转卡片。
- 工具授权走请求头 X-API-Key，学生版与管理员版分别调用 `/ai_api.php` 与 `/ai_admin_api.php`，数据权限由后端会话决定。

## 实时通知

- 登录学生通过 `EventSource` 连接 `p_sse.php`。
- SSE 每 5 秒检查一次未读通知数，变化时推送 `unread` 事件。
- 连接建立后立即 `session_write_close()`，避免长连接锁住会话。
- 前端连接失败或浏览器不支持时自动回退到 60 秒轮询。

## 图片处理

- 上传时通过 `getimagesize()` 校验真实图片类型，仅允许 JPG/PNG/GIF。
- JPEG/PNG 上传后重新编码，清除 EXIF 元数据并限制最大边长。
- 自动生成 `sm`、`avatar`、`md` 三档缩略图，列表页通过 `thumbnailUrl()` 加载缩略图，缺失时回退原图。

## 可观测性

- `api.php?action=health` 提供无需登录的服务与数据库健康状态。
- `bin/health.php` 用于命令行健康检查。
- PHP 致命错误由 `logFatalErrors()` 自动写入 `var/logs/app.log`。
- 登录尝试、关键管理员操作分别记录在 `login_attempt` 与 `audit_log`。

## 安全设计

- 所有写操作校验 CSRF
- 所有 SQL 使用预处理
- 输出统一转义
- 上传图片重新编码并限制尺寸
- 登录、注册、找回密码均有限流或验证码，限流阈值可由环境变量配置
- 关键管理员操作写入审计日志