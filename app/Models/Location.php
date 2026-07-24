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
        'status'
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean'
        ];
    }

    // Relationships
    public function calendars() {
        return $this->hasMany(Calendar::class, 'location_id', 'id');
    }
}
