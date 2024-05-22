<?php
/**
 * Main plugin class file.
 *
 * @package WordPress Plugin Template/Includes
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class.
 */
class ASR
{

    /**
     * The single instance of ASR.
     *
     * @var     object
     * @access  private
     * @since   1.0.0
     */
    private static $_instance = null; //phpcs:ignore

    /**
     * Local instance of ASR_Admin_API
     *
     * @var ASR_Admin_API|null
     */
    public $admin = null;

    /**
     * Settings class object
     *
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version; //phpcs:ignore

    /**
     * The token.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token; //phpcs:ignore

    /**
     * The main plugin file.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for JavaScripts.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * Constructor funtion.
     *
     * @param string $file File constructor.
     * @param string $version Plugin version.
     */
    public function __construct($file = '', $version = '1.1.0')
    {
        $this->_version = $version;
        $this->_token = 'ASR';

        // Load plugin environment variables.
        $this->file = $file;
        $this->dir = dirname($this->file);
        $this->assets_dir = trailingslashit($this->dir) . 'assets';
        $this->assets_url = esc_url(trailingslashit(plugins_url('/assets/', $this->file)));

        $this->script_suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';


        register_activation_hook($this->file, array($this, 'install'));
        //$this->register_post_type('Downloader', 'Downloaders', 'Downloader', 'Page with video downloader');

        //register_activation_hook($this->file, 'beardbot_plugin_activation');

        // Load frontend JS & CSS.
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'), 10);
        //add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'), 10);

        // Load admin JS & CSS.
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'), 10, 1);
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'), 10, 1);
        //add_filter('admin_footer_text', array($this, 'remove_footer_admin'));
        add_action('wp_before_admin_bar_render', array($this, 'remove_logo_wp_admin'), 0);
        add_action('wp_dashboard_setup', array($this, 'dashboardWidgets'));

        $routes = new ASR_Routes();
        $routes->register_routes();

        // Load API for generic admin functions.
        if (is_admin()) {
            $this->admin = new ASR_Admin_API();
        }

        // Handle localisation.
        $this->load_plugin_textdomain();
        add_action('init', array($this, 'load_localisation'), 0);
        add_action('init', array($this, 'register_session'));
    } // End __construct ()

    public function register_session()
    {
        if (!session_id()) {
            session_start();
        }
    }

    function dashboardWidgets()
    {
        global $wp_meta_boxes;
        //wp_add_dashboard_widget('latest_downloads_widget', 'Latest Downloads', array($this, 'latestDownloadsWidget'));
        //wp_add_dashboard_widget('download_stats_widget', 'Download Stats', array($this, 'downloadStatsWidget'));
       // wp_add_dashboard_widget('news_widget', 'News', array($this, 'newsWidget'));
    }

    function latestDownloadsWidget()
    {
        echo '<table><tr><th>Thumbnail</th><th>Title</th><th>Source</th><th>IP</th></tr>';
        $downloads = json_decode(get_option('asr_latest_downloads'), true);
        if (!empty($downloads) && is_array($downloads)) {
            foreach ($downloads as $download) {
                if (empty($download['thumbnail']) || empty($download['url'])) {
                    continue;
                }
                $image = '<img style="max-width:5vh" src="' . $download['thumbnail'] . '">';
                echo '<tr><td>' . $image . '</td><td><a href="' . $download['url'] . '" target="_blank">' . substr($download['title'], 0, 24) . '...</a></td><td>' . $download['source'] . '</td><td>' . $download['clientIp'] . '</td></tr>';
            }
        }
        echo '</table>';
    }

    function downloadStatsWidget()
    {
        $stats = json_decode(get_option('asr_stats'), true);
        $total = $stats['total'];
        unset($stats['total']);
        echo '<strong>Total Downloads: </strong>' . $total;
        echo '<br>';
        if (!empty($stats)) {
            foreach ($stats as $source => $value) {
                echo '<strong>' . ucwords($source) . ': </strong>' . $value;
                echo '<br>';
            }
        }
    }

    function newsWidget()
    {
        require_once __DIR__ . '/Http.php';
        $http = new Http('https://www.clipsav.com/asaver.json');
        $http->run();
        $news = json_decode($http->response, true);
        echo '<table><tr><th></th><th></th></tr>';
        if (!empty($news)) {
            foreach ($news as $new) {
                echo '<tr><td><a href="' . $new['url'] . '" target="_blank">' . $new['title'] . '</a></td><td>' . $new['date'] . '</td></tr>';
            }
        }
        echo '</table>';
    }

    function remove_logo_wp_admin()
    {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('wp-logo');
    }


    function remove_footer_admin()
    {
        echo '<span id="footer-thankyou">Developed by <a href="https://nicheoffice.web.tr" target="_blank">Niche Office</a></span>';
    }


    function beardbot_plugin_activation()
    {

        if (!current_user_can('activate_plugins')) return;

        global $wpdb;

        if (null === $wpdb->get_row("SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name = 'new-page-slug'", 'ARRAY_A')) {

            $current_user = wp_get_current_user();

            // create post object
            $page = array(
                'post_title' => __('New Page'),
                'post_status' => 'publish',
                'post_author' => $current_user->ID,
                'post_type' => 'page',
            );

            // insert the post into the database
            wp_insert_post($page);
        }
    }

