<?php  namespace Kris\LaravelFormBuilder\Fields;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Kris\LaravelFormBuilder\Form;
use Kris\LaravelFormBuilder\FormHelper;
use Kris\LaravelFormBuilder\RulesParser;

/**
 * Class FormField
 *
 * @package Kris\LaravelFormBuilder\Fields
 */
abstract class FormField
{
    /**
     * Name of the field
     *
     * @var
     */
    protected $name;

    /**
     * Type of the field
     *
     * @var
     */
    protected $type;

    /**
     * All options for the field
     *
     * @var
     */
    protected $options = [];

    /**
     * Is field rendered
     *
     * @var bool
     */
    protected $rendered = false;

    /**
     * @var Form
     */
    protected $parent;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var FormHelper
     */
    protected $formHelper;

    /**
     * Name of the property for value setting
     *
     * @var string
     */
    protected $valueProperty = 'value';

    /**
     * Name of the property for default value
     *
     * @var string
     */
    protected $defaultValueProperty = 'default_value';

    /**
     * Is default value set?
     * @var bool
     */
    protected $hasDefault = false;

    /**
     * @var \Closure|null
     */
    protected $valueClosure = null;

    protected $css = [];
    protected $js = [];
    protected $js_content = '';

    public function getCss()
    {
        return $this->css;
    }
    public function getJs()
    {
        return $this->js;
    }
    public function getJsContent()
    {
        return $this->js_content;
    }
    /**
     * @param             $name
     * @param             $type
     * @param Form        $parent
     * @param array       $options
     */
    public function __construct($name, $type, Form $parent, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->parent = $parent;
        $this->formHelper = $this->parent->getFormHelper();
        $this->setTemplate();
        $this->setDefaultOptions($options);
        $this->setupValue();
    }

    protected function setupValue()
    {
        $value = $this->getOption($this->valueProperty);
        $isChild = $this->getOption('is_child');

        if ($value instanceof \Closure) {
            $this->valueClosure = $value;
        }

        if (($value === null || $value instanceof \Closure) && !$isChild) {
            $this->setValue($this->getModelValueAttribute($this->parent->getModel(), $this->name));
        } elseif (!$isChild) {
            $this->hasDefault = true;
        }
    }

    /**
     * Get the template, can be config variable or view path
     *
     * @return string
     */
    abstract protected function getTemplate();

    /**
     * @return string
     */
    protected function getViewTemplate()
    {
        return $this->parent->getTemplatePrefix() . $this->getOption('template', $this->template);
    }

    /**
     * @param array $options
     * @param bool  $showLabel
     * @param bool  $showField
     * @param bool  $showError
     * @return string
     */
    public function render(array $options = [], $showLabel = true, $showField = true, $showError = true)
    {

        $this->prepareOptions($options);
        $value = $this->getValue();
        $defaultValue = $this->getDefaultValue();

        if ($showField) {
            $this->rendered = true;
        }

        // Override default value with value
        if (!$value && $defaultValue) {
            $this->setOption($this->valueProperty, $defaultValue);
        }

        if (!$this->needsLabel()) {
            $showLabel = false;
        }

        if ($showError) {
            $showError = $this->parent->haveErrorsEnabled();
        }

        return $this->formHelper->getView()->make(
            $this->getViewTemplate(),
            [
                'name' => $this->name,
                'nameKey' => $this->getNameKey(),
                'type' => $this->type,
                'options' => $this->options,
                'showLabel' => $showLabel,
                'showField' => $showField,
                'showError' => $showError
            ]
        )->render();
    }

    /**
     * Get the attribute value from the model by name
     *
     * @param mixed $model
     * @param string $name
     * @return mixed
     */
    protected function getModelValueAttribute($model, $name)
    {
        $transformedName = $this->transformKey($name);
        if (is_string($model)) {
            return $model;
        } elseif (is_object($model)) {
            return object_get($model, $transformedName);
        } elseif (is_array($model)) {
            return array_get($model, $transformedName);
        }
    }

    /**
     * Transform array like syntax to dot syntax
     *
     * @param $key
     * @return mixed
     */
    protected function transformKey($key)
    {
        return $this->formHelper->transformToDotSyntax($key);
    }

    /**
     * Prepare options for rendering
     *
     * @param array $options
     * @return array
     */
    protected function prepareOptions(array $options = [])
    {

        $helper = $this->formHelper;
        $this->options = $helper->mergeOptions($this->options, $options);

        if ($this->getOption('attr.multiple') && !$this->getOption('tmp.multipleBracesSet')) {
            $this->name = $this->name.'[]';
            $this->setOption('tmp.multipleBracesSet', true);
        }

        if ($this->parent->haveErrorsEnabled()) {
            $this->addErrorClass();
        }

        if ($this->getOption('required') === true) {
            $lblClass = $this->getOption('label_attr.class', '');
            $requiredClass = $helper->getConfig('defaults.required_class', 'required');
            if (!str_contains($lblClass, $requiredClass)) {
                $lblClass .= ' ' . $requiredClass;
                $this->setOption('label_attr.class', $lblClass);
                $this->setOption('attr.required', 'required');
            }
        }

        if ($this->parent->clientValidationEnabled() && $rules = $this->getOption('rules')) {
            $rulesParser = new RulesParser($this);
            $attrs = $this->getOption('attr') + $rulesParser->parse($rules);
            $this->setOption('attr', $attrs);
        }

        $this->setOption('wrapperAttrs', $helper->prepareAttributes($this->getOption('wrapper')));
        $this->setOption('errorAttrs', $helper->prepareAttributes($this->getOption('errors')));

        if ($this->getOption('is_child')) {
            $this->setOption('labelAttrs', $helper->prepareAttributes($this->getOption('label_attr')));
        }

        if ($this->getOption('help_block.text')) {
            $this->setOption(
                'help_block.helpBlockAttrs',
                $helper->prepareAttributes($this->getOption('help_block.attr'))
            );
        }

        return $this->options;
    }

