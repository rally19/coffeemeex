<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
        ];
    }

    /**
     * Get the type that owns the tag.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(TagType::class, 'type_id');
    }

    /**
     * Get all items that have this tag.
     */
    public function item(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'tags_cons', 'tag_id', 'item_id');
    }
}