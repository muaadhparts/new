<?php 

namespace App\Livewire;

use App\Models\Catalog;
use App\Models\NewCategory;
use App\Services\CatalogSessionManager;
use App\Traits\NormalizesInput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Exception;

class VehicleSearchBox extends Component
{
    use NormalizesInput;

    public string $query = '';
    public $catalog;
    public array $results = [];
    public ?string $vin = null;
    public bool $isLoading = false;
    public ?string $selectedItem = null;
    public string $searchType = 'number';
    public string $errorMessage = '';
    protected int $maxResults = 1000;

    public bool $showCalloutPicker = false;
    public array $calloutOptions = [];
    public ?string $singleRedirectUrl = null;

    // override optional لتمرير allowed codes من صفحة الرسم
    public array $allowedCodesOverride = [];

    public $preloadedCategories = [];

    /** نطاق البحث: section أو catalog (الافتراضي: catalog عند أول دخول) */
    public string $searchScope = 'catalog';

    /** كاش داخلي لقائمة أكواد الكتالوج عند اختيار catalog */
    protected ?array $allCatalogCodesCache = null;

    /** مفاتيح سيشن أساسية (ستُربط بالكود لاحقًا) */
    private const SS_SEARCH_TYPE  = 'search_type';
    private const SS_SEARCH_SCOPE = 'search_scope';

    /** استقبال حدث خارجي للتبديل بين section/catalog */
    protected $listeners = [
        'setSearchScope' => 'setSearchScope',
    ];

    /** Service للتعامل مع الجلسة */
    protected CatalogSessionManager $sessionManager;

    /** ================== مفاتيح الترجمة لرسائل الخطأ ================== */
    private const ERR_LOAD       = 'errors.load_failed';
    private const ERR_SHORT_NUM  = 'errors.part_number_too_short';
    private const ERR_SHORT_LBL  = 'errors.part_name_too_short';
    private const ERR_NO_ALLOWED = 'errors.no_allowed_sections';
    private const ERR_NO_CALLOUT_NUM   = 'errors.no_matching_callout_number';
    private const ERR_NO_CALLOUT_LABEL = 'errors.no_matching_callout_label';


    private function setError(?string $msg): void
    {
        // $msg هنا إما نص مترجم أو مفتاح ترجمة مسبق
        $this->errorMessage = $msg ?? '';
        // بثّ حدث للواجهة (اختياري للسكرول/الفوكس)
        $this->dispatchBrowserEvent('vehicle-search-error', [
            'message' => $this->errorMessage,
        ]);
        // dd($this->errorMessage); // // لأغراض فحص لاحقة بإزالة التعليق
    }
    /** ============================================================ */

    /** إنشاء مفتاح سيشن مربوط بكود الكاتلوج (يُستدعى بعد ضبط $catalog) */
    private function ssKey(string $base): string
    {
        $code = $this->catalog->code ?? 'default';
        return "{$base}_{$code}";
    }


    /** تبديل النطاق (section | catalog) */
    public function setSearchScope($scope = 'section'): void
    {
        $scope = $scope === 'catalog' ? 'catalog' : 'section';
        if ($this->searchScope !== $scope) {
            $this->searchScope = $scope;

            // حفظ في السيشن (بعد الكاتلوج وبمفتاح مرتبط به)
            if ($this->catalog && $this->catalog->code) {
                session([$this->ssKey(self::SS_SEARCH_SCOPE) => $this->searchScope]);
            }

            $this->resetSearchState();

            // أرسل حدث للواجهة لتحديث لون الأزرار
            $this->dispatchBrowserEvent('search-scope-updated', ['scope' => $this->searchScope]);
        }
    }

    // داخل VehicleSearchBox
    public function clearSearch(): void
    {
        $this->resetSearchState();
    }

