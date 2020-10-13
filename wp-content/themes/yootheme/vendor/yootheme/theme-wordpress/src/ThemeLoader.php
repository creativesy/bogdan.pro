<?php

namespace YOOtheme\Theme\Wordpress;

use YOOtheme\Application;
use YOOtheme\Arr;
use YOOtheme\Config;
use YOOtheme\Container;
use YOOtheme\Event;
use YOOtheme\Path;
use YOOtheme\Theme\Updater;

class ThemeLoader
{
    /**
     * @var array
     */
    protected $configs = [];

    /**
     * Constructor.
     *
     * @link https://developer.wordpress.org/reference/hooks/after_setup_theme/
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        add_action('after_setup_theme', $app->wrap([$this, 'setupTheme']));
        add_action('wp_loaded', $app->wrap([$this, 'initTheme']));
    }

    /**
     * Load theme configurations.
     *
     * @param Container $container
     * @param array     $configs
     */
    public function __invoke(Container $container, array $configs)
    {
        $this->configs = array_merge($this->configs, $configs);
    }

    /**
     * Setup theme.
     *
     * @param Application $app
     * @param Config      $configuration
     */
    public function setupTheme(Application $app, Config $configuration)
    {
        // load childtheme config
        if (is_child_theme()) {
            $app->load(get_stylesheet_directory() . '/config.php');
        }

        // add configurations
        foreach ($this->configs as $config) {

            if ($config instanceof \Closure) {
                $config = $config($configuration, $app);
            }

            $configuration->add('theme', (array) $config);
        }

        $configuration->add('theme', [
            'id' => get_current_blog_id(),
            'default' => is_main_site(),
            'template' => basename($configuration('theme.rootDir')),
        ]);
    }

    /**
     * Initialize theme.
     *
     * @param Application $app
     * @param Config      $configuration
     */
    public function initTheme(Application $app, Config $configuration)
    {
        // add update scripts
        $updater = $app(Updater::class);
        $updater->add(Path::get('../updates.php'));

        // merge defaults with configuration
        $config = $updater->update(json_decode(get_theme_mod('config', '{}'), true), ['app' => $app]);
        $configuration->set('~theme', Arr::merge($configuration('theme.defaults', []), $config));

        Event::emit('theme.init');
    }
}
