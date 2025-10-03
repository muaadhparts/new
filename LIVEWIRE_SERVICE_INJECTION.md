# Livewire Service Injection Pattern

## ✅ Correct Implementation

All Livewire components in this project correctly implement service dependency injection using the following pattern:

### Pattern:
1. **Declare protected property** for the service
2. **Inject via boot() method**
3. **Use throughout component** methods

### Example:

```php
class MyComponent extends Component
{
    // ✅ Step 1: Declare as PROTECTED (not public!)
    protected CatalogSessionManager $sessionManager;

    // ✅ Step 2: Inject via boot() method
    public function boot(CatalogSessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }

    // ✅ Step 3: Use in methods
    public function myMethod()
    {
        $filters = $this->sessionManager->getSelectedFilters();
    }
}
```

---

## 📋 Implementation Status

### ✅ All Components Verified

| Component | Service(s) | Status |
|-----------|-----------|--------|
| **CatlogTreeLevel1** | CatalogSessionManager, CategoryFilterService | ✅ Protected |
| **CatlogTreeLevel2** | CatalogSessionManager, CategoryFilterService | ✅ Protected |
| **CatlogTreeLevel3** | CatalogSessionManager, CategoryFilterService | ✅ Protected |
| **Attributes** | CatalogSessionManager | ✅ Protected |
| **Illustrations** | CatalogSessionManager | ✅ Protected |
| **SearchBoxvin** | CatalogSessionManager | ✅ Protected |
| **VehicleSearchBox** | CatalogSessionManager | ✅ Protected (Fixed) |
| **Alternativeproduct** | AlternativeService | ✅ Protected |
| **Compatibility** | CompatibilityService | ✅ Protected |

---

## ⚠️ Important Rules

### Livewire Property Type Restrictions:

**Public properties** can ONLY be:
- `string`
- `int` / `float`
- `bool`
- `array`
- `null`

**Protected/Private properties** can be:
- Any type (including objects, services, models, etc.)

### Why?

Livewire serializes public properties to JavaScript for reactivity. Objects cannot be serialized, so they must be `protected` or `private`.

---

## 🚫 Common Mistakes

### ❌ Wrong - Public service property:
```php
class MyComponent extends Component
{
    public CatalogSessionManager $sessionManager; // ❌ ERROR!

    public function boot(CatalogSessionManager $sm)
    {
        $this->sessionManager = $sm;
    }
}
```

**Error:** `PublicPropertyTypeNotAllowedException`

### ✅ Correct - Protected service property:
```php
class MyComponent extends Component
{
    protected CatalogSessionManager $sessionManager; // ✅ CORRECT

    public function boot(CatalogSessionManager $sm)
    {
        $this->sessionManager = $sm;
    }
}
```

---

## 📝 Component Details

### Tree Level Components (3 files)

**CatlogTreeLevel1.php:**
```php
protected CatalogSessionManager $sessionManager;
protected CategoryFilterService $filterService;

public function boot(CatalogSessionManager $sessionManager, CategoryFilterService $filterService)
{
    $this->sessionManager = $sessionManager;
    $this->filterService = $filterService;
}
```

**CatlogTreeLevel2.php:** (Same pattern)
**CatlogTreeLevel3.php:** (Same pattern)

---

### Session Management Components (4 files)

**Attributes.php:**
```php
protected CatalogSessionManager $sessionManager;

public function boot(CatalogSessionManager $sessionManager)
{
    $this->sessionManager = $sessionManager;
}
```

**Illustrations.php:** (Same pattern)
**SearchBoxvin.php:** (Same pattern)
**VehicleSearchBox.php:** (Same pattern)

---

### Alternative/Compatibility Components (2 files)

**Alternativeproduct.php:**
```php
protected AlternativeService $alternativeService;

public function boot(AlternativeService $alternativeService)
{
    $this->alternativeService = $alternativeService;
}
```

**Compatibility.php:**
```php
protected CompatibilityService $compatibilityService;

public function boot(CompatibilityService $compatibilityService)
{
    $this->compatibilityService = $compatibilityService;
}
```

---

## 🔍 Verification Commands

Check for any public service properties (should return nothing):
```bash
grep -R "public .*SessionManager\|public .*FilterService\|public .*AlternativeService\|public .*CompatibilityService" app/Livewire/*.php | grep -v "boot("
```

Verify all protected properties exist:
```bash
grep -R "protected.*SessionManager\|protected.*FilterService\|protected.*AlternativeService\|protected.*CompatibilityService" app/Livewire/*.php
```

---

## ✅ Testing

After fixing VehicleSearchBox, all components now properly implement the pattern:

```bash
✅ php -l app/Livewire/VehicleSearchBox.php
   No syntax errors detected

✅ All components have protected service properties
✅ All components use boot() method for injection
✅ No PublicPropertyTypeNotAllowedException errors
```

---

## 📚 References

- [Livewire Dependency Injection](https://livewire.laravel.com/docs/properties#dependency-injection)
- [Livewire Property Types](https://livewire.laravel.com/docs/properties#supported-types)

---

**Last Updated:** 2025-01-10
**Status:** ✅ All components compliant
