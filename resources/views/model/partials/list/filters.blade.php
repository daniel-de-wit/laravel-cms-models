
@php
    $filterData = $model->list->filters;
@endphp

@if ( ! $model->list->disable_filters && $filterData && count($filterData))

<div class="filter-container">

    <form id="filters-form" class="form-inline" role="form" method="post" action="{{ route("{$routePrefix}.filter") }}">
        {{ csrf_field() }}

        <input id="input-filters-clear" type="hidden" name="_clear" value="">

        @foreach ($filterData as $key => $filter)
            {!! $filterStrategies[ $key ] !!}
        @endforeach

        <div class="btn-group">
            <button type="submit" class="btn btn-default btn-sm" data-style="slide-left">
                <i class="fa fa-search"></i>
                &nbsp;
                {{ cms_trans('models.filter.button-label') }}
            </button>

            <button id="input-filters-clear-button" type="button" class="btn btn-default btn-sm filter-clear" data-style="slide-left">
                <i class="fa fa-close"></i>
            </button>
        </div>

    </form>
</div>

@endif

@cms_script
    <script>
        $('#input-filters-clear-button').click(function () {
            var form = $('#filters-form');
            $('#input-filters-clear').val(1);
            form.submit();
        });
    </script>
@cms_endscript
