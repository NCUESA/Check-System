<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;
    protected $table = "cards";
    protected $fillable = [
        "person_id", 
        "inner_code", 
        "status"
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean'
        ];
    }

    public function owner() {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }
}
