@extends('layouts.front')

@section('content')

<!-- Hero Search Section -->
<section class="modern-hero-section">
    <div class="hero-overlay"></div>
    <div class="container position-relative">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Hero Title -->
                <div class="hero-content text-center mb-5">
                    <h1 class="hero-title">@lang('Search By Part Number Or Name')</h1>
                </div>

                <!-- Search Box Component -->
                <div class="hero-search-wrapper mb-3">
                    @include('includes.frontend.search-part-ajax', ['uniqueId' => 'home'])
                </div>

                <p class="hero-subtitle text-center mt-3">
                    @lang("If you don't know the correct part number for your vehicle, search below using your VIN, the category tree, or the catalogues.")
                </p>

                <!-- VIN Search Button -->
                <div class="text-center mt-4">
                    <button type="button" class="btn-vin-search" data-bs-toggle="modal" data-bs-target="#vinSearchModalHome">
                        <i class="fas fa-car me-2"></i>
                        @lang('Search by VIN')
                    </button>
                    <p class="hero-vin-description mt-3">
                        @lang('Search for spare parts inside the vehicle by VIN number')
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Brands Section -->
<section class="brands-showcase-section">
    <div class="container">
        <!-- Section Header -->
        <div class="section-header text-center mb-5">
            <span class="section-badge">@lang('Genuine Parts Catalogues')</span>
            <h2 class="section-title">@lang('Explore genuine OEM parts catalogues for all vehicle models. Fast search. precise results, and certified quality guaranteed.')</h2>
            <p class="section-description mt-3">@lang('Select your vehicle brand to find the perfect parts')</p>
        </div>

        <!-- Brands Grid -->
        <div class="brands-grid">
            @foreach (DB::table('brands')->get() as $brand)
                <div class="brand-card-wrapper">
                    <a href="{{ route('catlogs.index', $brand->name) }}" class="brand-card">
                        <div class="brand-image-container">
                            <img src="{{ asset('assets/images/brand/' . $brand->photo) }}"
                                 alt="{{ $brand->name }}"
                                 class="brand-image"
                                 loading="lazy">
                        </div>
                        <div class="brand-overlay">
                            <span class="brand-name">{{ $brand->name }}</span>
                            <i class="fas fa-arrow-right brand-arrow"></i>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-modern-section">
    <div class="container">
        <!-- Section Header -->
        <div class="section-header text-center mb-5">
            <span class="section-badge">@lang('Browse Categories')</span>
            <h2 class="section-title">@lang('Shop by Category')</h2>
            <p class="section-description">@lang('Find exactly what you need from our extensive catalog')</p>
        </div>

        <!-- Categories Grid -->
        <div class="categories-grid">
            @foreach ($featured_categories as $fcategory)
                <div class="category-card-wrapper">
                    <a href="{{ route('front.category', $fcategory->slug) }}" class="category-card">
                        <div class="category-image-wrapper">
                            <img class="category-image"
                                 src="{{ asset('assets/images/categories/' . $fcategory->image) }}"
                                 alt="{{ $fcategory->name }}"
                                 loading="lazy">
                            <div class="category-badge">
                                <span class="product-count">{{ $fcategory->products_count }}</span>
                            </div>
                        </div>
                        <div class="category-content">
                            <h6 class="category-title">{{ $fcategory->name }}</h6>
                            <p class="category-subtitle">{{ $fcategory->products_count }} @lang('Products')</p>
                            <div class="category-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-modern-section">
    <div class="container">
        <div class="services-grid">
            @foreach (DB::table('services')->get() as $service)
                <div class="service-card">
                    <div class="service-icon-wrapper">
                        <img src="{{ asset('assets/images/services/' . $service->photo) }}"
                             alt="{{ $service->title }}"
                             class="service-icon">
                    </div>
                    <div class="service-content">
                        <h6 class="service-title">{{ $service->title }}</h6>
                        <p class="service-description">{{ $service->details }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<style>
/* ========================================
   MODERN HERO SECTION
   ======================================== */
.modern-hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 8rem 0 6rem;
    position: relative;
    overflow: hidden;
}

