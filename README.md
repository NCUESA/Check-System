# 學生會人員簽到系統

## 安裝步驟
1. 將`.env.example`複製成`.env`並修改參數
2. 安裝依賴：
   ```bash
   composer install
   npm install
   ```
3. 執行
   ```bash
   php artisan key:generate #產生APP_KEY
   php artisan migrate #執行遷移
   ```
4. 啟動開發伺服器：
   ```bash
   composer run dev
   ```
   伺服器預設埠號為 `http://localhost:8000`
   
## 建置與部署
- 產生生產環境檔案：
  ```bash
  npm run build
  ```
  
## 更新歷程
  
### Last Update
- Version:      1.3
- Commit name:  Vue
- Update Date:  2025/06/27
- Builder:      默默

更新重點:
1. 把頁面用Vue重寫
2. UI優化
3. 資料表結構調整
4. 正式支持一人多卡

<hr>

### 歷史紀錄
- Version:         1.2   
- Commit Name:     Auth Fixed
- Update Date:     2025/1/23
- Builder:        安得

更新重點:
1. 刪除Session紀錄身分的方式
2. UI優化

<hr>

- Commit Name:     Expired Update
- Update Date:     2024/11/12
- Builder:        安得

更新重點:
1. Session不會過期了
2. 打卡列會自動變成英文輸入法

<hr>

- Commit Name:     Auth Visible Update Hot Fixed
- Update Date:     2024/11/12
- Builder:        安得

更新重點:
1. 修正View會顯示Unauthorized的連結錯誤。
   
<hr>

- Commit Name:     Month Table Update
- Update Date:     2024/10/31
- Builder:        安得

更新重點:
1. 新增月報表
2. 打卡清單頁面更新，增加搜尋功能
3. 不合格的IP現在可以被刪掉了
