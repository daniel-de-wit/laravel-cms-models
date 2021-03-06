<?php
namespace Czim\CmsModels\ModelInformation\Analyzer\Processor\Steps;

use Codesleeve\Stapler\Attachment as StaplerAttachmentInstance;
use Codesleeve\Stapler\AttachmentConfig as StaplerAttachmentConfig;
use Codesleeve\Stapler\ORM\StaplerableInterface;
use Czim\CmsModels\Support\Data\Analysis\PaperclipAttachment as PaperclipAttachmentData;
use Czim\CmsModels\Support\Data\Analysis\StaplerAttachment as StaplerAttachmentData;
use Czim\CmsModels\ModelInformation\Data\ModelAttributeData;
use Czim\CmsModels\Support\Enums\AttributeCast;
use Czim\Paperclip\Attachment\Attachment as PaperclipAttachmentInstance;
use Czim\Paperclip\Contracts\AttachableInterface as PaperclippableInterface;

/**
 * Class DetectStaplerAttributes
 *
 * This detects for both Stapler as well as its Paperclip replacement.
 */
class DetectStaplerAttributes extends AbstractAnalyzerStep
{

    /**
     * Performs the analyzer step on the stored model information instance.
     */
    protected function performStep()
    {
        $this
            ->handleStaplerAttachments()
            ->handlePaperclipAttachments();
    }

    /**
     * Handles analysis of stapler attachments.
     *
     * @return $this
     */
    protected function handleStaplerAttachments()
    {
        // Stapler / attachment attributes
        $attachments = $this->detectStaplerAttachments();

        // Make a list of attributes to insert before the stapler attributes
        /** @var ModelAttributeData[] $inserts */
        $inserts = [];

        foreach ($attachments as $key => $attachment) {

            $attribute = new ModelAttributeData([
                'name'     => $key,
                'cast'     => AttributeCast::STAPLER_ATTACHMENT,
                'type'     => $attachment->image ? 'image' : 'file',
            ]);

            $inserts[ $key . '_file_name' ] = $attribute;
        }


        if ( ! count($inserts)) {
            return $this;
        }

        $attributes = $this->info->attributes;

        foreach ($inserts as $before => $attribute) {

            $attributes = $this->insertInArray($attributes, $attribute->name, $attribute, $before);
        }

        $this->info->attributes = $attributes;

        return $this;
    }

    /**
     * Handles analysis of paperclip attachments.
     *
     * @return $this
     */
    protected function handlePaperclipAttachments()
    {
        // Paperclip / attachment attributes
        $attachments = $this->detectPaperclipAttachments();

        // Make a list of attributes to insert before the paperclip attributes
        /** @var ModelAttributeData[] $inserts */
        $inserts = [];

        foreach ($attachments as $key => $attachment) {

            $attribute = new ModelAttributeData([
                'name'     => $key,
                'cast'     => AttributeCast::PAPERCLIP_ATTACHMENT,
                'type'     => $attachment->image ? 'image' : 'file',
            ]);

            $inserts[ $key . '_file_name' ] = $attribute;
        }


        if ( ! count($inserts)) {
            return $this;
        }

        $attributes = $this->info->attributes;

        foreach ($inserts as $before => $attribute) {

            $attributes = $this->insertInArray($attributes, $attribute->name, $attribute, $before);
        }

        $this->info->attributes = $attributes;

        return $this;
    }

    /**
     * Returns list of stapler attachments, if the model has any.
     *
     * @return StaplerAttachmentData[]  assoc, keyed by attribute name
     */
    protected function detectStaplerAttachments()
    {
        $model = $this->model();

        if ( ! ($model instanceof StaplerableInterface)) {
            return [];
        }

        $files = $model->getAttachedFiles();

        $attachments = [];

        /** @var StaplerAttachmentInstance[] $files */
        foreach ($files as $attribute => $file) {

            /** @var StaplerAttachmentConfig $config */
            $config = $file->getConfig();
            $styles = $config->styles;

            $normalizedStyles = [];

            foreach ($styles as $style) {

                if ($style->name === 'original') {
                    continue;
                }

                $normalizedStyles[ $style->name ] = $style->dimensions;
            }

            $attachments[ $attribute ] = new StaplerAttachmentData([
                'image'   => (is_array($styles) && count($styles) > 1),
                'resizes' => $normalizedStyles,
            ]);
        }

        return $attachments;
    }

    /**
     * Returns list of paperclip attachments, if the model has any.
     *
     * @return PaperclipAttachmentData[]  assoc, keyed by attribute name
     */
    protected function detectPaperclipAttachments()
    {
        $model = $this->model();

        if ( ! ($model instanceof PaperclippableInterface)) {
            return [];
        }

        $files = $model->getAttachedFiles();

        $attachments = [];

        /** @var PaperclipAttachmentInstance[] $files */
        foreach ($files as $attribute => $file) {

            $variants = $file->variants();

            $normalizedVariants = [];

            foreach ($variants as $variant) {

                if ($variant === 'original') {
                    continue;
                }

                $normalizedVariants[ $variant ] = $this->extractPaperclipVariantInfo($file, $variant);
            }

            $attachments[ $attribute ] = new PaperclipAttachmentData([
                'image'      => (is_array($variants) && count($variants) > 1),
                'resizes'    => array_pluck($normalizedVariants, 'dimensions'),
                'variants'   => $variants,
                'extensions' => array_pluck($normalizedVariants, 'extension'),
                'types'      => array_pluck($normalizedVariants, 'mimeType'),
            ]);
        }

        return $attachments;
    }

    /**
     * Returns extracted data from the paperclip instance for a variant.
     *
     * @param PaperclipAttachmentInstance $paperclip
     * @param string                      $variant
     * @return array    associative
     */
    protected function extractPaperclipVariantInfo(PaperclipAttachmentInstance $paperclip, $variant)
    {
        $config = $paperclip->getNormalizedConfig();

        $variantSteps = array_get($config, "variants.{$variant}", []);

        $dimensions = array_get($variantSteps, 'resize.dimensions');
        $extension  = array_get($variantSteps, "extensions.{$variant}");
        $mimeType   = array_get($variantSteps, "types.{$variant}");

        return compact('dimensions', 'extension', 'mimeType');
    }

    /**
     * Insert an item into an associative array at the position before a given key.
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     * @param string $beforeKey
     * @return array
     */
    protected function insertInArray($array, $key, $value, $beforeKey)
    {
        // Find the position of the array
        $position = array_search($beforeKey, array_keys($array));

        // Safeguard: silently append if injected position could not be found
        if (false === $position) {
            // @codeCoverageIgnoreStart
            $array[ $key ] = $value;
            return $array;
            // @codeCoverageIgnoreEnd
        }

        if (0 === $position) {
            return [ $key => $value ] + $array;
        }

        // Slice the array up with the new entry in between
        return array_slice($array, 0, $position, true)
             + [ $key => $value ]
             + array_slice($array, $position, count($array) - $position, true);
    }

}