    public function boot(CatalogSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    protected function getEffectiveAllowedCodes(): array
    {
        // القائمة الكاملة المفلترة على المواصفات من الجلسة
        $allFromSession = $this->sessionManager->getAllowedLevel3Codes();

        // وضع Catalog → كل الأكواد المسموح بها في الجلسة
        if ($this->searchScope === 'catalog') {
            return $allFromSession;
        }

        // وضع Section → نفلتر الـ override بما يتوافق مع القائمة الكاملة
        if (!empty($this->allowedCodesOverride)) {
            return array_values(array_intersect(
                $allFromSession,
                array_map('strval', $this->allowedCodesOverride)
            ));
        }

        // في حال ما فيه override، نرجع القائمة الكاملة
        return $allFromSession;
    }

    public function mount($catalog, $vin = null): void
    {
        try {
            // 1) ضبط الكاتلوج أولًا
            $this->catalog = is_string($catalog)
                ? Catalog::with('brand')->where('code', $catalog)->first()
                : $catalog;

            if (!$this->catalog) {
                throw new \Exception('Catalog not found');
            }

            $this->vin = $this->sanitizeInput($vin);

            // 2) بعد ضبط الكاتلوج: استعادة السيشن بمفاتيح مربوطة بالكاتلوج
            $sessionType  = session($this->ssKey(self::SS_SEARCH_TYPE));
            $sessionScope = session($this->ssKey(self::SS_SEARCH_SCOPE));

            // نوع البحث: default → number
            $this->searchType  = $sessionType === 'label' ? 'label' : 'number';

            // نطاق البحث: default → catalog (كما طلبت للدخول الأول)
            $this->searchScope = $sessionScope === 'section' ? 'section' : 'catalog';

            $this->setError('');

            // عند التحميل: أرسل حدث لتلوين الأزرار حسب النطاق الحالي
            $this->dispatchBrowserEvent('search-scope-updated', ['scope' => $this->searchScope]);
        } catch (Exception $e) {
            Log::error('VehicleSearchBox mount error', ['error' => $e->getMessage()]);
            $this->setError( __(self::ERR_LOAD) );
        }
    }

    public function updatedSearchType($value): void
    {
        // حفظ في السيشن بعد الكاتلوج وبمفتاح مرتبط به
        $val = $value === 'label' ? 'label' : 'number';
        if ($this->catalog && $this->catalog->code) {
            session([$this->ssKey(self::SS_SEARCH_TYPE) => $val]);
        }
        $this->resetSearchState();
    }

    protected function resetSearchState(): void
    {
        $this->query = '';
        $this->selectedItem = null;
        $this->results = [];
        $this->setError('');
        $this->showCalloutPicker = false;
        $this->calloutOptions = [];
        $this->singleRedirectUrl = null;
    }

    // public function updatedQuery(): void
    // {
    //     if ($this->searchType === 'label') {
    //         $this->updatedQueryForLabel();
    //     } else {
    //         $this->updatedQueryForNumber();
    //     }
    // }

    public function updatedQuery(): void
    {
        // حرر أي تحديد سابق إذا بدأ المستخدم يكتب قيمة مختلفة
        $current = $this->sanitizeInput($this->query);
        if ($this->selectedItem !== null && $this->selectedItem !== $current) {
            $this->selectedItem      = null;
            $this->showCalloutPicker = false;
            $this->singleRedirectUrl = null;
            $this->calloutOptions    = [];
            $this->setError('');
            // dd(['reset_on_typing' => $current]); // اختبار سريع عند الحاجة
        }

        // تابع المسار المعتاد حسب نوع البحث
        if ($this->searchType === 'label') {
            $this->updatedQueryForLabel();
        } else {
            $this->updatedQueryForNumber();
        }
    }


    protected function updatedQueryForNumber(): void
    {
        try {
            $this->setError('');
            $this->query = $this->sanitizeInput($this->query);
            if ($this->isQueryTooShort($this->query)) {
                $this->results = [];
                return;
            }
        } catch (Exception $e) {
            $this->setError( __(self::ERR_LOAD) );
            $this->results = [];
        }
    }

    protected function updatedQueryForLabel(): void
    {
        try {
            $this->setError('');
            // ⚠️ منطق التلميحات كما هو
            $this->results = $this->getLabelSuggestions($this->query);
        } catch (Exception $e) {
            $this->setError( __(self::ERR_LOAD) );
            $this->results = [];
        }
    }

    // اقتراحات بالاسم، مفلترة بالallowedCodes:
    protected function getLabelSuggestions($query): array
    {
        if (mb_strlen(trim($query ?? ''), 'UTF-8') < 2) {
            return [];
        }
        $allowedCodes = $this->getEffectiveAllowedCodes();
        if (empty($allowedCodes)) {
            return [];
        }
        $catalog = $this->catalog->code;
        $this->ensureValidCatalogCode($catalog);
        $partsTable        = $this->dyn('parts', $catalog);
        $sectionPartsTable = $this->dyn('section_parts', $catalog);

        $isArabic = preg_match('/[\x{0600}-\x{06FF}]/u', $query);
        $targetColumn = $isArabic ? 'label_ar' : 'label_en';

        $normalized = $this->normalizeArabic($query);
        $words = array_values(array_filter(preg_split('/\s+/', trim($normalized))));
        if (empty($words)) {
            return [];
        }
        $base = DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes);

        foreach ($words as $w) {
            $like = "%{$w}%";
            $base->where(function ($q) use ($like) {
                $q->where('p.label_en', 'like', $like)
                  ->orWhere('p.label_ar', 'like', $like);
            });
        }
        $suggestions = $base
            ->distinct()
            ->limit($this->maxResults)
            ->pluck("p.$targetColumn")
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        if (!empty($suggestions)) {
            return $suggestions;
        }
        // fallback من كلمة لكلمة (لا تغيّر المنطق هنا)
        $fallback = collect();
        foreach ($words as $w) {
            if (mb_strlen($w, 'UTF-8') < 2) continue;
            $like = "%{$w}%";
            $labels = DB::table("$partsTable as p")
                ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
                ->join('sections as s', 's.id', '=', 'sp.section_id')
                ->whereIn('s.full_code', $allowedCodes)
                ->where(function ($q) use ($like) {
                    $q->where('p.label_en', 'like', $like)
                      ->orWhere('p.label_ar', 'like', $like);
                })
                ->distinct()
                ->limit($this->maxResults)
                ->pluck("p.$targetColumn");
            $fallback = $fallback->merge($labels);
        }
        return $fallback->filter()->unique()->values()->toArray();
    }


