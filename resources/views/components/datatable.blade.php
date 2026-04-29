@props([
    'id' => 'datatable',
    'endpoint' => '',
    'columns' => [],
    'hasCheckbox' => true,
    'hasActions' => false,
    'rowClickRoute' => null,
    'additionalClass' => ''
])

<div class="table-container">
    <table id="{{ $id }}" class="responsive-table {{ $additionalClass }}" style="width:100%">
        <thead>
            <tr>
                @if($hasCheckbox)
                <th style="width: 50px;">
                    <input type="checkbox" id="selectAllDataTable" class="w-4 h-4 bg-white border border-gray-300 rounded cursor-pointer">
                </th>
                @endif
                
                @foreach($columns as $column)
                <th>{{ $column['label'] }}</th>
                @endforeach
                
                @if($hasActions)
                <th style="width: 100px;">Actions</th>
                @endif
            </tr>
            <!-- Filter Row -->
            <tr class="filter-row">
                @if($hasCheckbox)
                <th></th>
                @endif
                
                @foreach($columns as $index => $column)
                <th>
                    @if($column['searchable'] ?? true)
                    <input type="text" class="column-search form-input" 
                           placeholder="Filter {{ $column['label'] }}" 
                           data-column="{{ $hasCheckbox ? $index + 1 : $index }}"
                           style="width: 100%; padding: 6px 8px; font-size: 12px; border: 1px solid #d1d5db; border-radius: 4px;">
                    @endif
                </th>
                @endforeach
                
                @if($hasActions)
                <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
            <!-- Data will be populated by DataTables -->
        </tbody>
    </table>
</div>

<style>
/* DataTables Custom Styling */
#{{ $id }}_wrapper {
    width: 100%;
}

#{{ $id }} thead tr.filter-row th {
    background-color: #f3f4f6;
    padding: 8px;
    border-bottom: 1px solid #e5e7eb;
}

#{{ $id }} thead tr.filter-row input.column-search {
    background-color: white;
}

#{{ $id }} thead tr.filter-row input.column-search:focus {
    outline: none;
    border-color: #214589;
    box-shadow: 0 0 0 2px rgba(33, 69, 137, 0.1);
}

/* Pagination styling */
.dataTables_wrapper .dataTables_paginate {
    text-align: center;
    padding: 1rem 0;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px;
    margin: 0 2px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background-color: #f9fafb;
    color: #374151;
    cursor: pointer;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #214589;
    color: white;
    border-color: #214589;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.dataTables_wrapper .dataTables_info {
    padding: 1rem;
    text-align: center;
    color: #6b7280;
}

.dataTables_wrapper .dataTables_length {
    padding: 1rem;
}

.dataTables_wrapper .dataTables_length select {
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    margin: 0 8px;
}

/* Loading overlay */
.dataTables_wrapper .dataTables_processing {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 200px;
    margin-left: -100px;
    margin-top: -26px;
    text-align: center;
    padding: 1rem;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
</style>

@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#{{ $id }}').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ $endpoint }}',
            type: 'GET',
            error: function(xhr, error, thrown) {
                console.error('DataTables Error:', error, thrown);
                alert('Gagal memuat data. Silakan periksa console untuk detailnya.');
            }
        },
        columns: [
            @if($hasCheckbox)
            { 
                data: 'id', 
                name: 'id',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return '<input type="checkbox" class="row-checkbox w-4 h-4" value="' + data + '">';
                }
            },
            @endif
            
            @foreach($columns as $column)
            {
                data: '{{ $column['data'] }}',
                name: '{{ $column['data'] }}',
                @if(isset($column['render']))
                render: {!! $column['render'] !!},
                @endif
                @if(isset($column['orderable']))
                orderable: {{ $column['orderable'] ? 'true' : 'false' }},
                @endif
                @if(isset($column['searchable']))
                searchable: {{ $column['searchable'] ? 'true' : 'false' }},
                @endif
            },
            @endforeach
            
            @if($hasActions)
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            }
            @endif
        ],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<i class="fas fa-spinner fa-spin"></i> Loading data...',
            lengthMenu: 'Show _MENU_ entries',
            info: 'Showing _START_ to _END_ of _TOTAL_ entries',
            infoEmpty: 'Showing 0 to 0 of 0 entries',
            infoFiltered: '(filtered from _MAX_ total entries)',
            search: 'Search:',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            },
            emptyTable: 'No data available in table'
        },
        responsive: true,
        dom: '<"top"lf>rt<"bottom"ip><"clear">',
        @if($rowClickRoute)
        createdRow: function(row, data, dataIndex) {
            $(row).css('cursor', 'pointer');
            $(row).on('click', function(e) {
                if (!$(e.target).is('input[type="checkbox"]') && !$(e.target).is('a') && !$(e.target).is('button')) {
                    let routeTemplate = '{{ $rowClickRoute }}';
                    let route = routeTemplate.replace(':id', data.id);
                    window.location.href = route;
                }
            });
        }
        @endif
    });
    
    // Individual column searching
    $('.column-search').on('keyup change', function() {
        let columnIndex = $(this).data('column');
        let searchValue = this.value;
        
        table
            .column(columnIndex)
            .search(searchValue)
            .draw();
    });
    
    // Select all checkbox
    $('#selectAllDataTable').on('change', function() {
        $('.row-checkbox').prop('checked', this.checked);
    });
    
    // Individual checkbox
    $('#{{ $id }}').on('change', '.row-checkbox', function() {
        let allChecked = $('.row-checkbox').length === $('.row-checkbox:checked').length;
        $('#selectAllDataTable').prop('checked', allChecked);
    });
});
</script>
@endpush

