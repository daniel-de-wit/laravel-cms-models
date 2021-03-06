<?php
namespace Czim\CmsModels\ModelInformation\Data\Export;

use Czim\CmsModels\Contracts\ModelInformation\Data\Export\ModelExportStrategyDataInterface;
use Czim\CmsModels\ModelInformation\Data\AbstractModelInformationDataObject;

/**
 * Class ModelExportStrategyData
 *
 * Data container that represents list representation for the model.
 *
 * @property string $strategy
 * @property string $label
 * @property string $label_translated
 * @property string $icon
 * @property string|string[] $permissions
 * @property string $repository_strategy
 * @property array $repository_strategy_parameters
 * @property array|ModelExportColumnData[] $columns
 * @property array $options
 */
class ModelExportStrategyData extends AbstractModelInformationDataObject implements ModelExportStrategyDataInterface
{

    protected $objects = [
        'columns' => ModelExportColumnData::class . '[]',
    ];

    protected $attributes = [

        // The strategy identifier (alias or FQN) for the exporting strategy.
        'strategy' => null,

        // Label (or translation key) to show on the export action link/button.
        'label' => null,
        'label_translated' => null,

        // The name for a glyphicon/font awesome icon (without prefix).
        // How this is interpreted depends on the export buttons view partial.
        'icon' => null,

        // The permission(s) required to use this export strategy (string or array of strings).
        'permissions' => null,

        // The strategy to apply to the base repository/context query for this export.
        'repository_strategy' => null,

        // Optional parameters to pass along to the repository/context strategy instance.
        'repository_strategy_parameters' => [],

        // Arrays (instances of ModelExportColumnData) with information about a single column.
        // All columns that should be present in the export, should be listed here, in the right order.
        // Overrules default export columns, if set.
        'columns' => [],

        // Options for this export strategy.
        'options' => [],
    ];

    protected $known = [
        'strategy',
        'label',
        'label_translated',
        'icon',
        'permissions',
        'repository_strategy',
        'repository_strategy_parameters',
        'columns',
        'options',
    ];


    /**
     * Returns display label for the export link/button.
     *
     * @return string
     */
    public function label()
    {
        if ($this->label_translated) {
            return cms_trans($this->label_translated);
        }

        if ($this->label) {
            return $this->label;
        }

        return ucfirst(str_replace('_', ' ', snake_case($this->strategy)));
    }

    /**
     * Returns icon name to use for the export link/button.
     *
     * @return string|null
     */
    public function icon()
    {
        return $this->getAttribute('icon');
    }

    /**
     * Returns permissions required to use the export strategy.
     *
     * @return false|string[]
     */
    public function permissions()
    {
        if (is_array($this->permissions)) {
            return $this->permissions;
        }

        if ($this->permissions) {
            return [ $this->permissions ];
        }

        return false;
    }

    /**
     * Returns options for the export strategy.
     *
     * @return array
     */
    public function options()
    {
        return $this->options ?: [];
    }

    /**
     * @param ModelExportStrategyDataInterface|ModelExportStrategyData $with
     * @return $this
     */
    public function merge(ModelExportStrategyDataInterface $with)
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

        $standardMergeKeys = [
            'icon',
            'label',
            'label_translated',
            'permissions',
            'repository_strategy',
            'repository_strategy_parameters',
            'strategy',
            'permissions',
        ];

        foreach ($standardMergeKeys as $key) {
            $this->mergeAttribute($key, $with->{$key});
        }

        $this->options = array_merge($this->options(), $with->options());

        return $this;
    }

}