    // الباحث الرئيسي
    public function searchFromInput(): void
    {
        $startTime = microtime(true);
        try {
            $this->isLoading = true;
            $this->setError('');

            if ($this->searchType === 'number') {
                $this->query = $this->sanitizeInput($this->query);

                // حدث اختياري
                if (!empty($this->results)) {
                    $firstItem = $this->results[0] ?? null;
                    if ($firstItem && isset($firstItem['callout'])) {
                        $this->dispatchBrowserEvent('open-callout-from-search', [
                            'part' => $firstItem
                        ]);
                    }
                }
            }

            if ($this->isQueryTooShort($this->query)) {
                $this->setError( $this->searchType === 'number' ? __(self::ERR_SHORT_NUM) : __(self::ERR_SHORT_LBL) );
                return;
            }
            
            if ($this->selectedItem !== null && $this->selectedItem !== $this->query) {
                $this->selectedItem = null; // لا تسمح للقيمة القديمة أن تتغلب على المكتوبة الآن
            }


            $this->query = $this->selectedItem ?? $this->query;
            $this->selectedItem = $this->query;

            $allowedCodes = $this->getEffectiveAllowedCodes();
            if (empty($allowedCodes)) {
                $this->setError( __(self::ERR_NO_ALLOWED) );
                return;
            }

            // ⚠️ مفاتيح رسالة "لا يوجد كول آوت" تختلف حسب التبويب
            // يجب تعريف الثابتين التاليين في أعلى الكلاس:
            // private const ERR_NO_CALLOUT_NUM   = 'errors.no_matching_callout_number';
            // private const ERR_NO_CALLOUT_LABEL = 'errors.no_matching_callout_label';
            $noCalloutKey = $this->searchType === 'number'
                ? self::ERR_NO_CALLOUT_NUM
                : self::ERR_NO_CALLOUT_LABEL;

            // جلب الكول آوتس
            $rows = $this->searchType === 'number'
                ? $this->fetchCalloutsByNumber($this->catalog->code, $this->query, $allowedCodes)
                : $this->fetchCalloutsByLabel($this->catalog->code, $this->query, $allowedCodes);
                // dd([
                //     'scope'               => $this->searchScope,          // section or catalog
                //     'allowedCodes_count'  => count($allowedCodes),
                //     'allowedCodes_sample' => array_slice($allowedCodes, 0, 1000),
                //     'rows_count'          => count($rows),
                //     'rows_cats_unique'    => array_values(array_unique(array_column($rows, 'category_code'))),
                // ]);

            // نبني الخيارات الفريدة (callout + section + code)
            $this->calloutOptions = collect($rows)
                ->filter(fn($r) => !empty($r['part_callout']) && !empty($r['section_id']) && !empty($r['category_code']))
                ->map(fn($r) => [
                    'callout'       => $r['part_callout'],
                    'section_id'    => $r['section_id'],
                    'category_code' => $r['category_code'],
                    'label_ar'      => $r['part_label_ar'] ?? null,
                    'label_en'      => $r['part_label_en'] ?? null,
                    'qty'           => $r['part_qty'] ?? null,
                    'period_begin'  => $r['period_begin'] ?? null,
                    'period_end'    => $r['period_end'] ?? null,
                ])
                ->unique(fn($o) => $o['callout'].'|'.$o['section_id'].'|'.$o['category_code'])
                ->filter(fn($o) => in_array($o['category_code'], $allowedCodes, true))
                ->values()
                ->all();

            // ثراء المفاتيح
            $this->calloutOptions = $this->enrichCalloutOptionsWithKeys($this->calloutOptions);

            // توجيه أو عرض المودال
            $this->showCalloutPicker = false;
            $this->singleRedirectUrl = null;

            $count = count($this->calloutOptions);
            if ($count === 1) {
                $opt = $this->calloutOptions[0];
                if (!empty($opt['key1']) && !empty($opt['key2']) && !empty($opt['key3'])) {
                    $this->singleRedirectUrl = route('illustrations', [
                        'id'            => $this->catalog->brand->name,
                        'data'          => $this->catalog->code,
                        'key1'          => $opt['key1'],
                        'key2'          => $opt['key2'],
                        'key3'          => $opt['key3'],
                        'vin'           => session('vin'),
                        // بارامترات الـ API لفتح المودال تلقائياً
                        'callout'       => $opt['callout'],
                        'auto_open'     => 1,
                        'section_id'    => $opt['section_id'],
                        'category_code' => $opt['category_code'],
                        'catalog_code'  => $this->catalog->code,
                        'category_id'   => $opt['category_id'] ?? null,
                    ]);

                    $this->emit('single-callout-ready');
                } elseif ($count > 1) {
                    $this->showCalloutPicker = true;
                } else {
                    // لا توجد نتائج مناسبة لهذا التبويب
                    $this->setError( __($noCalloutKey) );
                }
            } elseif ($count > 1) {
                $this->showCalloutPicker = true;
            } else {
                // لا توجد نتائج مناسبة لهذا التبويب
                $this->setError( __($noCalloutKey) );
            }

            Log::info('Callout search performance', [
                'query'          => $this->query,
                'type'           => $this->searchType,
                'execution_time' => round((microtime(true) - $startTime) * 1000, 2),
                'callouts_count' => $count,
                'scope'          => $this->searchScope,
            ]);
        } catch (Exception $e) {
            $this->setError( __(self::ERR_LOAD) );
            Log::error('Search error', ['error' => $e->getMessage(), 'query' => $this->query]);
        } finally {
            $this->isLoading = false;
        }
    }

