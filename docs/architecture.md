# 架构说明

## 分层

- 页面层：`p_*.php` 负责接收请求、渲染视图、调用服务函数
- API 层：`api.php` 提供 JSON 接口，统一处理登录态、CSRF 与错误响应
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
```

## 核心模块

| 模块 | 职责 |
|------|------|
| bootstrap.php | Session 安全、安全响应头、统一致命错误日志注册 |
| logger.php | 结构化应用日志，写入 var/logs/app.log |
| core.php | 数据库连接、CSRF、迁移入口 |
| migrations.php | 版本化 SQL 迁移与性能索引 |
| auth.php | 登录、可配置限流、密码找回、会话令牌 |
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