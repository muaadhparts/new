<?php

namespace App\Http\Controllers\Merchant;

use App\Domain\Merchant\Models\MerchantCredential;
use App\Domain\Merchant\Services\MerchantCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class CredentialController extends MerchantBaseController
{
    protected MerchantCredentialService $credentialService;

    public function __construct(MerchantCredentialService $credentialService)
    {
        parent::__construct();
        $this->credentialService = $credentialService;
    }

    /**
     * Display a listing of merchant credentials.
     */
    public function index()
    {
        $user = $this->user;
        $credentials = MerchantCredential::where('user_id', $user->id)
            ->orderBy('service_name')
            ->orderBy('key_name')
            ->get();

        $availableServices = MerchantCredential::getAvailableServices();

        return view('merchant.credentials.index', compact('user', 'credentials', 'availableServices'));
    }

    /**
     * Show the form for creating a new credential.
     */
    public function create()
    {
        $user = $this->user;
        $availableServices = MerchantCredential::getAvailableServices();

        return view('merchant.credentials.create', compact('user', 'availableServices'));
    }

    /**
     * Store a newly created credential.
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_type' => 'required|in:payment,shipping',
            'service_name' => 'required|string|max:100',
            'key_name' => 'required|string|max:100',
            'environment' => 'required|in:live,sandbox',
            'value' => 'required|string',
            'description' => 'nullable|string|max:500',
        ]);

        $user = $this->user;

        // Check if credential already exists
        $exists = MerchantCredential::where('user_id', $user->id)
            ->where('service_name', $request->service_name)
            ->where('key_name', $request->key_name)
            ->where('environment', $request->environment)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => __('Credential already exists for this service, key, and environment.')])
                ->withInput();
        }

        MerchantCredential::create([
            'user_id' => $user->id,
            'service_name' => $request->service_name,
            'key_name' => $request->key_name,
            'environment' => $request->environment,
            'encrypted_value' => Crypt::encryptString($request->value),
            'description' => $request->description,
            'is_active' => true,
        ]);

        return redirect()->route('merchant-credentials-index')
            ->with('success', __('Credential added successfully.'));
    }

    /**
     * Show the form for editing the specified credential.
     */
    public function edit($id)
    {
        $user = $this->user;
        $credential = MerchantCredential::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $availableServices = MerchantCredential::getAvailableServices();

        return view('merchant.credentials.edit', compact('user', 'credential', 'availableServices'));
    }

    /**
     * Update the specified credential.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'value' => 'nullable|string',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $user = $this->user;
        $credential = MerchantCredential::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $updateData = [
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Only update value if provided
        if ($request->filled('value')) {
            $updateData['encrypted_value'] = Crypt::encryptString($request->value);
        }

        $credential->update($updateData);

        // Clear cache
        $this->credentialService->clearCache($user->id, $credential->service_name, $credential->key_name);

        return redirect()->route('merchant-credentials-index')
            ->with('success', __('Credential updated successfully.'));
    }

    /**
     * Toggle credential active status.
     */
    public function toggle($id)
    {
        $user = $this->user;
        $credential = MerchantCredential::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $credential->update(['is_active' => !$credential->is_active]);

        // Clear cache
        $this->credentialService->clearCache($user->id, $credential->service_name, $credential->key_name);

        $status = $credential->is_active ? __('activated') : __('deactivated');
        return back()->with('success', __('Credential :status successfully.', ['status' => $status]));
    }

    /**
     * Remove the specified credential.
     */
    public function destroy($id)
    {
        $user = $this->user;
        $credential = MerchantCredential::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Clear cache before deleting
        $this->credentialService->clearCache($user->id, $credential->service_name, $credential->key_name);

        $credential->delete();

        return redirect()->route('merchant-credentials-index')
            ->with('success', __('Credential deleted successfully.'));
    }
}
