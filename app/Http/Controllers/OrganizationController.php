<?php

namespace App\Http\Controllers;

use App\Contracts\OrganizationUpdater;
use App\Data\OrganizationData;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationUpdater $updater,
    ) {}

    public function edit(): View
    {
        $organization = Organization::current();
        $this->authorize('view', $organization);

        $organization->load('addresses');
        $addresses = $organization->addresses->toArray();

        return view('organization.edit', [
            'addresses' => $addresses,
            'organization' => $organization,
            'timezones' => timezone_identifiers_list(),
            'currencies' => ['PKR'],
            'dateFormats' => ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-M-Y'],
        ]);
    }

    public function update(UpdateOrganizationRequest $request): RedirectResponse
    {
        $this->authorize('update', Organization::current());

        $this->updater->update(
            OrganizationData::fromRequest($request),
            $request->file('logo'),
        );

        return redirect()
            ->route('organization.edit')
            ->with('status', 'Organization updated.');
    }
}
