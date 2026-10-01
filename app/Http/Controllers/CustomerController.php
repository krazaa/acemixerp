<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\CustomerManager;
use App\Enums\CustomerStatus;
use App\Http\Requests\Customers\StoreCustomerRequest;
use App\Http\Requests\Customers\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerManager $customers) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Customer::class);

        return view('customers.index', [
            'customers' => $this->customers->paginate($request->only(['search', 'status'])),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Customer::class);

        return view('customers.create', [
            'customer' => new Customer(['status' => CustomerStatus::Prospect]),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = $this->customers->create($request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', "Customer {$customer->name} created.");
    }

    public function show(Customer $customer): View
    {
        $this->authorize('view', $customer);

        return view('customers.show', [
            'customer' => $customer->load(['addresses', 'creator', 'updater']),
        ]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('update', $customer);

        return view('customers.edit', [
            'customer' => $customer->load('addresses'),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        return redirect()
            ->route('customers.show', $customer)
            ->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        $this->customers->delete($customer);

        return redirect()
            ->route('customers.index')
            ->with('status', 'Customer deleted.');
    }

    public function changeStatus(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('changeStatus', $customer);

        $request->validate(['status' => ['required', 'string']]);

        $this->customers->changeStatus(
            $customer,
            CustomerStatus::from((string) $request->input('status')),
        );

        return back()->with('status', 'Customer status updated.');
    }
}
