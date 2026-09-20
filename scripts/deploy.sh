#!/usr/bin/env bash
set -euo pipefail

echo "==> Installing dependencies"
composer install --no-dev --no-interaction --prefer-dist

echo "==> Applying migrations"
php bin/migrate.php

echo "==> Building and starting production containers"
docker compose -f deploy/docker-compose.prod.yml --env-file deploy/.env.production up -d --build

echo "==> Health check"
sleep 5
curl -fsS http://127.0.0.1:${APP_PORT:-8080}/bin/health.php || true