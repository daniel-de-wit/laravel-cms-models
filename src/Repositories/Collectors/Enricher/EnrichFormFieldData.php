<?php
namespace Czim\CmsModels\Repositories\Collectors\Enricher;

use Czim\CmsModels\Analyzer\AttributeStrategyResolver;
use Czim\CmsModels\Analyzer\RelationStrategyResolver;
use Czim\CmsModels\Contracts\Data\ModelFormFieldDataInterface;
use Czim\CmsModels\Contracts\Data\ModelInformationInterface;
use Czim\CmsModels\Contracts\Repositories\Collectors\ModelInformationEnricherInterface;
use Czim\CmsModels\Support\Data\ModelAttributeData;
use Czim\CmsModels\Support\Data\ModelFormFieldData;
use Czim\CmsModels\Support\Data\ModelInformation;
use Czim\CmsModels\Support\Data\ModelRelationData;
use Czim\CmsModels\Support\Enums\AttributeCast;
use Czim\CmsModels\Support\Enums\RelationType;
use UnexpectedValueException;

class EnrichFormFieldData extends AbstractEnricherStep
{

    /**
     * @var AttributeStrategyResolver
     */
    protected $attributeStrategyResolver;

    /**
     * @var RelationStrategyResolver
     */
    protected $relationStrategyResolver;

    /**
     * @param ModelInformationEnricherInterface $enricher
     * @param AttributeStrategyResolver         $attributeStrategyResolver
     * @param RelationStrategyResolver          $relationStrategyResolver
     */
    public function __construct(
        ModelInformationEnricherInterface $enricher,
        AttributeStrategyResolver $attributeStrategyResolver,
        RelationStrategyResolver $relationStrategyResolver
    ) {
        parent::__construct($enricher);

        $this->attributeStrategyResolver = $attributeStrategyResolver;
        $this->relationStrategyResolver  = $relationStrategyResolver;
    }

    /**
     * Performs enrichment.
     */
    protected function performEnrichment()
    {
        if ( ! count($this->info->form->fields)) {
            $this->fillDataForEmpty();
        } else {
            $this->enrichCustomData();
        }
    }

    /**
     * Fills column data if no field data is set.
     */
    protected function fillDataForEmpty()
    {
        // Fill field references if they are empty
        $fields = [];

        // Add columns for attributes
        foreach ($this->info->attributes as $attribute) {

            if ($attribute->hidden || ! $this->shouldAttributeBeEditableByDefault($attribute, $this->info)) {
                continue;
            }

            $fields[ $attribute->name ] = $this->makeModelFormFieldDataForAttributeData($attribute, $this->info);
        }


        // Add fields for relations
        foreach ($this->info->relations as $relation) {

            $fields[ $relation->name ] = $this->makeModelFormFieldDataForRelationData($relation, $this->info);
        }


        $this->info->form->fields = $fields;
    }

    /**
     * Enriches existing user configured data.
     */
    protected function enrichCustomData()
    {
        // Check filled fields and enrich them as required
        // Note that these can be either attributes or relations

        $fields = [];

        foreach ($this->info->form->fields as $key => $field) {

            // Check if we can enrich, if we must.
            if ( ! isset($this->info->attributes[ $key ]) && ! isset($this->info->relations[ $key ])) {

                // if the data is fully set, no need to enrich
                if ($this->isFormFieldDataComplete($field)) {
                    $fields[ $key ] = $field;
                    continue;
                }

                throw new UnexpectedValueException(
                    "Unenriched form field set with non-attribute/non-relation-name key; "
                    . "make sure full field data is provided"
                );
            }

            if (isset($this->info->attributes[ $key ])) {
                $enrichFieldInfo = $this->makeModelFormFieldDataForAttributeData($this->info->attributes[ $key ], $this->info);
            } else {
                // get from relation data
                $enrichFieldInfo = $this->makeModelFormFieldDataForRelationData($this->info->relations[ $key ], $this->info);
            }

            // Detect whether update/create were not explicitly defined
            // If they were not, assume that they should be shown,
            // since they were explicitly included in the config.
            if (null === $field->update && null === $field->create) {
                $field->update = true;
                $field->create = true;
            }

            $enrichFieldInfo->merge($field);

            $fields[ $key ] = $enrichFieldInfo;
        }

        $this->info->form->fields = $fields;
    }

    /**
     * Returns whether the given data set is filled to the extent that enrichment is not required.
     *
     * @param ModelFormFieldDataInterface|ModelFormFieldData $data
     * @return bool
     */
    protected function isFormFieldDataComplete(ModelFormFieldDataInterface $data)
    {
        return $data->key;
    }

    /**
     * Returns whether an attribute should be editable if no user-defined fields are configured.
     *
     * @param ModelAttributeData                         $attribute
     * @param ModelInformationInterface|ModelInformation $info
     * @return bool
     */
    protected function shouldAttributeBeEditableByDefault(ModelAttributeData $attribute, ModelInformationInterface $info)
    {
        // Auto-incrementing key
        if ($attribute->name === $this->model->getKeyName() && $info->incrementing) {
            return false;
        }

        // Activatable column is used in listing, so hide in edit form
        if ($info->list->activatable && $info->list->active_column == $attribute->name) {
            return false;
        }

        // Automated timestamp columns
        if (    $this->model->timestamps
            &&  (   $attribute->name == $this->model->getCreatedAtColumn()
                ||  $attribute->name == $this->model->getUpdatedAtColumn()
                )
        ) {
            return false;
        }

        // Exclude stapler fields other than the main field
        if (preg_match('#^(?<field>[^_]+)_(file_name|file_size|content_type|updated_at)$#', $attribute->name, $matches)) {
            if (array_has($info->attributes, $matches['field'])) {
                return $info->attributes[ $matches['field'] ]->cast !== AttributeCast::STAPLER_ATTACHMENT;
            }
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
        if ( ! count($info->relations)) {
            return false;
        }

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
            if ( ! $relation->foreign_keys || ! count($relation->foreign_keys)) {
                continue;
            }

            if (in_array($attribute, $relation->foreign_keys)) {
                return true;
            }
        }

        return false;
    }


