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
        'stu_id', 
        'inner_code', 
        'inner_code_backup', 
        'status'
    ];
    protected function casts(): array
    {
        return [
            'status' => 'boolean'
        ];
    }

    public function cards() {
        return $this->hasMany(Card::class, 'person_id', 'id');
    }
}
