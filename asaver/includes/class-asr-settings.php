<?php
/**
 * Settings class file.
 *
 * @package WordPress Plugin Template/Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class.
 */
class ASR_Settings
{

    /**
     * The single instance of ASR_Settings.
     *
     * @var     object
     * @access  private
     * @since   1.0.0
     */
    private static $_instance = null; //phpcs:ignore

    /**
     * The main plugin object.
     *
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $parent = null;

    /**
     * Prefix for plugin settings.
     *
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $base = '';

    /**
     * Available settings for plugin.
     *
     * @var     array
     * @access  public
     * @since   1.0.0
     */
    public $settings = array();

    /**
     * Constructor function.
     *
     * @param object $parent Parent object.
     */
    public function __construct($parent)
    {
        $this->parent = $parent;

        $this->base = 'asr_';

        // Initialise settings.
        add_action('init', array($this, 'init_settings'), 11);

        // Register plugin settings.
        add_action('admin_init', array($this, 'register_settings'));

        // Add settings page to menu.
        add_action('admin_menu', array($this, 'add_menu_item'));

        // Add settings link to plugins page.
        add_filter(
            'plugin_action_links_' . plugin_basename($this->parent->file),
            array(
                $this,
                'add_settings_link',
            )
        );

        // Configure placement of plugin settings page. See readme for implementation.
        add_filter($this->base . 'menu_settings', array($this, 'configure_settings'));
    }

    /**
     * Initialise settings
     *
     * @return void
     */
    public function init_settings()
    {
        $this->settings = $this->settings_fields();
    }

