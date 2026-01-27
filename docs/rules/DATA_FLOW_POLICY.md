# سياسة مسار البيانات الصارمة (Data Flow Policy)

> **تاريخ الإنشاء:** 2026-01-26
> **الحالة:** إلزامي
> **مستوى الأهمية:** حرج

---

## 1. المبدأ الأساسي

```
┌─────────────┐      ┌─────────────┐      ┌─────────────┐      ┌─────────────┐
│   Model     │ ───► │   Service   │ ───► │    DTO      │ ───► │    View     │
│  (Domain)   │      │  (تجهيز)    │      │  (جاهز)     │      │  (عرض فقط) │
└─────────────┘      └─────────────┘      └─────────────┘      └─────────────┘
       │                    │                    │                    │
       │                    │                    │                    │
   استعلام            حساب/تنسيق           بيانات نهائية         لا يوجد منطق
   فقط               ومعالجة               immutable             مجرد {{ }}
```

**القاعدة الذهبية:**
```
البيانات تتدفق في اتجاه واحد فقط.
View لا تطلب بيانات - تستقبل فقط.
Service هي المصدر الوحيد للمنطق.
```

---

## 2. طبقات مسار البيانات

### 2.1 Layer 1: Model (Domain)
**المسؤولية:** تمثيل البيانات وعلاقاتها فقط

```php
// ✅ مسموح في Model
class CatalogItem extends Model
{
    protected $casts = ['data' => 'array'];

    public function merchantItems(): HasMany { ... }
    public function brand(): BelongsTo { ... }

    // Accessor بسيط
    public function getPhotoUrlAttribute(): string { ... }
}

// ❌ ممنوع في Model
class CatalogItem extends Model
{
    public function getFormattedPriceAttribute()
    {
        return monetaryUnit()->format($this->price); // منطق عرض!
    }

    public function getOffersCountAttribute()
    {
        return $this->merchantItems()->count(); // Query في accessor!
    }
}
```

### 2.2 Layer 2: Service (تجهيز البيانات)
**المسؤولية:** كل المنطق، الحسابات، التنسيق، الاستعلامات

```php
// ✅ Service هي المكان الوحيد للمنطق
class CatalogItemCardDataBuilder
{
    public function __construct(
        private MonetaryUnitService $monetary,
        private FavoritesService $favorites,
    ) {}

    public function build(CatalogItem $item, ?MerchantItem $offer): CatalogItemCardDTO
    {
        // كل المنطق هنا
        $price = $offer?->price ?? 0;
        $previousPrice = $offer?->previous_price ?? 0;
        $discount = $this->calculateDiscount($previousPrice, $price);

        return new CatalogItemCardDTO(
            id: $item->id,
            name: $item->name,
            formattedPrice: $this->monetary->format($price),
            discountPercentage: $discount,
            offersCount: $this->countActiveOffers($item),
            // ... كل القيم محسوبة
        );
    }

    private function countActiveOffers(CatalogItem $item): int
    {
        return $item->merchantItems()
            ->where('status', 1)
            ->whereHas('user', fn($q) => $q->where('is_merchant', 2))
            ->count();
    }
}
```

### 2.3 Layer 3: DTO (البيانات الجاهزة)
**المسؤولية:** حمل البيانات النهائية فقط - immutable

```php
// ✅ DTO صحيح - readonly و final
final class CatalogItemCardDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $photoUrl,
        public readonly string $formattedPrice,
        public readonly ?string $formattedPreviousPrice,
        public readonly int $discountPercentage,
        public readonly bool $inStock,
        public readonly string $stockText,
        public readonly string $stockClass,
        public readonly int $offersCount,
        public readonly bool $hasMultipleOffers,
        public readonly string $detailsUrl,
        public readonly bool $canAddToCart,
        public readonly ?string $merchantName,
    ) {}

    // لا methods باستثناء serialization
    public function toArray(): array { ... }
}

// ❌ DTO خاطئ
class CatalogItemCardDTO
{
    public $price; // ليس readonly

    public function getFormattedPrice(): string
    {
        return number_format($this->price); // منطق في DTO!
    }
}
```

### 2.4 Layer 4: Controller (التنسيق)
**المسؤولية:** استدعاء Service وتمرير DTO للـ View

