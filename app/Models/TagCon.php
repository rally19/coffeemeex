<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TagCon extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tags_cons';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'item_id',
        'tag_id',
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
}