    /**
     * Get name of the field
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name of the field
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get dot notation key for fields
     *
     * @return string
     **/
    public function getNameKey()
    {
        return $this->transformKey($this->name);
    }

    /**
     * Get field options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get single option from options array. Can be used with dot notation ('attr.class')
     *
     * @param        $option
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($option, $default = null)
    {
        return array_get($this->options, $option, $default);
    }

    /**
     * Set field options
     *
     * @param array $options
     * @return $this
     */
    public function setOptions($options)
    {
        $this->options = $this->prepareOptions($options);

        return $this;
    }

    /**
     * Set single option on the field
     *
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function setOption($name, $value)
    {
        array_set($this->options, $name, $value);

        return $this;
    }

    /**
     * Get the type of the field
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type of the field
     *
     * @param mixed $type
     * @return $this
     */
    public function setType($type)
    {
        if ($this->formHelper->getFieldType($type)) {
            $this->type = $type;
        }

        return $this;
    }

    /**
     * @return Form
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Check if the field is rendered
     *
     * @return bool
     */
    public function isRendered()
    {
        return $this->rendered;
    }

    /**
     * Default options for field
     *
     * @return array
     */
    protected function getDefaults()
    {
        return [];
    }

    /**
     * Defaults used across all fields
     *
     * @return array
     */
    private function allDefaults()
    {
        $class_val = '';
        if($this->type == 'submit')
        {
            $class_val = $this->formHelper->getConfig('defaults.submit_class');
        }
        elseif($this->type == 'reset')
        {
            $class_val = $this->formHelper->getConfig('defaults.reset_class');
        }
        else
        {
            $class_val = $this->formHelper->getConfig('defaults.field_class');
        }

        return [
            'wrapper' => ['class' => $this->formHelper->getConfig('defaults.wrapper_class')],
            'attr' => ['class' => $class_val],
            'help_block' => ['text' => null, 'tag' => 'p', 'attr' => [
                'class' => $this->formHelper->getConfig('defaults.help_block_class')
            ]],
            'value' => null,
            'default_value' => null,
            'label' => $this->formHelper->formatLabel($this->getRealName()),
            'is_child' => false,
            'label_attr' => ['class' => $this->formHelper->getConfig('defaults.label_class')],
            'errors' => ['class' => $this->formHelper->getConfig('defaults.error_class')],
            'rules' => []
        ];
    }

    /**
     * Get real name of the field without form namespace
     *
     * @return string
     */
    public function getRealName()
    {
        return $this->getOption('real_name', $this->name);
    }

    /**
     * @param $value
     * @return $this
     */
    public function setValue($value)
    {
        if ($this->hasDefault) {
            return $this;
        }

        $closure = $this->valueClosure;

        if ($closure instanceof \Closure) {
            $value = $closure($value ?: null);
        }

        if ($value === null || $value === false) {
            $value = $this->getOption($this->defaultValueProperty);
        }

        $this->options[$this->valueProperty] = $value;

        return $this;
    }

    /**
     * Set the template property on the object
     */
    private function setTemplate()
    {
        $this->template = $this->formHelper->getConfig($this->getTemplate(), $this->getTemplate());
    }

    /**
     * Add error class to wrapper if validation errors exist
     */
    protected function addErrorClass()
    {
        $errors = $this->parent->getRequest()->session()->get('errors');

        if ($errors && $errors->has($this->getNameKey())) {
            $errorClass = $this->formHelper->getConfig('defaults.wrapper_error_class');
            $wrapperClass = $this->getOption('wrapper.class');

            if ($this->getOption('wrapper') && !str_contains($wrapperClass, $errorClass)) {
                $wrapperClass .= ' ' . $errorClass;
                $this->setOption('wrapper.class', $wrapperClass);
            }
        }
    }


    /**
     * Merge all defaults with field specific defaults and set template if passed
     *
     * @param array $options
     */
    protected function setDefaultOptions(array $options = [])
    {
        $this->options = $this->formHelper->mergeOptions($this->allDefaults(), $this->getDefaults());
        $this->options = $this->prepareOptions($options);
    }

    /**
     * Check if fields needs label
     *
     * @return bool
     */
    protected function needsLabel()
    {
        // If field is <select> and child of choice, we don't need label for it
        $isChildSelect = $this->type == 'select' && $this->getOption('is_child') === true;

        if ($this->type == 'hidden' || $isChildSelect) {
            return false;
        }

        return true;
    }

    /**
     * Disable field
     *
     * @return $this
     */
    public function disable()
    {
        $this->setOption('attr.disabled', 'disabled');

        return $this;
    }

    /**
     * Enable field
     *
     * @return $this
     */
    public function enable()
    {
        array_forget($this->options, 'attr.disabled');

        return $this;
    }

    /**
     * Get validation rules for a field if any with label for attributes
     *
     * @return array|null
     */
    public function getValidationRules()
    {
        $rules = $this->getOption('rules', []);
        $name = $this->getNameKey();

        if (!$rules) {
            return null;
        }

        return [
            'rules' => [$name => $rules],
            'attributes' => [$name => $this->getOption('label')]
        ];
    }

    /**
     * Get value property
     *
     * @param mixed|null $default
     * @return mixed
     */
    public function getValue($default = null)
    {
        return $this->getOption($this->valueProperty, $default);
    }

    /**
     * Get default value property
     *
     * @param mixed|null $default
     * @return mixed
     */
    public function getDefaultValue($default = null)
    {
        return $this->getOption($this->defaultValueProperty, $default);
    }
}
