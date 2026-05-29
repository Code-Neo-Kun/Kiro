<?php
/**
 * Plugin Name: Kiro Cursor
 * Plugin URI:  https://example.com/kiro-cursor
 * Description: Adds a smooth, customizable cursor with 8 predefined styles and animation presets.
 * Version:     1.0.0
 * Author:      Neo
 * Author URI:  https://example.com
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: kiro-cursor
 * Domain Path: /languages
 *
 * @package KiroCursor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ── Plugin constants ──────────────────────────────────────────────────────────
define( 'KIRO_CURSOR_VERSION',     '1.0.0' );
define( 'KIRO_CURSOR_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'KIRO_CURSOR_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'KIRO_CURSOR_TEXT_DOMAIN', 'kiro-cursor' );

require_once KIRO_CURSOR_PLUGIN_DIR . 'includes/admin-settings.php';


// ═══════════════════════════════════════════════════════════════════════════════
// FRONTEND: Inject cursor DOM — WRAPPER + INNER shell architecture
//
// WHY TWO LAYERS PER ELEMENT:
//   JS writes transform on the OUTER wrapper (position only).
//   CSS animations run on the INNER shell (scale / rotate / opacity only).
//   This eliminates JS-vs-CSS transform conflicts that would stomp animations
//   on every rAF tick.
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Inject cursor HTML into the footer.
 *
 * Hooked to wp_footer so the DOM is available before JS runs.
 * aria-hidden prevents screen-readers from announcing decorative elements.
 */
function kiro_cursor_inject_html(): void {
	if ( is_admin() ) {
		return;
	}

	$disable_mobile = (int) get_option( 'kiro_cursor_disable_mobile', 0 );
	if ( $disable_mobile && wp_is_mobile() ) {
		return;
	}

	// Outer div  → JS positions via translate3d (GPU-composited).
	// Inner div  → CSS animates scale / rotate / opacity / glow.
	echo '<div id="kiro-cursor" aria-hidden="true"><div class="kiro-inner"></div></div>' . "\n";
	echo '<div id="kiro-cursor-ring" aria-hidden="true"><div class="kiro-inner"></div></div>' . "\n";
	echo '<div id="kiro-particles" aria-hidden="true"></div>' . "\n";
}
add_action( 'wp_footer', 'kiro_cursor_inject_html' );


// ═══════════════════════════════════════════════════════════════════════════════
// FRONTEND: CSS custom properties + JS config injected into <head>
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Output --kiro-color, --kiro-size CSS vars and window.kiroCursorConfig.
 *
 * Using wp_head (priority 10) ensures vars are available before enqueued
 * stylesheets that may reference them.
 */
function kiro_cursor_inline_config(): void {
	if ( is_admin() ) {
		return;
	}

	$disable_mobile = (int) get_option( 'kiro_cursor_disable_mobile', 0 );
	if ( $disable_mobile && wp_is_mobile() ) {
		return;
	}

	$color = sanitize_hex_color( get_option( 'kiro_cursor_color', '#00ffff' ) );
	if ( empty( $color ) ) {
		$color = '#00ffff';
	}

	$size = (int) get_option( 'kiro_cursor_size', 12 );
	$size = max( 4, min( 60, $size ) );

	$style     = kiro_cursor_sanitize_style(     (string) get_option( 'kiro_cursor_style',     'dot-ring' ) );
	$animation = kiro_cursor_sanitize_animation( (string) get_option( 'kiro_cursor_animation', 'none'     ) );

	printf(
		"<style id=\"kiro-cursor-vars\">:root{--kiro-color:%s;--kiro-size:%dpx;}</style>\n",
		esc_attr( $color ),
		$size
	);

	printf(
		"<script id=\"kiro-cursor-config\">window.kiroCursorConfig={style:%s,animation:%s};</script>\n",
		wp_json_encode( $style ),
		wp_json_encode( $animation )
	);
}
add_action( 'wp_head', 'kiro_cursor_inline_config' );


// ═══════════════════════════════════════════════════════════════════════════════
// FRONTEND: Enqueue split CSS + JS assets
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Enqueue cursor CSS and JS only on the frontend.
 *
 * Assets are split by responsibility:
 *   cursor-base.css       → reset, .kiro-active cursor:none, hidden state
 *   cursor-styles.css     → per-style shapes (dot-ring, crosshair, neon …)
 *   cursor-animations.css → CSS-only animation keyframes (pulse, breathe …)
 *   cursor-core.js        → rAF loop, mouse tracking, hover delegation
 *   cursor-animations.js  → JS-driven particles + magnetic spring module
 */
function kiro_cursor_enqueue_assets(): void {
	if ( is_admin() ) {
		return;
	}

	$disable_mobile = (int) get_option( 'kiro_cursor_disable_mobile', 0 );
	if ( $disable_mobile && wp_is_mobile() ) {
		return;
	}

	$css = KIRO_CURSOR_PLUGIN_URL . 'assets/css/';
	$js  = KIRO_CURSOR_PLUGIN_URL . 'assets/js/';
	$ver = KIRO_CURSOR_VERSION;

	wp_enqueue_style( 'kiro-cursor-base',       $css . 'cursor-base.css',       [],                        $ver );
	wp_enqueue_style( 'kiro-cursor-styles',     $css . 'cursor-styles.css',     [ 'kiro-cursor-base' ],    $ver );
	wp_enqueue_style( 'kiro-cursor-animations', $css . 'cursor-animations.css', [ 'kiro-cursor-base' ],    $ver );

	// Scripts load in footer (last param = true) so DOM is ready.
	wp_enqueue_script( 'kiro-cursor-core',       $js . 'cursor-core.js',        [],                        $ver, true );
	wp_enqueue_script( 'kiro-cursor-animations', $js . 'cursor-animations.js',  [ 'kiro-cursor-core' ],    $ver, true );
}
add_action( 'wp_enqueue_scripts', 'kiro_cursor_enqueue_assets' );


