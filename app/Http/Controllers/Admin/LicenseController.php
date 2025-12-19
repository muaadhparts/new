<?php

namespace App\Http\Controllers\Admin;

use App\Models\License;
use Illuminate\Http\Request;
use Validator;
use Datatables;

class LicenseController extends AdminBaseController
{
    /**
     * Display licenses list
     */
    public function index()
    {
        return view('admin.license.index');
    }

    /**
     * Get licenses for DataTable
     */
    public function datatables()
    {
        $datas = License::latest('id')->get();

        return Datatables::of($datas)
            ->editColumn('status', function (License $data) {
                $badges = [
                    'active' => 'badge-success',
                    'inactive' => 'badge-warning',
                    'expired' => 'badge-danger',
                    'suspended' => 'badge-dark',
                ];
                $badge = $badges[$data->status] ?? 'badge-secondary';
                return '<span class="badge ' . $badge . '">' . ucfirst($data->status) . '</span>';
            })
            ->editColumn('license_type', function (License $data) {
                $badges = [
                    'unlimited' => 'badge-primary',
                    'developer' => 'badge-info',
                    'extended' => 'badge-success',
                    'standard' => 'badge-secondary',
                ];
                $badge = $badges[$data->license_type] ?? 'badge-light';
                return '<span class="badge ' . $badge . '">' . ucfirst($data->license_type) . '</span>';
            })
            ->editColumn('expires_at', function (License $data) {
                if (!$data->expires_at) {
                    return '<span class="badge badge-success">غير محدود</span>';
                }
                $isExpired = $data->expires_at->isPast();
                $badge = $isExpired ? 'badge-danger' : 'badge-info';
                return '<span class="badge ' . $badge . '">' . $data->expires_at->format('Y-m-d') . '</span>';
            })
            ->addColumn('action', function (License $data) {
                $buttons = '<div class="action-list">';
                $buttons .= '<a data-href="' . route('admin-license-edit', $data->id) . '" class="edit" data-bs-toggle="modal" data-bs-target="#modal1"><i class="fas fa-edit"></i> ' . __('Edit') . '</a>';

                if ($data->status === 'active') {
                    $buttons .= '<a href="' . route('admin-license-deactivate', $data->id) . '" class="btn btn-sm btn-warning"><i class="fas fa-pause"></i></a>';
                } else {
                    $buttons .= '<a href="' . route('admin-license-activate-license', $data->id) . '" class="btn btn-sm btn-success"><i class="fas fa-play"></i></a>';
                }

                $buttons .= '<a href="javascript:;" data-href="' . route('admin-license-delete', $data->id) . '" data-bs-toggle="modal" data-bs-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
                $buttons .= '</div>';

                return $buttons;
            })
            ->rawColumns(['status', 'license_type', 'expires_at', 'action'])
            ->toJson();
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('admin.license.create');
    }

    /**
     * Store new license
     */
    public function store(Request $request)
    {
        $rules = [
            'owner_name' => 'nullable|string|max:255',
            'owner_email' => 'nullable|email|max:255',
            'license_type' => 'required|in:standard,extended,developer,unlimited',
            'max_domains' => 'required|integer|min:0',
            'expires_at' => 'nullable|date',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $license = new License();
        $license->license_key = License::generateLicenseKey();
        $license->owner_name = $request->owner_name;
        $license->owner_email = $request->owner_email;
        $license->license_type = $request->license_type;
        $license->max_domains = $request->max_domains;
        $license->expires_at = $request->expires_at;
        $license->status = 'inactive';
        $license->features = $request->features ?? [];
        $license->notes = $request->notes;
        $license->save();

        return response()->json(__('تم إنشاء الترخيص بنجاح. مفتاح الترخيص: ') . $license->license_key);
    }

    /**
     * Show edit form
     */
    public function edit($id)
    {
        $data = License::findOrFail($id);
        return view('admin.license.edit', compact('data'));
    }

    /**
     * Update license
     */
    public function update(Request $request, $id)
    {
        $rules = [
            'owner_name' => 'nullable|string|max:255',
            'owner_email' => 'nullable|email|max:255',
            'license_type' => 'required|in:standard,extended,developer,unlimited',
            'max_domains' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive,expired,suspended',
            'expires_at' => 'nullable|date',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $license = License::findOrFail($id);
        $license->owner_name = $request->owner_name;
        $license->owner_email = $request->owner_email;
        $license->license_type = $request->license_type;
        $license->max_domains = $request->max_domains;
        $license->status = $request->status;
        $license->expires_at = $request->expires_at;
        $license->features = $request->features ?? [];
        $license->notes = $request->notes;
        $license->save();

        return response()->json(__('تم تحديث الترخيص بنجاح.'));
    }

    /**
     * Delete license
     */
    public function destroy($id)
    {
        $license = License::findOrFail($id);
        $license->delete();

        return response()->json(__('تم حذف الترخيص بنجاح.'));
    }

    /**
     * Activate license
     */
    public function activateLicense($id)
    {
        $license = License::findOrFail($id);
        $license->status = 'active';
        $license->activated_at = now();
        $license->domain = request()->getHost();
        $license->save();

        return redirect()->back()->with('success', __('تم تفعيل الترخيص بنجاح.'));
    }

    /**
     * Deactivate license
     */
    public function deactivate($id)
    {
        $license = License::findOrFail($id);
        $license->status = 'inactive';
        $license->save();

        return redirect()->back()->with('success', __('تم إلغاء تفعيل الترخيص.'));
    }

    /**
     * Activation page - for activating with license key
     */
    public function activation()
    {
        $activeLicense = License::getActiveLicense();
        return view('admin.license.activation', compact('activeLicense'));
    }

    /**
     * Activate with license key
     */
    public function activateWithKey(Request $request)
    {
        $rules = [
            'license_key' => 'required|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->getMessageBag()->toArray()]);
        }

        $license = License::where('license_key', $request->license_key)->first();

        if (!$license) {
            return response()->json(['errors' => ['license_key' => [__('مفتاح الترخيص غير صالح.')]]]);
        }

        if ($license->status === 'suspended') {
            return response()->json(['errors' => ['license_key' => [__('هذا الترخيص معلق.')]]]);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            return response()->json(['errors' => ['license_key' => [__('هذا الترخيص منتهي الصلاحية.')]]]);
        }

        if ($license->max_domains > 0 && $license->used_domains >= $license->max_domains) {
            return response()->json(['errors' => ['license_key' => [__('تم استخدام الحد الأقصى من النطاقات لهذا الترخيص.')]]]);
        }

        // Activate the license
        $license->status = 'active';
        $license->activated_at = now();
        $license->domain = request()->getHost();
        $license->used_domains += 1;
        $license->save();

        return response()->json(__('تم تفعيل النظام بنجاح! مرحباً بك.'));
    }

    /**
     * Generate new license key (AJAX)
     */
    public function generateKey()
    {
        return response()->json(['key' => License::generateLicenseKey()]);
    }
}
