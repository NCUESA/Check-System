<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 地點與 IP 的對應。打卡時用來判斷這台機器在哪個地點。
     *
     * 跟舊的 authip 表是不同概念：那張是存取白名單（已停用），
     * 這張是「這個 IP 屬於哪個值勤地點」。
     */
    public function up(): void
    {
        Schema::create('location_ips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            // 一個 IP 只能屬於一個地點，否則打卡時無法判斷在哪
            $table->string('ip_address', 45)->unique();
            $table->string('description', 100)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        $this->seedKnownIps();
    }

    /**
     * 帶入既有的對照。這份清單來自 checklist 地點回填時整理出來的結果，
     * 直接沿用，管理員不用從零開始建。
     */
    private function seedKnownIps(): void
    {
        $map = [
            '進德' => [
                ['10.21.51.7', '進德打卡機'],
                ['10.21.48.41', '進德打卡機'],
                ['120.107.148.253', '進德對外 IP'],
            ],
            '寶山' => [
                ['10.21.44.35', '寶山打卡機'],
                ['120.107.148.248', '寶山對外 IP'],
            ],
            '系統' => [
                ['10.21.44.148', '系統打卡'],
                ['120.107.148.249', '系統打卡對外 IP'],
            ],
        ];

        $now = now();
        $rows = [];

        foreach ($map as $name => $ips) {
            $locationId = DB::table('locations')->where('name', $name)->value('id');
            if (!$locationId) {
                continue;
            }

            foreach ($ips as [$ip, $description]) {
                $rows[] = [
                    'location_id' => $locationId,
                    'ip_address' => $ip,
                    'description' => $description,
                    'status' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows) {
            DB::table('location_ips')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('location_ips');
    }
};
