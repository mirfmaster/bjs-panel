<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user',
        'link',
        'start_count',
        'count',
        'service_id',
        'status',
        'remains',
        'order_created_at',
        'order_cancel_reason',
        'order_fail_reason',
        'charge',
    ];

    protected function casts(): array
    {
        return [
            'start_count' => 'integer',
            'count' => 'integer',
            'service_id' => 'integer',
            'status' => OrderStatus::class,
            'remains' => 'integer',
            'order_created_at' => 'datetime',
            'charge' => 'decimal:2',
        ];
    }
}