.modern-hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
    opacity: 0.3;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 30% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 1.8rem;
    font-weight: 800;
    color: #fff;
    margin-bottom: 1.5rem;
    text-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    line-height: 1.2;
}

.hero-subtitle {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.95);
    max-width: 700px;
    margin: 0 auto;
    line-height: 1.8;
}

.hero-search-wrapper {
    position: relative;
    z-index: 2;
}

.hero-vin-description {
    font-size: 0.9rem;
    color: rgba(255, 255, 255, 0.95);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.btn-vin-search {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: 2px solid transparent;
    color: #fff;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-vin-search:hover {
    background: #fff;
    color: #667eea;
    border-color: #667eea;
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

/* ========================================
   BRANDS SHOWCASE SECTION
   ======================================== */
.brands-showcase-section {
    padding: 6rem 0;
    background: #f8fafc;
    position: relative;
}

.section-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 0.5rem 1.5rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 1rem;
}

.section-title {
    font-size: 0.95rem;
    font-weight: 800;
    color: #1e293b;
    margin-bottom: 1rem;
}

.section-description {
    font-size: 0.95rem;
    color: #64748b;
    max-width: 600px;
    margin: 0 auto;
}

.brands-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.brand-card-wrapper {
    animation: fadeInUp 0.6s ease backwards;
}

.brand-card {
    display: block;
    background: #fff;
    border-radius: 20px;
    padding: 2rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    border: 2px solid transparent;
}

.brand-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.4s ease;
    z-index: 1;
}

.brand-card:hover::before {
    opacity: 0.05;
}

.brand-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
    border-color: #667eea;
}

.brand-image-container {
    position: relative;
    z-index: 2;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.brand-image {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.4s ease;
    filter: grayscale(0.3);
}

.brand-card:hover .brand-image {
    transform: scale(1.1);
    filter: grayscale(0);
}

.brand-overlay {
    position: relative;
    z-index: 2;
    text-align: center;
    padding-top: 1rem;
    border-top: 2px solid #f1f5f9;
}

.brand-name {
    font-weight: 600;
    color: #1e293b;
    font-size: 0.95rem;
}

.brand-arrow {
    opacity: 0;
    transform: translateX(-10px);
    transition: all 0.3s ease;
    color: #667eea;
    margin-left: 0.5rem;
}

.brand-card:hover .brand-arrow {
    opacity: 1;
    transform: translateX(0);
}

/* ========================================
   CATEGORIES MODERN SECTION
   ======================================== */
.categories-modern-section {
    padding: 6rem 0;
    background: #fff;
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 2rem;
    margin-top: 3rem;
}

.category-card-wrapper {
    animation: fadeInUp 0.6s ease backwards;
}

.category-card {
    display: block;
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    border: 2px solid #f1f5f9;
}

.category-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
    border-color: #667eea;
}

.category-image-wrapper {
    position: relative;
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.category-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.category-card:hover .category-image {
    transform: scale(1.15);
}

.category-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 0.5rem 1rem;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.product-count {
    font-weight: 700;
    color: #667eea;
    font-size: 0.875rem;
}

.category-content {
    padding: 1.5rem;
    position: relative;
}

.category-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.5rem;
}

.category-subtitle {
    font-size: 0.8rem;
    color: #64748b;
    margin-bottom: 0;
}

.category-arrow {
    position: absolute;
    bottom: 1.5rem;
    right: 1.5rem;
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
}

.category-card:hover .category-arrow {
    opacity: 1;
    transform: scale(1);
}

/* ========================================
   SERVICES MODERN SECTION
   ======================================== */
.services-modern-section {
    padding: 6rem 0;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 2rem;
}

.service-card {
    background: #fff;
    border-radius: 20px;
    padding: 2.5rem;
    display: flex;
    gap: 1.5rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    border: 2px solid transparent;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    border-color: #667eea;
}

.service-icon-wrapper {
    flex-shrink: 0;
    width: 70px;
    height: 70px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    transition: transform 0.3s ease;
}

.service-card:hover .service-icon-wrapper {
    transform: scale(1.1) rotate(5deg);
}

