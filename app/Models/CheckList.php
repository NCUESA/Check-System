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
        'user_id',
        'checkin_time',
        'checkin_operation',
        'checkout_time',
        'checkout_operation',
        'checkin_ip',
        'checkout_ip',
        'checkin_location_id',
        'checkout_location_id',
        'inner_code_backup'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function checkinLocation() {
        return $this->belongsTo(Location::class, 'checkin_location_id', 'id');
    }

    public function checkoutLocation() {
        return $this->belongsTo(Location::class, 'checkout_location_id', 'id');
    }

    protected function casts()  {
        return [
            'checkin_operation' => 'boolean', 
            'checkout_operation' => 'boolean'
        ];
    }

}
