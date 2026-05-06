<?php
/**
 * Plugin Name:  Studio Production Scheduler
 * Plugin URI:   https://1876productions.com
 * Description:  1876 Productions Studio Scheduler — calendar, status report, and capacity planning.
 * Version:      1.0.0
 * Author:       1876 Productions
 * License:      Private
 *
 * @package StudioScheduler
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SS_VERSION',     '1.0.0' );
define( 'SS_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'SS_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'SS_API_NS',      'studio-scheduler/v1' );

// ── Load sub-modules ─────────────────────────────────────────────────────────
require_once SS_PLUGIN_DIR . 'includes/db.php';
require_once SS_PLUGIN_DIR . 'includes/api.php';

// Load migration page only if the file exists (delete after migrating)
if ( file_exists( SS_PLUGIN_DIR . 'migrate.php' ) && is_admin() ) {
    require_once SS_PLUGIN_DIR . 'migrate.php';
}

// ── Activation / Deactivation ─────────────────────────────────────────────────
register_activation_hook( __FILE__, 'ss_activate' );
function ss_activate() {
    ss_create_tables();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'ss_deactivate' );
function ss_deactivate() {
    flush_rewrite_rules();
}

// ── Enqueue assets + register shortcode ──────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'ss_enqueue_assets' );
function ss_enqueue_assets() {
    // Only enqueue on pages that contain the shortcode
    global $post;
    if ( ! is_a( $post, 'WP_Post' ) || ! has_shortcode( $post->post_content, 'studio_scheduler' ) ) {
        return;
    }

    // SheetJS (Excel import — loaded from CDN, same as original)
    wp_enqueue_script(
        'sheetjs',
        'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js',
        [],
        '0.18.5',
        false
    );

    // Main app JS
    wp_enqueue_script(
        'studio-scheduler',
        SS_PLUGIN_URL . 'assets/app.js',
        [ 'sheetjs' ],
        SS_VERSION,
        true  // load in footer
    );

    // Pass WordPress REST API info to the JS
    wp_localize_script( 'studio-scheduler', 'SS_API', [
        'root'     => esc_url_raw( rest_url( SS_API_NS ) ),
        'nonce'    => wp_create_nonce( 'wp_rest' ),
        'version'  => SS_VERSION,
    ] );

    // Main app CSS (extracted from the original HTML <style> block)
    wp_enqueue_style(
        'studio-scheduler',
        SS_PLUGIN_URL . 'assets/app.css',
        [],
        SS_VERSION
    );
}

// ── Shortcode ─────────────────────────────────────────────────────────────────
add_shortcode( 'studio_scheduler', 'ss_render_shortcode' );
function ss_render_shortcode( $atts ) {
    ob_start();
    include SS_PLUGIN_DIR . 'includes/template.php';
    return ob_get_clean();
}