```php
// ✅ Controller صحيح
class CatalogController extends Controller
{
    public function __construct(
        private CatalogItemService $catalogService,
        private CatalogItemCardDataBuilder $cardBuilder,
    ) {}

    public function show(string $partNumber)
    {
        // 1. احصل على البيانات الخام من Service
        $catalogItem = $this->catalogService->findByPartNumber($partNumber);
        $offers = $this->catalogService->getActiveOffers($catalogItem);

        // 2. حول إلى DTOs
        $mainCard = $this->cardBuilder->build($catalogItem, $offers->first());
        $offerCards = $offers->map(fn($o) => $this->cardBuilder->buildOfferCard($o));

        // 3. مرر DTOs فقط للـ View
        return view('frontend.catalog.show', [
            'card' => $mainCard,          // DTO
            'offers' => $offerCards,      // Collection<DTO>
            'breadcrumbs' => $this->buildBreadcrumbs($catalogItem), // array
        ]);
    }
}

// ❌ Controller خاطئ
class CatalogController extends Controller
{
    public function show(string $partNumber)
    {
        $item = CatalogItem::where('part_number', $partNumber)
            ->with('merchantItems.user')
            ->firstOrFail();

        // تمرير Model مباشرة!
        return view('frontend.catalog.show', [
            'item' => $item,  // ❌ Model وليس DTO
            'offers' => $item->merchantItems, // ❌ Collection<Model>
        ]);
    }
}
```

### 2.5 Layer 5: View (عرض فقط)
**المسؤولية:** عرض البيانات الجاهزة - لا منطق

```blade
{{-- ✅ View صحيح --}}
<div class="product-card">
    <img src="{{ $card->photoUrl }}" alt="{{ $card->name }}">
    <h3>{{ $card->name }}</h3>

    @if($card->discountPercentage > 0)
        <span class="badge">-{{ $card->discountPercentage }}%</span>
    @endif

    <div class="price">{{ $card->formattedPrice }}</div>
    <span class="stock {{ $card->stockClass }}">{{ $card->stockText }}</span>

    @if($card->hasMultipleOffers)
        <a href="{{ $card->detailsUrl }}">
            {{ $card->offersCount }} {{ __('offers') }}
        </a>
    @endif
</div>

{{-- ❌ View خاطئ --}}
<div class="product-card">
    <h3>{{ $item->name ?? 'Unknown' }}</h3>  {{-- fallback! --}}

    @php
        $discount = (($item->previous_price - $item->price) / $item->previous_price) * 100;
    @endphp
    <span>-{{ round($discount) }}%</span>  {{-- منطق! --}}

    <span>{{ $item->merchantItems()->where('status', 1)->count() }} offers</span>  {{-- Query! --}}

    <div>{{ number_format($item->price, 2) }} {{ $currency->sign }}</div>  {{-- تنسيق! --}}
</div>
```

---

## 3. قواعد Helper Functions

### 3.1 المسموح في Helpers

```php
// app/Helpers/helper.php

// ✅ مسموح - وصول لـ Service
function monetaryUnit(): MonetaryUnitService
{
    return app(MonetaryUnitService::class);
}

// ✅ مسموح - وصول لإعدادات
function platformSetting(string $key, $default = null): mixed
{
    return app(PlatformSettingsService::class)->get($key, $default);
}

// ✅ مسموح - utilities بسيطة
function getLocalizedShopName(User $merchant): string
{
    return app()->getLocale() === 'ar'
        ? ($merchant->shop_name_ar ?: $merchant->shop_name)
        : $merchant->shop_name;
}
```

### 3.2 الممنوع في Helpers

```php
// ❌ ممنوع - Query مباشر
function getActiveOffersCount(int $catalogItemId): int
{
    return MerchantItem::where('catalog_item_id', $catalogItemId)
        ->where('status', 1)
        ->count();
}

// ❌ ممنوع - منطق أعمال
function calculateShippingCost(array $cart, int $cityId): float
{
    // هذا يجب أن يكون في ShippingService
}

// ❌ ممنوع - تنسيق معقد
function formatProductCard(CatalogItem $item): array
{
    // هذا يجب أن يكون في DTO Builder
}
```

### 3.3 قاعدة Helper الصارمة

```
كل Helper يجب أن يكون:
1. Stateless (لا يحفظ حالة)
2. Pure function أو Service accessor
3. لا يحتوي على Query مباشر
4. لا يحتوي على منطق أعمال
```

