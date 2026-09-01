<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DutyPeriod extends Model
{
    use HasFactory;
    protected $table = 'duty_periods';
    protected $fillable = [
        'name',
        'duty_start_date',
        'duty_end_date',
        'open_at',
        'close_at',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'duty_start_date' => 'date',
            'duty_end_date' => 'date',
            'open_at' => 'datetime',
            'close_at' => 'datetime',
            'status' => 'boolean'
        ];
    }

    // Relationships
    public function calendars()
    {
        return $this->hasMany(Calendar::class, 'period_id', 'id');
    }

    // 學生現在可以填寫或取消這期的班嗎
    // 期間停用、還沒開放、已經截止，三種都算不行
    public function isOpenForStudents(): bool
    {
        return $this->status && now()->between($this->open_at, $this->close_at);
    }
}
