<?php
namespace Czim\CmsModels\Strategies\ListColumn;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Check extends AbstractListDisplayStrategy
{

    /**
     * Renders a display value to print to the list view.
     *
     * @param Model $model
     * @param mixed $source     source column, method name or value
     * @return string
     */
    public function render(Model $model, $source)
    {
        $source = $this->resolveModelSource($model, $source);

        if ($this->interpretAsBoolean($source)) {
            return '<i class="fa fa-check text-success" title="' . e(cms_trans('common.boolean.true')) . '"></i>';
        }

        return '<i class="fa fa-times text-danger" title="' . e(cms_trans('common.boolean.false')) . '"></i>';
    }

    /**
     * Returns an optional style string for the list display value container.
     *
     * @param Model  $model
     * @param string $source    source column, method name or value
     * @return string|null
     */
    public function style(Model $model, $source)
    {
        return 'column-center';
    }

    /**
     * Parses a source value as a boolean value.
     *
     * @param mixed $value
     * @return bool
     */
    protected function interpretAsBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return $value != 0;
        }

        if (is_string($value)) {

            $value = trim($value);

            if ('' === $value || preg_match('#^n|no|f|false|nee|off|disabled|inactive&#', trim($value))) {
                return false;
            }

            return true;
        }

        if (is_array($value) || $value instanceof Collection) {
            return count($value) > 0;
        }

        return (bool) $value;
    }

}
