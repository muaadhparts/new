<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Identity\Models\User;
use App\Domain\Merchant\Models\MerchantCredential;
use App\Domain\Merchant\Services\MerchantCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Operator MerchantCredentialController
 *
 * Manages merchant credentials (payment and shipping keys)
 * Each credential belongs to a specific merchant (user_id)
 */
class MerchantCredentialController extends OperatorBaseController
{
    protected MerchantCredentialService $credentialService;

    /**
     * Payment and Shipping services for merchants
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
        $this->credentialService = new MerchantCredentialService();
    }

    /**
     * Display all merchant credentials
     *
     * user_id = 0 means platform-level credentials
     */
    public function index(Request $request)
    {
        $query = MerchantCredential::with('user')
            ->orderBy('user_id')
            ->orderBy('service_name');

        // Filter by owner (merchant or platform)
        if ($request->has('merchant_id') && $request->merchant_id !== '') {
            $query->where('user_id', (int) $request->merchant_id);
        }

        // Filter by service
        if ($request->filled('service')) {
            $query->where('service_name', $request->service);
        }

        $credentials = $query->get()->map(function ($cred) {
            $decrypted = $cred->decrypted_value;
            $cred->masked_value = $decrypted
                ? substr($decrypted, 0, 8) . '--------' . substr($decrypted, -4)
                : null;
            return $cred;
        });

        // Group by merchant
        $groupedCredentials = $credentials->groupBy('user_id');

        // Get approved merchants (is_merchant = 2 means approved)
        $merchants = User::where('is_merchant', 2)
            ->orderBy('shop_name')
            ->get(['id', 'name', 'shop_name']);

        $services = $this->services;

        return view('operator.merchant-credentials.index', compact(
            'credentials',
            'groupedCredentials',
            'merchants',
            'services'
        ));
    }

    /**
     * Show create form
     */
    public function create()
    {
        // Get approved merchants (is_merchant = 2 means approved)
        $merchants = User::where('is_merchant', 2)
            ->orderBy('shop_name')
            ->get(['id', 'name', 'shop_name']);

        $services = $this->services;
        $keyTypes = $this->keyTypes;
        $environments = $this->environments;

        return view('operator.merchant-credentials.create', compact(
            'merchants',
            'services',
            'keyTypes',
            'environments'
        ));
    }

    /**
     * Store new credential
     *
     * user_id = 0 means platform-level credential
     * user_id > 0 means merchant-specific credential
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|min:0',
            'service_name' => 'required|string|max:255',
            'key_name' => 'required|string|max:255',
            'credential_value' => 'required|string',
            'environment' => 'required|in:live,sandbox',
            'description' => 'nullable|string|max:500',
        ]);

        // Validate user_id if not platform (0)
        if ((int) $request->user_id > 0) {
            $exists = User::where('id', $request->user_id)->where('is_merchant', 2)->exists();
            if (!$exists) {
                return back()->withErrors(['error' => __('Invalid merchant selected')]);
            }
        }

        $serviceName = $request->service_name === 'other'
            ? $request->custom_service_name
            : $request->service_name;

        $keyName = $request->key_name === 'other'
            ? $request->custom_key_name
            : $request->key_name;

        // Check if credential already exists for this merchant
        $exists = MerchantCredential::where('user_id', $request->user_id)
            ->where('service_name', $serviceName)
            ->where('key_name', $keyName)
            ->where('environment', $request->environment)
            ->exists();

        if ($exists) {
            return back()->withErrors(['error' => __('Credential already exists for this merchant. Use edit to update it.')]);
        }

        MerchantCredential::create([
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

        return redirect()->route('operator.merchant-credentials.index')
            ->with('success', __('Merchant credential added successfully'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $credential = MerchantCredential::with('user')->findOrFail($id);
        $environments = $this->environments;

        return view('operator.merchant-credentials.edit', compact('credential', 'environments'));
    }

    /**
     * Update credential
     */
    public function update(Request $request, $id)
    {
        $credential = MerchantCredential::findOrFail($id);

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

        return redirect()->route('operator.merchant-credentials.index')
            ->with('success', __('Merchant credential updated successfully'));
    }

