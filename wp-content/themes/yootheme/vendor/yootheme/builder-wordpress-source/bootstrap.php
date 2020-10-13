<?php

namespace YOOtheme\Builder\Wordpress\Source;

use YOOtheme\Builder;
use YOOtheme\Builder\Source\SourceTransform;
use YOOtheme\Path;

return [

    'routes' => [
        ['get', '/wordpress/posts', [SourceController::class, 'posts']],
    ],

    'events' => [

        'source.init' => [
            SourceListener::class => 'initSource',
        ],

        'customizer.init' => [
            SourceListener::class => 'initCustomizer',
        ],

        'builder.template' => [
            TemplateListener::class => ['onTemplate', 5],
        ],

    ],

    'filters' => [

        'template_include' => [
            TemplateListener::class => ['onTemplateInclude', 20],
        ],

        'wp_link_query_args' => [
            SourceListener::class => 'addPostTypeFilter',
        ],

    ],

    'extend' => [

        Builder::class => function (Builder $builder) {
            $builder->addTypePath(Path::get('./elements/*/element.json'));
        },

        SourceTransform::class => function (SourceTransform $transform) {

            $transform->addFilter('date', function ($value, $format) {

                if (is_string($value)) {
                    $value = strtotime($value);
                }

                return date_i18n($format ?: get_option('date_format', 'd/m/Y'), intval($value) ?: time());
            });

        },

    ],

];
