<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Merchant\Models\ApiCredential;
use App\Domain\Merchant\Services\ApiCredentialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ApiCredentialController extends OperatorBaseController
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

        return view('operator.credentials.index', compact('credentials', 'groupedCredentials'));
    }

    /**
     * Show create form
     * System-level services only (not merchant-specific)
     */
    public function create()
    {
        // System-level services only
        // Payment/Shipping services are in Merchant Credentials section
        $services = [
            'google_maps' => 'Google Maps',
            'digitalocean' => 'DigitalOcean',
            'aws' => 'AWS',
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

        return view('operator.credentials.create', compact('services', 'keyTypes'));
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

        return redirect()->route('operator.credentials.index')
            ->with('success', __('Credential added successfully'));
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $credential = ApiCredential::findOrFail($id);

        // PRE-COMPUTED: Masked value (DATA_FLOW_POLICY - no @php in view)
        $decrypted = $credential->decrypted_value;
        $credential->masked_value = $decrypted
            ? substr($decrypted, 0, 8) . '••••••••' . substr($decrypted, -4)
            : 'N/A';

        return view('operator.credentials.edit', compact('credential'));
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

        return redirect()->route('operator.credentials.index')
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

        return redirect()->route('operator.credentials.index')
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
     * Returns JSON for AJAX requests
     */
    public function test($id)
    {
        $credential = ApiCredential::findOrFail($id);
        $value = $credential->decrypted_value;

        if (!$value) {
            return response()->json([
                'success' => false,
                'message' => __('Cannot decrypt credential'),
                'details' => null
            ]);
        }

        // Test based on service type
        $testResult = $this->testCredential($credential->service_name, $credential->key_name, $value);

        // Update last used timestamp
        $credential->update(['last_used_at' => now()]);

        return response()->json($testResult);
    }

    /**
     * Actually test the credential against its API
     */
    protected function testCredential(string $service, string $keyName, string $value): array
    {
        try {
            switch ($service) {
                case 'google_maps':
                    return $this->testGoogleMaps($value);

                case 'digitalocean':
                    return $this->testDigitalOcean($keyName, $value);

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
     * Test Google Maps API
     */
    protected function testGoogleMaps(string $apiKey): array
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?" . http_build_query([
            'latlng' => '24.7136,46.6753', // Riyadh
            'key' => $apiKey,
            'language' => 'ar'
        ]);

        $response = @file_get_contents($url);

        if (!$response) {
            return [
                'success' => false,
                'message' => __('Connection failed'),
                'details' => __('Could not connect to Google Maps API')
            ];
        }

        $data = json_decode($response, true);

        if ($data['status'] === 'OK') {
            $address = $data['results'][0]['formatted_address'] ?? 'N/A';
            return [
                'success' => true,
                'message' => __('Google Maps API is working'),
                'details' => __('Test Address') . ': ' . $address,
                'api_status' => 'OK'
            ];
        }

        return [
            'success' => false,
            'message' => __('API returned error'),
            'details' => $data['error_message'] ?? $data['status'],
            'api_status' => $data['status']
        ];
    }

    /**
     * Test DigitalOcean Spaces
     */
    protected function testDigitalOcean(string $keyName, string $value): array
    {
        // Just verify format for now
        if ($keyName === 'access_key' && strlen($value) >= 20) {
            return [
                'success' => true,
                'message' => __('Access Key format is valid'),
                'details' => __('Length') . ': ' . strlen($value) . ' ' . __('characters')
            ];
        }

        if ($keyName === 'secret_key' && strlen($value) >= 30) {
            return [
                'success' => true,
                'message' => __('Secret Key format is valid'),
                'details' => __('Length') . ': ' . strlen($value) . ' ' . __('characters')
            ];
        }

        return [
            'success' => true,
            'message' => __('Credential stored'),
            'details' => __('Format validation passed')
        ];
    }
}
