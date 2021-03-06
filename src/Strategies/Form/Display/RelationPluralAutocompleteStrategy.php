<?php
namespace Czim\CmsModels\Strategies\Form\Display;

use Illuminate\Contracts\Support\Arrayable;

class RelationPluralAutocompleteStrategy extends AbstractRelationStrategy
{

    /**
     * Returns the view partial that should be used.
     *
     * @return string
     */
    protected function getView()
    {
        return 'cms-models::model.partials.form.strategies.relation_plural_autocomplete';
    }

    /**
     * Normalizes a value to make sure it can be processed uniformly.
     *
     * @param mixed $value
     * @param bool  $original
     * @return mixed
     */
    protected function normalizeValue($value, $original = false)
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        return [ $value ];
    }

    /**
     * Enriches field data before passing it on to the view.
     *
     * @param array $data
     * @return array
     */
    protected function decorateFieldData(array $data)
    {
        // Get the key-reference pairs to allow the form to display values for the
        // currently selected keys for the model.

        $keys = $data['value'] ?: [];

        if ($keys instanceof Arrayable) {
            $keys = $keys->toArray();
        }

        $data['references'] = $this->getReferencesForModelKeys($keys);

        // Determine the min. input length to trigger autocomplete ajax lookups
        $data['minimumInputLength'] = array_get(
            $this->field->options(),
            'minimum_input_length',
            $this->determineBestMinimumInputLength()
        );

        return $data;
    }

}
