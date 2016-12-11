<?php
namespace Czim\CmsModels\Support\Data;

use Czim\CmsCore\Support\Data\AbstractDataObject;
use Czim\CmsModels\Contracts\Data\ModelFormLayoutNodeInterface;
use Czim\CmsModels\Support\Enums\LayoutNodeType;
use Czim\DataObject\Contracts\DataObjectInterface;
use UnexpectedValueException;

/**
 * Class AbstractModelFormLayoutNodeData
 *
 * @property string $type
 * @property string $label
 * @property string $label_translated
 * @property bool   $required
 * @property array|string[] $children
 * @property null|string    $label_for
 */
class AbstractModelFormLayoutNodeData extends AbstractDataObject implements ModelFormLayoutNodeInterface
{

    /**
     * Returns the type of layout node.
     *
     * @return string
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Returns display label.
     *
     * @return string|null
     */
    public function display()
    {
        if ($this->label_translated) {
            return cms_trans($this->label_translated);
        }

        return $this->label;
    }

    /**
     * Returns whether the field(s) related to this node are required.
     *
     * @return bool
     */
    public function required()
    {
        return (bool) $this->required;
    }

    /**
     * Converts attributes to specific dataobjects if configured to
     *
     * @param string $key
     * @return mixed|DataObjectInterface
     */
    public function &getAttributeValue($key)
    {
        if ($key !== 'children') {
            return parent::getAttributeValue($key);
        }

        if ( ! isset($this->attributes[$key]) || ! is_array($this->attributes[$key])) {
            $null = null;
            return $null;
        }

        // If object is an array, interpret it by type and make the corresponding data object.
        $this->decorateChildrenAttribute($key);

        return $this->attributes[$key];
    }

    /**
     * @param string $topKey
     */
    protected function decorateChildrenAttribute($topKey = 'children')
    {
        foreach ($this->attributes[$topKey] as $key => &$value) {

            if (is_array($value)) {

                $type = strtolower(array_get($value, 'type', ''));

                switch ($type) {

                    case LayoutNodeType::FIELDSET:
                        $value = new ModelFormFieldsetData($value);
                        break;

                    case LayoutNodeType::GROUP:
                        $value = new ModelFormFieldGroupData($value);
                        break;

                    case LayoutNodeType::LABEL:
                        $value = new ModelFormFieldLabelData($value);
                        break;

                    default:
                        throw new UnexpectedValueException("Unknown or unacceptable layout node type '{$type}'");
                }
            }
        }

        unset ($value);
    }

    /**
     * Returns nested nodes or field keys.
     *
     * @return string[]|ModelFormLayoutNodeInterface[]
     */
    public function children()
    {
        return $this->children;
    }

    /**
     * Returns list of keys of form fields that are descendants of this tab.
     *
     * @return string[]
     */
    public function descendantFieldKeys()
    {
        $keys = [];

        foreach ($this->children() as $key => $node) {

            if ($node instanceof ModelFormLayoutNodeInterface) {
                $keys = array_merge($keys, $node->descendantFieldKeys());
                continue;
            }

            $keys[] = $node;
        }

        return $keys;
    }

    /**
     * Returns what field (key) the label for the node should connected with.
     *
     * @return string|null
     */
    public function labelFor()
    {
        return $this->label_for;
    }

}
