<?php
/**
 * Plugin Name: ASaver
 * Version: 1.0
 * Plugin URI: https://www.clipsav.com/asaver/
 * Description: Asaver, a potent WordPress plugin, enables website owners to offer visitors hassle-free video downloads from multiple online sources.
 * Author: ASaver
 * Author URI: https://www.clipsav.com/asaver/
 * Requires at least: 4.0
 * Tested up to: 4.0
 *
 * Text Domain: asr
 * Domain Path: /lang/
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load plugin class files.
require_once 'includes/class-asr.php';
require_once 'includes/class-asr-settings.php';

// Load plugin libraries.
require_once 'includes/lib/class-asr-admin-api.php';
require_once 'includes/lib/class-asr-post-type.php';
require_once 'includes/lib/class-asr-taxonomy.php';
require_once 'includes/lib/class-asr-downloaders.php';
require_once 'includes/lib/class-asr-routes.php';

define('AUTOSAVE_INTERVAL', 300);
define('WP_POST_REVISIONS', false);

/**
 * Returns the main instance of ASR to prevent the need to use globals.
 *
 * @return object ASR
 * @since  1.0.0
 */
function ASR()
{
    $instance = ASR::instance(__FILE__, '2.4.0');

    if (is_null($instance->settings)) {
        $instance->settings = ASR_Settings::instance($instance);
    }

    return $instance;
}

define('ASR_VIDEO_DOWNLOADER_VERSION', 'MTc3NTIyLjYwMDIuODgyMDQuMTM4MDAw2');
ASR();

add_action('wp_enqueue_scripts', 'xf_enqueue_scripts' );
function xf_enqueue_scripts(){

    wp_register_script( 'xf-captcha', plugin_dir_url( __FILE__ ) . 'assets/js/captcha.js' );

    $ver = filemtime( plugin_dir_path( __FILE__ ) . 'assets/js/main.js' );
    wp_register_script( 'xf-main', plugin_dir_url( __FILE__ ) . 'assets/js/main.js', array('jquery'), $ver );
    wp_localize_script('xf-main', 'WPURLS', array('siteurl' => get_option('siteurl'), 'pluginurl' => plugin_dir_url(__FILE__)));    
    
    $ver = filemtime( plugin_dir_path( __FILE__ ) . 'assets/css/xf-style.css' );
    wp_enqueue_style( 'xf-style', plugin_dir_url( __FILE__ ) . 'assets/css/xf-style.css' );
}


add_shortcode('asaver-downloader', 'asr_video_downloader' );

function asr_video_downloader(){

    ob_start();

    require_once 'asaver-downloader.php';

    wp_enqueue_script( 'xf-main' );

    return ob_get_clean();
}

add_action('wp_footer', 'xf_footer', 100);
function xf_footer(){
    if (get_option('asr_recaptcha') == 'on') {
        $recaptchaPublicKey = get_option('asr_recaptcha_public_api_key');
        printf('<script src="https://www.google.com/recaptcha/api.js?render=%s"></script>', $recaptchaPublicKey);
        printf("<script>%s</script>", str_replace('%s', $recaptchaPublicKey, file_get_contents(plugin_dir_url(__FILE__) . 'assets/js/captcha.js')));
    }
    echo get_option('asr_tracking_code');
}

function generateToken()
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
