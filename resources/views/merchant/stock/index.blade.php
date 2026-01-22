@extends('layouts.merchant')

@section('content')
    <div class="gs-merchant-outlet">
        <!-- breadcrumb start  -->
        <div class="gs-merchant-breadcrumb has-mb">
            <div class="d-flex gap-4 flex-wrap align-items-center custom-gap-sm-2">
                <h4 class="text-capitalize">إدارة المخزون</h4>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('merchant-stock-export') }}" class="template-btn md-btn primary-btn">
                        <i class="fas fa-download"></i> تصدير المخزون الحالي
                    </a>
                    <button type="button" class="template-btn md-btn btn-warning" data-bs-toggle="modal" data-bs-target="#fullRefreshModal">
                        <i class="fas fa-sync-alt"></i> تحديث المخزون من الملفات
                    </button>
                </div>
            </div>
            <ul class="breadcrumb-menu">
                <li>
                    <a href="{{ route('merchant.dashboard') }}" class="text-capitalize">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" class="home-icon-merchant-panel-breadcrumb">
                            <path
                                d="M9 21V13.6C9 13.0399 9 12.7599 9.109 12.546C9.20487 12.3578 9.35785 12.2049 9.54601 12.109C9.75993 12 10.04 12 10.6 12H13.4C13.9601 12 14.2401 12 14.454 12.109C14.6422 12.2049 14.7951 12.3578 14.891 12.546C15 12.7599 15 13.0399 15 13.6V21M2 9.5L11.04 2.72C11.3843 2.46181 11.5564 2.33271 11.7454 2.28294C11.9123 2.23902 12.0877 2.23902 12.2546 2.28295C12.4436 2.33271 12.6157 2.46181 12.96 2.72L22 9.5M4 8V17.8C4 18.9201 4 19.4802 4.21799 19.908C4.40974 20.2843 4.7157 20.5903 5.09202 20.782C5.51985 21 6.0799 21 7.2 21H16.8C17.9201 21 18.4802 21 18.908 20.782C19.2843 20.5903 19.5903 20.2843 19.782 19.908C20 19.4802 20 18.9201 20 17.8V8L13.92 3.44C13.2315 2.92361 12.8872 2.66542 12.5091 2.56589C12.1754 2.47804 11.8246 2.47804 11.4909 2.56589C11.1128 2.66542 10.7685 2.92361 10.08 3.44L4 8Z"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </a>
                </li>
                <li><a href="{{ route('merchant.dashboard') }}">لوحة التحكم</a></li>
                <li><a href="{{ route('merchant-stock-management') }}">إدارة المخزون</a></li>
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

        <!-- Branch Information Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-name">الفروع والملفات</h5>
                <p class="text-muted mb-3">كل فرع يتم تحديثه من ملف منفصل على S3:</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>الفرع</th>
                                <th>كود المستودع</th>
                                <th>اسم الملف</th>
                                <th>الموقع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($branches as $branch)
                            <tr>
                                <td>{{ $branch->branch_name }}</td>
                                <td><code>{{ $branch->warehouse_name }}</code></td>
                                <td><code>twa{{ str_pad(substr($branch->warehouse_name, -2), 2, '0', STR_PAD_LEFT) }}.csv</code></td>
                                <td>{{ $branch->getCityName() ?? 'غير محدد' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-info-circle"></i>
                    <strong>آلية التحديث:</strong>
                    <ul class="mb-0 mt-2">
                        <li>يتم تحميل ملفات CSV (twa01-twa05) من S3</li>
                        <li>يتم مطابقة رقم القطعة (item_code) مع جدول التربيط (catalog_item_code_mappings)</li>
                        <li>يتم تحديث السعر والكمية في جدول merchant_items لكل فرع</li>
                        <li>القطع غير الموجودة في الملفات يتم تصفير كميتها وتعطيلها</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Table area start  -->
        <div class="merchant-table-wrapper">
            <div class="user-table table-responsive position-relative">
                <table class="gs-data-table w-100" id="stock-updates-table">
                    <thead>
                        <tr>
                            <th>رقم التحديث</th>
                            <th>نوع التحديث</th>
                            <th>الحالة</th>
                            <th>التقدم</th>
                            <th>تاريخ البدء</th>
                            <th>تاريخ الاكتمال</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Table area end -->
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1" aria-labelledby="errorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-name" id="errorModalLabel">تفاصيل الأخطاء</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <pre id="errorContent" style="max-height: 400px; overflow-y: auto;"></pre>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Refresh Modal -->
    <div class="modal fade" id="fullRefreshModal" tabindex="-1" aria-labelledby="fullRefreshModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="fullRefreshForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-name" id="fullRefreshModalLabel">تحديث المخزون من ملفات الفروع</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="closeModalBtn"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Initial Form -->
                        <div id="formSection">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>ملاحظة:</strong> هذه العملية ستقوم بـ:
                                <ul class="mb-0 mt-2">
                                    <li>تحميل ملفات CSV المخزون (twa01-twa05) من S3</li>
                                    <li>تحديث المخزون والأسعار لكل فرع بناءً على الملف الخاص به</li>
                                    <li>تصفير وتعطيل القطع غير الموجودة في الملفات</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>تحذير:</strong> هذه العملية قد تستغرق عدة دقائق. لا تغلق الصفحة حتى تكتمل العملية.
                            </div>
                        </div>

                        <!-- Progress Section (Hidden Initially) -->
                        <div id="progressSection" style="display: none;">
                            <div class="text-center mb-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">جاري التحميل...</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <h6 class="text-center" id="progressStatus">جاري بدء العملية...</h6>
                                <div class="progress" style="height: 25px;">
                                    <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                         role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                        <span id="progressText">0%</span>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-info mb-0">
                                <i class="fas fa-clock"></i>
                                <strong>يرجى الانتظار...</strong> العملية قيد التنفيذ. لا تغلق هذه النافذة.
                            </div>

                            <!-- Progress Details -->
                            <div id="progressDetails" class="mt-3" style="display: none;">
                                <div class="card">
                                    <div class="card-body">
                                        <p class="mb-1"><strong>عدد الصفوف المعالجة:</strong> <span id="updatedRows">0</span></p>
                                        <p class="mb-1"><strong>إجمالي الصفوف:</strong> <span id="totalRows">0</span></p>
                                        <p class="mb-0"><strong>الصفوف الفاشلة:</strong> <span id="failedRows" class="text-danger">0</span></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Success/Error Section -->
                        <div id="resultSection" style="display: none;">
                            <div id="successAlert" class="alert alert-success" style="display: none;">
                                <i class="fas fa-check-circle"></i>
                                <strong>نجحت العملية!</strong>
                                <p class="mb-0 mt-2" id="successMessage"></p>
                            </div>

                            <div id="errorAlert" class="alert alert-danger" style="display: none;">
                                <i class="fas fa-times-circle"></i>
                                <strong>فشلت العملية!</strong>
                                <p class="mb-0 mt-2" id="errorMessage"></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="cancelBtn">إلغاء</button>
                        <button type="submit" class="btn btn-warning" id="submitBtn">
                            <i class="fas fa-sync-alt"></i> بدء التحديث
                        </button>
                        <button type="button" class="btn btn-primary" id="doneBtn" style="display: none;" data-bs-dismiss="modal">
                            <i class="fas fa-check"></i> تم
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script type="text/javascript">
    $(document).ready(function() {
        var table = $('#stock-updates-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('merchant-stock-datatables') }}',
            columns: [
                {data: 'id', name: 'id'},
                {data: 'update_type', name: 'update_type'},
                {data: 'status', name: 'status'},
                {data: 'progress', name: 'progress', orderable: false, searchable: false},
                {data: 'created_at', name: 'created_at'},
                {data: 'completed_at', name: 'completed_at', orderable: false},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[0, 'desc']],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Arabic.json'
            }
        });

        // View errors modal
        $(document).on('click', '.view-errors', function() {
            var errors = $(this).data('errors');
            $('#errorContent').text(errors);
            $('#errorModal').modal('show');
        });

        // Auto-refresh table every 30 seconds for processing updates
        setInterval(function() {
            table.ajax.reload(null, false);
        }, 30000);

        // ============ Full Refresh Modal Handling ============
        let currentUpdateId = null;
        let progressInterval = null;
        let processingStarted = false;

        // Reset modal when opened
        $('#fullRefreshModal').on('show.bs.modal', function() {
            resetModal();
        });

        // Reset modal when closed
        $('#fullRefreshModal').on('hidden.bs.modal', function() {
            if (progressInterval) {
                clearInterval(progressInterval);
                progressInterval = null;
            }
            if (processingStarted) {
                table.ajax.reload(null, false);
                processingStarted = false;
            }
        });

        function resetModal() {
            $('#formSection').show();
            $('#progressSection').hide();
            $('#resultSection').hide();
            $('#submitBtn').show();
            $('#cancelBtn').show();
            $('#doneBtn').hide();
            $('#closeModalBtn').show();
            $('#successAlert').hide();
            $('#errorAlert').hide();
            $('#progressDetails').hide();

            // Reset progress bar
            updateProgressBar(0, 'جاري التحضير...');

            currentUpdateId = null;
            processingStarted = false;
        }

        // Handle form submission
        $('#fullRefreshForm').on('submit', function(e) {
            e.preventDefault();
            startFullRefresh();
        });

        function startFullRefresh() {
            // Hide form, show progress
            $('#formSection').hide();
            $('#progressSection').show();
            $('#submitBtn').hide();
            $('#cancelBtn').hide();
            $('#closeModalBtn').hide();

            updateProgressBar(5, 'جاري بدء العملية...');

            // Step 1: Initialize the update
            $.ajax({
                url: '{{ route('merchant-stock-full-refresh') }}',
                method: 'POST',
                data: {
                    _token: $('input[name="_token"]').val()
                },
                timeout: 30000, // 30 seconds timeout for initialization
                success: function(response) {
                    if (response.success && response.update_id) {
                        currentUpdateId = response.update_id;
                        processingStarted = true;
                        updateProgressBar(10, response.message);

                        // Step 2: Start processing
                        startProcessing();
                    } else {
                        showError('فشل في بدء العملية: ' + (response.message || 'خطأ غير معروف'));
                    }
                },
                error: function(xhr, status, error) {
                    handleAjaxError(xhr, status, error, 'فشل في الاتصال بالخادم');
                }
            });
        }

        function startProcessing() {
            updateProgressBar(15, 'جاري تحميل الملفات ومعالجة البيانات...');

            $.ajax({
                url: '{{ route('merchant-stock-process-full-refresh') }}',
                method: 'POST',
                data: {
                    _token: $('input[name="_token"]').val(),
                    update_id: currentUpdateId
                },
                timeout: 600000, // 10 minutes timeout
                success: function(response) {
                    if (progressInterval) {
                        clearInterval(progressInterval);
                    }

                    if (response.success) {
                        updateProgressBar(100, 'اكتملت العملية بنجاح!');
                        setTimeout(() => showSuccess('تم تحديث المخزون بنجاح'), 500);
                    } else {
                        showError(response.message || 'فشلت العملية');
                    }
                },
                error: function(xhr, status, error) {
                    if (progressInterval) {
                        clearInterval(progressInterval);
                    }
                    handleAjaxError(xhr, status, error, 'حدث خطأ أثناء المعالجة');
                }
            });

            // Start monitoring progress
            monitorProgress();
        }

        function monitorProgress() {
            if (!currentUpdateId) return;

            // Initial check after 2 seconds
            setTimeout(checkProgress, 2000);

            // Then check every 3 seconds
            progressInterval = setInterval(checkProgress, 3000);
        }

        function checkProgress() {
            if (!currentUpdateId) return;

            $.ajax({
                url: '{{ url('merchant/stock/progress') }}/' + currentUpdateId,
                method: 'GET',
                timeout: 10000,
                success: function(response) {
                    if (response.success) {
                        const progress = response.progress || 0;
                        const status = response.status;
                        const message = response.message || '';

                        // Update progress bar (15% to 95% range during processing)
                        const adjustedProgress = 15 + (progress * 0.8);
                        updateProgressBar(Math.min(adjustedProgress, 95), message);

                        // Show details if available
                        if (response.total_rows > 0) {
                            $('#updatedRows').text(response.updated_rows || 0);
                            $('#totalRows').text(response.total_rows || 0);
                            $('#failedRows').text(response.failed_rows || 0);
                            $('#progressDetails').show();
                        }

                        // Check if completed or failed
                        if (status === 'completed') {
                            if (progressInterval) {
                                clearInterval(progressInterval);
                                progressInterval = null;
                            }
                        } else if (status === 'failed') {
                            if (progressInterval) {
                                clearInterval(progressInterval);
                                progressInterval = null;
                            }
                            showError('فشلت العملية. يرجى المحاولة مرة أخرى.');
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Don't stop on progress check errors, continue monitoring
                    console.warn('Progress check failed:', error);
                }
            });
        }

        function updateProgressBar(percentage, message) {
            percentage = Math.min(Math.max(percentage, 0), 100);

            $('#progressBar')
                .css('width', percentage + '%')
                .attr('aria-valuenow', percentage);
            $('#progressText').text(Math.round(percentage) + '%');
            $('#progressStatus').text(message);

            // Change color based on percentage
            $('#progressBar').removeClass('bg-primary bg-success bg-danger');
            if (percentage === 100) {
                $('#progressBar').addClass('bg-success');
            } else {
                $('#progressBar').addClass('bg-primary');
            }
        }

        function showSuccess(message) {
            $('#progressSection').hide();
            $('#resultSection').show();
            $('#successAlert').show();
            $('#successMessage').text(message);
            $('#doneBtn').show();
            $('#closeModalBtn').show();

            // Reload table
            table.ajax.reload(null, false);

            // Show notification
            showNotification(message, 'success');
        }

        function showError(message) {
            $('#progressSection').hide();
            $('#resultSection').show();
            $('#errorAlert').show();
            $('#errorMessage').text(message);
            $('#cancelBtn').show().text('إغلاق');
            $('#closeModalBtn').show();

            // Show notification
            showNotification(message, 'error');
        }

        function handleAjaxError(xhr, status, error, defaultMessage) {
            let errorMessage = defaultMessage;

            if (status === 'timeout') {
                errorMessage = 'انتهت مهلة الاتصال. يرجى المحاولة مرة أخرى.';
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            } else if (error) {
                errorMessage = defaultMessage + ': ' + error;
            }

            console.error('Ajax Error:', {xhr, status, error});
            showError(errorMessage);
        }

        function showNotification(message, type) {
            // Create notification element
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-times-circle';

            const notification = $(`
                <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
                     role="alert"
                     style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <i class="fas ${icon}"></i> ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);

            $('body').append(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.alert('close');
            }, 5000);
        }
    });
</script>
@endsection
