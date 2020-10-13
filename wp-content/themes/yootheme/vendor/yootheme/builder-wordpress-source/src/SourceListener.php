<?php

namespace YOOtheme\Builder\Wordpress\Source;

use YOOtheme\Builder\Source;
use YOOtheme\Builder\Source\Type\SiteType;
use YOOtheme\Config;
use YOOtheme\Http\Request;
use YOOtheme\Str;

class SourceListener
{
    public static function initSource($source)
    {
        $types = [
            ['Site', SiteType::config()],
            ['User', Type\UserType::config()],
            ['Attachment', Type\AttachmentType::config()],
        ];

        $arguments = [
            'public' => true,
            'show_ui' => true,
            'show_in_nav_menus' => true,
        ];

        $source->queryType(Type\SiteQueryType::config());

        foreach ($types as $args) {
            $source->objectType(...$args);
        }

        foreach (get_post_types($arguments, 'objects') as $name => $type) {

            if (!$type->rest_base || $name === $type->rest_base) {
                continue;
            }

            $source->queryType(Type\PostQueryType::config($source, $type));
            $source->objectType(Str::camelCase($name, true), Type\PostType::config($type));
        }

        foreach (get_taxonomies($arguments, 'objects') as $name => $taxonomy) {

            if (!$taxonomy->rest_base) {
                continue;
            }

            $source->queryType(Type\TaxonomyQueryType::config($source, $taxonomy));
            $source->objectType(Str::camelCase($name, true), Type\TaxonomyType::config($source, $taxonomy));
        }
    }

    public static function initCustomizer(Config $config)
    {
        $args = [
            'public' => true,
            'show_ui' => true,
            'show_in_nav_menus' => true,
        ];

        $archives = [];

        foreach (get_post_types($args, 'objects') as $name => $type) {

            if (!$type->rest_base || $name === $type->rest_base) {
                continue;
            }

            $templates["single-{$name}"] = [
                'label' => "Single {$type->labels->singular_name}",
            ];

            if ($taxes = get_object_taxonomies($name)) {

                $label_lower = mb_strtolower($type->labels->name);

                $templates["single-{$name}"] += [

                    'fieldset' => [
                        'default' => [
                            'fields' => [
                                'terms' => [
                                    'label' => 'Limit by Terms',
                                    'description' => "The template is only assigned to {$label_lower} with the selected terms. {$type->labels->name} from child terms are not included. Use the <kbd>shift</kbd> or <kbd>ctrl/cmd</kbd> key to select multiple terms.",
                                    'type' => 'select-term',
                                    'taxonomies' => $taxes,
                                    'default' => [],
                                    'attrs' => [
                                        'multiple' => true,
                                        'class' => 'uk-height-medium uk-resize-vertical',
                                    ],
                                ],
                            ],
                        ],
                    ],

                ];

            }

            if ($name === 'post' || $type->has_archive) {

                $archives["archive-{$name}"] = [
                    'label' => "{$type->label} Archive",
                ];

            }

        }

        $templates += $archives;

        foreach (get_taxonomies($args, 'objects') as $name => $taxonomy) {

            $taxonomies[$name] = [
                'label' => $taxonomy->label,
                'terms' => static::getTerms($taxonomy, function ($term) {

                    $name = html_entity_decode($term->name);
                    $level = get_ancestors($term->term_id, $term->taxonomy);

                    return [$term->term_id, str_repeat('- ', count($level)) . $name];
                }),
            ];

            $label_lower = mb_strtolower($taxonomy->labels->name);
            $has_archive = $taxonomy->hierarchical ? "Child {$label_lower} are not included." : '';

            $templates["taxonomy-{$name}"] = [

                'label' => "{$taxonomy->labels->singular_name} Archive",
                'fieldset' => [
                    'default' => [
                        'fields' => [
                            'terms' => [
                                'label' => $taxonomy->label,
                                'description' => "The template is only assigned to the selected {$label_lower}. {$has_archive} Use the <kbd>shift</kbd> or <kbd>ctrl/cmd</kbd> key to select multiple {$label_lower}.",
                                'type' => 'select-term',
                                'taxonomy' => $name,
                                'default' => [],
                                'attrs' => [
                                    'multiple' => true,
                                    'class' => 'uk-height-small uk-resize-vertical',
                                ],
                            ],
                        ],
                    ],
                ],

            ];

        }

        $options = [];

        foreach ([
            'Single Post' => 'single-',
            'Post Type Archive' => 'archive-',
            'Taxonomy Archive' => 'taxonomy-',
        ] as $label => $type) {

            foreach ($templates as $name => $template) {
                if (Str::startsWith($name, $type)) {
                    $options[$label][$template['label']] = $name;
                }
            }
        }

        $config->add('customizer.templates', $templates);
        $config->add('customizer.taxonomies', $taxonomies);
        $config->add('customizer.sections.builder-templates.fieldset.default.fields.type.options', $options);
    }

    public static function addPostTypeFilter(Request $request, $query)
    {
        if ($post_type = $request->getParam('post_type')) {
            return ['post_type' => [$post_type]] + $query;
        }

        return $query;
    }

    protected static function getTerms($taxonomy, callable $callback = null)
    {
        $terms = get_terms([
            'taxonomy' => $taxonomy->name,
            'hide_empty' => false,
        ]);

        if ($taxonomy->hierarchical) {
            $terms = _get_term_children(0, $terms, $taxonomy->name);
        }

        return $callback ? array_map($callback, $terms) : $terms;
    }
}
