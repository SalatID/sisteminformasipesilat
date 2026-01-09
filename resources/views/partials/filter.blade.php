<div class=" col-12 d-flex justify-content-between my-3">
    <div class="row col-6">
        @php
            $explode = explode(':', request('sort_by'));
            $orderBy = $explode[0] ?? '';
            $sortType = $explode[1] ?? 'asc';
        @endphp
        <select name="sort_by" class="form-control form-control-sm col-sm-4 mr-1" onchange="sortBy(this)">
            <option value="">-Sort By-</option>
            @foreach ($ordering as $key => $item)
                <option value="{{ $key }}"{{ $orderBy == $key ? 'selected' : '' }}>
                    {{ ucwords($item) }}
                </option>
            @endforeach
        </select>
        <select name="per_page" class="form-control form-control-sm col-sm-3 mr-1" onchange="perPage(this)">
            <option value="">-Per Page-</option>
            <option value="20" {{ request('per_page') == '20' ? 'selected' : '' }}>20</option>
            <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50</option>
            <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>100</option>
        </select>
        <button type="button" class="btn btn-success col-sm-1 btn-sm mr-1" data-target="#filterModal"
            data-toggle="modal"><i class="fas fa-filter"></i> Filter</button>
        @if (count($filter??[]) > 0)
            <a href="{{ route($resetRoute) }}"
                class="btn btn-secondary col-sm-1 btn-sm">Reset</a>
        @endif
    </div>
</div>
<div class="ml-1 mb-1 row col-6">
    <div class="form-check col-sm-2">
        <input class="form-check-input" id="asc" type="radio" name="sort_type" value="asc"
            {{ ($sortType ?? 'asc') == 'asc' ? 'checked' : '' }} onchange="sortBy(this)">
        <label class="form-check-label" for="asc">Ascending</label>
    </div>
    <div class="form-check col-sm-2">
        <input class="form-check-input" id="desc" type="radio" name="sort_type" value="desc"
            {{ ($sortType ?? '') == 'desc' ? 'checked' : '' }} onchange="sortBy(this)">
        <label class="form-check-label" for="desc">Descending</label>
    </div>
</div>
