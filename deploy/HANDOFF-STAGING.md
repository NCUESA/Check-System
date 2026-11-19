# 交接給 IT：測試環境（staging）

這份是給 IT 的規格說明。mysql 和 nginx 由 IT 用標準 image 自己架，
這裡只描述**我們的服務需要什麼**，不規定你們怎麼架。

---

## 一句話版本

一個 php-fpm container，聽 9000（**不對外開埠**），
前面要一台 nginx 幫它服務靜態檔並轉發 `.php`，旁邊要一個 MySQL 資料庫。

```
[IT 的反向代理] --https--> [nginx] --fastcgi--> [check_stage:9000] --> [mysql:3306]
                            靜態檔               Laravel               資料
```

---

## 1. 我們的 image

```
image.ncuesa.org.tw/check-system:staging
```

- 內容：PHP 8.4 + php-fpm + Laravel 程式碼 + 編譯好的前端靜態檔
- **裡面沒有任何設定值**（沒有 `.env`、沒有密碼、沒有網址）
- `ENTRYPOINT` 會做 migration 以外的初始化，然後 `php-fpm -F`
- 監聽 `0.0.0.0:9000`（FastCGI，不是 HTTP，不能直接代理）

---

## 2. compose

用 `deploy/app/docker-compose.yml`（正式與測試同一份，內容不用改）。

所有設定都是 `${VAR}` 形式，compose 會依序從
**執行 compose 的 shell 環境變數** → **同目錄的 `.env`** 取值。
所以 CI/CD 直接注入環境變數就好，伺服器上不需要有 `.env` 檔。

沒給值的必填項目，compose 會直接報錯停下來，不會用預設值悄悄跑起來。

### 必填（密碼類，建議放 secret manager）

| 變數 | 說明 |
|---|---|
| `APP_KEY` | Laravel 的加解密金鑰。用 `docker run --rm <image> php artisan key:generate --show` 產生。**正式與測試必須是不同的兩把**，共用的話兩邊的 session cookie 可以互相解開 |
| `DB_PASSWORD` | 資料庫密碼 |
| `KEYCLOAK_CLIENT_SECRET` | Keycloak client 的 secret |

### 必填（非密碼，但沒有合理預設值）

| 變數 | staging 的值 |
|---|---|
| `APP_IMAGE` | `image.ncuesa.org.tw/check-system:staging` |
| `COMPOSE_PROJECT` | `check_stage` |
| `APP_CONTAINER` | `check_stage` |
| `APP_URL` | `https://stage.check.ncuesa.org.tw` |
| `DB_DATABASE` | `sa_duty_stage` |
| `DB_USERNAME` | `check_stage` |
| `KEYCLOAK_REDIRECT_URI` | `https://stage.check.ncuesa.org.tw/login/keycloak/callback` |

### 建議覆蓋

| 變數 | staging 的值 | 預設 |
|---|---|---|
| `APP_ENV` | `staging` | `production` |
| `APP_DEBUG` | `true` | `false` |
| `LOG_LEVEL` | `debug` | `warning` |

其餘（`DB_HOST`、`SESSION_*`、`KEYCLOAK_BASE_URL`、`DUTY_*` 等）
都有預設值，可以不管。完整清單見 compose 檔本身。

### 兩個要掛的目錄

| 容器內路徑 | 用途 | 預設 host 路徑 |
|---|---|---|
| `/var/www/html/storage` | 上傳的檔案、log。**要保留，重新部署不能清掉** | `./storage`（可用 `STORAGE_PATH` 改） |
| `/var/www/html/public-shared` | 給 nginx 讀的靜態檔，見下一節 | `./public`（可用 `PUBLIC_PATH` 改） |

### 網路

要接一個已存在的 docker network，預設叫 `ncuesa`（可用 `DOCKER_NETWORK` 改）。
mysql 和 nginx 也要在同一個網路上，才能用 container 名稱互相解析。

---

## 3. nginx 要怎麼設

有三個地方跟一般 PHP 站不一樣，會踩坑：

### (a) 靜態檔要從掛載目錄來

nginx 用標準 image，裡面沒有我們的檔案。app container **每次啟動時**
會把 image 裡的 `public/`（含編譯好的 JS/CSS）整份複製到
`/var/www/html/public-shared`，也就是 host 上的 `PUBLIC_PATH`。

nginx 唯讀掛載那個目錄當 `root` 即可。換 image 之後那份會自動更新，
不需要重建 nginx。

### (b) `SCRIPT_FILENAME` 不能用 `$realpath_root`

nginx 和 php-fpm 看到的路徑不一樣。傳過去的必須是 **php-fpm 那邊的路徑**：

```nginx
fastcgi_param SCRIPT_FILENAME /var/www/html/public$fastcgi_script_name;
fastcgi_param DOCUMENT_ROOT   /var/www/html/public;
```

用 `$realpath_root` 會一直收到 `Primary script unknown`。

### (c) `fastcgi_pass` 要用變數，不能直接寫名字

```nginx
resolver 127.0.0.11 valid=10s ipv6=off;   # Docker 內建 DNS
set $fpm check_stage:9000;
fastcgi_pass $fpm;
```

直接寫 `fastcgi_pass check_stage:9000;` 的話，nginx **只在啟動時解析一次 IP**。
每次重新部署 app，容器換 IP，nginx 還在打舊 IP，會一直 502 到手動重啟 nginx
為止。這個實測過，很難查，因為 app 本身完全正常。

### (d) 其他

- 使用者上傳的檔案走 `/storage/`，那是一條指向 `storage/app/public` 的軟連結，
  在 nginx 容器裡會斷掉。用 `alias` 指到掛進來的實體目錄，或把
  `STORAGE_PATH/app/public` 也掛給 nginx
- 要送 **`X-Forwarded-For`**。系統靠來源 IP 判斷打卡地點，
  沒有的話所有打卡都會被認成同一個地點
- 要送 **`X-Forwarded-Proto`**，否則 https 站會產生 http 的連結

一份可以直接用的完整設定在 [`deploy/nginx/conf.d/check_stage.conf`](nginx/conf.d/check_stage.conf)，
上面四點都處理好了。

---

## 4. MySQL 要準備什麼

```sql
CREATE DATABASE `sa_duty_stage`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'check_stage'@'%' IDENTIFIED BY '（密碼）';
GRANT ALL PRIVILEGES ON `sa_duty_stage`.* TO 'check_stage'@'%';
```

- 編碼一定要 `utf8mb4`，資料有中文
- 時區建議設 `Asia/Taipei`
- 不需要對外開 3306

---

## 5. 第一次部署之後

資料表要建起來：

```bash
docker compose exec app php artisan migrate --force
```

（或設 `AUTO_MIGRATE=true` 讓它每次啟動自動跑。正式環境不建議。）

確認活著：

```bash
curl -H 'Host: stage.check.ncuesa.org.tw' http://<nginx>/up
```

Laravel 內建的健康檢查路由，會實際打到 php-fpm，回 200 就是通了。

---

## 6. 還需要你們配合的兩件事

1. **反向代理**指到 nginx，並送 `X-Forwarded-For` / `X-Forwarded-Proto`
2. **Keycloak** 的 client 設定要加上
   `https://stage.check.ncuesa.org.tw/*`，
   **Valid redirect URIs** 和 **Valid post logout redirect URIs** 兩欄都要加
   （後者是分開設定的，沒加的話登出會停在 Keycloak 頁面）