// ═══════════════════════════════════════════════════════════════════════════════
// ADMIN MENU
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Register the Settings > Kiro Cursor submenu page.
 */
function kiro_cursor_register_menu(): void {
	add_options_page(
		esc_html__( 'Kiro Cursor Settings', 'kiro-cursor' ),
		esc_html__( 'Kiro Cursor',          'kiro-cursor' ),
		'manage_options',
		'kiro-cursor-settings',
		'kiro_cursor_settings_page'
	);
}
add_action( 'admin_menu', 'kiro_cursor_register_menu' );


// ═══════════════════════════════════════════════════════════════════════════════
// I18N
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Load plugin text domain for translations.
 *
 * init priority 1 ensures translations are ready before any hook that
 * might output translatable strings (e.g. admin_menu at priority 10).
 */
function kiro_cursor_load_textdomain(): void {
	load_plugin_textdomain(
		'kiro-cursor',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'init', 'kiro_cursor_load_textdomain', 1 );


// ═══════════════════════════════════════════════════════════════════════════════
// SHARED VALIDATION HELPERS
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Returns all 8 valid cursor style slugs with admin UI metadata.
 *
 * @return array<string, array<string, string>>
 */
function kiro_cursor_get_valid_styles(): array {
	return [
		'dot-ring'  => [ 'label' => __( 'Dot + Ring',  'kiro-cursor' ), 'preview_emoji' => '⊙', 'desc' => __( 'Classic filled dot with lagging outer ring.',           'kiro-cursor' ) ],
		'crosshair' => [ 'label' => __( 'Crosshair',   'kiro-cursor' ), 'preview_emoji' => '⊕', 'desc' => __( 'Precision crosshair lines with center dot.',             'kiro-cursor' ) ],
		'arrow'     => [ 'label' => __( 'Arrow',       'kiro-cursor' ), 'preview_emoji' => '➤', 'desc' => __( 'Stylized CSS arrow pointer.',                            'kiro-cursor' ) ],
		'spotlight' => [ 'label' => __( 'Spotlight',   'kiro-cursor' ), 'preview_emoji' => '◉', 'desc' => __( 'Radial glow spotlight that illuminates content.',         'kiro-cursor' ) ],
		'glitch'    => [ 'label' => __( 'Glitch',      'kiro-cursor' ), 'preview_emoji' => '▣', 'desc' => __( 'RGB-split glitch effect with chromatic aberration.',      'kiro-cursor' ) ],
		'neon'      => [ 'label' => __( 'Neon',        'kiro-cursor' ), 'preview_emoji' => '◎', 'desc' => __( 'Neon ring with multi-layered glow bloom.',               'kiro-cursor' ) ],
		'ghost'     => [ 'label' => __( 'Ghost',       'kiro-cursor' ), 'preview_emoji' => '○', 'desc' => __( 'Translucent ring that fades and follows.',               'kiro-cursor' ) ],
		'emoji'     => [ 'label' => __( 'Emoji Star',  'kiro-cursor' ), 'preview_emoji' => '✦', 'desc' => __( 'Spinning ✦ star emoji cursor.',                         'kiro-cursor' ) ],
	];
}

/**
 * Returns all 6 valid animation preset slugs with admin UI metadata.
 *
 * @return array<string, array<string, string>>
 */
function kiro_cursor_get_valid_animations(): array {
	return [
		'none'      => [ 'label' => __( 'None',            'kiro-cursor' ), 'desc' => __( 'No extra animation.',                              'kiro-cursor' ) ],
		'pulse'     => [ 'label' => __( 'Pulse',           'kiro-cursor' ), 'desc' => __( 'Continuous scale pulse on the dot.',               'kiro-cursor' ) ],
		'breathe'   => [ 'label' => __( 'Breathe',         'kiro-cursor' ), 'desc' => __( 'Slow opacity in-out like breathing.',              'kiro-cursor' ) ],
		'spin-ring' => [ 'label' => __( 'Spin Ring',       'kiro-cursor' ), 'desc' => __( 'Outer ring rotates continuously.',                 'kiro-cursor' ) ],
		'particles' => [ 'label' => __( 'Trail Particles', 'kiro-cursor' ), 'desc' => __( 'Leaves a trail of fading sparkle dots.',           'kiro-cursor' ) ],
		'magnetic'  => [ 'label' => __( 'Magnetic Snap',   'kiro-cursor' ), 'desc' => __( 'Cursor snaps toward links with a spring effect.',  'kiro-cursor' ) ],
	];
}

/**
 * Sanitize and validate a cursor style slug.
 *
 * Falls back to 'dot-ring' for any unrecognised value.
 *
 * @param  string $raw Raw user input.
 * @return string      Validated style slug.
 */
function kiro_cursor_sanitize_style( string $raw ): string {
	$raw = sanitize_key( $raw );
	return array_key_exists( $raw, kiro_cursor_get_valid_styles() ) ? $raw : 'dot-ring';
}

/**
 * Sanitize and validate an animation preset slug.
 *
 * Falls back to 'none' for any unrecognised value.
 *
 * @param  string $raw Raw user input.
 * @return string      Validated animation slug.
 */
function kiro_cursor_sanitize_animation( string $raw ): string {
	$raw = sanitize_key( $raw );
	return array_key_exists( $raw, kiro_cursor_get_valid_animations() ) ? $raw : 'none';
}
