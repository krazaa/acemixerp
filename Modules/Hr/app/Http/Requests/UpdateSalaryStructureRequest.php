<?php

declare(strict_types=1);

namespace Modules\Hr\Http\Requests;

class UpdateSalaryStructureRequest extends StoreSalaryStructureRequest
{
    public function rules(): array
    {
        return parent::rules();
    }
}
