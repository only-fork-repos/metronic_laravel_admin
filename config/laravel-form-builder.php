<?php

return [
    'defaults'      => [
        'wrapper_class'       => 'form-group',
        'wrapper_error_class' => 'has-error',
        'label_class'         => 'col-md-3 control-label',
        'field_class'         => 'form-control',
        'submit_class'        => 'btn green',
        'reset_class'         => 'btn default',
        'help_block_class'    => 'help-block',
        'error_class'         => 'text-danger',
        'required_class'      => 'required'
    ],
    // Templates
    'form'          => 'laravel-form-builder::form',
    'text'          => 'laravel-form-builder::text',
    'textarea'      => 'laravel-form-builder::textarea',
    'button'        => 'laravel-form-builder::button',
    'radio'         => 'laravel-form-builder::radio',
    'checkbox'      => 'laravel-form-builder::checkbox',
    'select'        => 'laravel-form-builder::select',
    'choice'        => 'laravel-form-builder::choice',
    'repeated'      => 'laravel-form-builder::repeated',
    'child_form'    => 'laravel-form-builder::child_form',
    'collection'    => 'laravel-form-builder::collection',
    'static'        => 'laravel-form-builder::static',

    // Remove the laravel-form-builder:: prefix above when using template_prefix
    'template_prefix'   => '',
    'default_namespace' => '',

    'custom_fields' => [
//        'datetime' => 'App\Forms\Fields\Datetime'
          'multiselect'=>'App\Http\Widgets\MultiSelectField',
          'area'=>'App\Http\Widgets\AreaField',
          'select_extend'=>'App\Http\Widgets\SelectExtendField',
          'editor'=>'App\Http\Widgets\EditorField',

    ]
];
