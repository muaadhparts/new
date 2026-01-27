# CSS Design System & Theme Architecture

## CSS File Structure & Load Order

Files MUST be loaded in this exact order:

```html
1. bootstrap.min.css     <!-- Framework base -->
2. External libraries    <!-- slick, nice-select, etc. -->
3. style.css             <!-- Legacy styles (FROZEN - no new code) -->
4. muaadh-system.css     <!-- Design System (ALL NEW STYLES HERE) -->
5. rtl.css               <!-- RTL support (if Arabic) -->
6. theme-colors.css      <!-- Admin Panel overrides (ALWAYS LAST) -->
```

Location: `public/assets/front/css/`

## CSS Files Purpose

- `muaadh-system.css` -> Design System (ALL new styles here)
- `theme-colors.css` -> Theme variables (auto-generated)
- `rtl.css` -> RTL support
- `style.css` -> FROZEN legacy (do not modify)

## Build Commands

```bash
npm run lint:theme   # Check for color violations
npm run build        # Lint + Build (fails on violations)
npm run build:prod   # Lint + PurgeCSS + Build
```

## NEW Components: Use `m-` Prefix

For ALL new CSS, use the Design System in `muaadh-system.css`:

```html
<!-- CORRECT - Design System -->
<button class="m-btn m-btn--primary">Save</button>
<button class="m-btn m-btn--danger">Delete</button>
<button class="m-btn m-btn--success m-btn--lg">Approve</button>

<span class="m-badge m-badge--paid">Paid</span>
<span class="m-badge m-badge--pending">Pending</span>

<div class="m-card">
    <div class="m-card__header">Name</div>
    <div class="m-card__body">Content</div>
</div>
```

## Legacy Classes (Still Work, But Don't Add New)

```html
<!-- LEGACY - Still works, don't use for new code -->
<button class="template-btn">Primary</button>
<button class="btn btn-primary">Primary</button>
<button class="muaadh-btn">Primary</button>
```

## Variable Hierarchy

```css
/* Level 1: Theme (Admin Panel) */
--theme-primary: #7c3aed;

/* Level 2: Semantic (Design System) */
--action-primary: var(--theme-primary);
--action-danger: var(--theme-danger);

/* Level 3: Component */
--btn-primary-bg: var(--action-primary);
```

## Component Inventory

| Class | Purpose | Color |
|-------|---------|-------|
| `.m-btn--primary` | Main action (Save) | `--action-primary` |
| `.m-btn--danger` | Destructive (Delete) | `--action-danger` |
| `.m-btn--success` | Positive (Approve) | `--action-success` |
| `.m-btn--warning` | Caution (Edit) | `--action-warning` |
| `.m-badge--paid` | Payment confirmed | green |
| `.m-badge--pending` | Awaiting action | yellow |
| `.m-badge--cancelled` | Cancelled | red |

## Page Background Convention

All frontend pages MUST follow this background system:

```
Level 1: PAGE WRAPPER (.m-page or .muaadh-page-wrapper)
  - Full page background color

  Level 2: SECTIONS (.m-page__section or .muaadh-section)
    - Transparent by default (inherits from page)

    Level 3: CARDS/CONTENT (.m-card, .m-content-box)
      - White/elevated backgrounds for content areas
```

**New Pages (Preferred):**
```html
<div class="m-page m-page--gray">
    <section class="m-page__section">
        <div class="container">
            <div class="m-card">Content</div>
        </div>
    </section>
</div>
```

**Background Variants:**

| Class | Description |
|-------|-------------|
| `.m-page--gray` / `.muaadh-section-gray` | Gray background (default for most pages) |
| `.m-page--white` | White background (special pages) |
| `.m-page--gradient` | Gradient background (landing pages) |

## Standard Page Template

```blade
@extends('layouts.front')

@section('content')
    {{-- 1. Breadcrumb Section --}}
    <section class="gs-breadcrumb-section bg-class"
        data-background="{{ $gs->breadcrumb_banner ? asset('assets/images/' . $gs->breadcrumb_banner) : asset('assets/images/noimage.png') }}">
        <div class="container">
            <div class="row justify-content-center content-wrapper">
                <div class="col-12">
                    <h2 class="breadcrumb-name">@lang('Page Name')</h2>
                    <ul class="bread-menu">
                        <li><a href="{{ route('front.index') }}">@lang('Home')</a></li>
                        <li><a href="#">@lang('Current Page')</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Main Content with Gray Background --}}
    <div class="gs-page-wrapper muaadh-section-gray">
        <div class="container py-4">
            <div class="m-card">
                <div class="m-card__body">
                    Content goes here
                </div>
            </div>
        </div>
    </div>
@endsection
```

## New/Modified Page Checklist

1. Layout: @extends('layouts.front')
2. Breadcrumb section with bg-class
3. Main content wrapped with muaadh-section-gray
4. No inline style="" for colors
5. Use m-* classes for new components
6. Cards use white background (m-card or bg-white)
7. Clear cache after changes: `php artisan view:clear && php artisan cache:clear`

## Reference Documentation

- Full policy: `DESIGN_SYSTEM_POLICY.md`
- Theme guide: `THEME_SYSTEM_COMPLETE_GUIDE.md`
- Token reference: `DESIGN_TOKENS_REFERENCE.md`
