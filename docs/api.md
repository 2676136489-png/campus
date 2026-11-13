# API 文档

统一入口：`api.php`，返回 JSON。POST 操作需要 CSRF Token。

## 获取未读通知数

```http
GET /api.php?action=unread
```

响应：

```json
{"ok": true, "unread": 3}
```

## 点赞 / 取消点赞

```http
POST /api.php
Content-Type: multipart/form-data

action=like
dynamicPk=1
csrf_token=...
```

响应：

```json
{"ok": true, "liked": true, "likeCount": 6}
```

## 保存动态草稿

```http
POST /api.php

action=saveDraft
content=今天的草稿内容
tags=学习,摄影
csrf_token=...
```

内容与标签都为空时会清除草稿。

响应：

```json
{"ok": true}
```

## 收藏 / 取消收藏

```http
POST /api.php

action=favorite
dynamicPk=1
csrf_token=...
```

响应：

```json
{"ok": true, "favorited": true, "favoriteCount": 3}
```

## 发表评论

```http
POST /api.php

action=comment
dynamicPk=1
comment=很棒
parentPk=0
csrf_token=...
```

响应包含评论对象。

## 删除评论

```http
POST /api.php

action=deleteComment
commentPk=5
csrf_token=...
```

仅评论作者、动态发布者、管理员可操作。

## 关注 / 取消关注

```http
POST /api.php

action=follow
targetUser=3
csrf_token=...
```

响应：

```json
{"ok": true, "following": true, "followerCount": 12, "followingCount": 8}
```

## 发送私信

```http
POST /api.php

action=sendMessage
targetUser=3
content=周末一起打球吗
csrf_token=...
```

可选附加图片字段 `image`，单张最大 20MB，支持 JPG/PNG/GIF。

## 撤回私信

```http
POST /api.php

action=recallMessage
messagePk=120
csrf_token=...
```

仅发送者可在 2 分钟内撤回。

## 加载更早私信

```http
GET /api.php?action=messagesBefore&user=3&before=120
```

返回 `before` 之前的历史消息。

## 轮询新私信

```http
GET /api.php?action=messages&user=3&after=120
```

响应包含 `after` 之后的新消息和未读私信数。

## 会话置顶 / 免打扰

```http
POST /api.php

action=conversationSetting
targetUser=3
field=isPinned
value=1
csrf_token=...
```

## 更新隐私设置

```http
POST /api.php

action=privacy
allowMessages=followed
showProfile=1
csrf_token=...
```

## 拉黑 / 解除拉黑

```http
POST /api.php

action=block
targetUser=3
csrf_token=...
```

响应：

```json
{"ok": true, "blocked": true}
```

## 全部标记已读

```http
POST /api.php

action=readNotifications
csrf_token=...
```
## 健康检查

无需登录，供部署探活和监控使用。

```http
GET /api.php?action=health
```

响应：

```json
{"ok": true, "service": "campus-circle", "version": "1.0.0", "db": "ok", "time": "2026-08-09T12:00:00+08:00"}
```

`db` 为 `ok` 或 `error`，表示 MySQL 连接检查结果。

## 实时未读通知（SSE）

```http
GET /p_sse.php
```

需要学生登录且实名审核状态为已通过。浏览器使用 `EventSource` 订阅，连接最长保持 60 秒，服务端每 5 秒检查一次未读通知数；数量变化时推送 `unread` 事件。

事件格式：

```text
event: unread
data: {"count": 3}
```

客户端应在 `error` 时回退到 `GET /api.php?action=unread` 轮询，并在 `visibilitychange` 恢复页面可见时重新建立连接。

## 管理员登录与 TOTP 二次验证

管理员登录使用表单 POST 到 `p_adminAuth.php`，所有请求都需要 `csrf_token`。

第一步，提交管理员账号密码：

```http
POST /p_adminAuth.php
Content-Type: application/x-www-form-urlencoded

adminLogin=1
adminUser=admin
adminPwd=...
csrf_token=...
```

若管理员已开启 TOTP，服务端不会直接建立会话，而是暂存待验证账号并重新渲染二次验证表单。此时提交：

```http
POST /p_adminAuth.php
Content-Type: application/x-www-form-urlencoded

adminTotpLogin=1
totpCode=123456
csrf_token=...
```

验证成功后写入管理员会话。若当前仍在使用初始密码，必须先修改密码才能使用后台功能。

管理员登录后可通过同一页面管理 TOTP：

- `provisionAdminTotp=1`：生成并暂存 TOTP 密钥，页面展示身份验证器配置信息。
- `enableAdminTotp=1` + `totpCode`：校验动态码后启用二次验证。
- `disableAdminTotp=1` + `totpCode`：校验动态码后关闭二次验证。
## AI 助手

独立于 `api.php` 的对话接口，由 `ai_chat_api.php` 代理 Dify Agent 应用。
学生会话与管理员会话自动分流到两套 Dify 应用与密钥，密钥只存在服务端。

### 发送提问

```http
POST /ai_chat_api.php?action=send
Content-Type: application/x-www-form-urlencoded

question=最近有什么校园活动&conversationId=conv-8f3a1c92&csrf_token=...
```

| 字段 | 必填 | 说明 |
|------|------|------|
| `question` | 是 | 提问内容，长度 1-500 |
| `conversationId` | 否 | 会话标识，`[A-Za-z0-9-]{8,64}`；首次留空，服务端返回新的标识供后续续聊 |
| `csrf_token` | 是 | 写操作 CSRF 令牌 |

```json
{
  "ok": true,
  "answer": "……",
  "conversationId": "conv-8f3a1c92",
  "entities": [{ "type": "student", "pk": 58, "name": "张启铭" }]
}
```

`entities` 是服务端做的实体链接化结果：回答里的站内同学会被标记出来，前端渲染成可点击卡片，不依赖模型输出格式。

### 拉取历史

```http
GET /ai_chat_api.php?action=history&conversationId=conv-8f3a1c92
```

```json
{ "ok": true, "messages": [{ "question": "……", "answer": "……" }] }
```

`conversationId` 为空或非法时返回空数组。

### 错误码

| 状态码 | error | 原因 |
|--------|-------|------|
| 400 | `bad_question` | 提问为空或超过 500 字 |
| 400 | `unknown_action` | action 不匹配 |
| 403 | `bad_csrf` | CSRF 校验失败 |
| 502 | `ai_upstream_error` | 上游返回异常或流式数据不完整 |
| 503 | `ai_not_configured` | 服务端未配置对应应用的密钥 |

## 站点数据工具接口

供 Dify Agent 调用的站内数据接口，鉴权走请求头 `X-API-Key`：

- `POST /ai_api.php`（学生版，密钥 `AI_API_KEY`）
- `POST /ai_admin_api.php`（管理员版，密钥 `AI_ADMIN_API_KEY`）
