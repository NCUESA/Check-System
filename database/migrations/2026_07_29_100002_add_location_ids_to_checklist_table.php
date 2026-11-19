<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 簽到地點改由 locations 表決定，不再靠硬編的 IP 清單反推。
     *
     * checkin_ip / checkout_ip 保留不動，原始資料留著才查得出對照問題。
     * 對不上任何地點的紀錄（手動補登的 0.0.0.0、零星未知 IP）留 null。
     */
    private const IP_MAP = [
        '進德' => ['10.21.51.7', '10.21.48.41', '120.107.148.253'],
        '寶山' => ['10.21.44.35', '120.107.148.248'],
        '系統' => ['10.21.44.148', '120.107.148.249'],
    ];

    public function up(): void
    {
        Schema::table('checklist', function (Blueprint $table) {
            $table->foreignId('checkin_location_id')
                ->nullable()
                ->after('checkin_ip')
                ->constrained('locations')
                ->nullOnDelete();
            $table->foreignId('checkout_location_id')
                ->nullable()
                ->after('checkout_ip')
                ->constrained('locations')
                ->nullOnDelete();
        });

        // 依 IP 對照回填既有紀錄。用地點名稱查 id，不寫死數字
        foreach (self::IP_MAP as $name => $ips) {
            $locationId = DB::table('locations')->where('name', $name)->value('id');
            if (!$locationId) {
                continue;
            }

            DB::table('checklist')
                ->whereIn('checkin_ip', $ips)
                ->update(['checkin_location_id' => $locationId]);

            DB::table('checklist')
                ->whereIn('checkout_ip', $ips)
                ->update(['checkout_location_id' => $locationId]);
        }
    }

    public function down(): void
    {
        Schema::table('checklist', function (Blueprint $table) {
            $table->dropConstrainedForeignId('checkin_location_id');
            $table->dropConstrainedForeignId('checkout_location_id');
        });
    }
};
