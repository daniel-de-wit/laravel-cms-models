<?php
namespace Czim\CmsModels\ModelInformation\Data\Listing;

use Czim\CmsCore\Contracts\Core\CoreInterface;
use Czim\CmsCore\Support\Enums\Component;
use Czim\CmsModels\Contracts\ModelInformation\Data\ModelActionReferenceDataInterface;
use Czim\CmsModels\Contracts\ModelInformation\Data\Listing\ModelListDataInterface;
use Czim\CmsModels\ModelInformation\Data\AbstractModelInformationDataObject;
use Czim\CmsModels\ModelInformation\Data\ModelIncludesData;
use Czim\CmsModels\ModelInformation\Data\ModelActionReferenceData;
use Czim\CmsModels\ModelInformation\Data\ModelViewReferenceData;

/**
 * Class ModelListData
 *
 * Data container that represents list representation for the model.
 *
 * @property int|array                                                          $page_size
 * @property array|ModelListColumnData[]                                        $columns
 * @property bool                                                               $disable_filters
 * @property array|ModelListFilterData[]                                        $filters
 * @property array|ModelIncludesData                                            $includes
 * @property bool                                                               $disable_scopes
 * @property array|ModelScopeData[]                                             $scopes
 * @property string|array                                                       $default_sort
 * @property bool                                                               $orderable
 * @property string                                                             $order_strategy
 * @property string                                                             $order_column
 * @property string                                                             $order_scope_relation
 * @property bool                                                               $activatable
 * @property string                                                             $active_column
 * @property array|ModelActionReferenceData[]                                   $default_action
 * @property array|\Czim\CmsModels\ModelInformation\Data\ModelViewReferenceData $before
 * @property array|\Czim\CmsModels\ModelInformation\Data\ModelViewReferenceData $after
 * @property bool                                                               $default_top_relation
 * @property array|ModelListParentData[]                                        $parents
 */
class ModelListData extends AbstractModelInformationDataObject implements ModelListDataInterface
{

    protected $objects = [
        'columns'        => ModelListColumnData::class . '[]',
        'filters'        => ModelListFilterData::class . '[]',
        'includes'       => ModelIncludesData::class,
        'scopes'         => ModelScopeData::class . '[]',
        'default_action' => ModelActionReferenceData::class . '[]',
        'before'         => ModelViewReferenceData::class,
        'after'          => ModelViewReferenceData::class,
        'parents'        => ModelListParentData::class . '[]',
    ];

    protected $attributes = [

        // Arrays (instances of ModelListColumnData) with information about a single column.
        // These should appear in the order in which they should be displayed, and exclude standard/global list columns.
        // The entries should be keyed with an identifiying string that may be referred to by other list options.
        'columns' => [],

        // Whether to disable filters even if they're set
        'disable_filters' => false,
        // Arrays (instances of ModelListFilterData) with information about available filters in the listing.
        // These should appear in the order in which they should be displayed. They should be keyed by strings
        // that may be referred to in filter POST data.
        'filters' => [],

        // How the listing should be ordered by default
        // This value can also refer to an ordering strategy by name or <FQN>@<method> for custom ordering,
        // or consist of an array of such patterns.
        'default_sort' => null,

        // The (default) page size to use when showing the list.
        // If the page size should be variable, this can also by an array with integer values, the first of which
        // should be the default, so the user can manually switch them in the listing.
        'page_size' => null,

        // Whether the list may be manually ordered (f.i. by dragging and dropping records)
        'orderable' => null,
        // The strategy by which the model can be ordered. For now, this should always be 'listify'.
        'order_strategy' => 'listify',
        // The column used for the order strategy ('position' for listify)
        'order_column' => null,
        // If listify is scoped in a way to restrict it for a relation's foreign key, set the relation method name here.
        'order_scope_relation' => null,

        // Whether the model may be activated/deactived through the listing; ie. whether it has a manipulable 'active' flag.
        'activatable' => null,
        // The column that should be toggled when toggling 'active' status for the model.
        'active_column' => null,

        // Whether to disable the use and display of scopes.
        'disable_scopes' => null,
        // Scopes or scoping strategies, keyed by the scope name.
        'scopes' => [],

        // The default action(s) for clicking on a (normal) table row.
        // The first action that is permissible will apply, if any.
        'default_action' => [],

        // Views to show before and/or after the list. Instance of ModelViewReferenceData.
        'before' => null,
        'after'  => null,

        // Whether to hide everything but top-level list parents by default, and if so, using what relation.
        // Useful to remove clutter for nested content with a click-through-to-children setup.
        // Set to relation method name that should be present in 'parents'.
        'default_top_relation' => null,

        // List parents for list hierarchy handling (instances of ModelListParentData)
        'parents' => [],
    ];

    protected $known = [
        'columns',
        'disable_filters',
        'filters',
        'default_sort',
        'page_size',
        'orderable',
        'order_strategy',
        'order_column',
        'order_scope_relation',
        'activatable',
        'active_column',
        'disable_scopes',
        'scopes',
        'default_action',
        'before',
        'after',
        'default_top_relation',
        'parents',
    ];


    /**
     * Returns the orderable (listify) column that should be used.
     *
     * @return string
     */
    public function getOrderableColumn()
    {
        return $this->order_column ?: 'position';
    }

    /**
     * Returns the default action for list rows.
     *
     * @return ModelActionReferenceDataInterface|null
     */
    public function getDefaultAction()
    {
        // Determine the appliccable action
        if ( ! $this->default_action) {
            return null;
        }

        $actions = $this->default_action;

        if ( ! is_array($actions)) {
            $actions = [
                new ModelActionReferenceData([
                    'strategy' => $actions,
                ]),
            ];
        }

        $core = $this->getCore();


        foreach ($actions as $action) {

            $permissions = $action->permissions();

            if (    ! count($permissions)
                ||  $core->auth()->user()->isAdmin()
                ||  $core->auth()->user()->can($permissions)
            ) {
                return $action;
            }
        }

        return null;
    }

    /**
     * @param ModelListDataInterface|ModelListData $with
     * @return $this
     */
    public function merge(ModelListDataInterface $with)
    {
        // Overwrite columns intelligently: keep only the columns for keys that were set
        // and merge those for which data is set.
        if ($with->columns && count($with->columns)) {

            $mergedColumns = [];

            foreach ($with->columns as $key => $data) {

                if ( ! array_has($this->columns, $key)) {
                    $mergedColumns[ $key ] = $data;
                    continue;
                }

                $this->columns[ $key ]->merge($data);
                $mergedColumns[ $key ] = $this->columns[ $key ];
            }

            $this->columns = $mergedColumns;
        }

        // Overwrite filters if specifically set
        if ($with->filters && count($with->filters)) {
            $this->filters = $with->filters;
        }

        // Overwrite scopes if they are specifically set
        if ($with->scopes && count($with->scopes)) {
            $this->scopes = $with->scopes;
        }

        // Overwrite default action if specifically set
        if ($with->default_action && count($with->default_action)) {
            $this->default_action = $with->default_action;
        }


        $standardMergeKeys = [
            'page_size',
            'orderable',
            'order_strategy',
            'order_column',
            'order_scope_relation',
            'activatable',
            'active_column',
            'default_sort',
            'disable_filters',
            'disable_scopes',
            'before',
            'after',
            'default_top_relation',
            'parents',
        ];

        foreach ($standardMergeKeys as $key) {
            $this->mergeAttribute($key, $with->{$key});
        }

        return $this;
    }

    /**
     * @return CoreInterface
     */
    public function getCore()
    {
        return app(Component::CORE);
    }

}
