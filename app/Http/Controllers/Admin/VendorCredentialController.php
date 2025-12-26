<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\VendorCredential;
use App\Services\VendorCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Admin VendorCredentialController
 *
 * إدارة credentials التجار (مفاتيح الدفع والشحن)
 * كل credential تنتمي لتاجر معين (user_id)
 */
class VendorCredentialController extends AdminBaseController
{
    protected VendorCredentialService $credentialService;

    /**
     * Payment and Shipping services for vendors
     */
    protected array $services = [
        'myfatoorah' => 'MyFatoorah',
        'tryoto' => 'Tryoto',
        'stripe' => 'Stripe',
        'paypal' => 'PayPal',
        'razorpay' => 'Razorpay',
        'tap' => 'Tap Payments',
        'other' => 'Other',
    ];

    /**
     * Key types
     */
    protected array $keyTypes = [
        'api_key' => 'API Key',
        'secret_key' => 'Secret Key',
        'refresh_token' => 'Refresh Token',
        'public_key' => 'Public Key',
        'private_key' => 'Private Key',
        'webhook_secret' => 'Webhook Secret',
        'other' => 'Other',
    ];

    /**
     * Environments
     */
    protected array $environments = [
        'live' => 'Live (Production)',
        'sandbox' => 'Sandbox (Testing)',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->credentialService = new VendorCredentialService();
    }

    /**
     * Display all vendor credentials
     */
    public function index(Request $request)
    {
        $query = VendorCredential::with('user')
            ->orderBy('user_id')
            ->orderBy('service_name');

        // Filter by vendor
        if ($request->filled('vendor_id')) {
            $query->where('user_id', $request->vendor_id);
        }

        // Filter by service
        if ($request->filled('service')) {
            $query->where('service_name', $request->service);
        }

        $credentials = $query->get()->map(function ($cred) {
            $decrypted = $cred->decrypted_value;
            $cred->masked_value = $decrypted
                ? substr($decrypted, 0, 8) . '••••••••' . substr($decrypted, -4)
                : null;
            return $cred;
        });

        // Group by vendor
        $groupedCredentials = $credentials->groupBy('user_id');

        // Get approved vendors (is_vendor = 2 means approved)
        $vendors = User::where('is_vendor', 2)
            ->orderBy('shop_name')
            ->get(['id', 'name', 'shop_name']);

        $services = $this->services;

        return view('admin.vendor-credentials.index', compact(
            'credentials',
            'groupedCredentials',
            'vendors',
            'services'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        // Get approved vendors (is_vendor = 2 means approved)
        $vendors = User::where('is_vendor', 2)
            ->orderBy('shop_name')
            ->get(['id', 'name', 'shop_name']);

        $services = $this->services;
        $keyTypes = $this->keyTypes;
        $environments = $this->environments;

        return view('admin.vendor-credentials.create', compact(
            'vendors',
            'services',
            'keyTypes',
            'environments'
        ));
    }

    /**
     * Store new credential
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'service_name' => 'required|string|max:255',
            'key_name' => 'required|string|max:255',
            'credential_value' => 'required|string',
            'environment' => 'required|in:live,sandbox',
            'description' => 'nullable|string|max:500',
        ]);

        $serviceName = $request->service_name === 'other'
            ? $request->custom_service_name
            : $request->service_name;

        $keyName = $request->key_name === 'other'
            ? $request->custom_key_name
            : $request->key_name;

        // Check if credential already exists for this vendor
        $exists = VendorCredential::where('user_id', $request->user_id)
            ->where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->where('environment', $request->environment)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => __('Credential already exists for this vendor. Use edit to update it.')]);
        }

        VendorCredential::create([
            'user_id' => $request->user_id,
            'service_name' => $serviceName,
            'key_name' => $keyName,
            'environment' => $request->environment,
            'encrypted_value' => Crypt::encryptString($request->credential_value),
            'description' => $request->description,
            'is_active' => true,
        ]);

        // Clear cache
        $this->credentialService->clearCache($request->user_id, $serviceName, $keyName);

        return redirect()->route('admin.vendor-credentials.index')
            ->with('success', __('Vendor credential added successfully'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $credential = VendorCredential::with('user')->findOrFail($id);
        $environments = $this->environments;

        return view('admin.vendor-credentials.edit', compact('credential', 'environments'));
    }

    /**
     * Update credential
     */
    public function update(Request $request, $id)
    {
        $credential = VendorCredential::findOrFail($id);

        $request->validate([
            'credential_value' => 'nullable|string',
            'environment' => 'required|in:live,sandbox',
            'description' => 'nullable|string|max:500',
        ]);

        $updateData = [
            'environment' => $request->environment,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
        ];

        // Only update value if provided
        if ($request->filled('credential_value')) {
            $updateData['encrypted_value'] = Crypt::encryptString($request->credential_value);
        }

        $credential->update($updateData);

        // Clear cache
        $this->credentialService->clearCache(
            $credential->user_id,
            $credential->service_name,
            $credential->key_name
        );

        return redirect()->route('admin.vendor-credentials.index')
            ->with('success', __('Vendor credential updated successfully'));
    }

    /**
     * Delete credential
     */
    public function destroy($id)
    {
        $credential = VendorCredential::findOrFail($id);

        // Clear cache before deletion
        $this->credentialService->clearCache(
            $credential->user_id,
            $credential->service_name,
            $credential->key_name
        );

        $credential->delete();

        return redirect()->route('admin.vendor-credentials.index')
            ->with('success', __('Vendor credential deleted successfully'));
    }

    /**
     * Toggle credential status
     */
    public function toggle($id)
    {
        $credential = VendorCredential::findOrFail($id);
        $credential->is_active = !$credential->is_active;
        $credential->save();

        // Clear cache
        $this->credentialService->clearCache(
            $credential->user_id,
            $credential->service_name,
            $credential->key_name
        );

        return back()->with('success', __('Credential status updated'));
    }

    /**
     * Test credential
     */
    public function test($id)
    {
        $credential = VendorCredential::findOrFail($id);
        $value = $credential->decrypted_value;

        if (!$value) {
            return back()->with('error', __('Cannot decrypt credential'));
        }

        // Update last used timestamp
        $credential->update(['last_used_at' => now()]);

        return back()->with('success', __('Credential is valid and accessible'));
    }
}
