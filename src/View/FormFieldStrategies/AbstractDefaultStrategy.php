<?php
namespace Czim\CmsModels\View\FormFieldStrategies;

abstract class AbstractDefaultStrategy Extends AbstractFormFieldDisplayStrategy
{

    /**
     * Renders a form field.
     *
     * @param mixed       $value
     * @param array       $errors
     * @param null|string $locale
     * @return string
     */
    public function renderField($value, array $errors = [], $locale = null)
    {
        $type = $this->field->type ?: array_get($this->field->options(), 'type', 'text');

        return view($this->getView(), [
            'record'     => $this->model,
            'key'        => $this->field->key(),
            'name'       => $this->getFormFieldName($locale),
            'value'      => $value,
            'type'       => $type,
            'errors'     => $errors,
            'required'   => $this->field->required(),
            'options'    => $this->field->options(),
            'translated' => $this->field->translated(),
        ]);
    }

    /**
     * Returns the view partial that should be used.
     *
     * @return string
     */
    abstract protected function getView();

    /**
     * Returns name for the form field input tag.
     *
     * @param null|string $locale
     * @return string
     */
    protected function getFormFieldName($locale = null)
    {
        if ( ! $locale) {
            return $this->field->key();
        }

        return $this->field->key() . '[' . $locale . ']';
    }

}