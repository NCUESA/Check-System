<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationIp extends Model
{
    use HasFactory;
    protected $table = 'location_ips';
    protected $fillable = [
        'location_id',
        'ip_address',
        'description',
        'status'
    ];

    protected function casts(): array
    {
        return [
            'status' => 'boolean'
        ];
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'id');
    }

    /**
     * 這個 IP 屬於哪個地點。查不到就回 null。
     * 停用的對應不算數，地點本身被停用的也不算。
     */
    public static function resolve(?string $ip): ?Location
    {
        if (!$ip) {
            return null;
        }

        return static::with('location')
            ->where('ip_address', $ip)
            ->where('status', true)
            ->first()
            ?->location;
    }
}
