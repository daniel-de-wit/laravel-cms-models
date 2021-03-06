<?php
namespace Czim\CmsModels\Strategies\Export\Column;

use Czim\CmsModels\Contracts\ModelInformation\Data\ModelAttributeDataInterface;
use Czim\CmsModels\Contracts\ModelInformation\Data\Export\ModelExportColumnDataInterface;
use Czim\CmsModels\Contracts\Strategies\Export\ExportColumnInterface;
use Czim\CmsModels\ModelInformation\Data\ModelAttributeData;
use Czim\CmsModels\ModelInformation\Data\Export\ModelExportColumnData;
use Czim\CmsModels\Support\Strategies\Traits\ResolvesSourceStrategies;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractColumnStrategy implements ExportColumnInterface
{
    use ResolvesSourceStrategies;

    /**
     * @var ModelExportColumnDataInterface|ModelExportColumnData
     */
    protected $exportColumnData;

    /**
     * @var ModelAttributeDataInterface|ModelAttributeData
     */
    protected $attributeData;

    /**
     * @var array
     */
    protected $options = [];


    /**
     * Renders a display value to print to the export.
     *
     * @param Model  $model
     * @param string $source    source column, method name or value
     * @return string
     */
    abstract public function render(Model $model, $source);

    /**
     * Returns custom options.
     *
     * @return array
     */
    public function options()
    {
        if ($this->options && count($this->options)) {
            return $this->options;
        }

        if ( ! $this->exportColumnData) {
            return [];
        }

        return $this->exportColumnData->options();
    }

    /**
     * Sets custom options.
     *
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Sets the export column data to use.
     *
     * @param ModelExportColumnDataInterface|ModelExportColumnData $data
     * @return $this
     */
    public function setColumnInformation(ModelExportColumnDataInterface $data)
    {
        $this->exportColumnData = $data;

        return $this;
    }

    /**
     * Sets the attribute column data to use.
     *
     * @param ModelAttributeDataInterface|ModelAttributeData $data
     * @return $this
     */
    public function setAttributeInformation(ModelAttributeDataInterface $data)
    {
        $this->attributeData = $data;

        return $this;
    }

    /**
     * Initializes the strategy instance for further calls.
     *
     * Should be called after setColumnInformation, if this is set at all.
     *
     * @param string $modelClass
     * @return $this
     */
    public function initialize($modelClass)
    {
        return $this;
    }

}
