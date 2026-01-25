<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Models\CatalogItemCodeMapping;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantStockUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;

/**
 * StockManagementController - Stock updates for Merchant #1 only
 *
 * This controller handles branch-based stock updates using:
 * - CSV files: twa01.csv to twa05.csv (one per branch)
 * - catalog_item_code_mappings table for item_code -> catalog_item_id mapping
 * - merchant_items table for actual stock/price updates per branch
 *
 * Branch mapping:
 * - twa01.csv -> BR-01 (merchant_branch_id = 1)
 * - twa02.csv -> BR-02 (merchant_branch_id = 2)
 * - twa03.csv -> BR-03 (merchant_branch_id = 3)
 * - twa04.csv -> BR-04 (merchant_branch_id = 4)
 * - twa05.csv -> BR-05 (merchant_branch_id = 5)
 */
class StockManagementController extends Controller
{
    /**
     * The merchant user ID that has access to this page
     */
    private const ALLOWED_MERCHANT_ID = 1;

    /**
     * Branch file mapping: filename => warehouse_name
     */
    private const BRANCH_FILE_MAPPING = [
        'twa01' => 'BR-01',
        'twa02' => 'BR-02',
        'twa03' => 'BR-03',
        'twa04' => 'BR-04',
        'twa05' => 'BR-05',
    ];

    /**
     * Check if current user has access to stock management
     */
    private function checkAccess(): bool
    {
        return Auth::id() === self::ALLOWED_MERCHANT_ID;
    }

    /**
     * Abort if user doesn't have access
     */
    private function abortIfNoAccess()
    {
        if (!$this->checkAccess()) {
            abort(403, 'هذه الصفحة خاصة بتاجر معين فقط');
        }
    }

    /**
     * Display stock management page
     */
    public function index()
    {
        $this->abortIfNoAccess();

        $branches = MerchantBranch::where('user_id', self::ALLOWED_MERCHANT_ID)
            ->where('status', 1)
            ->get();

        return view('merchant.stock.index', compact('branches'));
    }

