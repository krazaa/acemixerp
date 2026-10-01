<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

class UpdateEmployeeRequest extends StoreEmployeeRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
