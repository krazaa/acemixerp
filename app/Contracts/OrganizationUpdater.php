<?php

namespace App\Contracts;

use App\Data\OrganizationData;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\ConcurrencyException;
use App\Models\Organization;
use Illuminate\Http\UploadedFile;

interface OrganizationUpdater
{
    /**
     * @throws BusinessRuleException when a locked field is being changed
     * @throws ConcurrencyException
     */
    public function update(OrganizationData $data, ?UploadedFile $logo = null): Organization;
}