    /**
     * Makes data set for form field given attribute data.
     *
     * @param ModelAttributeData                         $attribute
     * @param ModelInformationInterface|ModelInformation $info
     * @return ModelFormFieldData
     */
    protected function makeModelFormFieldDataForAttributeData(ModelAttributeData $attribute, ModelInformationInterface $info)
    {
        return new ModelFormFieldData([
            'key'              => $attribute->name,
            'display_strategy' => $this->determineFormDisplayStrategyForAttribute($attribute),
            'store_strategy'   => $this->determineFormStoreStrategyForAttribute($attribute),
            'translated'       => $attribute->translated,
            'required'         => ! $attribute->nullable,
            'options'          => [
                'length' => $attribute->length,
            ],
        ]);
    }

    /**
     * Makes data set for form field given relation data.
     *
     * @param ModelRelationData                          $relation
     * @param ModelInformationInterface|ModelInformation $info
     * @return ModelFormFieldData
     */
    protected function makeModelFormFieldDataForRelationData(ModelRelationData $relation, ModelInformationInterface $info)
    {
        $required = (   in_array($relation->type, [
                            RelationType::BELONGS_TO,
                            RelationType::BELONGS_TO_THROUGH,
                            RelationType::MORPH_TO,
                        ])
                    &&  ! $relation->nullable_key);

        // Only show the fields if they are not of a to-many type, to prevent automatically including
        // relations with huge datasets, while still keeping the information enrichment going.
        $show = in_array($relation->type, [
            RelationType::BELONGS_TO,
            RelationType::BELONGS_TO_THROUGH,
            RelationType::HAS_ONE,
            RelationType::MORPH_ONE,
            RelationType::MORPH_TO,
        ]);

        return new ModelFormFieldData([
            'key'              => $relation->method,
            'update'           => $show,
            'create'           => $show,
            'source'           => $relation->method,
            'required'         => $required,
            'translated'       => $relation->translated,
            'display_strategy' => $this->determineFormDisplayStrategyForRelation($relation),
            'store_strategy'   => $this->determineFormStoreStrategyForRelation($relation),
            'options'          => $this->determineFormStoreOptionsForRelation($relation),
        ]);
    }

    /**
     * @param ModelAttributeData $attribute
     * @return null|string
     */
    protected function determineFormDisplayStrategyForAttribute(ModelAttributeData $attribute)
    {
        return $this->attributeStrategyResolver->determineFormDisplayStrategy($attribute);
    }

    /**
     * @param ModelAttributeData $attribute
     * @return null|string
     */
    protected function determineFormStoreStrategyForAttribute(ModelAttributeData $attribute)
    {
        return $this->attributeStrategyResolver->determineFormStoreStrategy($attribute);
    }

    /**
     * @param ModelRelationData $relation
     * @return null|string
     */
    protected function determineFormDisplayStrategyForRelation(ModelRelationData $relation)
    {
        return $this->relationStrategyResolver->determineFormDisplayStrategy($relation);
    }

    /**
     * @param ModelRelationData $relation
     * @return null|string
     */
    protected function determineFormStoreStrategyForRelation(ModelRelationData $relation)
    {
        return $this->relationStrategyResolver->determineFormStoreStrategy($relation);
    }

    /**
     * @param ModelRelationData $relation
     * @return array
     */
    protected function determineFormStoreOptionsForRelation(ModelRelationData $relation)
    {
        $options = $this->relationStrategyResolver->determineFormStoreOptions($relation);

        // Prepare MorphTo models, if they are not set.
        if ($relation->type === RelationType::MORPH_TO) {
            $options['models'] = $this->determineMorphModelsForRelationData($relation);
        }

        return $options;
    }

    /**
     * Determines models for MorphTo relation data.
     *
     * @param ModelRelationData $data
     * @return string[]
     */
    protected function determineMorphModelsForRelationData(ModelRelationData $data)
    {
        if ($data->morphModels && count($data->morphModels)) {
            return $data->morphModels;
        }

        // Use information for other models in the CMS to find (some of) the related models
        // If there is no context, ignore it.
        if ( ! ($context = $this->enricher->getAllModelInformation())) {
            return [];
        }

        $models = [];

        foreach ($context as $information) {

            // If a relation is related to this model by a reverse morph relation,
            // it is an intended MorphTo targetable model.
            foreach ($information->relations as $relation) {

                if (    $this->info->modelClass() !== $relation->relatedModel
                    ||  ! in_array($relation->type, [ RelationType::MORPH_ONE, RelationType::MORPH_MANY ])
                ) {
                    continue;
                }

                $models[ $information->modelClass() ] = [];
            }
        }

        return $models;
    }

}
