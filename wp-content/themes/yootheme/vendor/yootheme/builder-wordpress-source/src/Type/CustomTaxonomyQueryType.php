<?php

namespace YOOtheme\Builder\Wordpress\Source\Type;

use YOOtheme\Str;

class CustomTaxonomyQueryType
{
    /**
     * @param  \WP_Taxonomy $taxonomy
     * @return array
     */
    public static function config(\WP_Taxonomy $taxonomy)
    {
        $name = Str::camelCase($taxonomy->name, true);
        $base = Str::camelCase($taxonomy->rest_base, true);

        $plural = Str::lower($taxonomy->label);
        $singular = Str::lower($taxonomy->labels->singular_name);

        return [

            'fields' => [

                "custom{$name}" => [

                    'type' => $name,

                    'args' => [
                        'id' => [
                            'type' => 'Int',
                        ],
                    ],

                    'metadata' => [
                        'label' => "Custom {$taxonomy->labels->singular_name}",
                        'group' => 'Custom',
                        'fields' => [
                            'id' => [
                                'label' => $taxonomy->labels->singular_name,
                                'type' => 'select-term',
                                'taxonomy' => $taxonomy->name,
                            ],
                        ],
                    ],

                    'extensions' => [
                        'call' => __CLASS__ . '::resolveTerm',
                    ],

                ],

                "custom{$base}" => [

                    'type' => [
                        'listOf' => $name,
                    ],

                    'args' => [
                        'id' => [
                            'type' => 'Int',
                        ],
                        'offset' => [
                            'type' => 'Int',
                        ],
                        'limit' => [
                            'type' => 'Int',
                        ],
                        'order' => [
                            'type' => 'String',
                        ],
                        'order_direction' => [
                            'type' => 'String',
                        ],
                    ],

                    'metadata' => [
                        'label' => "Custom {$taxonomy->label}",
                        'group' => 'Custom',
                        'fields' => [
                            'id' => [
                                'label' => "Parent {$taxonomy->labels->singular_name}",
                                'description' => "{$taxonomy->label} are only loaded from the selected parent {$singular}.",
                                'type' => 'select-term',
                                'taxonomy' => $taxonomy->name,
                                'root' => true,
                                'default' => 0,
                            ],
                            '_offset' => [
                                'description' => "Set the starting point and limit the number of {$plural}.",
                                'type' => 'grid',
                                'width' => '1-2',
                                'fields' => [
                                    'offset' => [
                                        'label' => 'Start',
                                        'type' => 'number',
                                        'default' => 0,
                                        'modifier' => 1,
                                        'attrs' => [
                                            'min' => 1,
                                            'required' => true,
                                        ],
                                    ],
                                    'limit' => [
                                        'label' => 'Quantity',
                                        'type' => 'limit',
                                        'default' => 10,
                                        'attrs' => [
                                            'min' => 1,
                                        ],
                                    ],
                                ],
                            ],
                            '_order' => [
                                'type' => 'grid',
                                'width' => '1-2',
                                'fields' => [
                                    'order' => [
                                        'label' => 'Order',
                                        'type' => 'select',
                                        'default' => 'term_order',
                                        'options' => [
                                            'Term Order' => 'term_order',
                                            'Alphabetical' => 'name',
                                        ],
                                    ],
                                    'order_direction' => [
                                        'label' => 'Direction',
                                        'type' => 'select',
                                        'default' => 'ASC',
                                        'options' => [
                                            'Ascending' => 'ASC',
                                            'Descending' => 'DESC',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],

                    'extensions' => [

                        'call' => [
                            'func' => __CLASS__ . '::resolveTerms',
                            'args' => ['taxonomy' => $taxonomy->name],
                        ],

                    ],

                ],

            ],

        ];
    }

    public static function resolveTerm($root, array $args)
    {
        $args += ['id' => 0];

        $term = get_term($args['id']);

        return $term instanceof \WP_Term ? $term : null;
    }

    public static function resolveTerms($root, array $args)
    {
        $query = [
            'taxonomy' => $args['taxonomy'],
            'parent' => $args['id'],
            'orderby' => $args['order'],
            'order' => $args['order_direction'],
            'number' => $args['limit'],
            'offset' => $args['offset'],
        ];

        return get_terms($query);
    }
}
