<!-- Controls Row -->
<div class="flex flex-row justify-between items-center w-full p-4 bg-white controls-row">
    <div class="flex flex-row justify-start items-center w-full controls-left">
        <div class="flex flex-row justify-start items-center w-auto">
            <div class="flex flex-row items-center">
                <input type="checkbox" id="selectAll" class="w-4 h-4 bg-white border border-[#888888] rounded cursor-pointer">
                <label for="selectAll" class="ml-2 text-sm text-[#3d3d3d] cursor-pointer">Select all</label>
            </div>
        </div>
        
        <button class="btn btn-secondary ml-4" onclick="deleteSelected()">
            <i class="fas fa-trash"></i>
            <span>Delete</span>
        </button>
    </div>
    
    <!-- Pagination Controls -->
    <div class="pagination-controls">
        @if($items->currentPage() > 1)
            <a href="{{ $items->previousPageUrl() }}" class="btn btn-secondary btn-sm">Previous</a>
        @else
            <button class="btn btn-secondary btn-sm" disabled>Previous</button>
        @endif
        
        @if($items->lastPage() > 0)
            @php
                $start = max(1, $items->currentPage() - 2);
                $end = min($items->lastPage(), $items->currentPage() + 2);
            @endphp
            
            <div class="flex items-center gap-2">
                @if($start > 1)
                    <a href="{{ $items->url(1) }}" class="page-number">1</a>
                    @if($start > 2)
                        <span class="text-sm text-gray-500">...</span>
                    @endif
                @endif
                
                @for($i = $start; $i <= $end; $i++)
                    @if($i == $items->currentPage())
                        <span class="page-number active">{{ $i }}</span>
                    @else
                        <a href="{{ $items->url($i) }}" class="page-number">{{ $i }}</a>
                    @endif
                @endfor
                
                @if($end < $items->lastPage())
                    @if($end < $items->lastPage() - 1)
                        <span class="text-sm text-gray-500">...</span>
                    @endif
                    <a href="{{ $items->url($items->lastPage()) }}" class="page-number">{{ $items->lastPage() }}</a>
                @endif
            </div>
        @else
            <span class="page-number active">1</span>
        @endif
        
        @if($items->currentPage() < $items->lastPage())
            <a href="{{ $items->nextPageUrl() }}" class="btn btn-secondary btn-sm">Next</a>
        @else
            <button class="btn btn-secondary btn-sm" disabled>Next</button>
        @endif
        
        <div class="page-dropdown-container">
            <span class="text-sm text-gray-700">Page</span>
            <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm border border-gray-300 focus:outline-none focus:border-[#214589]">
                <option>{{ $items->currentPage() }}</option>
            </select>
            <span class="text-sm text-gray-700">of <span class="inline">{{ $items->lastPage() }}</span></span>
        </div>
    </div>
</div>