    /**
     * Register post type function.
     *
     * @param string $post_type Post Type.
     * @param string $plural Plural Label.
     * @param string $single Single Label.
     * @param string $description Description.
     * @param array $options Options array.
     *
     * @return bool|string|ASR_Post_Type
     */
    public function register_post_type($post_type = '', $plural = '', $single = '', $description = '', $options = array())
    {

        if (!$post_type || !$plural || !$single) {
            return false;
        }

        $post_type = new ASR_Post_Type($post_type, $plural, $single, $description, $options);

        return $post_type;
    }

    /**
     * Wrapper function to register a new taxonomy.
     *
     * @param string $taxonomy Taxonomy.
     * @param string $plural Plural Label.
     * @param string $single Single Label.
     * @param array $post_types Post types to register this taxonomy for.
     * @param array $taxonomy_args Taxonomy arguments.
     *
     * @return bool|string|ASR_Taxonomy
     */
    public function register_taxonomy($taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array())
    {

        if (!$taxonomy || !$plural || !$single) {
            return false;
        }

        $taxonomy = new ASR_Taxonomy($taxonomy, $plural, $single, $post_types, $taxonomy_args);

        return $taxonomy;
    }

    /**
     * Load frontend CSS.
     *
     * @access  public
     * @return void
     * @since   1.0.0
     */
    public function enqueue_styles()
    {
        wp_register_style($this->_token . '-frontend', esc_url($this->assets_url) . 'css/frontend.css', array(), $this->_version);
        wp_enqueue_style($this->_token . '-frontend');
    } // End enqueue_styles ()

    /**
     * Load frontend Javascript.
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function enqueue_scripts()
    {
        wp_register_script($this->_token . '-frontend', esc_url($this->assets_url) . 'js/frontend' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
        wp_enqueue_script($this->_token . '-frontend');
        wp_localize_script($this->_token . '-frontend', 'WPURLS', array('siteurl' => get_option('siteurl'), 'pluginurl' => plugin_dir_url(dirname(__FILE__)) ));
    } // End enqueue_scripts ()

    /**
     * Admin enqueue style.
     *
     * @param string $hook Hook parameter.
     *
     * @return void
     */
    public function admin_enqueue_styles($hook = '')
    {
        if(isset( $_REQUEST['page'] ) && $_REQUEST['page']==='ASR_settings')
            wp_register_style($this->_token . '-admin', esc_url($this->assets_url) . 'css/admin.css', array(), $this->_version);
        wp_enqueue_style($this->_token . '-admin');

        wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin.js', array('jquery'), $this->_version, true);
        wp_enqueue_script($this->_token . '-admin');
    } // End admin_enqueue_styles ()

    /**
     * Load admin Javascript.
     *
     * @access  public
     *
     * @param string $hook Hook parameter.
     *
     * @return  void
     * @since   1.0.0
     */
    public function admin_enqueue_scripts($hook = '')
    {
        wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin' . $this->script_suffix . '.js', array('jquery'), $this->_version, true);
        wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin.js', array('jquery'), $this->_version, true);
        wp_enqueue_script($this->_token . '-admin');
    } // End admin_enqueue_scripts ()

    /**
     * Load plugin localisation
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function load_localisation()
    {
        load_plugin_textdomain('asr', false, dirname(plugin_basename($this->file)) . '/lang/');
    } // End load_localisation ()

    /**
     * Load plugin textdomain
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function load_plugin_textdomain()
    {
        $domain = 'asr';

        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, false, dirname(plugin_basename($this->file)) . '/lang/');
    } // End load_plugin_textdomain ()

    /**
     * Main ASR Instance
     *
     * Ensures only one instance of ASR is loaded or can be loaded.
     *
     * @param string $file File instance.
     * @param string $version Version parameter.
     *
     * @return Object ASR instance
     * @see ASR()
     * @since 1.0.0
     * @static
     */
    public static function instance($file = '', $version = '1.0.0')
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($file, $version);
        }

        return self::$_instance;
    } // End instance ()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of ASR is forbidden')), esc_attr($this->_version));

    } // End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of ASR is forbidden')), esc_attr($this->_version));
    } // End __wakeup ()

    public function installTranslations()
    {
        $translations = file_get_contents(__DIR__ . '/../lang/translations.json');
        $translations = json_decode($translations, true);
        foreach ($translations as $translation) {
            $language = $translation['meta_id'];
            $postExists = post_exists($language) != 0;
            if (!$postExists) {
                $id = wp_insert_post(array(
                    'post_title' => $language,
                    'post_name' => $language,
                    'post_content' => '',
                    'post_status' => 'private',
                    'comment_status' => 'closed',
                    'ping_status' => 'closed',
                    'post_type' => 'polylang_mo',
                    'post_author' => 1,
                ));
                if (is_numeric($id)) {
                    update_post_meta($id, '_pll_strings_translations', $translation['meta_value']);
                }
            }
        }
        $terms = file_get_contents(__DIR__ . '/../lang/wp_terms.json');
        $terms = json_decode($translations, true);
        foreach ($terms as $term) {
            $termExists = term_exists($term['name']) != null;
            if (!$termExists) {
                wp_insert_term($term['name'], 0, ['slug' => $term['slug']]);
            }
        }
        update_option('_transient_pll_languages_list', file_get_contents(__DIR__ . '/../lang/languages-list.txt'));
    }

    /**
     * Installation. Runs on activation.
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    public function install()
    {
        $this->_log_version_number();
        //$this->installTranslations();
    } // End install ()


    /**
     * Log the plugin version number.
     *
     * @access  public
     * @return  void
     * @since   1.0.0
     */
    private function _log_version_number()
    { //phpcs:ignore
        update_option($this->_token . '_version', $this->_version);
    } // End _log_version_number ()

}