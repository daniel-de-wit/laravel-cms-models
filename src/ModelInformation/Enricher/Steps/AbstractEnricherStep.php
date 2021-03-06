<?php
namespace Czim\CmsModels\ModelInformation\Enricher\Steps;

use Czim\CmsModels\Contracts\ModelInformation\Data\ModelInformationInterface;
use Czim\CmsModels\Contracts\ModelInformation\Enricher\EnricherStepInterface;
use Czim\CmsModels\Contracts\ModelInformation\ModelInformationEnricherInterface;
use Czim\CmsModels\ModelInformation\Data\ModelAttributeData;
use Czim\CmsModels\ModelInformation\Data\ModelInformation;
use Czim\CmsModels\Support\Enums\AttributeCast;
use Czim\CmsModels\Support\Enums\RelationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

abstract class AbstractEnricherStep implements EnricherStepInterface
{

    /**
     * Parent enricher for this step.
     *
     * @var null|ModelInformationEnricherInterface
     */
    protected $enricher;

    /**
     * @var ModelInformationInterface|ModelInformation
     */
    protected $info;

    /**
     * All model information known (so far), before enrichment.
     *
     * @var Collection|ModelInformationInterface[]|ModelInformation[]|null
     */
    protected $allInfo;

    /**
     * @var Model
     */
    protected $model;

    /**
     * Sets parent enricher model.
     *
     * This cannot be part of the constructor since Laravel 5.4's removal
     * of container make parameters.
     *
     * @param ModelInformationEnricherInterface $enricher
     * @return $this
     */
    public function setEnricher(ModelInformationEnricherInterface $enricher)
    {
        $this->enricher = $enricher;

        return $this;
    }

    /**
     * Performs enrichment on model information.
     *
     * Optionally takes all model information known as context.
     *
     * @param ModelInformationInterface|ModelInformation                     $info
     * @param Collection|ModelInformationInterface[]|ModelInformation[]|null $allInformation
     * @return ModelInformationInterface|ModelInformation
     */
    public function enrich(ModelInformationInterface $info, $allInformation = null)
    {
        $this->info = $info;
        $class = $this->info->modelClass();
        $this->model = new $class;

        $this->allInfo = $allInformation;

        $this->performEnrichment();

        return $this->info;
    }

    /**
     * Performs enrichment.
     */
    abstract protected function performEnrichment();

    /**
     * Returns whether an attribute should be displayed if no user-defined list columns are configured.
     *
     * @param ModelAttributeData                         $attribute
     * @param ModelInformationInterface|ModelInformation $info
     * @return bool
     */
    protected function shouldAttributeBeDisplayedByDefault(ModelAttributeData $attribute, ModelInformationInterface $info)
    {
        if (in_array($attribute->type, [
            'text', 'longtext', 'mediumtext',
            'blob', 'longblob', 'mediumblob',
        ])) {
            return false;
        }

        // Hide active column if the model if activatable
        if ($info->list->activatable && $info->list->active_column == $attribute->name) {
            return false;
        }

        // Hide orderable position column if the model if orderable
        if ($info->list->orderable && $info->list->order_column == $attribute->name) {
            return false;
        }

        // Hide stapler fields other than the main field
        if (    preg_match('#^(?<field>[^_]+)_(file_name|file_size|content_type|updated_at)$#', $attribute->name, $matches)
            &&  array_has($info->attributes, $matches['field'])
        ) {
            return $info->attributes[ $matches['field'] ]->cast !== AttributeCast::STAPLER_ATTACHMENT;
        }

        // Any attribute that is a foreign key and should be handled with relation-based strategies
        return ! $this->isAttributeForeignKey($attribute->name, $info);
    }

    /**
     * @param string                                     $attribute
     * @param ModelInformationInterface|ModelInformation $info
     * @return bool
     */
    protected function isAttributeForeignKey($attribute, ModelInformationInterface $info)
    {
        foreach ($info->relations as $relation) {

            if ( ! in_array($relation->type, [
                RelationType::BELONGS_TO,
                RelationType::BELONGS_TO_THROUGH,
                RelationType::MORPH_TO,
            ])) {
                continue;
            }

            // the relation has a foreign key on this model, check their name(s)
            // and check if the attribute matches
            if (    $relation->foreign_keys
                &&  count($relation->foreign_keys)
                &&  in_array($attribute, $relation->foreign_keys)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalizes a string representation for a relation method to the expected key name.
     *
     * @param string $key   key or relation method
     * @return string
     */
    protected function normalizeRelationName($key)
    {
        return camel_case($key);
    }

}
