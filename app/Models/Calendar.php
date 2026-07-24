<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;
    protected $table = 'calendar';
    protected $fillable = [
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
    public function location() {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
