=== Kiro Cursor ===
Contributors: Uddhav Shrimali
Tags: cursor, custom cursor, mouse cursor, animation, smooth cursor
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a smooth, animated custom cursor with 8 styles, 6 animation presets, and a visual settings panel — zero coding required.

== Description ==

Kiro Cursor replaces the default browser cursor with a beautiful animated dot + ring cursor effect. Fully customizable from the WordPress admin panel.

**Features:**

* 8 cursor styles: Dot + Ring, Crosshair, Arrow, Spotlight, Glitch, Neon, Ghost, Emoji Star
* 6 animation presets: Pulse, Breathe, Spin Ring, Trail Particles, Magnetic Snap, None
* Visual card-picker UI — no dropdowns, just click
* Smooth lagging ring animation using requestAnimationFrame
* Magnetic spring effect that snaps toward links and buttons
* Trail particle effect with pool-capped DOM management
* Customizable cursor color (native color picker)
* Customizable cursor size (4–60 px)
* Option to disable on mobile / touch devices
* Hides cursor when mouse leaves the browser window
* Zero dependencies — pure vanilla JavaScript
* Lightweight and performance-optimized
* Two-layer JS/CSS architecture — animations never conflict with position tracking

== Installation ==

1. Upload the `kiro-cursor` folder to `/wp-content/plugins/`, or install via **Plugins → Add New**.
2. Activate the plugin through the Plugins menu.
3. Go to **Settings → Kiro Cursor** to configure color, size, style, and animation.

== Frequently Asked Questions ==

= Does it work with all themes? =
Yes. Cursor elements are injected via `wp_footer` and assets are loaded on all frontend pages.

= Can I disable it on mobile? =
Yes. Enable "Disable on Mobile" under **Settings → Kiro Cursor**.

= Why does my default cursor still show? =
Ensure no other plugin or theme CSS is overriding `cursor: none`. Kiro Cursor sets `cursor: none !important` on all elements.

= What WordPress version is required? =
WordPress 5.8 or higher. PHP 8.0 or higher.

== Screenshots ==

1. Admin settings panel — style picker, animation picker, color and size controls.
2. Cursor in action on the frontend — dot + ring with particle trail.

== Changelog ==

= 1.0.0 =
* Initial release of Kiro Cursor.
* 8 cursor styles and 6 animation presets.
* Two-layer DOM architecture eliminates JS/CSS transform conflicts.
* Full security hardening: nonces, capability checks, sanitization, escaping.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
