<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gallery extends Model
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
        'code', // user inputted unique identification
        'index', // to set order or priorities (higher means more prioritized or first, lower means it will be last or bottom, can be minus or plus and null (default))
        'name',
        'status', // 'unknown', 'show', 'hide' default is 'unknown'
        'description',
        'picture',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'index' => 'integer',
            'status' => 'string',
        ];
    }
}