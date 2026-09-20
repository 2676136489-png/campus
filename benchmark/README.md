# 压测脚本

使用 [k6](https://k6.io) 对公开页面、静态资源与登录态接口进行冒烟/负载测试。

## 公开页面压测

```bash
k6 run benchmark/load-test.js
```

指定服务地址：

```bash
k6 run -e BASE_URL=http://127.0.0.1:8765 benchmark/load-test.js
```

覆盖：

- 登录页
- 用户协议页
- 隐私政策页
- 核心 CSS 资源

## 登录态接口压测

先用浏览器登录学生账号，从开发者工具复制当前站点 Cookie（至少包含 `PHPSESSID`），再注入 k6：

```bash
k6 run -e BASE_URL=http://127.0.0.1:8765 -e "SESSION_COOKIE=PHPSESSID=...; CAMPUS_CSRF=..." benchmark/load-test-auth.js
```

覆盖：

- `api.php?action=unread` 未读通知接口
- `p_dynamics.php?page=1` 动态广场
- `p_notifications.php?page=1` 通知列表
- `p_messages.php` 私信列表

## 为什么登录接口本身不做高频压测

登录接口包含一次性验证码、CSRF 与登录限流，直接压测会消耗验证码并触发风控，不代表真实容量。生产压测时建议：

1. 使用真实演示账号按低频并发验证登录链路。
2. 通过浏览器导出 Session Cookie，注入 k6 压测登录后接口。
3. 记录单机 QPS、P95、失败率和 MySQL 连接数。

## 后续建议指标

- 单机 QPS
- 接口 P95 / P99
- 数据库连接池使用率
- 图片上传与缩略图生成耗时
- 私信轮询升级 SSE/WebSocket 后的连接数