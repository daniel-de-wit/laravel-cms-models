<?php
namespace Czim\CmsModels\View\FormFieldStrategies;

class TextAreaStrategy extends AbstractDefaultStrategy
{

    /**
     * Returns the view partial that should be used.
     *
     * @return string
     */
    protected function getView()
    {
        return 'cms-models::model.partials.form.strategies.textarea';
    }

}
