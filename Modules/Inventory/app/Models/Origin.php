<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Inventory\Enums\OriginStatus;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Origin extends Model
{

    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'status',
    ];

    protected function casts(): array
    {
        return ['status' => OriginStatus::class];

    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs()->useLogName('Origin');
    }
}
