-- 這個檔案只有在 mysql-data volume 是空的時候（第一次啟動）才會執行。
-- 之後想改，要嘛手動下 SQL，要嘛砍掉 volume 重來（會清空所有資料）。

-- 正式環境
CREATE DATABASE IF NOT EXISTS `sa_duty`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 測試環境
CREATE DATABASE IF NOT EXISTS `sa_duty_stage`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 兩個環境各自的帳號，權限只給自己的資料庫。
-- 密碼記得改掉，並且要和各自 /srv/check{,_stage}/.env 裡的 DB_PASSWORD 一致。
CREATE USER IF NOT EXISTS 'check'@'%' IDENTIFIED BY 'CHANGE_ME_PROD';
GRANT ALL PRIVILEGES ON `sa_duty`.* TO 'check'@'%';

CREATE USER IF NOT EXISTS 'check_stage'@'%' IDENTIFIED BY 'CHANGE_ME_STAGE';
GRANT ALL PRIVILEGES ON `sa_duty_stage`.* TO 'check_stage'@'%';

FLUSH PRIVILEGES;
