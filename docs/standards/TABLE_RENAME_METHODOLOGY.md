# Table Rename Methodology (محو الهوية الكامل)

## Overview

This document defines the complete methodology for renaming a database table and removing all traces of the old identity throughout the codebase. This is a "Complete Identity Removal" process.

**Usage**: When you need to rename a table, specify:
- `[OLD_NAME]` - The current table/identity name (e.g., `coupon`, `voucher`, `ticket`)
- `[NEW_NAME]` - The new table/identity name (e.g., `discount_code`, `gift_card`, `support_request`)

---

## ⛔ CRITICAL SAFETY RULES (قواعد أمان حرجة)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│  🚫 ABSOLUTELY FORBIDDEN - حتى لو طلب المستخدم ذلك:                          │
│  ─────────────────────────────────────────────────────────────────────────  │
│  • DROP DATABASE - Never delete the database                                │
│  • DROP TABLE (with data) - Never delete tables containing data             │
│  • TRUNCATE TABLE - Never truncate tables                                   │
│  • DELETE FROM table (without WHERE) - Never mass delete                    │
│                                                                             │
│  ⚠️ Even if the user explicitly asks to delete, DO NOT do it!              │
├─────────────────────────────────────────────────────────────────────────────┤
│  ✅ REQUIRED - Safe Table Rename Process:                                   │
│  ─────────────────────────────────────────────────────────────────────────  │
│  1. Create the NEW table with new structure                                 │
│  2. Migrate/copy data from OLD table to NEW table                           │
│  3. Rename OLD table with `_old` suffix (never delete!)                     │
│  4. Keep `_old` tables permanently for safety/rollback                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Naming Convention Mapping

When renaming from `[OLD_NAME]` to `[NEW_NAME]`, follow these patterns:

| Layer | Old Pattern | New Pattern |
|-------|-------------|-------------|
| Table | `[old_name]s` | `[new_name]s` |
| Model | `OldName.php` | `NewName.php` |
| Controller | `OldNameController.php` | `NewNameController.php` |
| Route Name | `old-name.*` | `new-name.*` |
| URL Segment | `/old-name/` | `/new-name/` |
| View Folder | `views/old-name/` | `views/new-name/` |
| Session Key | `session('old_name')` | `session('new_name')` |
| Permission | `Old Name` | `New Name` |
| Translation | `'Old Name'` | `'New Name'` |

---

## Complete 9-Step Process

### Step 1: Database Migration (Safe Approach)

**IMPORTANT:** Never delete tables with data. Always use the safe rename approach:

```php
// Migration: create_[new_name]s_and_migrate_from_[old_name]s.php

public function up()
{
    // Step 1: Create the NEW table structure
    Schema::create('[new_name]s', function (Blueprint $table) {
        $table->id();
        // ... define all columns for new table
        $table->timestamps();
    });

    // Step 2: Migrate data from old table to new table
    DB::statement('
        INSERT INTO [new_name]s (id, column1, column2, ...)
        SELECT id, column1, column2, ...
        FROM [old_name]s
    ');

    // Step 3: Rename OLD table with _old suffix (NEVER DELETE!)
    Schema::rename('[old_name]s', '[old_name]s_old');

    // Step 4: Update related tables' columns
    Schema::table('orders', function (Blueprint $table) {
        $table->renameColumn('[old_name]_id', '[new_name]_id');
        $table->renameColumn('[old_name]_discount', '[new_name]_discount');
    });
}

public function down()
{
    // Reverse: rename back, but keep _old table
    Schema::table('orders', function (Blueprint $table) {
        $table->renameColumn('[new_name]_id', '[old_name]_id');
        $table->renameColumn('[new_name]_discount', '[old_name]_discount');
    });

    Schema::rename('[old_name]s_old', '[old_name]s');
    Schema::dropIfExists('[new_name]s'); // Safe: data is in _old table
}
```

**Run**: `php artisan migrate`

**Note:** The `[old_name]s_old` table remains permanently for:
- Data recovery if needed
- Verification of migration
- Audit trail

---

### Step 2: Model Updates

#### 2.1 Rename Model File
```
app/Models/OldName.php → app/Models/NewName.php
```

#### 2.2 Update Model Content
```php
class NewName extends Model
{
    protected $table = '[new_name]s';

    protected $fillable = [
        // Update any column names that changed
        '[new_name]_field',
    ];
}
```

