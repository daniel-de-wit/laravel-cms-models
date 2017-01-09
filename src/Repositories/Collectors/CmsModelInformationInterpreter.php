<?php
namespace Czim\CmsModels\Repositories\Collectors;

use Czim\CmsCore\Support\Data\AbstractDataObject;
use Czim\CmsModels\Contracts\Data\ModelInformationInterface;
use Czim\CmsModels\Contracts\Repositories\Collectors\ModelInformationInterpreterInterface;
use Czim\CmsModels\Support\Data\ModelActionReferenceData;
use Czim\CmsModels\Support\Data\ModelFormFieldData;
use Czim\CmsModels\Support\Data\ModelInformation;
use Czim\CmsModels\Support\Data\ModelListColumnData;
use Czim\CmsModels\Support\Data\ModelListFilterData;
use Czim\CmsModels\Support\Data\ModelListParentData;
use Czim\CmsModels\Support\Data\ModelScopeData;
use Czim\CmsModels\Support\Data\ModelShowFieldData;

class CmsModelInformationInterpreter implements ModelInformationInterpreterInterface
{

    /**
     * @var array|mixed
     */
    protected $raw;

    /**
     * Interprets raw CMS model information as a model information object.
     *
     * @param array $information
     * @return ModelInformationInterface|ModelInformation
     */
    public function interpret($information)
    {
        $this->raw = $information;

        $this->interpretListData()
             ->interpretFormData()
             ->interpretShowData();

        return $this->createInformationInstance();
    }

    /**
     * @return ModelInformationInterface|ModelInformation
     */
    protected function createInformationInstance()
    {
        $info = (new ModelInformation([]))->clear();

        $info->setAttributes($this->raw);

        return $info;
    }


    /**
     * @return $this
     */
    protected function interpretListData()
    {
        if (array_has($this->raw, 'list') && is_array($this->raw['list'])) {

            $this->raw['list']['default_action'] = $this->normalizeStandardArrayProperty(
                array_get($this->raw['list'], 'default_action', []),
                'type',
                ModelActionReferenceData::class
            );

            $this->raw['list']['columns'] = $this->normalizeStandardArrayProperty(
                array_get($this->raw['list'], 'columns', []),
                'strategy',
                ModelListColumnData::class
            );


            $filters = array_get($this->raw['list'], 'filters', []);
            if (false === $filters) {
                $this->raw['list']['disable_filters'] = true;
            } else {
                $this->raw['list']['filters'] = $this->normalizeStandardArrayProperty(
                    $filters,
                    'strategy',
                    ModelListFilterData::class
                );
            }


            $scopes = array_get($this->raw['list'], 'scopes', []);
            if (false === $scopes) {
                $this->raw['list']['disable_scopes'] = true;
            } else {
                $this->raw['list']['scopes'] = $this->normalizeScopeArray($scopes);
            }


            $parents = [];
            foreach (array_get($this->raw['list'], 'parents', []) as $key => $parent) {
                if ( ! is_string($parent)) {
                    $parents[ $key ] = $parent;
                } else {
                    $parents[ $key ] = new ModelListParentData([ 'relation' => $parent ]);
                }
            }
            $this->raw['list']['parents'] = $parents;
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function interpretFormData()
    {
        if (array_has($this->raw, 'form') && is_array($this->raw['form'])) {

            $this->raw['form']['fields'] = $this->normalizeStandardArrayProperty(
                array_get($this->raw['form'], 'fields', []),
                'display_strategy',
                ModelFormFieldData::class
            );


            $this->raw['form']['layout'] = array_get($this->raw['form'], 'layout', []);


            if (array_has($this->raw['form'], 'validation') && is_array($this->raw['form']['validation'])) {

                $this->raw['form']['validation']['create'] = array_get($this->raw['form']['validation'], 'create');
                $this->raw['form']['validation']['update'] = array_get($this->raw['form']['validation'], 'update');
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function interpretShowData()
    {
        if (array_has($this->raw, 'show') && is_array($this->raw['show'])) {

            $this->raw['show']['fields'] = $this->normalizeStandardArrayProperty(
                array_get($this->raw['show'], 'fields', []),
                'strategy',
                ModelShowFieldData::class
            );
        }

        return $this;
    }

    /**
     * Normalizes an array with scope data.
     *
     * @param array $scopes
     * @return array
     */
    protected function normalizeScopeArray(array $scopes)
    {
        $scopes = $this->normalizeStandardArrayProperty(
            $scopes,
            'strategy',
            ModelScopeData::class
        );

        // Make sure that each scope entry has at least a method or a strategy
        foreach ($scopes as $key => &$value) {
            if ( ! $value['method'] && ! $value['strategy']) {
                $value['method'] = $key;
            }
        }

        unset($value);

        return $scopes;
    }

    /**
     * Normalizes a standard array property.
     *
     * @param array       $source
     * @param string      $standardProperty     property to set for string values in normalized array
     * @param null|string $objectClass          dataobject FQN to interpret as
     * @return array
     */
    protected function normalizeStandardArrayProperty(array $source, $standardProperty, $objectClass = null)
    {
        $normalized = [];

        foreach ($source as $key => $value) {

            // list column may just set for order, defaults need to be filled in
            if (is_numeric($key) && ! is_array($value)) {
                $key    = $value;
                $value = [];
            }

            // if value is just a string, it is the list strategy
            if (is_string($value)) {
                $value = [
                    $standardProperty => $value,
                ];
            }

            if ($objectClass) {
                $value = $this->makeClearedDataObject($objectClass, $value);
            }

            $normalized[ $key ] = $value;
        }

        return $normalized;
    }

    /**
     * Makes a fresh dataobject with its defaults cleared before filling it with data.
     *
     * @param string $objectClass
     * @param array  $data
     * @return AbstractDataObject
     */
    protected function makeClearedDataObject($objectClass, array $data)
    {
        /** @var AbstractDataObject $object */
        $object = new $objectClass();
        $object->clear();

        $object->setAttributes($data);

        return $object;
    }

}
