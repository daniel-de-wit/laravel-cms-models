<?php
namespace Czim\CmsModels\Strategies\ListColumn;

use Codesleeve\Stapler\Interfaces\Attachment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use UnexpectedValueException;

class StaplerImage extends AbstractListDisplayStrategy
{
    const VIEW = 'cms-models::model.partials.list.strategies.stapler_image';

    const WIDTH  = 64;
    const HEIGHT = 64;

    /**
     * Renders a display value to print to the list view.
     *
     * @param Model $model
     * @param mixed|Attachment $source     source column, method name or value
     * @return string|View
     */
    public function render(Model $model, $source)
    {
        $source = $this->resolveModelSource($model, $source);

        if ( ! ($source instanceof Attachment)) {
            throw new UnexpectedValueException("Stapler strategy expects Attachment as source");
        }

        $resize = $this->getResizetoUse($source);

        if ($this->listColumnData) {
            $width  = array_get($this->listColumnData->options, 'width');
            $height = array_get($this->listColumnData->options, 'height');
        } else {
            $width  = null;
            $height = null;
        }

        return view(static::VIEW, [
            'exists'      => $source->size() > 0,
            'filename'    => $source->originalFilename(),
            'urlThumb'    => $source->url($resize),
            'urlOriginal' => $source->url(),
            'width'       => $width ?: $height ?: static::WIDTH,
            'height'      => $height ?: $width ?: static::HEIGHT,
        ]);
    }

    /**
     * Returns the stapler resize to display.
     *
     * @param Attachment $attachment
     * @return string
     */
    protected function getResizetoUse(Attachment $attachment)
    {
        return array_get(
            $this->options(),
            'stapler_style',
            $this->getSmallestResize($attachment)
        );
    }

    /**
     * Returns smallest available resize for the attachment.
     *
     * @param Attachment $attachment
     * @return null|string
     */
    protected function getSmallestResize(Attachment $attachment)
    {
        $smallestKey = null;
        $smallest    = null;

        foreach ($attachment->getConfig()->styles as $style) {

            if ( ! preg_match('#(\d+)?x(\d+)?#', $style->dimensions, $matches)) {
                continue;
            }

            $smallestForStyle = null;

            if ((int) $matches[1] > 0) {
                $smallestForStyle = (int) $matches[1];
            }

            if ((int) $matches[2] > 0 && (int) $matches[2] < $smallestForStyle) {
                $smallestForStyle = (int) $matches[2];
            }

            if (    null !== $smallestForStyle
                &&  ($smallestForStyle < $smallest || null === $smallest)
            ) {
                $smallestKey = $style->name;
                $smallest    = $smallestForStyle;
            }
        }

        return $smallestKey;
    }

}
