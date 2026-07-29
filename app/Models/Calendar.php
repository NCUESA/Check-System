<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;
    protected $table = 'calendar';
    protected $fillable = [
        'period_id',
        'location_id',
        'user_id',
        'status',
        'duty_date'
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'duty_date' => 'date'
        ];
    }

    // Relationships
    public function period() {
        return $this->belongsTo(DutyPeriod::class, 'period_id', 'id');
    }

    public function location() {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // 學生不能異動這個名額嗎
    // 沒有歸屬期間（例如臨時增額）的名額一律視為已 Lock，只有管理員能動
    public function isLockedForStudents(): bool {
        return !$this->period?->isOpenForStudents();
    }
}