    // إثراء calloutOptions بمفاتيح route
    protected function enrichCalloutOptionsWithKeys(array $options): array
    {
        if (empty($options)) {
            return [];
        }

        // 1) جهّز قائمة الأكواد والـ IDs
        $codes = collect($options)->pluck('category_code')->filter()->unique()->values()->all();

        // newcategories: نجيب id, parents_key, spec_key, Applicability
        $cats = NewCategory::query()
            ->where('catalog_id', $this->catalog->id)
            ->whereIn('full_code', $codes)
            ->get(['id','full_code','parents_key','spec_key','Applicability'])
            ->keyBy('full_code');

        // 2) periods: من الجدول category_periods (قد يكون عدة صفوف لنفس الكاتيجري → ناخذ المدى العام)
        $catIds = $cats->pluck('id')->filter()->unique()->values()->all();

        $periods = empty($catIds) ? collect() :
            DB::table('category_periods')
                ->whereIn('category_id', $catIds)
                ->select(
                    'category_id',
                    DB::raw('MIN(begin_date) as begin_date'),
                    DB::raw('MAX(end_date)   as end_date')
                )
                ->groupBy('category_id')
                ->get()
                ->keyBy('category_id');

        // 3) رجّع المصفوفة بعد الإثراء
        return array_map(function (array $o) use ($cats, $periods) {
            $cat    = $cats[$o['category_code']] ?? null;
            $catId  = $cat->id ?? null;
            $p      = $catId ? ($periods[$catId] ?? null) : null;

            return [
                ...$o,
                'key1'           => $cat->parents_key ?? null,
                'key2'           => $cat->spec_key    ?? null,
                'key3'           => $o['category_code'],
                'category_id'    => $catId,
                'applicability'  => $cat->Applicability ?? null,
                'cat_begin'      => $p->begin_date ?? null,
                'cat_end'        => $p->end_date   ?? null,
            ];
        }, $options);
    }

