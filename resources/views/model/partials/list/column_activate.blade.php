<?php
    // todo: make strategy dependent...
    $active = $record->{$model->list->active_column};
?>

<td class="column column-activate">
    <div id="model-activate-{{ $record->getKey() }}"
         class="activate-toggle {{ $active ? 'tr-show-on-hover' : null }}"
         data-id="{{ $record->getKey() }}"
         data-active="{{ $active ? 1 : 0 }}">

        <div class="icon-wrapper">
            <i class="glyphicon glyphicon-ban-circle text-danger inactive {{ $active ? 'hidden' : null }}"
               title="{{ ucfirst(cms_trans('models.activatable.deactivated')) }}"
               data-toggle="tooltip" data-placement="right"></i>
            <i class="glyphicon glyphicon-ok-sign text-success active {{ ! $active ? 'hidden' : null }}"
               title="{{ cms_trans('models.activatable.deactivate') }}"></i>
            <i class="glyphicon glyphicon-refresh text-muted loading gly-spin hidden"
               title="{{ cms_trans('models.activatable.activate') }}"></i>
        </div>
    </div>
</td>
