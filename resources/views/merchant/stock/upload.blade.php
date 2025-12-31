@extends('layouts.merchant')

@section('content')
    <div class="gs-vendor-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-vendor-breadcrumb has-mb">
            <div class="d-flex gap-4 flex-wrap align-items-center custom-gap-sm-2">
                <h4 class="text-capitalize">رفع ملف المخزون</h4>
                <a href="{{ route('merchant-stock-management') }}" class="template-btn md-btn black-btn">
                    <i class="fas fa-arrow-left"></i> رجوع إلى إدارة المخزون
                </a>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" class="home-icon-vendor-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li><a href="{{ route('merchant.dashboard') }}">لوحة التحكم</a></li>
                <li><a href="{{ route('merchant-stock-management') }}">إدارة المخزون</a></li>
                <li><a href="#">رفع ملف</a></li>
            </ul>
        </div>
        <!-- breadcrumb end -->

        <!-- Alert Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Upload Form -->
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">رفع ملف تحديث المخزون</h5>
                    </div>
                    <div class="card-body">
                        <!-- Instructions -->
                        <div class="alert alert-info">
                            <h6 class="alert-heading">تعليمات مهمة:</h6>
                            <ul class="mb-0">
                                <li>صيغة الملف المقبولة: CSV, TXT, XLSX, XLS</li>
                                <li>الحد الأقصى لحجم الملف: 10 ميجابايت</li>
                                <li>يجب أن يحتوي الملف على الأعمدة التالية بالترتيب:
                                    <ul>
                                        <li><strong>SKU</strong> (مطلوب): رقم المنتج</li>
                                        <li><strong>Product Name</strong> (اختياري): اسم المنتج</li>
                                        <li><strong>Stock</strong> (مطلوب): الكمية المتوفرة</li>
                                        <li><strong>Price</strong> (اختياري): السعر</li>
                                        <li><strong>Previous Price</strong> (اختياري): السعر السابق</li>
                                    </ul>
                                </li>
                                <li>السطر الأول يجب أن يحتوي على عناوين الأعمدة</li>
                                <li>يمكنك <a href="{{ route('merchant-stock-template') }}" class="alert-link">تحميل ملف نموذجي</a> للمساعدة</li>
                            </ul>
                        </div>

                        <!-- Upload Form -->
                        <form action="{{ route('merchant-stock-upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                            @csrf
                            <div class="mb-3">
                                <label for="stock_file" class="form-label">اختر ملف المخزون</label>
                                <input type="file" class="form-control" id="stock_file" name="stock_file" accept=".csv,.txt,.xlsx,.xls" required>
                                <div class="form-text">الصيغ المقبولة: CSV, TXT, XLSX, XLS (الحد الأقصى: 10 ميجابايت)</div>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirmUpload" required>
                                    <label class="form-check-label" for="confirmUpload">
                                        أؤكد أن الملف بالصيغة الصحيحة وأن البيانات دقيقة
                                    </label>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="{{ route('merchant-stock-management') }}" class="btn btn-secondary">إلغاء</a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-upload"></i> رفع الملف وتحديث المخزون
                                </button>
                            </div>
                        </form>

                        <!-- Preview Section (optional enhancement) -->
                        <div id="filePreview" class="mt-4" style="display: none;">
                            <h6>معاينة الملف:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead id="previewHeader"></thead>
                                    <tbody id="previewBody"></tbody>
                                </table>
                            </div>
                            <p class="text-muted"><small>عرض أول 5 صفوف فقط</small></p>
                        </div>
                    </div>
                </div>

                <!-- Sample Data Preview -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h6 class="mb-0">مثال على تنسيق الملف</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>SKU</th>
                                        <th>Product Name</th>
                                        <th>Stock</th>
                                        <th>Price</th>
                                        <th>Previous Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>SAMPLE-SKU-001</td>
                                        <td>Sample Product Name</td>
                                        <td>100</td>
                                        <td>50.00</td>
                                        <td>60.00</td>
                                    </tr>
                                    <tr>
                                        <td>SAMPLE-SKU-002</td>
                                        <td>Another Sample Product</td>
                                        <td>50</td>
                                        <td>75.50</td>
                                        <td>80.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('#uploadForm').on('submit', function() {
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> جاري الرفع...');
    });

    // Optional: File validation
    $('#stock_file').on('change', function() {
        var file = this.files[0];
        if (file) {
            var fileSize = file.size / 1024 / 1024; // in MB
            if (fileSize > 10) {
                alert('حجم الملف كبير جداً! الحد الأقصى: 10 ميجابايت');
                $(this).val('');
                return;
            }

            var fileName = file.name;
            var extension = fileName.split('.').pop().toLowerCase();
            var validExtensions = ['csv', 'txt', 'xlsx', 'xls'];

            if ($.inArray(extension, validExtensions) === -1) {
                alert('صيغة الملف غير مقبولة! الصيغ المقبولة: CSV, TXT, XLSX, XLS');
                $(this).val('');
                return;
            }
        }
    });
});
</script>
@endsection
