<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'code', // code format = ORD-'current date with format YYYY-MM-DD'-'6 random character numbers and capitalized letters'
        'user_id',
        'order_status', // 'pending' (default), 'processing', 'completed', 'cancelled', 'failed'
        'payment_status', // 'pending' (default), 'paid', 'refunded', 'failed'
        'payment_method', // (banks, e-wallet, cash) this one is not stricly enum, just string
        'payment_proof',
        'comments_public', // admin message for user
        'comments_private', // for admins, not show to user 
        'name', // below are for user data history
        'email',
        'phone_numbers', // not must or nullable // above are for user data history
        'address',
        'notes', // user message or notes
        'total_cost',
        
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_status' => 'string',
            'payment_status' => 'string',
            'total_cost' => 'decimal:2',
        ];
    }

    /**
     * Get the user associated with this order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all items associated with this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }
}