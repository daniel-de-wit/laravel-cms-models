
@if (cms_auth()->can(["{$permissionPrefix}edit", "{$permissionPrefix}delete"]))
    <td>
        <div class="btn-group btn-group-xs record-actions pull-right tr-show-on-hover" role="group">

            @if (cms_auth()->can("{$permissionPrefix}edit"))
                <a class="btn btn-default edit-record-action" href="{{ route($route, [ $record->getKey() ]) }}" role="button"
                   title="{{ ucfirst(cms_trans('common.action.edit')) }}"
                ><i class="fa fa-edit"></i></a>
            @endif

            @if (cms_auth()->can("{$permissionPrefix}delete"))
                <a class="btn btn-danger delete-record-action" href="#" role="button"
                   data-id="{{ $record->getKey() }}"
                   data-toggle="modal" data-target="#delete-record-modal"
                   title="{{ ucfirst(cms_trans('common.action.delete')) }}"
                ><i class="fa fa-trash-o"></i></a>
            @endif
        </div>
    </td>
@endif