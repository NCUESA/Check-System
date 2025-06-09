<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckList extends Model
{
    use HasFactory;
    protected $table = 'checklist';
    protected $fillable = [
        'name', 
        'person_id', 
        'checkin_time',
        'checkin_operation',
        'checkout_time',
        'checkout_operation',
        'checkin_ip',
        'checkout_ip', 
        'inner_code_backup'
    ];

    public function person() {
        return $this->belongsTo(Person::class, 'person_id', 'id');
    }

}
