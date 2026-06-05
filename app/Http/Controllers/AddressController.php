<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $addresses = auth()->user()->addresses()->latest()->get();

        return view('shop.addresses.index', compact('addresses'));
    }

    public function create()
    {
        return view('shop.addresses.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validateAddress($request);

        if ($request->boolean('is_default') || ! auth()->user()->addresses()->exists()) {
            auth()->user()->addresses()->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        auth()->user()->addresses()->create($validated);

        return redirect()->route('addresses.index')->with('success', 'Alamat berhasil ditambahkan.');
    }

    public function edit(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        return view('shop.addresses.edit', compact('address'));
    }

    public function update(Request $request, Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);

        $validated = $this->validateAddress($request);

        if ($request->boolean('is_default')) {
            auth()->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $validated['is_default'] = true;
        }

        $address->update($validated);

        return redirect()->route('addresses.index')->with('success', 'Alamat diperbarui.');
    }

    public function destroy(Address $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);
        $address->delete();

        return redirect()->route('addresses.index')->with('success', 'Alamat dihapus.');
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address_line' => ['required', 'string'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:10'],
            'is_default' => ['sometimes', 'boolean'],
        ]);
    }
}
