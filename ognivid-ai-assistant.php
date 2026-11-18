<?php
/**
 * Plugin Name: OGNIVID AI Assistant
 * Plugin URI: https://github.com/diimasogan/ognivid-ai-assistant
 * Description: AI intake assistant that interviews website visitors about their project and emails you a structured project brief (HTML + PDF). Works with Claude, OpenAI and other AI providers, with a no-AI form fallback.
 * Version: 2.0.0
 * Author: Dimas Agung Prakasa
 * Author URI: https://diimasogan.github.io/
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ognivid-ai-assistant
 *
 * @package OgnividAIAssistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OGNIVID_AI_VERSION', '2.0.0' );
define( 'OGNIVID_AI_FILE', __FILE__ );
define( 'OGNIVID_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'OGNIVID_AI_URL', plugin_dir_url( __FILE__ ) );

require_once OGNIVID_AI_DIR . 'includes/class-ognivid-ai-settings.php';
require_once OGNIVID_AI_DIR . 'includes/class-ognivid-ai-provider.php';
require_once OGNIVID_AI_DIR . 'includes/class-ognivid-ai-brief.php';
require_once OGNIVID_AI_DIR . 'includes/class-ognivid-ai-chat.php';

register_activation_hook( __FILE__, array( 'Ognivid_AI_Settings', 'activate' ) );

add_action( 'init', array( 'Ognivid_AI_Brief', 'register_post_type' ) );
add_action( 'admin_menu', array( 'Ognivid_AI_Settings', 'admin_menu' ) );
add_action( 'admin_init', array( 'Ognivid_AI_Settings', 'register' ) );
add_action( 'add_meta_boxes', array( 'Ognivid_AI_Brief', 'meta_boxes' ) );
add_action( 'admin_post_ognivid_ai_brief_pdf', array( 'Ognivid_AI_Brief', 'download_pdf' ) );
add_action( 'admin_menu', array( 'Ognivid_AI_Brief', 'menu_badge' ), 99 );
add_action( 'admin_enqueue_scripts', array( 'Ognivid_AI_Brief', 'admin_css' ) );
add_action( 'plugins_loaded', array( 'Ognivid_AI_Settings', 'maybe_upgrade' ) );
add_action( 'save_post_ognivid_ai_brief', array( 'Ognivid_AI_Brief', 'save_status' ) );
add_action( 'restrict_manage_posts', array( 'Ognivid_AI_Brief', 'filter_dropdown' ) );
add_action( 'pre_get_posts', array( 'Ognivid_AI_Brief', 'apply_filter' ) );
add_filter( 'manage_ognivid_ai_brief_posts_columns', array( 'Ognivid_AI_Brief', 'columns' ) );
add_action( 'manage_ognivid_ai_brief_posts_custom_column', array( 'Ognivid_AI_Brief', 'column' ), 10, 2 );
add_filter( 'post_class', array( 'Ognivid_AI_Brief', 'row_class' ), 10, 3 );
add_action( 'rest_api_init', array( 'Ognivid_AI_Chat', 'routes' ) );
add_action( 'wp_enqueue_scripts', array( 'Ognivid_AI_Chat', 'enqueue' ) );
add_action( 'wp_footer', array( 'Ognivid_AI_Chat', 'render_root' ) );