---

## 4. أنماط ممنوعة (Anti-patterns)

### 4.1 في Controllers

| النمط الممنوع | السبب | البديل |
|---------------|-------|--------|
| `Model::with(...)->get()` مباشرة للـ View | تسريب Model للـ View | استخدم Service + DTO |
| `compact('model', 'items')` | يمرر Models | مرر DTOs فقط |
| Query في Controller | منطق في مكان خاطئ | استخدم Repository/Service |
| `$request->all()` بدون validation | أمان | استخدم FormRequest |

### 4.2 في Views

| النمط الممنوع | الكود | البديل |
|---------------|-------|--------|
| **Query** | `$model->items()->count()` | `$dto->itemsCount` |
| **Fallback** | `$var ?? 'default'` | بيانات كاملة في DTO |
| **منطق** | `@php $x = $a + $b @endphp` | حساب في Service |
| **تنسيق** | `number_format($price)` | `$dto->formattedPrice` |
| **شرط معقد** | `@if($a && $b \|\| $c)` | `$dto->shouldShow` |
| **Method chain** | `$item->brand->name` | `$dto->brandName` |

### 4.3 في Helpers

| النمط الممنوع | السبب | البديل |
|---------------|-------|--------|
| `DB::table()` | Query مباشر | Repository |
| `Model::where()` | Query مباشر | Service method |
| Business logic | مكان خاطئ | Domain Service |
| State storage | Side effects | Stateless |

---

## 5. قائمة DTOs المطلوبة

### 5.1 DTOs الموجودة (في Domain)

| DTO | الموقع | الاستخدام |
|-----|--------|----------|
| `CatalogItemCardDTO` | Catalog | بطاقات المنتجات |
| `QuickViewDTO` | Catalog | Quick view modal |
| `CartItemDTO` | Commerce | عناصر السلة |
| `CartTotalsDTO` | Commerce | إجماليات السلة |
| `BranchCartDTO` | Commerce | سلة الفرع |
| `CheckoutAddressDTO` | Commerce | عنوان الشحن |
| `ShippingOptionDTO` | Shipping | خيارات الشحن |
| `MonetaryValueDTO` | Platform | القيم المالية |

### 5.2 DTOs المطلوب إنشاؤها

| DTO | Domain | الاستخدام |
|-----|--------|----------|
| `MerchantCardDTO` | Merchant | بطاقة التاجر |
| `MerchantProfileDTO` | Merchant | صفحة التاجر |
| `PurchaseListDTO` | Commerce | قائمة الطلبات |
| `PurchaseDetailsDTO` | Commerce | تفاصيل الطلب |
| `UserDashboardDTO` | Identity | لوحة المستخدم |
| `CategoryTreeDTO` | Catalog | شجرة التصنيفات |
| `SearchResultDTO` | Catalog | نتائج البحث |
| `TrackingDTO` | Shipping | تتبع الشحنة |

---

## 6. آلية التنفيذ

### 6.1 Lint Command محسن

