<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    protected $fillable = [
        'key', 'prefix', 'pattern', 'current_value',
        'padding', 'reset_yearly', 'last_reset_year',
    ];

    protected function casts(): array
    {
        return [
            'current_value' => 'integer',
            'padding' => 'integer',
            'reset_yearly' => 'boolean',
            'last_reset_year' => 'integer',
        ];
    }
}
