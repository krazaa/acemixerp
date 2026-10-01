<?php

declare(strict_types=1);

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['employee_id', 'attendance_date', 'checked_in_at', 'checked_out_at', 'status', 'notes'];

    protected function casts(): array
    {
        return ['attendance_date' => 'date', 'checked_in_at' => 'datetime', 'checked_out_at' => 'datetime'];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
