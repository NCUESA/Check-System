<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    /** @use HasFactory<\Database\Factories\AnnouncementFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 
        'content', 
        'on_top'
    ];

    protected function casts(): array
    {
        return [
            'content' => 'string',
            'on_top' => 'boolean'
        ];
    }
}