    /**
     * Get stock updates history via DataTables
     */
    public function datatables()
    {
        $this->abortIfNoAccess();

        $updates = MerchantStockUpdate::where('user_id', self::ALLOWED_MERCHANT_ID)
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
            ->addColumn('completed_at', function ($update) {
                return $update->completed_at ? $update->completed_at->format('Y-m-d H:i:s') : '-';
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
                    $html .= '<button class="btn btn-sm btn-warning view-errors" data-id="' . $update->id . '" data-errors="' . htmlspecialchars($update->error_log) . '"><i class="fas fa-exclamation-triangle"></i></button> ';
                }
                if ($update->file_path && Storage::exists($update->file_path)) {
                    $html .= '<a href="' . route('merchant-stock-download', $update->id) . '" class="btn btn-sm btn-info"><i class="fas fa-download"></i></a>';
                }
                return $html;
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    /**
     * Trigger full refresh - download from S3 and process all branch files
     */
    public function triggerFullRefresh(Request $request)
    {
        $this->abortIfNoAccess();

        try {
            // Create stock update record
            $stockUpdate = MerchantStockUpdate::create([
                'user_id' => self::ALLOWED_MERCHANT_ID,
                'update_type' => 'automatic',
                'status' => 'pending',
                'started_at' => now(),
            ]);

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
     * Process full refresh - download and process each file one by one
     */
    public function processFullRefresh(Request $request)
    {
        $this->abortIfNoAccess();

        $updateId = $request->input('update_id');

        try {
            $stockUpdate = MerchantStockUpdate::where('id', $updateId)
                ->where('user_id', self::ALLOWED_MERCHANT_ID)
                ->firstOrFail();

            $stockUpdate->update([
                'status' => 'processing',
                'started_at' => now(),
            ]);

            set_time_limit(600); // 10 minutes max
            ini_set('memory_limit', '512M');

            // معالجة كل ملف على حدة لتوفير الذاكرة
            $results = $this->processAllBranchesOneByOne();

            // Mark as completed
            $stockUpdate->update([
                'status' => 'completed',
                'completed_at' => now(),
                'total_rows' => $results['total_rows'],
                'updated_rows' => $results['updated_rows'],
                'failed_rows' => $results['failed_rows'],
                'error_log' => $results['errors'] ? implode("\n", array_slice($results['errors'], 0, 100)) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم التحديث بنجاح',
                'status' => 'completed',
                'results' => $results
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
     * Process all branches one by one - download, process, then free memory
     */
    private function processAllBranchesOneByOne(): array
    {
        $totalRows = 0;
        $updatedRows = 0;
        $failedRows = 0;
        $errors = [];

        // Find the latest ATWJRY folder path (sync/YYYY/MM/DD/ATWJRY)
        $remotePath = $this->findLatestAtwjryPath();

        if (!$remotePath) {
            return [
                'total_rows' => 0,
                'updated_rows' => 0,
                'failed_rows' => 0,
                'errors' => ['Could not find ATWJRY folder in S3. Please check sync folder.'],
            ];
        }

        Log::info("Using S3 path: {$remotePath}");

        // Get all branches for this merchant indexed by warehouse_name
        $branches = MerchantBranch::where('user_id', self::ALLOWED_MERCHANT_ID)
            ->get()
            ->keyBy('warehouse_name');

        foreach (self::BRANCH_FILE_MAPPING as $filename => $warehouseName) {
            $branch = $branches->get($warehouseName);

            if (!$branch) {
                $errors[] = "Branch not found for warehouse: {$warehouseName}";
                continue;
            }

            Log::info("Processing branch: {$warehouseName} ({$filename})");

            // Step 1: Download file from S3 using Storage facade
            // Try both .csv and .CSV extensions
            $s3Path = $remotePath . '/' . $filename . '.csv';
            $s3PathUpper = $remotePath . '/' . $filename . '.CSV';
            $localPath = 'stock/branches/' . $filename . '.csv';

            try {
                // Check if file exists on S3 (try both .csv and .CSV)
                $actualPath = null;
                if (Storage::disk('do')->exists($s3Path)) {
                    $actualPath = $s3Path;
                } elseif (Storage::disk('do')->exists($s3PathUpper)) {
                    $actualPath = $s3PathUpper;
                }

                if (!$actualPath) {
                    $errors[] = "File not found on S3: {$filename}.csv";
                    Log::warning("Branch file not found on S3: {$s3Path}");
                    continue;
                }

                // Download using Storage facade (handles authentication)
                $content = Storage::disk('do')->get($actualPath);

                if (empty($content)) {
                    $errors[] = "File is empty: {$filename}.csv";
                    Log::warning("Branch file is empty: {$s3Path}");
                    continue;
                }

                // Save to local
                Storage::disk('local')->put($localPath, $content);
                unset($content); // Free memory immediately

                Log::info("Downloaded: {$filename}.csv from S3");

                // Step 2: Process file immediately (no pre-loaded mappings)
                $result = $this->processBranchFile($localPath, $branch->id);

                $totalRows += $result['total'];
                $updatedRows += $result['updated'];
                $failedRows += $result['failed'];

                // Only keep first 10 errors per file
                $errors = array_merge($errors, array_slice($result['errors'], 0, 10));

                // Step 3: Delete local file to free disk space
                Storage::disk('local')->delete($localPath);

                Log::info("Processed {$filename}: {$result['updated']}/{$result['total']} rows");

                // Force garbage collection
                gc_collect_cycles();

            } catch (\Exception $e) {
                $errors[] = "Error processing {$filename}: " . $e->getMessage();
                Log::error("Failed to process {$filename}.csv: " . $e->getMessage());
            }
        }

        return [
            'total_rows' => $totalRows,
            'updated_rows' => $updatedRows,
            'failed_rows' => $failedRows,
            'errors' => $errors,
        ];
    }

    /**
     * Process a single branch DBF file using batch operations
     * The .csv files are actually DBF (dBase) format
     */
    private function processBranchFile(string $localPath, int $branchId): array
    {
        $total = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];
        $processedCatalogItemIds = [];
        $batchSize = 1000;

        try {
            $fullPath = storage_path('app/' . $localPath);

            if (!file_exists($fullPath)) {
                throw new \Exception("File not found: {$fullPath}");
            }

            // Read file content
            $content = file_get_contents($fullPath);
            if ($content === false) {
                throw new \Exception("Cannot read file: {$fullPath}");
            }

            // Parse DBF file
            $records = $this->parseDbfFile($content);
            $total = count($records);

            Log::info("Parsed {$total} records from DBF file");

            $rowBatch = [];

            foreach ($records as $record) {
                $itemCode = trim($record['FITEMNO'] ?? '');
                if (empty($itemCode)) {
                    $failed++;
                    continue;
                }

                $rowBatch[] = [
                    'item_code' => $itemCode,
                    'qty' => (int) ($record['FQTYONHAND'] ?? 0),
                    'cost_price' => (float) ($record['FCOSTPRICE'] ?? 0),
                ];

                // Process batch when size is reached
                if (count($rowBatch) >= $batchSize) {
                    $result = $this->processBatchWithMappings($rowBatch, $branchId);
                    $updated += $result['updated'];
                    $failed += $result['failed'];
                    $processedCatalogItemIds = array_merge($processedCatalogItemIds, $result['catalog_ids']);
                    $rowBatch = [];
                    gc_collect_cycles();
                }
            }

            // Clear records to free memory
            unset($records);
            unset($content);

            // Process remaining batch
            if (!empty($rowBatch)) {
                $result = $this->processBatchWithMappings($rowBatch, $branchId);
                $updated += $result['updated'];
                $failed += $result['failed'];
                $processedCatalogItemIds = array_merge($processedCatalogItemIds, $result['catalog_ids']);
            }

            // Zero out items not in file for this branch
            $this->zeroOutMissingItems($branchId, $processedCatalogItemIds);

            unset($processedCatalogItemIds);
            gc_collect_cycles();

        } catch (\Exception $e) {
            $errors[] = "File processing error: " . $e->getMessage();
            Log::error("processBranchFile error: " . $e->getMessage());
        }

        return [
            'total' => $total,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Parse a DBF (dBase) file and return records as array
     */
    private function parseDbfFile(string $content): array
    {
        $records = [];

        // Parse DBF header
        $numRecords = unpack('V', substr($content, 4, 4))[1];
        $headerSize = unpack('v', substr($content, 8, 2))[1];
        $recordSize = unpack('v', substr($content, 10, 2))[1];

        // Parse field descriptors
        $fields = [];
        $offset = 32;
        while ($offset < $headerSize - 1) {
            $fieldName = trim(substr($content, $offset, 11), "\x00");
            if (empty($fieldName) || ord($fieldName[0]) === 0x0D) break;

            $fieldLen = ord($content[$offset + 16]);
            $fields[] = [
                'name' => $fieldName,
                'length' => $fieldLen,
            ];
            $offset += 32;
        }

        // Parse records
        $dataOffset = $headerSize;
        for ($i = 0; $i < $numRecords; $i++) {
            $recordData = substr($content, $dataOffset + ($i * $recordSize), $recordSize);

            // Skip deleted records (marked with *)
            if ($recordData[0] === '*') continue;

            $pos = 1;
            $record = [];
            foreach ($fields as $field) {
                $value = trim(substr($recordData, $pos, $field['length']));
                $record[$field['name']] = $value;
                $pos += $field['length'];
            }

            $records[] = $record;
        }

        return $records;
    }

    /**
     * Process a batch of rows with lazy-loaded mappings
     */
    private function processBatchWithMappings(array $inputRows, int $branchId): array
    {
        $updated = 0;
        $failed = 0;
        $catalogIds = [];

        // Extract item codes from batch
        $itemCodes = array_column($inputRows, 'item_code');

        // Load only needed mappings from database (only with valid catalog_item_id)
        $mappings = [];
        $dbRows = DB::table('catalog_item_code_mappings')
            ->select('item_code', 'catalog_item_id', 'quality_brand_id')
            ->whereIn('item_code', $itemCodes)
            ->whereNotNull('catalog_item_id')
            ->get();

        foreach ($dbRows as $dbRow) {
            $mappings[$dbRow->item_code] = [
                'catalog_item_id' => (int) $dbRow->catalog_item_id,
                'quality_brand_id' => (int) $dbRow->quality_brand_id,
            ];
        }

        $batchData = [];

        foreach ($inputRows as $row) {
            $itemCode = $row['item_code'];

            if (!isset($mappings[$itemCode])) {
                $failed++;
                continue;
            }

            $mapping = $mappings[$itemCode];
            $price = $row['cost_price']; // السعر مباشرة بدون هامش

            $batchData[] = [
                'catalog_item_id' => $mapping['catalog_item_id'],
                'user_id' => self::ALLOWED_MERCHANT_ID,
                'merchant_branch_id' => $branchId,
                'quality_brand_id' => $mapping['quality_brand_id'],
                'price' => $price,
                'stock' => $row['qty'],
                'status' => 1,
                'item_type' => 'normal',
                'item_condition' => 2,
            ];

            $catalogIds[] = $mapping['catalog_item_id'];
        }

        if (!empty($batchData)) {
            $updated = $this->upsertBatch($batchData);
        }

        return [
            'updated' => $updated,
            'failed' => $failed,
            'catalog_ids' => $catalogIds,
        ];
    }

    /**
     * Upsert a batch of merchant items
     */
    private function upsertBatch(array $batchData): int
    {
        if (empty($batchData)) {
            return 0;
        }

        try {
            MerchantItem::upsert(
                $batchData,
                ['catalog_item_id', 'user_id', 'merchant_branch_id'], // Unique keys
                ['stock', 'price', 'status', 'quality_brand_id'] // Columns to update
            );

            return count($batchData);
        } catch (\Exception $e) {
            Log::error('Batch upsert failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Zero out items not in the processed list for a branch
     * Uses chunked whereNotIn to avoid memory issues
     */
    private function zeroOutMissingItems(int $branchId, array $processedIds): void
    {
        if (empty($processedIds)) {
            // No processed items means zero out everything for this branch
            MerchantItem::where('user_id', self::ALLOWED_MERCHANT_ID)
                ->where('merchant_branch_id', $branchId)
                ->update(['stock' => 0, 'status' => 0]);
            return;
        }

        // Convert to unique set to minimize size
        $processedIds = array_unique($processedIds);

        // For smaller datasets, use direct whereNotIn
        if (count($processedIds) <= 10000) {
            MerchantItem::where('user_id', self::ALLOWED_MERCHANT_ID)
                ->where('merchant_branch_id', $branchId)
                ->whereNotIn('catalog_item_id', $processedIds)
                ->update(['stock' => 0, 'status' => 0]);
            return;
        }

        // For larger datasets, use raw SQL with temporary exclusion
        $ids = implode(',', $processedIds);
        DB::statement("
            UPDATE merchant_items
            SET stock = 0, status = 0
            WHERE user_id = ?
            AND merchant_branch_id = ?
            AND catalog_item_id NOT IN ({$ids})
        ", [self::ALLOWED_MERCHANT_ID, $branchId]);
    }

    /**
     * Find the latest ATWJRY folder path in S3
     * Structure: sync/{year}/{month}/{day}/ATWJRY
     */
    private function findLatestAtwjryPath(): ?string
    {
        try {
            // Get current year folder
            $currentYear = date('Y');
            $yearPath = "sync/{$currentYear}";

            // Get months
            $months = Storage::disk('do')->directories($yearPath);
            if (empty($months)) {
                // Try previous year
                $yearPath = "sync/" . ($currentYear - 1);
                $months = Storage::disk('do')->directories($yearPath);
            }

            if (empty($months)) {
                Log::warning("No months found in sync folder");
                return null;
            }

            // Sort and get latest month
            sort($months);
            $latestMonth = end($months);

            // Get days in latest month
            $days = Storage::disk('do')->directories($latestMonth);
            if (empty($days)) {
                Log::warning("No days found in {$latestMonth}");
                return null;
            }

            // Sort and get latest day
            sort($days);
            $latestDay = end($days);

            // Check if ATWJRY folder exists
            $atwjryPath = $latestDay . '/ATWJRY';

            if (Storage::disk('do')->exists($atwjryPath) ||
                count(Storage::disk('do')->files($atwjryPath)) > 0) {
                Log::info("Found ATWJRY at: {$atwjryPath}");
                return $atwjryPath;
            }

            Log::warning("ATWJRY folder not found at {$atwjryPath}");
            return null;

        } catch (\Exception $e) {
            Log::error("Error finding ATWJRY path: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download stock file from history
     */
    public function download($id)
    {
        $this->abortIfNoAccess();

        $stockUpdate = MerchantStockUpdate::where('id', $id)
            ->where('user_id', self::ALLOWED_MERCHANT_ID)
            ->firstOrFail();

        if (!$stockUpdate->file_path || !Storage::exists($stockUpdate->file_path)) {
            return back()->with('error', 'الملف غير موجود');
        }

        return Storage::download($stockUpdate->file_path, $stockUpdate->file_name);
    }

    /**
     * Get update progress status
     */
    public function getUpdateProgress($id)
    {
        $this->abortIfNoAccess();

        try {
            $stockUpdate = MerchantStockUpdate::where('id', $id)
                ->where('user_id', self::ALLOWED_MERCHANT_ID)
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
     * Export current merchant stock to CSV
     */
    public function export(Request $request)
    {
        $this->abortIfNoAccess();

        try {
            $branchId = $request->get('branch_id');

            $query = MerchantItem::where('user_id', self::ALLOWED_MERCHANT_ID)
                ->where('status', 1)
                ->with(['catalogItem:id,part_number,name', 'merchantBranch:id,warehouse_name,branch_name']);

            if ($branchId) {
                $query->where('merchant_branch_id', $branchId);
            }

            $merchantItems = $query->get();

            $filename = 'stock_export_' . self::ALLOWED_MERCHANT_ID . '_' . date('Y-m-d_His') . '.csv';

            $csvContent = "Branch,Warehouse,PART_NUMBER,Catalog Item Name,Stock,Price\n";

            foreach ($merchantItems as $mp) {
                $branchName = str_replace('"', '""', $mp->merchantBranch->branch_name ?? '');
                $warehouseName = $mp->merchantBranch->warehouse_name ?? '';
                $partNumber = $mp->catalogItem->part_number ?? '';
                $name = str_replace('"', '""', $mp->catalogItem->name ?? '');
                $stock = $mp->stock ?? 0;
                $price = $mp->price ?? 0;

                $csvContent .= "\"{$branchName}\",\"{$warehouseName}\",\"{$partNumber}\",\"{$name}\",{$stock},{$price}\n";
            }

            return response($csvContent, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Stock export failed: ' . $e->getMessage());
            return back()->with('error', 'فشل تصدير المخزون: ' . $e->getMessage());
        }
    }
}
