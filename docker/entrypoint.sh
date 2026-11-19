#!/bin/sh
set -e

cd /var/www/html

# 只有真的要啟動服務（CMD 是 supervisord）才做下面這些準備。
# 一次性指令要直接放行，否則連 `php artisan key:generate --show`
# 都會被 APP_KEY 檢查擋掉，變成雞生蛋蛋生雞。
case "$1" in
    *php-fpm) ;;
    *) exec "$@" ;;
esac

echo "[entrypoint] 準備啟動…"

# APP_KEY 沒設的話 session 和加密都會壞掉，直接擋下來比較好debug
if [ -z "${APP_KEY}" ]; then
    echo "[entrypoint] 錯誤：APP_KEY 沒有設定。"
    echo "[entrypoint] 在主機上執行 docker run --rm <image> php artisan key:generate --show"
    echo "[entrypoint] 把產生的字串填進 .env 的 APP_KEY。"
    exit 1
fi

# storage 通常是掛載進來的 volume，權限要在執行時重設
chown -R www-data:www-data storage bootstrap/cache
mkdir -p storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs \
         storage/app/public

# 套件探索。build 階段用了 --no-scripts，所以在這裡補做
php artisan package:discover --ansi

# 這些快取吃的是環境變數，只有執行時才知道實際的值，
# 所以不能在 build 階段做
php artisan config:cache
php artisan route:cache
php artisan view:cache

# public/storage 的軟連結。掛載 volume 之後會不見，每次啟動重建
php artisan storage:link 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache

# ---- 把 public/ 同步給 nginx ----
# nginx 是官方標準 image，裡面沒有這個專案的檔案，靜態檔要靠共用目錄給它。
# 每次啟動都整份覆蓋，是為了避免換 image 之後舊的 JS/CSS 留在目錄裡——
# Vite 的檔名帶 hash，舊檔留著不會被覆蓋，但 build/manifest.json 會指到新檔，
# 混在一起很難察覺哪裡不對。
if [ -d /var/www/html/public-shared ]; then
    echo "[entrypoint] 同步 public/ 給 nginx…"
    rm -rf /var/www/html/public-shared/.[!.]* /var/www/html/public-shared/* 2>/dev/null || true
    cp -a /var/www/html/public/. /var/www/html/public-shared/
fi

# 資料庫遷移預設不自動跑。兩個環境共用同一個 image，
# 自動跑的話很容易在不預期的時候改到正式資料。
# 要開啟就在 .env 設 AUTO_MIGRATE=true
if [ "${AUTO_MIGRATE}" = "true" ]; then
    echo "[entrypoint] 等待資料庫…"
    i=0
    until php artisan migrate --force 2>/dev/null; do
        i=$((i + 1))
        if [ "$i" -ge 30 ]; then
            echo "[entrypoint] 資料庫連不上，放棄遷移。"
            break
        fi
        sleep 2
    done
fi

echo "[entrypoint] 啟動完成。"

exec "$@"