```php
// app/Console/Commands/LintDataFlow.php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

class LintDataFlow extends Command
{
    protected $signature = 'lint:dataflow
                            {--layer= : Check specific layer (controller|view|helper)}
                            {--ci : Exit with error code on violations}
                            {--fix : Show fix suggestions}';

    protected $description = 'Check Data Flow Policy compliance';

    // أنماط ممنوعة حسب الطبقة
    protected array $layerPatterns = [
        'controller' => [
            [
                'pattern' => '/return\s+view\s*\([^)]+,\s*compact\s*\(/i',
                'message' => 'Using compact() may pass Models to View',
                'code' => 'COMPACT_IN_VIEW',
                'fix' => 'Pass DTOs explicitly: return view("x", ["dto" => $dto])',
            ],
            [
                'pattern' => '/return\s+view\s*\([^)]+\$(?!dto|card|data|breadcrumbs|pagination)\w+/i',
                'message' => 'Passing non-DTO variable to view',
                'code' => 'NON_DTO_TO_VIEW',
                'fix' => 'Convert to DTO before passing to view',
            ],
        ],
        'view' => [
            [
                'pattern' => '/\$\w+->(?:where|find|first|get|count|exists|pluck|load)\s*\(/i',
                'message' => 'Query/Lazy loading in Blade',
                'code' => 'QUERY_IN_BLADE',
                'fix' => 'Pre-compute in Service, pass via DTO',
            ],
            [
                'pattern' => '/\$(?!dto|card|item|data|errors|slot|attributes|loop)\w+->\w+->/i',
                'message' => 'Deep property access (possible Model relation)',
                'code' => 'DEEP_ACCESS',
                'fix' => 'Flatten in DTO: $dto->brandName instead of $item->brand->name',
            ],
            [
                'pattern' => '/@php[\s\S]{50,}?@endphp/s',
                'message' => 'Large @php block (>50 chars)',
                'code' => 'LARGE_PHP_BLOCK',
                'fix' => 'Move logic to Service/DTO',
            ],
            [
                'pattern' => '/\{\{\s*\$\w+\s*\?\?\s*[\'"][^\'"]+[\'"]\s*\}\}/i',
                'message' => 'Null coalescing fallback in output',
                'code' => 'FALLBACK_OUTPUT',
                'fix' => 'Ensure DTO provides complete data',
            ],
            [
                'pattern' => '/number_format\s*\(\s*\$/',
                'message' => 'Number formatting in Blade',
                'code' => 'FORMAT_IN_BLADE',
                'fix' => 'Use pre-formatted $dto->formattedPrice',
            ],
        ],
        'helper' => [
            [
                'pattern' => '/(?:DB::table|Model::where|::find|::first)\s*\(/i',
                'message' => 'Direct query in Helper',
                'code' => 'QUERY_IN_HELPER',
                'fix' => 'Use Repository or Service instead',
            ],
            [
                'pattern' => '/function\s+\w+\s*\([^)]*\)\s*:\s*(?:array|object)\s*\{[\s\S]*?(?:where|select|join)/i',
                'message' => 'Complex data building in Helper',
                'code' => 'BUILDER_IN_HELPER',
                'fix' => 'Move to dedicated Builder/Service class',
            ],
        ],
    ];

    public function handle(): int
    {
        $layer = $this->option('layer');
        $ciMode = $this->option('ci');
        $showFix = $this->option('fix');

        $violations = [];

        if (!$layer || $layer === 'controller') {
            $violations = array_merge(
                $violations,
                $this->scanLayer('controller', app_path('Http/Controllers'))
            );
        }

        if (!$layer || $layer === 'view') {
            $violations = array_merge(
                $violations,
                $this->scanLayer('view', resource_path('views'))
            );
        }

        if (!$layer || $layer === 'helper') {
            $violations = array_merge(
                $violations,
                $this->scanLayer('helper', app_path('Helpers'))
            );
        }

        $this->reportViolations($violations, $showFix);

        if ($ciMode && count($violations) > 0) {
            return 1;
        }

        return 0;
    }

    // ... implementation
}
```

### 6.2 Middleware للتحقق (Development فقط)

```php
// app/Http/Middleware/ValidateViewData.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

class ValidateViewData
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (app()->environment('local') && $response instanceof \Illuminate\View\View) {
            $this->validateData($response->getData());
        }

        return $response;
    }

    protected function validateData(array $data): void
    {
        foreach ($data as $key => $value) {
            if ($value instanceof Model) {
                logger()->warning("Model passed to view: \${$key}", [
                    'class' => get_class($value),
                    'route' => request()->route()?->getName(),
                ]);
            }

            if ($value instanceof \Illuminate\Database\Eloquent\Collection) {
                if ($value->first() instanceof Model) {
                    logger()->warning("Model Collection passed to view: \${$key}", [
                        'class' => get_class($value->first()),
                        'route' => request()->route()?->getName(),
                    ]);
                }
            }
        }
    }
}
```

---

## 7. أمثلة التحويل

### 7.1 مثال: صفحة تفاصيل المنتج

**قبل (خاطئ):**
```php
// Controller
public function show($slug)
{
    $product = CatalogItem::with(['merchantItems.user', 'brand'])->findOrFail($slug);
    return view('product.show', compact('product'));
}
```
```blade
{{-- View --}}
<h1>{{ $product->name ?? 'Unknown' }}</h1>
<img src="{{ $product->photo ? Storage::url($product->photo) : asset('noimage.png') }}">
<p>Brand: {{ $product->brand->name ?? 'N/A' }}</p>
<p>{{ $product->merchantItems()->where('status', 1)->count() }} offers</p>
@foreach($product->merchantItems as $offer)
    <div>{{ number_format($offer->price, 2) }} {{ session('currency_sign', 'SAR') }}</div>
@endforeach
```

