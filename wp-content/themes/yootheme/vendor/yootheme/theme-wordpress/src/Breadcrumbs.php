<?php

namespace YOOtheme\Theme\Wordpress;

class Breadcrumbs
{
    public static function getItems($options = [])
    {
        $options += [
            'show_current' => true,
            'show_home' => true,
            'home_text' => '',
        ];

        $items = [];

        if (!is_front_page()) {
            if (is_page() || is_home() && get_queried_object_id() == get_option('page_for_posts')) {
                $items = static::handlePage();
            } elseif (is_singular('post')) {
                $items = static::handlePost();
            } elseif (is_singular()) {
                $items = static::handleSingular();
            } elseif (is_category()) {
                $items = static::handleCategory();
            } elseif (is_tag()) {
                $items = static::handleTag();
            } elseif (is_date()) {
                $items = static::handleDate();
            } elseif (is_author()) {
                $items = static::handleAuthor();
            } elseif (is_search()) {
                $items = static::handleSearch();
            } elseif (is_tax()) {
                $items = static::handleTax();
            } elseif (is_post_type_archive()) {
                $items = static::handlePostTypeArchive();
            } elseif (is_archive()) {
                $items = static::handleArchive();
            }
        }

        if ($options['show_home']) {
            array_unshift($items, [
                'name' => $options['home_text'] ? __($options['home_text'], 'yootheme') : __('Home'),
                'link' => get_option('home'),
            ]);
        }

        if (!$options['show_current']) {
            array_pop($items);
        } elseif ($items) {
            $items[count($items) - 1]['link'] = '';
        }

        return array_map(function ($item) {
            return (object) $item;
        }, $items);
    }

    protected static function handlePage($id = null)
    {
        $id = isset($id) ? $id : get_queried_object_id();

        if (!$id) {
            return [];
        }

        $items[] = ['name' => get_the_title($id), 'link' => get_page_link($id)];

        foreach (get_ancestors($id, 'page') as $ancestor) {
            $items[] = ['name' => get_the_title($ancestor), 'link' => get_page_link($ancestor)];
        }

        return array_reverse($items);
    }

    protected static function handlePost()
    {
        if ($categories = get_the_category() and $category = $categories[0] and is_object($category)) {
            $items = static::getCategories($category);
        }

        $items[] = ['name' => get_the_title(), 'link' => ''];

        return $items;
    }

    protected static function handleSingular()
    {
        $post = get_queried_object();

        if ($type = static::getPostType($post->post_type)) {
            $items[] = $type;
        }

        if ($term = static::getPostTerm($post)) {
            $items[] = $term;
        }

        $items[] = ['name' => get_the_title(), 'link' => ''];

        return $items;
    }

    protected static function handleCategory()
    {
        return static::getCategories(get_queried_object());
    }

    protected static function handleTag()
    {
        $items = [];

        if (get_option('show_on_front') == 'page') {
            $items = static::handlePage(get_option('page_for_posts'));
        }

        $items[] = ['name' => single_tag_title('', false), 'link' => ''];

        return $items;
    }

    protected static function handleDate()
    {
        return [['name' => single_month_title(' ', false), 'link' => '']];
    }

    protected static function handleAuthor()
    {
        $user = get_queried_object();
        return [['name' => $user->display_name, 'link' => '']];
    }

    protected static function handleSearch()
    {
        return [['name' => stripslashes(strip_tags(get_search_query())), 'link' => '']];
    }

    protected static function handleTax()
    {
        $term = get_queried_object();
        $taxonomy = get_taxonomy($term->taxonomy);

        if (!empty($taxonomy->object_type) && $type = static::getPostType($taxonomy->object_type[0])) {
            $items[] = $type;
        }

        $items[] = ['name' => single_term_title('', false), 'link' => ''];

        return $items;
    }

    protected static function handlePostTypeArchive()
    {
        $item = static::getPostType(get_queried_object(), false);
        return $item ? [$item] : [];
    }

    protected static function handleArchive()
    {
        // WooCommerce shop page
        if (class_exists('WooCommerce') && is_shop()) {
            return [['name' => wc_get_page_id('shop') ? get_the_title(wc_get_page_id('shop')) : '', 'link' => '']];
        }
        return [];
    }

    protected static function getCategories($category, $categories = [])
    {
        if (!$category->parent && get_option('show_on_front') == 'page') {
            $categories = self::handlePage(get_option('page_for_posts'));
        }

        if ($category->parent) {
            $categories = static::getCategories(get_term($category->parent, 'category'), $categories);
        }

        $categories[] = ['name' => $category->name, 'link' => esc_url(get_category_link($category->term_id))];

        return $categories;
    }

    protected static function getPostType($type, $link = true)
    {
        if (is_string($type)) {
            $type = get_post_type_object($type);
        }

        return $type && $type->has_archive ? [
            'name' => apply_filters('post_type_archive_title', $type->labels->name, $type->name),
            'link' => $link ? get_post_type_archive_link($type->name) : '',
        ] : null;
    }

    protected static function getPostTerm($post)
    {
        foreach (get_object_taxonomies($post, 'object') as $taxonomy) {
            if ($taxonomy->public
                && $taxonomy->show_ui
                && $taxonomy->show_in_nav_menus
                && $terms = get_the_terms($post, $taxonomy->name)
            ) {
                return [
                    'name' => apply_filters('single_term_title', $terms[0]->name),
                    'link' => get_term_link($terms[0]),
                ];
            }
        }
    }
}
