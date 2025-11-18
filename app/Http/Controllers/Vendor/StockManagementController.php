<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\MerchantProduct;
use App\Models\Product;
use App\Models\VendorStockUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class StockManagementController extends Controller
{
    /**
     * Display stock management page
     */
    public function index()
    {
        return view('vendor.stock.index');
    }

    /**
     * Get stock updates history via DataTables
     */
    public function datatables()
    {
        $userId = Auth::id();
        $updates = VendorStockUpdate::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        return DataTables::of($updates)
            ->editColumn('status', function ($update) {
                $badges = [
                    'pending' => '<span class="badge badge-warning">قيد الانتظار</span>',
                    'processing' => '<span class="badge badge-info">جاري المعالجة</span>',
                    'completed' => '<span class="badge badge-success">مكتمل</span>',
                    'failed' => '<span class="badge badge-danger">فشل</span>',
                ];
                return $badges[$update->status] ?? $update->status;
            })
            ->editColumn('update_type', function ($update) {
                return $update->update_type === 'manual' ? 'يدوي' : 'تلقائي';
            })
            ->editColumn('created_at', function ($update) {
                return $update->created_at->format('Y-m-d H:i:s');
            })
            ->addColumn('progress', function ($update) {
                if ($update->total_rows > 0) {
                    $percentage = round(($update->updated_rows / $update->total_rows) * 100, 2);
                    return "{$update->updated_rows} / {$update->total_rows} ({$percentage}%)";
                }
                return 'N/A';
            })
            ->addColumn('action', function ($update) {
                $html = '';
                if ($update->error_log) {
                    $html .= '<button class="btn btn-sm btn-warning view-errors" data-id="' . $update->id . '" data-errors="' . htmlspecialchars($update->error_log) . '"><i class="fas fa-exclamation-triangle"></i> عرض الأخطاء</button> ';
                }
                if ($update->file_path && Storage::exists($update->file_path)) {
                    $html .= '<a href="' . route('vendor-stock-download', $update->id) . '" class="btn btn-sm btn-info"><i class="fas fa-download"></i> تحميل الملف</a>';
                }
                return $html;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Export current vendor stock to Excel/CSV
     */
    public function export(Request $request)
    {
        $userId = Auth::id();
        $format = $request->get('format', 'csv'); // csv or excel

        try {
            // Get all merchant products for this vendor with product SKU
            $merchantProducts = MerchantProduct::where('user_id', $userId)
                ->where('status', 1)
                ->with('product:id,sku,name')
                ->get();

            $filename = 'stock_export_' . $userId . '_' . date('Y-m-d_His') . '.' . $format;
            $filepath = 'stock_exports/' . $filename;

            // Create CSV content
            $csvContent = "SKU,Product Name,Current Stock,Price,Previous Price\n";

            foreach ($merchantProducts as $mp) {
                $sku = $mp->product->sku ?? '';
                $name = str_replace('"', '""', $mp->product->name ?? ''); // Escape quotes
                $stock = $mp->stock ?? 0;
                $price = $mp->price ?? 0;
                $previousPrice = $mp->previous_price ?? 0;

                $csvContent .= "\"{$sku}\",\"{$name}\",{$stock},{$price},{$previousPrice}\n";
            }

            // Save to storage
            Storage::put($filepath, $csvContent);

            // Download the file
            return Storage::download($filepath, $filename, [
                'Content-Type' => 'text/csv',
            ]);

        } catch (\Exception $e) {
            Log::error('Stock export failed: ' . $e->getMessage());
            return back()->with('error', 'فشل تصدير المخزون: ' . $e->getMessage());
        }
    }

    /**
     * Show upload form
     */
    public function uploadForm()
    {
        return view('vendor.stock.upload');
    }

    /**
     * Handle file upload and process stock update
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stock_file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $userId = Auth::id();

        try {
            DB::beginTransaction();

            // Store the uploaded file
            $file = $request->file('stock_file');
            $filename = 'stock_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filepath = $file->storeAs('stock_uploads', $filename);

            // Create stock update record
            $stockUpdate = VendorStockUpdate::create([
                'user_id' => $userId,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $filepath,
                'update_type' => 'manual',
                'status' => 'pending',
            ]);

            DB::commit();

            // Process the file immediately (or queue it for large files)
            $this->processStockFile($stockUpdate);

            return back()->with('success', 'تم رفع الملف بنجاح وجاري معالجة التحديثات');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stock upload failed: ' . $e->getMessage());
            return back()->with('error', 'فشل رفع الملف: ' . $e->getMessage());
        }
    }

    /**
     * Process uploaded stock file
     */
    protected function processStockFile(VendorStockUpdate $stockUpdate)
    {
        try {
            $stockUpdate->markAsProcessing();

            $filepath = storage_path('app/' . $stockUpdate->file_path);

            if (!file_exists($filepath)) {
                throw new \Exception('File not found: ' . $filepath);
            }

            // Read CSV file
            $handle = fopen($filepath, 'r');
            $header = fgetcsv($handle); // Skip header row

            $totalRows = 0;
            $updatedRows = 0;
            $failedRows = 0;
            $errors = [];

            while (($row = fgetcsv($handle)) !== false) {
                $totalRows++;

                try {
                    // Expected format: SKU, Product Name (optional), Stock, Price (optional), Previous Price (optional)
                    $sku = trim($row[0] ?? '');
                    $newStock = isset($row[2]) ? (int) $row[2] : null;
                    $newPrice = isset($row[3]) && $row[3] !== '' ? (float) $row[3] : null;
                    $newPreviousPrice = isset($row[4]) && $row[4] !== '' ? (float) $row[4] : null;

                    if (empty($sku)) {
                        $errors[] = "Row {$totalRows}: SKU is empty";
                        $failedRows++;
                        continue;
                    }

                    if ($newStock === null) {
                        $errors[] = "Row {$totalRows}: Stock value is missing";
                        $failedRows++;
                        continue;
                    }

                    // Find product by SKU
                    $product = Product::where('sku', $sku)->first();

                    if (!$product) {
                        $errors[] = "Row {$totalRows}: Product not found for SKU: {$sku}";
                        $failedRows++;
                        continue;
                    }

                    // Find merchant product for this vendor
                    $merchantProduct = MerchantProduct::where('user_id', $stockUpdate->user_id)
                        ->where('product_id', $product->id)
                        ->first();

                    if (!$merchantProduct) {
                        $errors[] = "Row {$totalRows}: Merchant product not found for SKU: {$sku}";
                        $failedRows++;
                        continue;
                    }

                    // Update stock and optionally price
                    $updateData = ['stock' => $newStock];

                    if ($newPrice !== null) {
                        $updateData['price'] = $newPrice;
                    }

                    if ($newPreviousPrice !== null) {
                        $updateData['previous_price'] = $newPreviousPrice;
                    }

                    $merchantProduct->update($updateData);
                    $updatedRows++;

                } catch (\Exception $e) {
                    $errors[] = "Row {$totalRows}: " . $e->getMessage();
                    $failedRows++;
                }
            }

            fclose($handle);

            // Update stock update record
            $stockUpdate->update([
                'total_rows' => $totalRows,
            ]);

            $errorLog = !empty($errors) ? implode("\n", array_slice($errors, 0, 100)) : null; // Limit to 100 errors
            $stockUpdate->markAsCompleted($updatedRows, $failedRows, $errorLog);

        } catch (\Exception $e) {
            Log::error('Stock file processing failed: ' . $e->getMessage());
            $stockUpdate->markAsFailed($e->getMessage());
        }
    }

    /**
     * Download stock file from history
     */
    public function download($id)
    {
        $userId = Auth::id();
        $stockUpdate = VendorStockUpdate::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (!$stockUpdate->file_path || !Storage::exists($stockUpdate->file_path)) {
            return back()->with('error', 'الملف غير موجود');
        }

        return Storage::download($stockUpdate->file_path, $stockUpdate->file_name);
    }

    /**
     * Trigger automatic stock update (calls the existing console command)
     */
    public function triggerAutoUpdate(Request $request)
    {
        $userId = Auth::id();

        try {
            // Call the existing UpdateProductsStockCommand
            \Artisan::call('products:update-stock', [
                '--user_id' => $userId
            ]);

            $output = \Artisan::output();

            // Create a record for this automatic update
            VendorStockUpdate::create([
                'user_id' => $userId,
                'update_type' => 'automatic',
                'status' => 'completed',
                'started_at' => now(),
                'completed_at' => now(),
                'error_log' => $output,
            ]);

            return back()->with('success', 'تم تحديث المخزون تلقائياً من قاعدة البيانات الرئيسية');

        } catch (\Exception $e) {
            Log::error('Auto stock update failed: ' . $e->getMessage());
            return back()->with('error', 'فشل التحديث التلقائي: ' . $e->getMessage());
        }
    }

    /**
     * Trigger full refresh (download from remote + import + update)
     */
    public function triggerFullRefresh(Request $request)
    {
        $userId = Auth::id();
        $margin = $request->input('margin', 1.3);
        $branch = $request->input('branch', 'ATWJRY');

        try {
            // Create stock update record
            $stockUpdate = VendorStockUpdate::create([
                'user_id' => $userId,
                'update_type' => 'automatic',
                'status' => 'pending',
                'started_at' => now(),
            ]);

            // Return immediately with the update ID for tracking
            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية التحديث الكامل. يرجى الانتظار...',
                'update_id' => $stockUpdate->id
            ]);

        } catch (\Exception $e) {
            Log::error('Full refresh failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل بدء التحديث: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process full refresh in background
     */
    public function processFullRefresh(Request $request)
    {
        $updateId = $request->input('update_id');
        $userId = Auth::id();

        try {
            $stockUpdate = VendorStockUpdate::where('id', $updateId)
                ->where('user_id', $userId)
                ->firstOrFail();

            // Mark as processing
            $stockUpdate->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Get parameters
            $margin = $request->input('margin', 1.3);
            $branch = $request->input('branch', 'ATWJRY');

            // Execute the command with timeout handling
            set_time_limit(600); // 10 minutes max

            \Artisan::call('stock:manage', [
                'action' => 'full-refresh',
                '--user_id' => $userId,
                '--margin' => $margin,
                '--branch' => $branch,
            ]);

            $output = \Artisan::output();

            // Mark as completed
            $stockUpdate->update([
                'status' => 'completed',
                'completed_at' => now(),
                'error_log' => $output,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم التحديث بنجاح',
                'status' => 'completed'
            ]);

        } catch (\Exception $e) {
            Log::error('Full refresh processing failed: ' . $e->getMessage());

            if (isset($stockUpdate)) {
                $stockUpdate->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'error_log' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'فشل التحديث: ' . $e->getMessage(),
                'status' => 'failed'
            ], 500);
        }
    }

    /**
     * Get update progress status
     */
    public function getUpdateProgress($id)
    {
        $userId = Auth::id();

        try {
            $stockUpdate = VendorStockUpdate::where('id', $id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $progress = 0;
            if ($stockUpdate->total_rows > 0) {
                $progress = round(($stockUpdate->updated_rows / $stockUpdate->total_rows) * 100, 2);
            }

            return response()->json([
                'success' => true,
                'status' => $stockUpdate->status,
                'progress' => $progress,
                'updated_rows' => $stockUpdate->updated_rows,
                'total_rows' => $stockUpdate->total_rows,
                'failed_rows' => $stockUpdate->failed_rows,
                'message' => $this->getStatusMessage($stockUpdate->status, $progress)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب حالة التحديث'
            ], 404);
        }
    }

    /**
     * Get status message in Arabic
     */
    private function getStatusMessage($status, $progress)
    {
        $messages = [
            'pending' => 'في انتظار البدء...',
            'processing' => "جاري المعالجة... {$progress}%",
            'completed' => 'اكتملت العملية بنجاح',
            'failed' => 'فشلت العملية'
        ];

        return $messages[$status] ?? 'حالة غير معروفة';
    }

    /**
     * Download sample CSV template
     */
    public function downloadTemplate()
    {
        $csvContent = "SKU,Product Name,Stock,Price,Previous Price\n";
        $csvContent .= "SAMPLE-SKU-001,Sample Product Name,100,50.00,60.00\n";
        $csvContent .= "SAMPLE-SKU-002,Another Sample Product,50,75.50,80.00\n";

        $filename = 'stock_template.csv';

        return response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