    /**
     * Add settings page to admin menu
     *
     * @return void
     */
    public function add_menu_item()
    {

        $args = $this->menu_settings();

        // Do nothing if wrong location key is set.
        if (is_array($args) && isset($args['location']) && function_exists('add_' . $args['location'] . '_page')) {
            switch ($args['location']) {
                case 'options':
                case 'submenu':
                $page = add_submenu_page($args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function']);
                break;
                case 'menu':
                $page = add_menu_page($args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['function'], $args['icon_url'], $args['position']);
                break;
                default:
                return;
            }
            add_action('admin_print_styles-' . $page, array($this, 'settings_assets'));
        }
    }

    /**
     * Prepare default settings page arguments
     *
     * @return mixed|void
     */
    private function menu_settings()
    {
        return apply_filters(
            $this->base . 'menu_settings',
            array(
                'location' => 'menu', // Possible settings: options, menu, submenu.
                'parent_slug' => 'options-general.php',
                'page_title' => __('Asaver', 'asr'),
                'menu_title' => __('Asaver', 'asr'),
                'capability' => 'manage_options',
                'menu_slug' => $this->parent->_token . '_settings',
                'function' => array($this, 'settings_page'),
                'icon_url' => $this->parent->assets_url . 'images/asaver.svg',
                'position' => 28,
            )
        );
    }

    /**
     * Container for settings page arguments
     *
     * @param array $settings Settings array.
     *
     * @return array
     */
    public function configure_settings($settings = array())
    {
        return $settings;
    }

    /**
     * Load settings JS & CSS
     *
     * @return void
     */
    public function settings_assets()
    {

        // We're including the farbtastic script & styles here because they're needed for the colour picker
        // If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below.
        wp_enqueue_style('farbtastic');
        wp_enqueue_script('farbtastic');

        // We're including the WP media scripts here because they're needed for the image upload field.
        // If you're not including an image upload then you can leave this function call out.
        wp_enqueue_media();

        wp_register_script($this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array('farbtastic', 'jquery'), '1.0.0', true);
        wp_enqueue_script($this->parent->_token . '-settings-js');
    }

    /**
     * Add settings link to plugin list table
     *
     * @param array $links Existing links.
     * @return array        Modified links.
     */
    public function add_settings_link($links)
    {
        $settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings">' . __('Settings', 'asr') . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Build settings fields
     *
     * @return array Fields to be displayed on settings page
     */
    private function settings_fields()
    {

        $settings['latest-downloads'] = array(
            'title' => __('Dashboard', 'asr'),
            'description' => 'You can see latest downloaded items here. If you do not see please enable latest downloads feature on general settings.',
            'fields' => array(
                array(
                    'id' => 'latest_downloads',
                    'label' => '',
                    'description' => '',
                    'type' => 'latest_downloads',
                    'default' => '',
                    'placeholder' => ''
                ),
            ),
        );

        $settings['downloaders'] = array(
            'title' => __('Downloaders', 'asr'),
            'description' => __('Choose which downloaders can be used on your website.', 'asr'),
            'fields' => array(),
        );
        $downloaders = ASR_Downloaders::$downloaders;
        foreach ($downloaders as $downloader) {
            array_push($settings['downloaders']['fields'], array(
                'id' => 'downloader_' . $downloader['slug'],
                'label' => __('Enable ' . $downloader['name'] . ' downloader', 'asr'),
                'description' => '',
                'type' => 'checkbox',
                'default' => 'on',
            ));
            array_push($settings['downloaders']['fields'], array(
                'id' => 'proxy_' . $downloader['slug'],
                'label' => __('Enable proxies for ' . $downloader['name'], 'asr'),
                'description' => '',
                'type' => 'checkbox',
                'default' => '',
            ));
            array_push($settings['downloaders']['fields'], array(
                'id' => 'downloader_slug_' . $downloader['slug'],
                'label' => __('Page slug for ' . $downloader['name'], 'asr'),
                'description' => '',
                'type' => 'text',
                'default' => '',
                'placeholder' => ''
            ));
        }
        $copysvg = $this->parent->assets_url."images/copy.svg";
        $shortcode = "
        <p class='settings_copy_shortcode'>
        <input id='copy_shortcode' type='text' readonly value='[asaver-downloader]' />
        <button type='button' class='btn_copy' onclick='copyAndHighlight(this)'>
        <img src='$copysvg'  width='20' /> <span id='copy_text'>Copy</span></button>
        <p>";
        $settings['general'] = array(
            'title' => __('Settings', 'asr'),
            'description' => __('General settings about the plugin. <br> '.$shortcode.' ', 'asr'),
            'fields' => array(
                array(
                    'id' => 'show_mp3',
                    'label' => __('Show M4A as MP3', 'asr'),
                    'description' => __('If you enable this YouTube videos will be shown with MP3 extension also.', 'asr'),
                    'type' => 'checkbox',
                    'default' => '',
                ),
                array(
                    'id' => 'hide_dash',
                    'label' => __('Hide dash videos', 'asr'),
                    'description' => __('If you enable this YouTube videos without sound will be hidden.', 'asr'),
                    'type' => 'checkbox',
                    'default' => '',
                ),
                array(
                    'id' => 'recaptcha',
                    'label' => __('Enable Recaptcha', 'asr'),
                    'description' => __('Before enabling this be sure you have added Google Recaptcha api keys on Api Keys tab.', 'asr'),
                    'type' => 'checkbox',
                    'default' => '',
                ),
                array(
                    'id' => 'enable_latest_downloads',
                    'label' => __('Latest downloads', 'asr'),
                    'description' => __('If you enable this latest downloaded videos will be recorded in the database.', 'asr'),
                    'type' => 'checkbox',
                    'default' => '',
                ),
                array(
                    'id' => 'latest_downloads_count',
                    'label' => __('Latest downloads count', 'asr'),
                    'description' => __('By default last 10 downloads will be recorded.', 'asr'),
                    'type' => 'number',
                    'default' => '10',
                    'placeholder' => __('Enter an integer', 'asr'),
                ),
                array(
                    'id' => 'download_timer',
                    'label' => __('Download timer', 'asr'),
                    'description' => __('Users will wait to start download after clicking a download link. Set it zero to disable waiting.', 'asr'),
                    'type' => 'number',
                    'default' => '0',
                    'placeholder' => __('Enter an integer', 'asr'),
                ),
                array(
                    'id' => 'filename_suffix',
                    'label' => __('Suffix for filenames', 'asr'),
                    'description' => __('This text will be appended after video title.', 'asr'),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => __('Enter a text', 'asr'),
                ),
            ),
        );
        $settings['api'] = array(
            'title' => __('API', 'asr'),
            'description' => __('Enter & setup your api keys.', 'asr'),
            'fields' => array(
                array(
                    'id' => 'soundcloud_api_key',
                    'label' => __('Soundcloud api key', 'asr'),
                    'description' => __('<a href="https://www.clipsav.com/asaver/docs/get-soundcloud-api-key/" target="_blank">Click to learn how can you get it.</a>', 'asr'),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => __('Paste your api key', 'asr'),
                ),
                array(
                    'id' => 'recaptcha_public_api_key',
                    'label' => __('Recaptcha V3 public key', 'asr'),
                    'description' => __('<a href="https://www.clipsav.com/asaver/docs/create-and-get-recaptcha-v3-private-and-public-keys/" target="_blank">Click to get an api key.</a>', 'asr'),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => __('Paste your api key', 'asr'),
                ),
                array(
                    'id' => 'recaptcha_private_api_key',
                    'label' => __('Recaptcha V3 private key', 'asr'),
                    'description' => __('<a href="https://www.clipsav.com/asaver/docs/create-and-get-recaptcha-v3-private-and-public-keys/" target="_blank">Click to get an api key.</a>', 'asr'),
                    'type' => 'text',
                    'default' => '',
                    'placeholder' => __('Paste your api key', 'asr'),
                ),
                array(
                    'id' => 'facebook_cookies',
                    'label' => __('Facebook cookies', 'asr'),
                    'description' => __('<a href="https://www.clipsav.com/asaver/docs/how-to-extract-or-get-facebook-cookies/" target="_blank">Click to learn how to find.</a>', 'asr'),
                    'type' => 'textarea',
                    'default' => '',
                    'placeholder' => __('Paste your cookies', 'asr'),
                ),
                array(
                    'id' => 'instagram_cookies',
                    'label' => __('Instagram cookies', 'asr'),
                    'description' => __('<a href="https://www.clipsav.com/asaver/docs/how-to-extract-or-get-instagram-cookies/" target="_blank">Click to learn how to find.</a>', 'asr'),
                    'type' => 'textarea',
                    'default' => '',
                    'placeholder' => __('Paste your cookies', 'asr'),
                ),
                
                array(
                    'id' => 'tracking_code',
                    'label' => __('Tracking code', 'asr'),
                    'description' => __('It will be inserted inside footer.', 'asr'),
                    'type' => 'textarea',
                    'default' => '',
                    'placeholder' => __('Paste your Javascript tracking code <script>...</script>', 'asr'),
                ),
            ),
        );
        $settings['system-check'] = array(
            'title' => __('System Check', 'asr'),
            'description' => __('Check system requirements.', 'asr'),
            'fields' => array(
                array(
                    'id' => 'php_version',
                    'label' => __('PHP version 7.0 or higher', 'asr'),
                    'description' => 'PHP Version: ' . PHP_VERSION,
                    'type' => 'checkbox_system',
                    'default' => '',
                    'condition' => explode('.', PHP_VERSION)[0] >= 7,
                    'placeholder' => '',
                ),
                array(
                    'id' => 'curl_enabled',
                    'label' => __('cURL installed', 'asr'),
                    'description' => 'cURL Version: ' . (isset(curl_version()["version"]) != '' ? curl_version()["version"] : ''),
                    'type' => 'checkbox_system',
                    'default' => '',
                    'condition' => isset(curl_version()["version"]) != '',
                    'placeholder' => ''
                ),
                array(
                    'id' => 'mbstring_enabled',
                    'label' => __('mbstring installed', 'asr'),
                    'description' => '',
                    'type' => 'checkbox_system',
                    'default' => '',
                    'condition' => extension_loaded('mbstring'),
                    'placeholder' => ''
                ),
                array(
                    'id' => 'allowurlfopen_enabled',
                    'label' => __('allow_url_fopen enabled', 'asr'),
                    'description' => '',
                    'type' => 'checkbox_system',
                    'default' => '',
                    'condition' => ini_get('allow_url_fopen'),
                    'placeholder' => ''
                ),
                array(
                    'id' => 'modrewrite_enabled',
                    'label' => __('mod_rewrite installed', 'asr'),
                    'description' => '',
                    'type' => 'checkbox_system',
                    'default' => '',
                    'condition' => $this->checkModRewrite(),
                    'placeholder' => ''
                ),
                array(
                    'id' => 'server_software',
                    'label' => __('Server software', 'asr'),
                    'description' => '',
                    'type' => 'info_text',
                    'default' => $_SERVER["SERVER_SOFTWARE"],
                    'placeholder' => ''
                ),
                array(
                    'id' => 'server_ip',
                    'label' => __('Server IP', 'asr'),
                    'description' => '',
                    'type' => 'info_text',
                    'default' => $_SERVER["SERVER_ADDR"],
                    'placeholder' => ''
                ),
                array(
                    'id' => 'server_os',
                    'label' => __('Server OS', 'asr'),
                    'description' => '',
                    'type' => 'info_text',
                    'default' => php_uname(),
                    'placeholder' => ''
                ),
                array(
                    'id' => 'cache_size',
                    'label' => __('Cache Size', 'asr'),
                    'description' => '',
                    'type' => 'info_text',
                    'default' => $this->cacheSize(__DIR__ . '/../cache/'),
                    'placeholder' => ''
                ),
            ),
        );
        $settings = apply_filters($this->parent->_token . '_settings_fields', $settings);
        return $settings;
    }

    public function createRegistrationCode()
    {
        $code = [];
        $nameValid = !empty(get_option('asr_license_name'));
        $emailValid = !empty(get_option('asr_license_email'));
        $codeValid = !empty(get_option('asr_license_code'));
        $ipValid = filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP);
        $versionValid = !empty(ASR_VIDEO_DOWNLOADER_VERSION);
        if ($nameValid && $emailValid && $codeValid && $ipValid && $versionValid) {
            $code['name'] = get_option('asr_license_name');
            $code['email'] = get_option('asr_license_email');
            $code['code'] = get_option('asr_license_code');
            $code['url'] = get_site_url();
            $code['ip'] = $_SERVER['SERVER_ADDR'];
            $code['version'] = ASR_VIDEO_DOWNLOADER_VERSION;
            $code = serialize($code);
        } else {
            $code = '';
        }
        return strrev(base64_encode($code));
    }

    public function getRestApiKey()
    {
        $apiKey = get_option('asr_rest_api_key');
        if ($apiKey == '') {
            return $this->generateToken();
        } else {
            return $apiKey;
        }
    }

    public function generateToken()
    {
        if (defined('PHP_MAJOR_VERSION') && PHP_MAJOR_VERSION > 5) {
            return bin2hex(random_bytes(32));
        } else {
            if (function_exists('mcrypt_create_iv')) {
                return bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM));
            } else {
                return bin2hex(openssl_random_pseudo_bytes(32));
            }
        }
    }

    public function cleanCache($dir, $time = 86400)
    {
        foreach (glob($dir . "*") as $file) {
            if (time() - filectime($file) > $time) {
                unlink($file);
            }
        }
    }

    public function cacheSize($path)
    {
        $this->cleanCache($path);
        $bytestotal = 0;
        $path = realpath($path);
        if ($path !== false && $path != '' && file_exists($path)) {
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object) {
                $bytestotal += $object->getSize();
            }
        }
        require_once __DIR__ . '/Helpers.php';
        return Helpers::formatSize($bytestotal);
    }

    public function checkModRewrite()
    {
        if (function_exists('apache_get_modules')) {
            $modules = apache_get_modules();
            return in_array('mod_rewrite', $modules);
        }
        return false;
    }

    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings()
    {
        if (is_array($this->settings)) {

            // Check posted/selected tab.
            //phpcs:disable
            $current_section = '';
            if (isset($_POST['tab']) && $_POST['tab']) {
                $current_section = $_POST['tab'];
            } else {
                if (isset($_GET['tab']) && $_GET['tab']) {
                    $current_section = $_GET['tab'];
                }
            }
            //phpcs:enable

            foreach ($this->settings as $section => $data) {

                if ($current_section && $current_section !== $section) {
                    continue;
                }

                // Add section to page.
                add_settings_section($section, $data['title'], array($this, 'settings_section'), $this->parent->_token . '_settings');

                foreach ($data['fields'] as $field) {

                    // Validation callback for field.
                    $validation = '';
                    if (isset($field['callback'])) {
                        $validation = $field['callback'];
                    }

                    // Register field.
                    $option_name = $this->base . $field['id'];
                    register_setting($this->parent->_token . '_settings', $option_name, $validation);

                    // Add field to page.
                    add_settings_field(
                        $field['id'],
                        $field['label'],
                        array($this->parent->admin, 'display_field'),
                        $this->parent->_token . '_settings',
                        $section,
                        array(
                            'field' => $field,
                            'prefix' => $this->base,
                        )
                    );
                }

                if (!$current_section) {
                    break;
                }
            }
        }
    }

    /**
     * Settings section.
     *
     * @param array $section Array of section ids.
     * @return void
     */
    public function settings_section($section)
    {
        $html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
        echo $html; //phpcs:ignore
    }

    /**
     * Load settings page content.
     *
     * @return void
     */
    public function settings_page()
    {

        $getpage=$_REQUEST['page'];
        $gettab=$_REQUEST['tab'] ?? 'dashboard';
        $breadcrumb= " / Dashboard";

        if ($getpage==='ASR_settings') {
            if($gettab==='latest-downloads' || $gettab==='dashboard') $breadcrumb=' / Dashboard';
            if($gettab==='api') $breadcrumb=' / API Keys';
            if($gettab==='downloaders') $breadcrumb=' / Downloaders';
            if($gettab==='general' || $gettab==='settings') $breadcrumb=' / Settings';
            if($gettab==='system-check' || $gettab==='dashboard') $breadcrumb=' / System Check';
        }


        $logo = $this->parent->assets_url.'images/asavertext.svg';

        // Build page HTML.
        $html = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
        $html .= '<h2><img height="18" src='.$logo.' />' . __($breadcrumb, 'asr') . '</h2>' . "\n";

        $tab = '';
        //phpcs:disable
        if (isset($_GET['tab']) && $_GET['tab']) {
            $tab .= $_GET['tab'];
        }
        //phpcs:enable

        // Show page tabs.
        if (is_array($this->settings) && 1 < count($this->settings)) {

            $html .= '<h2 class="nav-tab-wrapper">' . "\n";

            $c = 0;
            foreach ($this->settings as $section => $data) {
                // Set tab class.
                $class = 'nav-tab';
                if (!isset($_GET['tab'])) { //phpcs:ignore
                    if (0 === $c) {
                        $class .= ' nav-tab-active';
                        $classname='dashboard';
                    }
                } else {
                    if (isset($_GET['tab']) && $section == $_GET['tab']) { //phpcs:ignore
                        $class .= ' nav-tab-active';
                        $classname=$_GET['tab'];
                    } else {
                       $classname='dashboard';
                   }
               }

                // Set tab link.
               $tab_link = add_query_arg(array('tab' => $section));
                if (isset($_GET['settings-updated'])) { //phpcs:ignore
                    $tab_link = remove_query_arg('settings-updated', $tab_link);
                }

                // Output tab.
                $html .= '<a href="' . $tab_link . '" class="' . esc_attr($class) . '">' . esc_html($data['title']) . '</a>' . "\n";

                ++$c;
            }

            $html .= '</h2>' . "\n";
             if (isset($_GET['settings-updated'])) { //phpcs:ignore

               $html .='
               <div class="notice notice-success is-dismissible"
               style="padding-left: 20px;margin-left: 40px;border-radius:10px">
               <p>Settings updated successfully.
               </p>
               </div>
               ';
           }
       }


       $html .= '<form method="post" id='.$classname.' class='.$classname.' action="options.php" enctype="multipart/form-data">' . "\n";

        // Get settings fields.
       ob_start();
       settings_fields($this->parent->_token . '_settings');
       do_settings_sections($this->parent->_token . '_settings');
       $html .= ob_get_clean();
       $html .= '<p class="submit">' . "\n";
       $html .= '<input type="hidden" name="tab" value="' . esc_attr($tab) . '" />' . "\n";
       $html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr(__('Save Settings', 'asr')) . '" />' . "\n";
       $html .= '</p>' . "\n";
       $html .= '</form>' . "\n";

       $html .= '</div>' . "\n";
       self::saveInstagramCookie();
        echo $html; //phpcs:ignore
    }

    public static function saveInstagramCookie()
    {
        $cookieFile = __DIR__ . '/../cookies/ig-cookie.txt';
        file_put_contents($cookieFile, get_option('asr_instagram_cookies'));
    }

    public static function checkSettingsCache()
    {
        $cacheFile = __DIR__ . '/../settings.json';
        $cache = json_decode(file_get_contents($cacheFile), true);
        if ((time() - filemtime($cacheFile)) >= 86400 || empty($cache['siteUrl'])) {
            $option['siteUrl'] = get_site_url();
            $option['hideDash'] = get_site_url('asr_hide_dash');
            $option['showMp3'] = get_site_url('asr_show_mp3');
            file_put_contents($cacheFile, json_encode($option));
        }
    }

    /**
     * Main ASR_Settings Instance
     *
     * Ensures only one instance of ASR_Settings is loaded or can be loaded.
     *
     * @param object $parent Object instance.
     * @return object ASR_Settings instance
     * @since 1.0.0
     * @static
     * @see ASR()
     */
    public static function instance($parent)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self($parent);
        }
        return self::$_instance;
    } // End instance()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Cloning of ASR_API is forbidden.')), esc_attr($this->parent->_version));
    } // End __clone()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup()
    {
        _doing_it_wrong(__FUNCTION__, esc_html(__('Unserializing instances of ASR_API is forbidden.')), esc_attr($this->parent->_version));
    } // End __wakeup()

}
