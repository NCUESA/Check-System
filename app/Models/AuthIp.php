<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthIp extends Model
{
    use HasFactory;
    protected $table = 'authip';
    protected $fillable = [
        'ip_address',
        'description'
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean'
        ];
    }
}
