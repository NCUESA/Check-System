# deploy/ 裡面是什麼

伺服器由 IT 架，所以這裡的檔案分兩種：**要交出去的**，和**參考用的**。

| 檔案 | 性質 |
|---|---|
| [`HANDOFF-STAGING.md`](HANDOFF-STAGING.md) | **交出去。** 給 IT 的規格說明，他們主要看這份 |
| [`app/docker-compose.yml`](app/docker-compose.yml) | **交出去。** 我們的服務怎麼跑。正式與測試同一份 |
| [`nginx/conf.d/check_stage.conf`](nginx/conf.d/check_stage.conf) | **交出去（參考）。** IT 可以直接用，也可以照裡面的重點自己寫 |
| [`mysql/init/01-databases.sql`](mysql/init/01-databases.sql) | **交出去（參考）。** 要建的 DB 和帳號 |
| [`nginx/docker-compose.yml`](nginx/docker-compose.yml) | 參考。IT 用他們自己的方式架 nginx |
| [`mysql/docker-compose.yml`](mysql/docker-compose.yml) | 參考。同上 |
| [`nginx/conf.d/check.conf`](nginx/conf.d/check.conf)、[`00-default.conf`](nginx/conf.d/00-default.conf) | 參考。正式環境的版本、擋沒對到網域的請求 |
| [`app/.env.*.example`](app/) | 參考。手動部署時的變數清單；走 CI/CD 用不到 |

「參考」不代表沒用——那些是我實際跑起來驗證過的完整設定，
IT 要照抄可以直接抄，要自己寫也有個對照。

你自己要做的事在 [`../DEPLOY.md`](../DEPLOY.md)。
