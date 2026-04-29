<div class="flex items-center gap-2 mr-2">
    <!-- Icon Filter -->
    <div class="hidden md:block text-gray-400">
        <i class="fas fa-filter"></i>
    </div>
    
    <!-- Dropdown -->
    <select 
        id="statusFilter" 
        onchange="applyStatusFilter(this.value)"
        class="bg-white border border-gray-300 text-gray-700 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2 h-[42px] min-w-[140px]"
    >
        <option value="1" {{ request('filter.is_active') == '1' || !request()->has('filter.is_active') ? 'selected' : '' }}>
            Status: Active
        </option>
        <option value="0" {{ request('filter.is_active') === '0' ? 'selected' : '' }}>
            Status: Inactive
        </option>
        <option value="all" {{ request('filter.is_active') === 'all' ? 'selected' : '' }}>
            Status: All
        </option>
    </select>
</div>

<script>
    function applyStatusFilter(value) {
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);
        
        // Handle filter[is_active] parameter
        if (value === 'all') {
            params.set('filter[is_active]', 'all'); // Set explicit 'all' value
        } else {
            params.set('filter[is_active]', value);
        }
        
        // Reset pagination to page 1 when filter changes
        params.delete('page');
        
        window.location.href = `${url.pathname}?${params.toString()}`;
    }
</script>
