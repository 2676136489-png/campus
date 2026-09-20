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
```

## 4. 目录结构与 URL 路由（重要）

页面入口统一放在 `pages/`、接口放在 `api/`，根目录只保留 `index.php` 作为唯一入口。所有历史 URL（`/p_loginStu.php`、`/api.php`、`/ai_api.php` 等）**保持不变**，由根 `index.php` 转发，因此无需改动任何前端链接或外部集成。

代价是：**Web 服务器必须把「根目录下不存在的 .php 请求」回退到 `index.php`**，否则页面会 404。

### Nginx

`location ~ \.php$` 中默认的 `try_files $uri =404;` 必须改为回退到入口：

```nginx
location ~ \.php$ {
    try_files $uri /index.php?$query_string;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass 127.0.0.1:9000;
}
```

宝塔面板：站点 → 设置 → 配置文件，把 `location ~ [^/]\.php(/|$)` 里的 `try_files $uri =404;` 改成 `try_files $uri /index.php?$query_string;`，保存后重载 Nginx。

同时建议加上内部目录保护（`pages/`、`api/` 只允许经入口访问）：

```nginx
location ~ ^/(pages|api|lib|config|database|bin|scripts|tests|deploy|benchmark|var)/ {
    deny all;
}
```

### Apache

项目根目录的 `.htaccess` 已包含回退规则与目录保护，只需保证站点允许 `.htaccess` 生效：

```apache
<Directory "/var/www/html">
    AllowOverride All
</Directory>
```

### 本地开发

使用内置服务器时必须把 `index.php` 作为路由脚本传入：

```bash
php -S 127.0.0.1:8099 -t . index.php
```

不带 `index.php` 时，PHP 内置服务器只按文件路径查找，`/p_loginStu.php` 会 404。

## 5. 备份

Linux：

```bash
DB_HOST=127.0.0.1 DB_USER=root DB_PASS=xxx DB_NAME=circle bash scripts/backup.sh
```

Windows PowerShell：

```powershell
$env:DB_HOST="127.0.0.1"; $env:DB_USER="root"; $env:DB_PASS="xxx"; $env:DB_NAME="circle"
.\scripts\backup.ps1
```

## 6. 安全上线检查

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
- [ ] 已按第 4 节配置 `.php` 请求回退规则，`/p_loginStu.php` 可正常访问