**بعد (صحيح):**
```php
// Controller
public function show(string $slug, CatalogItemCardDataBuilder $builder)
{
    $product = $this->catalogService->findBySlug($slug);
    $offers = $this->catalogService->getActiveOffers($product);

    return view('product.show', [
        'card' => $builder->buildDetail($product, $offers->first()),
        'offerCards' => $offers->map(fn($o) => $builder->buildOfferCard($o)),
    ]);
}
```
```blade
{{-- View --}}
<h1>{{ $card->name }}</h1>
<img src="{{ $card->photoUrl }}" alt="{{ $card->name }}">
<p>Brand: {{ $card->brandName }}</p>
<p>{{ $card->offersCount }} {{ __('offers') }}</p>
@foreach($offerCards as $offer)
    <div>{{ $offer->formattedPrice }}</div>
@endforeach
```

### 7.2 مثال: قائمة الطلبات

**قبل (خاطئ):**
```php
// Controller
public function index()
{
    $purchases = Purchase::with('merchantPurchases.merchant')
        ->where('user_id', auth()->id())
        ->latest()
        ->paginate(10);

    return view('user.purchases', compact('purchases'));
}
```

**بعد (صحيح):**
```php
// DTO
final class PurchaseListItemDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $orderNumber,
        public readonly string $formattedDate,
        public readonly string $formattedTotal,
        public readonly string $status,
        public readonly string $statusClass,
        public readonly int $itemsCount,
        public readonly string $detailsUrl,
    ) {}
}

// Service
class PurchaseListBuilder
{
    public function buildList(Collection $purchases): Collection
    {
        return $purchases->map(fn($p) => new PurchaseListItemDTO(
            id: $p->id,
            orderNumber: $p->order_number,
            formattedDate: $p->created_at->format('Y-m-d'),
            formattedTotal: monetaryUnit()->format($p->total),
            status: __("status.{$p->status}"),
            statusClass: $this->getStatusClass($p->status),
            itemsCount: count($p->cart ?? []),
            detailsUrl: route('user.purchase.show', $p->id),
        ));
    }
}

// Controller
public function index(PurchaseListBuilder $builder)
{
    $purchases = $this->purchaseService->getUserPurchases(auth()->id(), 10);

    return view('user.purchases', [
        'purchases' => $builder->buildList($purchases),
        'pagination' => $purchases, // للـ pagination links فقط
    ]);
}
```

---

## 8. Checklist للمراجعة

### قبل كل Commit:

```markdown
## Controller Checklist
- [ ] لا يوجد `compact()` مع Models
- [ ] جميع المتغيرات الممررة للـ View هي DTOs أو arrays
- [ ] لا يوجد Query مباشر في Controller
- [ ] يستخدم Service للمنطق

## View Checklist
- [ ] لا يوجد `$model->relationship()`
- [ ] لا يوجد `??` أو `?:` للبيانات الأساسية
- [ ] لا يوجد `@php` blocks كبيرة
- [ ] لا يوجد `number_format()` أو تنسيق
- [ ] لا يوجد `->` متعدد (deep access)

## Helper Checklist
- [ ] لا يوجد `DB::` أو `Model::where()`
- [ ] Functions هي pure أو service accessors
- [ ] لا يوجد منطق أعمال
```

---

## 9. العقوبات والتنفيذ

### 9.1 CI/CD
- أي violation يفشل الـ build
- لا يمكن merge PR مع violations

### 9.2 Code Review
- Reviewer يرفض أي كود يخالف السياسة
- لا استثناءات بدون توثيق في `@dataflow-exception`

### 9.3 استثناءات موثقة
```php
// فقط في حالات استثنائية مع تبرير
/** @dataflow-exception: Legacy code, scheduled for refactor in v2.1 */
```

---

## 10. المراجع

- `docs/plans/BLADE_DISPLAY_ONLY_PLAN.md` - خطة التنفيذ التفصيلية
- `app/Domain/Catalog/DTOs/CatalogItemCardDTO.php` - مثال DTO نموذجي
- `app/Domain/Catalog/Services/CatalogItemCardDataBuilder.php` - مثال Builder

---

**نهاية السياسة**
