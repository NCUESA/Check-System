<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Person extends Model
{
    use HasFactory;
    protected $table = 'person';
    protected $fillable = [
        'name',
        'inner_code',
        'status'
    ];

    public function cards() {
        return $this->hasMany(Card::class, 'person_id', 'id');
    }
}
