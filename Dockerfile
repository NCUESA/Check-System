# syntax=docker/dockerfile:1
# check=skip=SecretsUsedInArgOrEnv
#
# 上面這行是關掉 VITE_TINYMCE_API_KEY 的警告。
# linter 看到名稱含 KEY 就會提醒，但 VITE_ 開頭的變數本來就會被
# 編譯進前端 JS，是公開值，不是機密。
# 真正的機密（KEYCLOAK_CLIENT_SECRET、DB_PASSWORD）走 .env，不進 image。

# =============================================================
#  第一階段：編譯前端
#  Vite 會把 Vue 打包成 public/build。少了這步，頁面上的
#  @vite 找不到 manifest，一開站就 500。
# =============================================================
FROM node:20-alpine AS frontend

WORKDIR /app

# 先只複製套件清單，這樣改程式碼時不會讓 npm ci 的快取失效
COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources

# TinyMCE 的金鑰是編譯時就寫進 JS 的，要在這裡帶入。
# 只在這一行的環境裡設定，不用 ENV，避免留在 image 的中繼資料裡
ARG VITE_TINYMCE_API_KEY=""
RUN VITE_TINYMCE_API_KEY="${VITE_TINYMCE_API_KEY}" npm run build


# =============================================================
#  第二階段：安裝 PHP 套件
#  --no-dev 排除開發用套件（pest、pint 這些正式環境用不到）
#
#  這裡刻意用 php:8.4-cli 而不是官方的 composer image：
#  composer image 內建的 PHP 已經是 8.5，但 composer.lock 裡有
#  套件只支援到 8.4，會裝不起來。用跟正式環境相同的 PHP 版本最保險。
# =============================================================
FROM php:8.4-cli AS vendor

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev \
    && docker-php-ext-install zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
# --no-scripts：這時候還沒有完整的專案，套件探索留到容器啟動時再做
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev --no-scripts


# =============================================================
#  第三階段：正式環境
# =============================================================
FROM php:8.4-fpm AS runtime

# 這個 image 只跑 php-fpm。nginx 是另一個 container（見 Dockerfile.nginx）
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libzip-dev \
        libicu-dev \
        unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# opcache：正式環境的效能關鍵，把編譯結果留在記憶體
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.interned_strings_buffer=16'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# 上傳大小要和 nginx 的 client_max_body_size 對得起來
RUN { \
        echo 'upload_max_filesize=20M'; \
        echo 'post_max_size=20M'; \
        echo 'memory_limit=256M'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/app.ini

# php-fpm 預設只聽 127.0.0.1。nginx 現在是另一個 container，
# 不改的話它連進來會被拒絕
RUN sed -i 's/^listen = .*/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/zz-docker.conf \
    && { \
        echo 'access.log = /proc/self/fd/2'; \
        echo 'clear_env = no'; \
        echo 'catch_workers_output = yes'; \
        echo 'decorate_workers_output = no'; \
    } >> /usr/local/etc/php-fpm.d/zz-docker.conf

WORKDIR /var/www/html

# 專案原始碼（.dockerignore 已經排除 .env、node_modules、vendor 等）
COPY . .

# 前兩個階段的產出
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

RUN chown -R www-data:www-data storage bootstrap/cache

# php-fpm 的埠，只在 docker 內部網路開放，不對外
EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm", "-F"]
