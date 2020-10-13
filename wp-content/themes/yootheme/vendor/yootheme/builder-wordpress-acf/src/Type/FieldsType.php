<?php

namespace YOOtheme\Builder\Wordpress\Acf\Type;

use YOOtheme\Builder\Source;
use YOOtheme\Str;

class FieldsType
{
    /**
     * @param  Source $source
     * @param  array  $fields
     * @return array
     */
    public static function config(Source $source, array $fields)
    {
        return [

            'fields' => array_filter(array_map(function ($field) use ($source) {

                $type = Str::camelCase($field['type'], true);

                $config = [
                    'type' => 'String',
                    'metadata' => [
                        'label' => $field['label'] ?: $field['name'],
                        'group' => $field['group']['title'],
                    ],
                    'extensions' => [
                        'call' => __CLASS__. '::resolve',
                    ],
                ];

                if (is_callable($callback = [__CLASS__, "config{$type}"])) {
                    return $callback($field, $config, $source);
                }

                return static::configField($field, $config, $source);

            }, $fields)),

        ];
    }

    protected static function configField($field, array $config, Source $source)
    {
        if (isset($field['choices'])) {
            return static::configChoices($field, $config);
        }

        if (in_array($field['type'], ['file', 'image'])) {
            return static::configAttachment($field, $config);
        }

        if (in_array($field['type'], ['time_picker', 'date_time_picker'])) {
            return static::configDatePicker($field, $config);
        }

        if (in_array($field['type'], ['relationship'])) {
            return static::configPostObject($field, $config);
        }

        if (isset($field['sub_fields'])) {
            return static::configSubfields($field, $config, $source);
        }

        if (in_array($field['type'], ['text', 'text_area', 'wysiwyg'])) {
            $config = array_merge_recursive($config, ['metadata' => ['filters' => ['limit']]]);
        }

        if (static::isMultiple($field)) {
            return ['type' => ['listOf' => 'ValueField']] + $config;
        }

        return $config;
    }

    protected static function configDatePicker($field, array $config)
    {
        return array_merge_recursive($config, ['metadata' => ['filters' => ['date']]]);
    }

    protected static function configPostObject($field, $config)
    {
        if (empty($field['post_type']) || count($field['post_type']) > 1) {
            return;
        }

        if (!$type = static::getPostType(array_pop($field['post_type']))) {
            return;
        }

        $type = Str::camelCase($type->name, true);

        return ['type' => static::isMultiple($field) ? ['listOf' => $type] : $type] + $config;
    }

    protected static function configTaxonomy($field, $config)
    {
        $taxonomy = !empty($field['taxonomy']) ? static::getTaxonomy($field['taxonomy']) : false;

        if (!$taxonomy) {
            return;
        }

        $taxonomy = Str::camelCase($taxonomy->name, true);

        return ['type' => static::isMultiple($field) ? ['listOf' => $taxonomy] : $taxonomy] + $config;
    }

    protected static function configUser($field, array $config)
    {
        return ['type' => static::isMultiple($field) ? ['listOf' => 'User'] : 'User'] + $config;
    }

    protected static function configChoices($field, array $config)
    {
        return ['type' => static::isMultiple($field) ? ['listOf' => 'ChoiceField'] : 'ChoiceField'] + $config;
    }

    protected static function configLink($field, array $config)
    {
        return ['type' => 'LinkField'] + $config;
    }

    protected static function configGoogleMap($field, array $config)
    {
        return ['type' => 'GoogleMapsField'] + $config;
    }

    protected static function configAttachment($field, array $config)
    {
        return ['type' => 'Attachment'] + $config;
    }

    protected static function configGallery($field, array $config)
    {
        return ['type' => ['listOf' => 'Attachment']] + $config;
    }

    protected static function configSubfields($field, array $config, Source $source)
    {
        $fields = [];

        foreach ($field['sub_fields'] as $sub_field) {
            $fields[$sub_field['name']] = static::configField($sub_field, [
                'type' => 'String',
                'metadata' => [
                    'label' => $sub_field['label'] ?: $sub_field['name'],
                    'group' => $field['label'] ?: $field['name'],
                ],
            ], $source);
        }

        if ($fields) {

            $name = Str::camelCase(['Field', $field['name']], true);
            $source->objectType($name, compact('fields'));

            return ['type' => static::isMultiple($field) ? ['listOf' => $name] : $name] + $config;
        }
    }

    public static function field($post, $args, $context, $info)
    {
        return $post;
    }

    public static function resolve($post, $args, $context, $info)
    {
        if ($field = acf_get_field($info->fieldName)) {
            return static::getField($post, $field);
        }
    }

    protected static function getField($post, array $field)
    {
        $postId = acf_get_valid_post_id($post);

        // Subfields field
        if (array_key_exists('sub_fields', $field)) {

            $values = [];

            if (empty($field['sub_fields'])) {
                return;
            }

            if (static::isMultiple($field)) {

                $value = acf_get_metadata($postId, $field['name']);

                for ($i = 0; $i < $value; $i++) {
                    foreach ($field['sub_fields'] as $subfield) {
                        $values[$i][$subfield['name']] = static::getField($post, ['name' => "{$field['name']}_{$i}_{$subfield['name']}"] + $subfield);
                    }
                }

                return $values;
            }

            foreach ($field['sub_fields'] as $subfield) {
                $values[$subfield['name']] = static::getField($post, $subfield);
            }

            return $values;
        }

        switch ($field['type']) {
            case 'post_object':
            case 'relationship':
            case 'taxonomy':
            case 'user':
                $field['return_format'] = 'object';
                break;
            case 'button_group':
            case 'checkbox':
            case 'radio':
            case 'select':
            case 'link':
                $field['return_format'] = 'array';
                break;
            case 'file':
            case 'gallery':
            case 'image':
                $field['return_format'] = 'id';
                break;
            case 'time_picker':
            case 'date_picker':
            case 'date_time_picker':
                $field['return_format'] = 'Y-m-d H:i:s';
                break;
        }

        // get value for field
        if (is_null($value = acf_get_value($postId, $field))) {
            return null;
        }

        $value = acf_format_value($value, $postId, $field);

        if (!empty($field['return_format'])) {
            return $value ?: null;
        }

        if (static::isMultiple($field)) {
            return array_map(function ($value) { return compact('value'); }, $value);
        }

        return $value;
    }

    protected static function getTaxonomy($taxonomy)
    {
        global $wp_taxonomies;

        if (empty($wp_taxonomies[$taxonomy]->rest_base) || $wp_taxonomies[$taxonomy]->name === $wp_taxonomies[$taxonomy]->rest_base) {
            return;
        }

        return $wp_taxonomies[$taxonomy];
    }

    protected static function getPostType($post_type)
    {
        global $wp_post_types;

        if (empty($wp_post_types[$post_type]->rest_base) || $wp_post_types[$post_type]->name === $wp_post_types[$post_type]->rest_base) {
            return;
        }

        return $wp_post_types[$post_type];
    }

    protected static function isMultiple(array $field)
    {
        return !empty($field['multiple']) && $field['multiple']
            || in_array($field['type'], ['checkbox', 'gallery', 'relationship'])
            || !empty($field['field_type']) && !in_array($field['field_type'], ['select', 'radio'])
            || isset($field['sub_fields'], $field['max']);
    }
}
