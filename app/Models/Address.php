<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type', 'is_primary', 'label',
        'contact_name', 'contact_email', 'contact_phone',
        'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopePrimary(Builder $q): Builder
    {
        return $q->where('is_primary', true);
    }

    public function oneLine(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }
}
