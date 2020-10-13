<?php

namespace YOOtheme\Builder\Wordpress\Toolset\Type;

use YOOtheme\Builder\Wordpress\Toolset\Helper;
use YOOtheme\Str;

class FieldsType
{
    public static function config($source, $fields)
    {
        return [

            'fields' => array_filter(array_map(function ($field) use ($source) {

                $config = [
                    'type' => 'String',
                    'name' => strtr($field['slug'], '-', '_'),
                    'metadata' => [
                        'label' => $field['name'],
                        'group' => $field['group'],
                    ],
                ];

                if ($field['type'] !== 'rfg') {

                    return Helper::loadField($field, $config + [

                        'extensions' => [
                            'call' => [
                                'func' => __CLASS__ . '::resolveField',
                                'args' => ['slug' => $field['slug']],
                            ],
                        ],

                    ]);

                }

                    return static::loadRfgField($source, $field, $config + [

                        'extensions' => [

                            'call' => [
                                'func' => __CLASS__ . '::resolveRfgField',
                                'args' => ['slug' => $field['slug']],
                            ],

                        ],

                    ]);

            }, $fields)),

        ];
    }

    public static function toolset($post, $args, $context, $info)
    {
        return $post;
    }

    public static function resolveField($item, $args, $context, $info)
    {
        $fieldService = new \Types_Field_Service(false);

        if ($item instanceof \WP_Post) {
            $fieldInstance = $fieldService->get_field(new \Types_Field_Gateway_Wordpress_Post(), $args['slug'], $item->ID);
        } elseif ($item instanceof \WP_Term) {
            $fieldInstance = $fieldService->get_field(new \Types_Field_Gateway_Wordpress_Term(), $args['slug'], $item->term_id);
        } elseif ($item instanceof \WP_User) {
            $fieldInstance = $fieldService->get_field(new \Types_Field_Gateway_Wordpress_User(), $args['slug'], $item->ID);
        }

        if ($fieldInstance) {
            return Helper::getFieldValue($fieldInstance);
        }
    }

    protected static function loadRfgField($source, $field, array $config)
    {
        $type = Str::camelCase(['toolset', $field['slug'], 'group'], true);
        $source->objectType($type, GroupType::config($field));

        return ['type' => ['listOf' => $type]] + $config;
    }

    public static function resolveRfgField($item, $args, $context, $info)
    {
        $rfg_service = new \Types_Field_Group_Repeatable_Service();
        $repeatableGroup = $rfg_service->get_object_from_prefixed_string($args['slug']);

        if (!$repeatableGroup) {
            return;
        }

        $rfg = $rfg_service->get_object_by_id($repeatableGroup->get_id(), $item);

        if (!$rfg) {
            return;
        }

        return $rfg->get_posts();
    }
}
