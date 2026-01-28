<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Domain\Catalog\Models\CatalogItemCodeMapping;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Merchant\Models\MerchantItem;
use App\Domain\Merchant\Models\MerchantStockUpdate;
use App\Domain\Merchant\Queries\MerchantItemQuery;
use App\Domain\Merchant\Services\MerchantItemDisplayService;
use App\Domain\Merchant\Services\MerchantItemStockService;
use App\Domain\Merchant\Services\MerchantItemPricingService;
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
    private const ALLOWED_MERCHANT_ID = 1;

    private const BRANCH_FILE_MAPPING = [
        'twa01' => 'BR-01',
        'twa02' => 'BR-02',
        'twa03' => 'BR-03',
        'twa04' => 'BR-04',
        'twa05' => 'BR-05',
    ];

    public function __construct(
        private MerchantItemQuery $itemQuery,
        private MerchantItemDisplayService $displayService,
        private MerchantItemStockService $stockService,
        private MerchantItemPricingService $pricingService,
    ) {}

    /**
     * Check if current user has access
     */
    private function checkAccess(): bool
    {
        return Auth::id() === self::ALLOWED_MERCHANT_ID;
    }

    /**
     * Abort if no access
     */
    private function abortIfNoAccess(): void
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

        $stockSummary = $this->stockService->getStockSummary(self::ALLOWED_MERCHANT_ID);

        return view('merchant.stock.index', compact('branches', 'stockSummary'));
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
            ->editColumn('created_at', fn($update) => $update->created_at->format('Y-m-d H:i'))
            ->editColumn('completed_at', fn($update) => $update->completed_at?->format('Y-m-d H:i') ?? '-')
            ->rawColumns(['status'])
            ->make(true);
    }

    /**
     * Trigger full stock refresh from CSV files
     */
    public function triggerFullRefresh(Request $request)
    {
        $this->abortIfNoAccess();

        try {
            $update = MerchantStockUpdate::create([
                'user_id' => self::ALLOWED_MERCHANT_ID,
                'type' => 'full_refresh',
                'status' => 'pending',
                'total_rows' => 0,
                'processed_rows' => 0,
                'created_rows' => 0,
                'updated_rows' => 0,
                'skipped_rows' => 0,
                'error_rows' => 0,
            ]);

            dispatch(function () use ($update) {
                $this->processFullRefresh($update);
            })->afterResponse();

            return response()->json([
                'success' => true,
                'message' => 'تم بدء عملية التحديث الكامل',
                'update_id' => $update->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock refresh trigger failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل بدء عملية التحديث: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process full stock refresh
     */
    public function processFullRefresh(MerchantStockUpdate $update)
    {
        try {
            $update->update(['status' => 'processing', 'started_at' => now()]);

            $stats = [
                'total' => 0,
                'processed' => 0,
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];

            foreach (self::BRANCH_FILE_MAPPING as $filename => $warehouseName) {
                $branch = MerchantBranch::where('user_id', self::ALLOWED_MERCHANT_ID)
                    ->where('warehouse_name', $warehouseName)
                    ->first();

                if (!$branch) {
                    Log::warning("Branch not found: {$warehouseName}");
                    continue;
                }

                $filePath = storage_path("app/stock_updates/{$filename}.csv");

                if (!file_exists($filePath)) {
                    Log::warning("CSV file not found: {$filePath}");
                    continue;
                }

                $branchStats = $this->processBranchFile($filePath, $branch);
                $stats['total'] += $branchStats['total'];
                $stats['processed'] += $branchStats['processed'];
                $stats['created'] += $branchStats['created'];
                $stats['updated'] += $branchStats['updated'];
                $stats['skipped'] += $branchStats['skipped'];
                $stats['errors'] += $branchStats['errors'];
            }

            $update->update([
                'status' => 'completed',
                'total_rows' => $stats['total'],
                'processed_rows' => $stats['processed'],
                'created_rows' => $stats['created'],
                'updated_rows' => $stats['updated'],
                'skipped_rows' => $stats['skipped'],
                'error_rows' => $stats['errors'],
                'completed_at' => now(),
            ]);

            Log::info('Stock refresh completed', $stats);
        } catch (\Exception $e) {
            $update->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            Log::error('Stock refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Process single branch CSV file
     */
    private function processBranchFile(string $filePath, MerchantBranch $branch): array
    {
        $stats = [
            'total' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $stats;
        }

        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            $stats['total']++;

            try {
                if (count($row) < 3) {
                    $stats['skipped']++;
                    continue;
                }

                $itemCode = trim($row[0]);
                $stock = (int) trim($row[1]);
                $price = (float) trim($row[2]);

                if (empty($itemCode) || $price <= 0) {
                    $stats['skipped']++;
                    continue;
                }

                $mapping = CatalogItemCodeMapping::where('item_code', $itemCode)->first();
                if (!$mapping) {
                    $stats['skipped']++;
                    continue;
                }

                $item = MerchantItem::where('user_id', self::ALLOWED_MERCHANT_ID)
                    ->where('catalog_item_id', $mapping->catalog_item_id)
                    ->where('merchant_branch_id', $branch->id)
                    ->first();

                if ($item) {
                    $item->update([
                        'stock' => $stock,
                        'price' => $price,
                    ]);
                    $stats['updated']++;
                } else {
                    MerchantItem::create([
                        'user_id' => self::ALLOWED_MERCHANT_ID,
                        'catalog_item_id' => $mapping->catalog_item_id,
                        'merchant_branch_id' => $branch->id,
                        'price' => $price,
                        'stock' => $stock,
                        'status' => 1,
                        'item_type' => 'normal',
                        'item_condition' => 1,
                        'stock_check' => 1,
                        'preordered' => false,
                    ]);
                    $stats['created']++;
                }

                $stats['processed']++;
            } catch (\Exception $e) {
                $stats['errors']++;
                Log::error('Row processing failed', [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        fclose($handle);
        return $stats;
    }

    /**
     * Get update progress
     */
    public function getUpdateProgress($updateId)
    {
        $this->abortIfNoAccess();

        $update = MerchantStockUpdate::findOrFail($updateId);

        return response()->json([
            'status' => $update->status,
            'total_rows' => $update->total_rows,
            'processed_rows' => $update->processed_rows,
            'created_rows' => $update->created_rows,
            'updated_rows' => $update->updated_rows,
            'skipped_rows' => $update->skipped_rows,
            'error_rows' => $update->error_rows,
            'completed_at' => $update->completed_at?->toISOString(),
        ]);
    }

    /**
     * Download sample CSV
     */
    public function download()
    {
        $this->abortIfNoAccess();

        $csv = "item_code,stock,price\n";
        $csv .= "SAMPLE001,10,150.00\n";
        $csv .= "SAMPLE002,5,250.50\n";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="sample_stock.csv"');
    }

    /**
     * Export current stock
     */
    public function export()
    {
        $this->abortIfNoAccess();

        $items = MerchantItem::Make()
            ->forMerchant(self::ALLOWED_MERCHANT_ID)
            ->withRelations()
            ->get();

        $csv = "item_code,catalog_item_name,branch,stock,price\n";

        foreach ($items as $item) {
            $itemCode = $item->catalogItem->codeMappings->first()?->item_code ?? 'N/A';
            $name = $item->catalogItem->name ?? 'N/A';
            $branch = $item->branch?->warehouse_name ?? 'N/A';
            
            $csv .= "\"{$itemCode}\",\"{$name}\",\"{$branch}\",{$item->stock},{$item->price}\n";
        }

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="stock_export_' . date('Y-m-d') . '.csv"');
    }
}
