<?php
namespace Czim\CmsModels\Contracts\Http\Controllers;

use Czim\CmsModels\Contracts\ModelInformation\Data\Form\ModelFormFieldDataInterface;
use Czim\CmsModels\Contracts\ModelInformation\Data\ModelInformationInterface;
use Illuminate\Database\Eloquent\Model;

interface FormFieldStoreStrategyInterface extends FormFieldListParentValueInterface
{

    /**
     * Sets the relevant form field data to provide a context.
     *
     * @param ModelFormFieldDataInterface $data
     * @return $this
     */
    public function setFormFieldData(ModelFormFieldDataInterface $data);

    /**
     * Sets parameters to use for retrieving & storing.
     *
     * @param array $parameters
     * @return $this
     */
    public function setParameters(array $parameters);

    /**
     * Retrieves current values from a model
     *
     * @param Model $model
     * @param mixed $source
     * @return mixed
     */
    public function retrieve(Model $model, $source);

    /**
     * Stores a submitted value on a model
     *
     * @param Model $model
     * @param mixed $source
     * @param mixed $value
     */
    public function store(Model $model, $source, $value);

    /**
     * Stores a submitted value on a model, after it has been created (or saved).
     *
     * @param Model $model
     * @param mixed $source
     * @param mixed $value
     */
    public function storeAfter(Model $model, $source, $value);

    /**
     * Returns validation rules to use for submitted form data for this strategy.
     *
     * If the return array is associative, rules are expected nested per key,
     * otherwise the rules will be added to the top level key.
     *
     * @param ModelFormFieldDataInterface|null $field
     * @param ModelInformationInterface|null   $modelInformation
     * @param bool                             $create              whether the rules are for creating a new record
     * @return array|false false if no validation should be performed.
     */
    public function validationRules(
        ModelFormFieldDataInterface $field = null,
        ModelInformationInterface $modelInformation = null,
        $create
    );

}