#### 2.3 Update Related Models
Update all models that have relationships to this model:

```php
// In Purchase.php, User.php, Merchant.php, etc.

// Change relationship method names
public function [newName]()  // was [oldName]()
{
    return $this->belongsTo(NewName::class, '[new_name]_id');
}

// Update fillable arrays
protected $fillable = [
    '[new_name]_id',
    '[new_name]_discount',
];
```

---

### Step 3: Controller Updates

#### 3.1 Rename Controller Files
```
OldNameController.php → NewNameController.php
MerchantOldNameController.php → MerchantNewNameController.php
```

#### 3.2 Update Controller Content
```php
namespace App\Http\Controllers\Admin;

class NewNameController extends Controller
{
    // Update all method references
    // Update model usage: NewName::all()
    // Update view returns: return view('admin.new-name.index')
    // Update route redirects: return redirect()->route('admin-new-name-index')
}
```

---

### Step 4: Helper Function Updates

Check and update `app/Helpers/helper.php`:

```php
// Rename functions
function applyNewName($code, $total)  // was applyOldName()
{
    // Update session key references
    session(['new_name' => $data]);  // was session(['old_name' => $data])

    // Update model references
    $item = NewName::where('code', $code)->first();
}

// Update all related helper functions
function removeNewName()  // was removeOldName()
{
    session()->forget('new_name');  // was 'old_name'
}
```

---

### Step 5: Route Updates

#### 5.1 Admin Routes (`routes/web.php`)
```php
// Rename route group and URLs
Route::prefix('new-name')->group(function () {  // was 'old-name'
    Route::get('/', [NewNameController::class, 'index'])
        ->name('admin-new-name-index');  // was 'admin-old-name-index'
    Route::get('/create', [NewNameController::class, 'create'])
        ->name('admin-new-name-create');
    // ... all other routes
});
```

#### 5.2 Merchant Routes
```php
Route::prefix('merchant/new-name')->group(function () {
    Route::get('/', [MerchantNewNameController::class, 'index'])
        ->name('merchant-new-name-index');
    // ... all other routes
});
```

#### 5.3 API Routes (if applicable)
```php
Route::prefix('api/new-name')->group(function () {
    // Update API endpoints
});
```

---

### Step 6: Session Key Updates

Search and update ALL session references:

```php
// In Controllers
session(['new_name' => $data]);     // was 'old_name'
session('new_name');                 // was 'old_name'
session()->forget('new_name');       // was 'old_name'
session()->has('new_name');          // was 'old_name'

// In Blade templates
@if(session('new_name'))             // was 'old_name'

// In JavaScript (AJAX responses)
data.new_name                        // was data.old_name
```

---

### Step 7: View Updates

#### 7.1 Rename View Folders
```
resources/views/admin/old-name/ → resources/views/admin/new-name/
resources/views/merchant/old-name/ → resources/views/merchant/new-name/
```

#### 7.2 Update View Content

**Labels and Titles:**
```blade
{{-- Change display text --}}
<h4>{{ __('New Names') }}</h4>          {{-- was 'Old Names' --}}
@lang('Add New Name')                    {{-- was 'Add Old Name' --}}
```

**Form Elements:**
```blade
{{-- Update name attributes --}}
<input name="new_name_field">           {{-- was 'old_name_field' --}}
<select name="new_name_id">             {{-- was 'old_name_id' --}}
```

**Breadcrumbs:**
```blade
<li><a href="{{ route('admin-new-name-index') }}">@lang('New Names')</a></li>
```

**Hidden Inputs:**
```blade
<input type="hidden" id="headerdata" value="{{ __('NEW NAME') }}">
```

**Modal Messages:**
```blade
<p>{{ __('You are about to delete this New Name.') }}</p>
```

---

### Step 8: JavaScript Updates

#### 8.1 AJAX URLs
```javascript
// Update all AJAX endpoint URLs
$.ajax({
    url: '{{ url("admin/new-name/status") }}/' + id,  // was 'old-name'
    // ...
});
```

#### 8.2 DOM Selectors
```javascript
// Update ID selectors
$('#new-name-table')           // was '#old-name-table'
$('#newNameForm')              // was '#oldNameForm'
$('.new-name-class')           // was '.old-name-class'
```

#### 8.3 Function Names
```javascript
// Update function names
function applyNewName() {}     // was applyOldName()
function removeNewName() {}    // was removeOldName()
```

