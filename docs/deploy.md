# 生产部署指南

## 1. 环境要求

- PHP 7.3+（推荐 8.2）且启用 mysqli、gd、fileinfo
- MySQL 5.7+ / 8.0
- Apache 或 Nginx
- 可选：Docker Compose

## 2. Docker Compose 生产部署

```bash
cp deploy/.env.production.example deploy/.env.production
# 修改 deploy/.env.production 中的密码
bash scripts/deploy.sh
```

启动后访问：

- 应用：`http://服务器IP:8080/p_loginStu.php`
- Web 健康检查：`http://服务器IP:8080/api.php?action=health`
- 命令行健康检查：`php bin/health.php`

## 3. 手动部署

1. 将项目上传到 `/var/www/html`
2. 配置数据库并执行 `php bin/migrate.php`
3. 可选执行 `php bin/seed.php` 生成演示数据
4. 配置 Nginx 或 Apache（参考 `deploy/nginx.conf`、`deploy/apache.conf`）
5. 配置 SSL：`certbot --nginx -d campus.example.com`
6. 设置环境变量：

```env
APP_ENV=production
DB_HOST=127.0.0.1
DB_NAME=circle
DB_USER=circle_user
DB_PASS=强密码
ADMIN_INITIAL_PASSWORD=强管理员密码
LOGIN_RATE_IP_FAILURES=20
LOGIN_RATE_NAME_FAILURES=10

# 登录邮箱验证码二步验证：MAIL_TRANSPORT 取 smtp / log / none。
# 三通道都不通时总开关 LOGIN_EMAIL_OTP_ENABLED 强制为 0，登录降级为直登而不是锁人。
MAIL_TRANSPORT=smtp
SMTP_HOST=smtp.qq.com
SMTP_PORT=465
SMTP_USERNAME=发信邮箱
SMTP_PASSWORD=邮箱授权码
SMTP_SENDER=发信邮箱
LOGIN_EMAIL_OTP_ENABLED=1

# AI 助手（Dify 控制台 → API 访问 → 生成密钥；Endpoint 以该页显示为准）
DIFY_BASE_URL=https://api.dify.ai/v1
DIFY_API_KEY=学生端应用密钥
DIFY_ADMIN_API_KEY=管理员端应用密钥
# 供 Dify 工具调用的站内数据接口密钥（工具弹窗用请求头 X-API-Key 传）
AI_API_KEY=站内数据接口密钥
AI_ADMIN_API_KEY=管理员数据接口密钥
```

改完 `.env` 后需要 reload PHP-FPM，常驻 worker 不会自动读新值。

## 4. 备份

Linux：

```bash
DB_HOST=127.0.0.1 DB_USER=root DB_PASS=xxx DB_NAME=circle bash scripts/backup.sh
```

Windows PowerShell：

```powershell
$env:DB_HOST="127.0.0.1"; $env:DB_USER="root"; $env:DB_PASS="xxx"; $env:DB_NAME="circle"
.\scripts\backup.ps1
```

## 5. 安全上线检查

- [ ] 修改数据库密码与管理员初始密码
- [ ] 开启 HTTPS
- [ ] `APP_ENV=production`
- [ ] 定期执行 `php bin/cleanup.php`
- [ ] 定期备份数据库与 `uploads/`
- [ ] 配置日志轮转与监控告警，关注 `var/logs/app.log`
- [ ] 关闭 PHP 错误展示：`display_errors=Off`
- [ ] 建议为管理员开启 TOTP 二次验证
- [ ] SSE 实时通知需确认反向代理不缓冲 `text/event-stream`
- [ ] 确认 `uploads/thumbs/` 目录可写，缩略图自动生成