    /**
     * Delete credential
     */
    public function destroy($id)
    {
        $credential = MerchantCredential::findOrFail($id);

        // Clear cache before deletion
        $this->credentialService->clearCache(
            $credential->user_id,
            $credential->service_name,
            $credential->key_name
        );

        $credential->delete();

        return redirect()->route('operator.merchant-credentials.index')
            ->with('success', __('Merchant credential deleted successfully'));
    }

    /**
     * Toggle credential status
     */
    public function toggle($id)
    {
        $credential = MerchantCredential::findOrFail($id);
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
     * Test credential - Returns JSON for AJAX
     */
    public function test($id)
    {
        $credential = MerchantCredential::findOrFail($id);
        $value = $credential->decrypted_value;

        if (!$value) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot decrypt credential'),
                'details' => null
            ]);
        }

        // Test based on service type
        $testResult = $this->testCredential(
            $credential->service_name,
            $credential->key_name,
            $value,
            $credential->environment,
            $credential->user_id
        );

        // Update last used timestamp
        $credential->update(['last_used_at' => now()]);

        return response()->json($testResult);
    }

    /**
     * Actually test the credential against its API
     */
    protected function testCredential(string $service, string $keyName, string $value, string $environment, int $userId): array
    {
        try {
            switch ($service) {
                case 'myfatoorah':
                    return $this->testMyFatoorah($value, $environment);

                case 'tryoto':
                    return $this->testTryoto($value, $environment);

                default:
                    return [
                        'success' => true,
                        'message' => __('Credential decrypted successfully'),
                        'details' => __('No API test available for this service'),
                        'value_preview' => substr($value, 0, 10) . '...'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => __('Test failed'),
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Test MyFatoorah API
     */
    protected function testMyFatoorah(string $apiKey, string $environment): array
    {
        $baseUrl = $environment === 'live'
            ? 'https://api.myfatoorah.com'
            : 'https://apitest.myfatoorah.com';

        $url = $baseUrl . '/v2/InitiatePayment';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'InvoiceAmount' => 1,
                'CurrencyIso' => 'SAR',
            ]),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => __('Connection failed'),
                'details' => $error
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['IsSuccess']) && $data['IsSuccess']) {
            $methods = count($data['Data']['PaymentMethods'] ?? []);
            return [
                'success' => true,
                'message' => __('MyFatoorah API is working'),
                'details' => __('Environment') . ': ' . ucfirst($environment) . ' | ' . __('Payment Methods') . ': ' . $methods,
                'api_status' => 'OK'
            ];
        }

        return [
            'success' => false,
            'message' => __('API returned error'),
            'details' => $data['Message'] ?? $data['ValidationErrors'][0]['Error'] ?? 'HTTP ' . $httpCode,
            'api_status' => 'ERROR'
        ];
    }

    /**
     * Test Tryoto API
     *
     * API URL: api.tryoto.com (not tryoto.com)
     * Field name: refresh_token (underscore, not camelCase)
     * Response field: access_token (underscore, not camelCase)
     */
    protected function testTryoto(string $refreshToken, string $environment): array
    {
        // Tryoto API moved to api.tryoto.com subdomain
        $baseUrl = $environment === 'live'
            ? 'https://api.tryoto.com'
            : 'https://staging.tryoto.com';

        $url = $baseUrl . '/rest/v2/refreshToken';

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            // Field name is refresh_token (underscore)
            CURLOPT_POSTFIELDS => json_encode([
                'refresh_token' => $refreshToken,
            ]),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => __('Connection failed'),
                'details' => $error
            ];
        }

        $data = json_decode($response, true);

        // Response field is access_token (underscore)
        if ($httpCode === 200 && (!empty($data['access_token']) || !empty($data['success']))) {
            return [
                'success' => true,
                'message' => __('Tryoto API is working'),
                'details' => __('Environment') . ': ' . ucfirst($environment) . ' | ' . __('Token refreshed successfully'),
                'api_status' => 'OK'
            ];
        }

        return [
            'success' => false,
            'message' => __('API returned error'),
            'details' => $data['otoErrorMessage'] ?? $data['error']['message'] ?? $data['message'] ?? 'HTTP ' . $httpCode,
            'api_status' => 'ERROR'
        ];
    }
}