#### 8.4 Variable Names
```javascript
// Update variable names
var newNameData = {};          // was oldNameData
let newNameDiscount = 0;       // was oldNameDiscount
```

---

### Step 9: Permission & Translation Updates

#### 9.1 Admin Permissions
```blade
{{-- In partials/admin-role/super.blade.php --}}
{{ __('Set New Names') }}      {{-- was 'Set Old Names' --}}
```

#### 9.2 Sidebar Navigation
```blade
{{-- In includes/admin/sidebar.blade.php --}}
<span class="label">@lang('New Names')</span>
<a href="{{ route('admin-new-name-index') }}">
```

#### 9.3 Merchant Sidebar
```blade
{{-- In includes/merchant/sidebar.blade.php --}}
<span class="label">@lang('New Names')</span>
```

---

## Verification Commands

After completing all steps, run these commands to verify complete removal:

```bash
# 1. Search for remaining references to old name (case-insensitive)
grep -ri "[old_name]" app/ --include="*.php" | grep -v ".git"
grep -ri "[old_name]" resources/views/ --include="*.php" | grep -v ".git"
grep -ri "[old_name]" routes/ --include="*.php" | grep -v ".git"

# 2. Search in JavaScript files
grep -ri "[old_name]" public/ --include="*.js" | grep -v ".git"
grep -ri "[old_name]" resources/ --include="*.js" | grep -v ".git"

# 3. Search in config files
grep -ri "[old_name]" config/ --include="*.php" | grep -v ".git"

# 4. Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# 5. Verify routes are registered correctly
php artisan route:list | grep "new-name"

# 6. Run tests to ensure nothing is broken
php artisan test
```

---

## Files Typically Affected

### PHP Files
- `app/Models/NewName.php` (renamed)
- `app/Models/Purchase.php` (relationship updates)
- `app/Models/MerchantPurchase.php` (relationship updates)
- `app/Http/Controllers/Operator/NewNameController.php` (renamed)
- `app/Http/Controllers/Merchant/NewNameController.php` (renamed)
- `app/Helpers/helper.php` (function updates)
- `routes/web.php` (route definitions)
- `database/migrations/*_rename_table.php` (new migration)

### View Files
- `resources/views/operator/new-name/*.blade.php` (renamed folder)
- `resources/views/merchant/new-name/*.blade.php` (renamed folder)
- `resources/views/includes/operator/sidebar.blade.php`
- `resources/views/includes/merchant/sidebar.blade.php`
- `resources/views/partials/admin-role/super.blade.php`
- `resources/views/includes/checkout-*.blade.php` (if applicable)
- `resources/views/frontend/*.blade.php` (if applicable)

### JavaScript Files
- `public/assets/admin/js/*.js`
- `public/assets/front/js/*.js`
- Inline `<script>` blocks in Blade files

---

## Checklist

Use this checklist for each table rename:

```
□ Step 1: Database migration created and run
□ Step 2: Model file renamed and updated
□ Step 2: Related models updated (relationships, fillable)
□ Step 3: Controller files renamed and updated
□ Step 4: Helper functions renamed and updated
□ Step 5: Admin routes updated (names, URLs, prefixes)
□ Step 5: Merchant routes updated
□ Step 5: API routes updated (if applicable)
□ Step 6: Session keys updated in PHP
□ Step 6: Session keys updated in Blade
□ Step 6: Session keys updated in JavaScript
□ Step 7: View folders renamed
□ Step 7: View labels/titles updated
□ Step 7: Form field names updated
□ Step 7: Breadcrumbs updated
□ Step 7: Modal messages updated
□ Step 8: AJAX URLs updated
□ Step 8: DOM selectors updated
□ Step 8: JS function names updated
□ Step 8: JS variable names updated
□ Step 9: Admin permissions updated
□ Step 9: Sidebar navigation updated
□ Step 9: Merchant sidebar updated
□ Verification: grep shows no remaining old references
□ Verification: All caches cleared
□ Verification: Routes list shows new names
□ Verification: Tests pass
```

---

## Notes

- **Always backup** before starting a table rename
- **Test in development** before applying to production
- **Search case-insensitively** as references may use different cases
- **Check comments** in code - they may contain old names
- **Check translations** in `resources/lang/` folders
- **Check API documentation** if you have any
