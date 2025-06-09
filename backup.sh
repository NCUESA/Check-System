php artisan migrate --path=/database/migrations/2025_06_07_064050_update_person_table.php
php artisan app:backup-person-inner-code
php artisan migrate --path=/database/migrations/2025_06_07_064051_update_checklist_table.php
php artisan app:backup-checklist-inner-code
php artisan migrate --path=/database/migrations/2025_06_07_064052_update_checklist_table.php
php artisan migrate --path=/database/migrations/2025_06_07_064053_update_person_table.php
php artisan migrate --path=/database/migrations/2025_06_07_064054_update_checklist_table.php