.service-icon {
    width: 40px;
    height: 40px;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.service-content {
    flex: 1;
}

.service-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 0.75rem;
}

.service-description {
    font-size: 0.85rem;
    color: #64748b;
    line-height: 1.7;
    margin: 0;
}

/* ========================================
   ANIMATIONS
   ======================================== */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* ========================================
   RESPONSIVE DESIGN
   ======================================== */
@media (max-width: 1199px) {
    .brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 1.5rem;
    }

    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}

@media (max-width: 991px) {
    .hero-title {
        font-size: 1.6rem;
    }

    .section-title {
        font-size: 0.9rem;
    }

    .brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }

    .services-grid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    }
}

@media (max-width: 767px) {
    .modern-hero-section {
        padding: 5rem 0 4rem;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .hero-subtitle {
        font-size: 0.9rem;
    }

    .section-title {
        font-size: 0.85rem;
    }

    .brands-grid {
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 1rem;
    }

    .brand-card {
        padding: 1.5rem 1rem;
    }

    .brand-image-container {
        height: 80px;
    }

    .categories-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1rem;
    }

    .category-image-wrapper {
        height: 150px;
    }

    .services-grid {
        grid-template-columns: 1fr;
    }

    .service-card {
        padding: 1.5rem;
    }
}

@media (max-width: 575px) {
    .hero-title {
        font-size: 1.3rem;
    }

    .btn-vin-search {
        padding: 0.65rem 1.5rem;
        font-size: 0.85rem;
    }

    .brands-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .categories-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<!-- VIN Search Modal - Home Page Only -->
<div class="modal fade" id="vinSearchModalHome" tabindex="-1" aria-labelledby="vinSearchModalHomeLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content modern-modal-home">
            <div class="modal-header modern-modal-header-home">
                <h5 class="modal-title" id="vinSearchModalHomeLabel">
                    <i class="fas fa-car me-2"></i>
                    <span class="d-none d-sm-inline">@lang('Search by VIN')</span>
                    <span class="d-sm-none">VIN</span>
                </h5>
                <button type="button" class="btn-close modern-close-home" data-bs-dismiss="modal" aria-label="@lang('Close')"></button>
            </div>
            <div class="modal-body modern-modal-body-home p-4">
                @include('includes.frontend.search-vin-ajax', ['uniqueId' => 'homeModal'])
            </div>
        </div>
    </div>
</div>


<style>
/* Modern Modal Styles - Home Page Specific */
.modern-modal-home {
    border-radius: 20px;
    border: none;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modern-modal-header-home {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 1.5rem 2rem;
    border-bottom: none;
}

.modern-modal-header-home .modal-title {
    font-weight: 700;
    font-size: 1.25rem;
}

.modern-modal-header-home .btn-close {
    filter: brightness(0) invert(1);
    opacity: 0.8;
    transition: all 0.3s ease;
}

.modern-modal-header-home .btn-close:hover {
    opacity: 1;
    transform: rotate(90deg);
}

.modern-modal-body-home {
    background: #f8fafc;
    min-height: 300px;
}

/* Ensure modal is on top */
#vinSearchModalHome {
    z-index: 1055 !important;
}

#vinSearchModalHome .modal-backdrop {
    z-index: 1050 !important;
}

/* Responsive Modal */
@media (max-width: 768px) {
    #vinSearchModalHome .modal-dialog {
        margin: 1rem;
    }

    .modern-modal-header-home {
        padding: 1.25rem 1.5rem;
    }

    .modern-modal-header-home .modal-title {
        font-size: 1.1rem;
    }

    .modern-modal-body-home {
        padding: 1.5rem !important;
    }
}

@media (max-width: 576px) {
    #vinSearchModalHome .modal-dialog {
        margin: 0.5rem;
    }

    .modern-modal-header-home {
        padding: 1rem 1.25rem;
    }

    .modern-modal-header-home .modal-title {
        font-size: 1rem;
    }

    .modern-modal-body-home {
        padding: 1.25rem !important;
        min-height: 250px;
    }
}
</style>

@endsection
