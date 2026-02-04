<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'orders_items';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code', // code format = ORD-'the item's code'-'6 random character numbers and capitalized letters'
        'order_id',
        'item_id',
        'quantity',
        'cost',
        'item_code', // below are for history, like if item deleted or something
        'item_name',
        'item_price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'cost' => 'decimal:2',
            'item_price' => 'decimal:2',
        ];
    }

    /**
     * Get the order associated with this order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the item associated with this order item.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}