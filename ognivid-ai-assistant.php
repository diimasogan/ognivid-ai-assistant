<?php
/**
 * Plugin Name: OGNIVID AI Assistant
 * Description: AI intake assistant that interviews website visitors about their project, turns the conversation into a structured Project Brief (HTML email + PDF) and sends it to OGNIVID. Includes a no-AI form fallback.
 * Version: 1.1.0
 * Author: OGNIVID
 * Requires PHP: 7.4
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ognivid-ai-assistant
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'OAA_VERSION', '1.1.0' );
define( 'OAA_FILE', __FILE__ );
define( 'OAA_DIR', plugin_dir_path( __FILE__ ) );
define( 'OAA_URL', plugin_dir_url( __FILE__ ) );

require_once OAA_DIR . 'includes/class-oaa-settings.php';
require_once OAA_DIR . 'includes/class-oaa-provider.php';
require_once OAA_DIR . 'includes/class-oaa-brief.php';
require_once OAA_DIR . 'includes/class-oaa-chat.php';

register_activation_hook( __FILE__, array( 'OAA_Settings', 'activate' ) );

add_action( 'init', array( 'OAA_Brief', 'register_post_type' ) );
add_action( 'admin_menu', array( 'OAA_Settings', 'admin_menu' ) );
add_action( 'admin_init', array( 'OAA_Settings', 'register' ) );
add_action( 'add_meta_boxes', array( 'OAA_Brief', 'meta_boxes' ) );
add_action( 'admin_post_oaa_brief_pdf', array( 'OAA_Brief', 'download_pdf' ) );
add_action( 'admin_menu', array( 'OAA_Brief', 'menu_badge' ), 99 );
add_action( 'admin_head', array( 'OAA_Brief', 'admin_css' ) );
add_action( 'save_post_oaa_brief', array( 'OAA_Brief', 'save_status' ) );
add_action( 'restrict_manage_posts', array( 'OAA_Brief', 'filter_dropdown' ) );
add_action( 'pre_get_posts', array( 'OAA_Brief', 'apply_filter' ) );
add_filter( 'manage_oaa_brief_posts_columns', array( 'OAA_Brief', 'columns' ) );
add_action( 'manage_oaa_brief_posts_custom_column', array( 'OAA_Brief', 'column' ), 10, 2 );
add_filter( 'post_class', array( 'OAA_Brief', 'row_class' ), 10, 3 );
add_action( 'rest_api_init', array( 'OAA_Chat', 'routes' ) );
add_action( 'wp_enqueue_scripts', array( 'OAA_Chat', 'enqueue' ) );
add_action( 'wp_footer', array( 'OAA_Chat', 'render_root' ) );
