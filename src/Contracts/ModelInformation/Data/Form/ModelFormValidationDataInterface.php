<?php
namespace Czim\CmsModels\Contracts\ModelInformation\Data\Form;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;

interface ModelFormValidationDataInterface extends ArrayAccess, Arrayable
{

    /**
     * Returns default/base rules shared by create and update.
     *
     * @return array
     */
    public function sharedRules();

    /**
     * Returns default or create specific rules.
     *
     * @return array
     */
    public function create();

    /**
     * Returns update specific rules.
     *
     * @return array
     */
    public function update();

    /**
     * Returns optional FQN of rules decorator/generator class.
     *
     * @return string
     */
    public function rulesClass();

    /**
     * @param ModelFormValidationDataInterface $with
     */
    public function merge(ModelFormValidationDataInterface $with);

}