    // جلب الكول آوت بالرقم، مفلتر بالallowedCodes
    protected function fetchCalloutsByNumber(string $catalogCode, string $query, array $allowedCodes): array
    {
        $this->ensureValidCatalogCode($catalogCode);
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $cleanQuery = preg_replace('/[^0-9A-Za-z]+/', '', $query ?? '');
        if (empty($cleanQuery) || empty($allowedCodes)) return [];

        $matchingCallouts = DB::table($partsTable)
            ->where(function ($q) use ($cleanQuery) {
                $q->where('part_number', 'like', "{$cleanQuery}%")
                  ->orWhere('callout', 'like', "{$cleanQuery}%");
            })
            ->pluck('callout')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($matchingCallouts)) return [];

        $rows = DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes)
            ->whereIn('p.callout', $matchingCallouts)
            ->select(
                'p.id as part_id',
                'p.part_number',
                'p.label_en as part_label_en',
                'p.label_ar as part_label_ar',
                'p.callout as part_callout',
                'p.qty as part_qty',
                's.id as section_id',
                's.full_code as category_code'
            )
            ->limit($this->maxResults * 5)
            ->get();

        return $rows->map(fn($r) => (array) $r)->toArray();
    }

    // جلب الكول آوت بالاسم، مفلتر بالallowedCodes
    protected function fetchCalloutsByLabel(string $catalogCode, string $query, array $allowedCodes): array
    {
        $this->ensureValidCatalogCode($catalogCode);
        $partsTable        = $this->dyn('parts', $catalogCode);
        $sectionPartsTable = $this->dyn('section_parts', $catalogCode);
        $cleanQuery = (string) $query;
        if (empty($cleanQuery) || empty($allowedCodes)) return [];

        $matchingCallouts = DB::table($partsTable)
            ->where(function ($q) use ($cleanQuery) {
                $q->where('label_en', 'like', "{$cleanQuery}%")
                  ->orWhere('label_ar', 'like', "{$cleanQuery}%");
            })
            ->pluck('callout')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($matchingCallouts)) return [];

        $rows = DB::table("$partsTable as p")
            ->join("$sectionPartsTable as sp", 'sp.part_id', '=', 'p.id')
            ->join('sections as s', 's.id', '=', 'sp.section_id')
            ->whereIn('s.full_code', $allowedCodes)
            ->whereIn('p.callout', $matchingCallouts)
            ->select(
                'p.id as part_id',
                'p.part_number',
                'p.label_en as part_label_en',
                'p.label_ar as part_label_ar',
                'p.callout as part_callout',
                'p.qty as part_qty',
                's.id as section_id',
                's.full_code as category_code'
            )
            ->limit($this->maxResults * 5)
            ->get();

        return $rows->map(fn($r) => (array) $r)->toArray();
    }

    protected function isQueryTooShort($query): bool
    {
        if ($this->searchType === 'number') {
            return strlen($query ?? '') < 5;
        }
        if ($this->searchType === 'label') {
            return mb_strlen($query ?? '', 'UTF-8') < 2;
        }
        return true;
    }

    public function render()
    {
        try {
            $codes = collect($this->calloutOptions)
                ->pluck('category_code')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $categories = !empty($codes)
                ? NewCategory::whereIn('full_code', $codes)
                    ->get(['id','full_code','parents_key','spec_key'])
                    ->keyBy('full_code')
                : collect();

            return view('livewire.vehicle-search-box', [
                'catalog'             => $this->catalog,
                'validCategoryCodes'  => $codes,
                'preloadedCategories' => $categories,
            ]);
        } catch (Exception $e) {
            $this->setError( __(self::ERR_LOAD) );
            Log::error('Render error', ['error' => $e->getMessage()]);
            return view('livewire.vehicle-search-box', [
                'catalog'             => $this->catalog,
                'validCategoryCodes'  => [],
                'preloadedCategories' => collect(),
            ]);
        }
    }
}
