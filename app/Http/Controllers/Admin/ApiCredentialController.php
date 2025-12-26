<?php

namespace App\Http\Controllers\Admin;

use App\Models\ApiCredential;
use App\Services\ApiCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ApiCredentialController extends AdminBaseController
{
    protected ApiCredentialService $credentialService;

    public function __construct()
    {
        parent::__construct();
        $this->credentialService = new ApiCredentialService();
    }

    /**
     * Display all credentials
     */
    public function index()
    {
        $credentials = ApiCredential::orderBy('service_name')->get()->map(function ($cred) {
            $decrypted = $cred->decrypted_value;
            $cred->masked_value = $decrypted
                ? substr($decrypted, 0, 8) . '••••••••' . substr($decrypted, -4)
                : null;
            return $cred;
        });

        // Group by service
        $groupedCredentials = $credentials->groupBy('service_name');

        return view('admin.credentials.index', compact('credentials', 'groupedCredentials'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $services = [
            'google_maps' => 'Google Maps',
            'myfatoorah' => 'MyFatoorah',
            'tryoto' => 'Tryoto',
            'digitalocean' => 'DigitalOcean',
            'aws' => 'AWS',
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'other' => 'Other',
        ];

        $keyTypes = [
            'api_key' => 'API Key',
            'secret_key' => 'Secret Key',
            'access_key' => 'Access Key',
            'refresh_token' => 'Refresh Token',
            'public_key' => 'Public Key',
            'private_key' => 'Private Key',
            'webhook_secret' => 'Webhook Secret',
            'other' => 'Other',
        ];

        return view('admin.credentials.create', compact('services', 'keyTypes'));
    }

    /**
     * Store new credential
     */
    public function store(Request $request)
    {
        $request->validate([
            'service_name' => 'required|string|max:255',
            'key_name' => 'required|string|max:255',
            'credential_value' => 'required|string',
            'description' => 'nullable|string|max:500',
        ]);

        $serviceName = $request->service_name === 'other'
            ? $request->custom_service_name
            : $request->service_name;

        $keyName = $request->key_name === 'other'
            ? $request->custom_key_name
            : $request->key_name;

        // Check if credential already exists
        $exists = ApiCredential::where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => __('Credential already exists. Use edit to update it.')]);
        }

        ApiCredential::create([
            'service_name' => $serviceName,
            'key_name' => $keyName,
            'encrypted_value' => Crypt::encryptString($request->credential_value),
            'description' => $request->description,
            'is_active' => true,
        ]);

        // Clear cache
        $this->credentialService->clearCache($serviceName, $keyName);

        return redirect()->route('admin.credentials.index')
            ->with('success', __('Credential added successfully'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $credential = ApiCredential::findOrFail($id);

        return view('admin.credentials.edit', compact('credential'));
    }

    /**
     * Update credential
     */
    public function update(Request $request, $id)
    {
        $credential = ApiCredential::findOrFail($id);

        $request->validate([
            'credential_value' => 'nullable|string',
            'description' => 'nullable|string|max:500',
        ]);

        $updateData = [
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ];

        // Only update value if provided
        if ($request->filled('credential_value')) {
            $updateData['encrypted_value'] = Crypt::encryptString($request->credential_value);
        }

        $credential->update($updateData);

        // Clear cache
        $this->credentialService->clearCache($credential->service_name, $credential->key_name);

        return redirect()->route('admin.credentials.index')
            ->with('success', __('Credential updated successfully'));
    }

    /**
     * Delete credential
     */
    public function destroy($id)
    {
        $credential = ApiCredential::findOrFail($id);

        // Clear cache before deletion
        $this->credentialService->clearCache($credential->service_name, $credential->key_name);

        $credential->delete();

        return redirect()->route('admin.credentials.index')
            ->with('success', __('Credential deleted successfully'));
    }

    /**
     * Toggle credential status
     */
    public function toggle($id)
    {
        $credential = ApiCredential::findOrFail($id);
        $credential->is_active = !$credential->is_active;
        $credential->save();

        // Clear cache
        $this->credentialService->clearCache($credential->service_name, $credential->key_name);

        return back()->with('success', __('Credential status updated'));
    }

    /**
     * Test credential (verify it's working)
     */
    public function test($id)
    {
        $credential = ApiCredential::findOrFail($id);
        $value = $credential->decrypted_value;

        if (!$value) {
            return back()->with('error', __('Cannot decrypt credential'));
        }

        // Update last used timestamp
        $credential->update(['last_used_at' => now()]);

        return back()->with('success', __('Credential is valid and accessible'));
    }
}
