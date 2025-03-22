<?php
/**
 * Plugin Name: WP Ikigai Finder
 * Plugin URI: https://github.com/grauschnabel/wp-ikigai
 * Description: An AI-powered Ikigai discovery tool using ChatGPT (Ein KI-gestütztes Ikigai-Findungs-Tool mit ChatGPT)
 * Version: 0.1.4
 * Author: Martin Kaffanke
 * Author URI: https://github.com/grauschnabel
 * Text Domain: wp-ikigai
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin Constants
define('WP_IKIGAI_VERSION', '0.1.4');
define('WP_IKIGAI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_IKIGAI_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once(WP_IKIGAI_PLUGIN_DIR . 'includes/class-wp-ikigai-block.php');
require_once(WP_IKIGAI_PLUGIN_DIR . 'includes/class-wp-ikigai-settings.php');

// Initialize Plugin Classes
add_action('plugins_loaded', function() {
    // Load text domain for translations
    load_plugin_textdomain('wp-ikigai', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    WP_Ikigai_Block::init();
    WP_Ikigai_Settings::init();
}); 
?>