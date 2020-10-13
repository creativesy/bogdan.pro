<?php

namespace YOOtheme\Builder\Wordpress\Source\Type;

class SiteQueryType
{
    /**
     * @return array
     */
    public static function config()
    {
        return [

            'fields' => [

                'site' => [
                    'type' => 'Site',
                    'metadata' => [
                        'label' => 'Site',
                    ],
                    'extensions' => [
                        'call' => __CLASS__ . '::resolve',
                    ],
                ],

            ],

        ];
    }

    public static function resolve()
    {
        return [
            'title' => get_bloginfo('name', 'display'),
            'page_title' => wp_title('&raquo;', false),
        ];
    }
}
