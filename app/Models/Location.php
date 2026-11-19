<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $table = 'locations';
    protected $fillable = [
        'name',
        'capacity',
        'allowance',
        'hours',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean',
            'allowance' => 'integer',
            'hours' => 'decimal:1'
        ];
    }

    // Relationships
    public function calendars() {
        return $this->hasMany(Calendar::class, 'location_id', 'id');
    }

    public function ips() {
        return $this->hasMany(LocationIp::class, 'location_id', 'id');
    }
}
