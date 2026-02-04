<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Item extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'status', // 'unknown', 'available', 'unavailable', 'closed'
        'description',
        'thumbnail_pic',
        'stock',
        'price',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'status' => 'string',
            'price' => 'decimal:2',
        ];
    }

    /**
     * Get all tags associated with the item.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tags_cons', 'item_id', 'tag_id')
                    ->using(TagCon::class);
    }

    /**
     * Get all order items for this item.
     */
    public function orderItems(): HasMany // Renamed from seats() to orderItems()
    {
        return $this->hasMany(OrderItem::class, 'item_id');
    }

    /**
     * Get all cart items for this item.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class, 'item_id');
    }
}