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
        'bjs_id',
        'status_bjs',
        'processed_by',
        'processed_at',
        'last_synced_at',
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
            'status_bjs' => OrderStatus::class,
            'processed_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
