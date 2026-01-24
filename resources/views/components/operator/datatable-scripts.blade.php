{{--
    DataTable Scripts Component - لتوحيد سكربتات DataTable المكررة

    Usage:
    @include('components.operator.datatable-scripts', [
        'tableId' => 'muaadhtable',
        'route' => 'admin-brand-datatables',
        'columns' => [
            ['data' => 'photo', 'name' => 'photo'],
            ['data' => 'link', 'name' => 'link'],
            ['data' => 'action', 'searchable' => false, 'orderable' => false]
        ],
        'addRoute' => 'admin-brand-create',
        'addLabel' => __('Add New Brand'),
        'modalId' => 'modal1'
    ])
--}}

@php
    $tableId = $tableId ?? 'muaadhtable';
    $modalId = $modalId ?? 'modal1';
    $ps = platformSettings();
@endphp

<script type="text/javascript">
(function($) {
    "use strict";

    var table = $('#{{ $tableId }}').DataTable({
        ordering: false,
        processing: true,
        serverSide: true,
        ajax: '{{ route($route) }}',
        columns: [
            @foreach($columns as $column)
            {
                data: '{{ $column['data'] }}',
                name: '{{ $column['name'] ?? $column['data'] }}'
                @if(isset($column['searchable']) && !$column['searchable']), searchable: false @endif
                @if(isset($column['orderable']) && !$column['orderable']), orderable: false @endif
            },
            @endforeach
        ],
        language: {
            processing: '<img src="{{ asset('assets/images/' . ($ps->get('admin_loader', 'loader.gif'))) }}">'
        }
    });

    @if(isset($addRoute))
    $(function() {
        $(".btn-area").append('<div class="col-sm-4 table-contents">'+
            '<a class="add-btn" data-href="{{ route($addRoute) }}" id="add-data" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">'+
            '<i class="fas fa-plus"></i> {{ $addLabel ?? __('Add New') }}'+
            '</a>'+
            '</div>');
    });
    @endif

})(jQuery);
</script>
