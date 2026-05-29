<?php
/**
 * Admin Settings Page — Kiro Cursor
 *
 * Renders the settings form at Settings > Kiro Cursor.
 * Handles POST saving with nonce + capability guards.
 *
 * @package KiroCursor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Kiro Cursor settings page and process form submissions.
 *
 * Callback registered via add_options_page() in the main plugin file.
 */
function kiro_cursor_settings_page(): void {

	// ── Capability gate ──────────────────────────────────────────────────────
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'kiro-cursor' ) );
	}

	// ── Handle POST save ─────────────────────────────────────────────────────
	$saved = false;
	if ( isset( $_POST['kiro_cursor_save'] ) ) {

		// Nonce verification — prevents CSRF.
		check_admin_referer( 'kiro_cursor_save_settings', 'kiro_cursor_nonce' );

		update_option(
			'kiro_cursor_color',
			sanitize_hex_color( wp_unslash( $_POST['kiro_cursor_color'] ?? '' ) ) ?: '#00ffff'
		);

		$size = (int) wp_unslash( $_POST['kiro_cursor_size'] ?? 12 );
		update_option( 'kiro_cursor_size', max( 4, min( 60, $size ) ) );

		update_option(
			'kiro_cursor_disable_mobile',
			isset( $_POST['kiro_cursor_disable_mobile'] ) ? 1 : 0
		);

		// Validate style and animation against known slugs via shared helpers.
		update_option(
			'kiro_cursor_style',
			kiro_cursor_sanitize_style( wp_unslash( $_POST['kiro_cursor_style'] ?? '' ) )
		);

		update_option(
			'kiro_cursor_animation',
			kiro_cursor_sanitize_animation( wp_unslash( $_POST['kiro_cursor_animation'] ?? '' ) )
		);

		$saved = true;
	}

	// ── Read current option values ────────────────────────────────────────────
	$color        = get_option( 'kiro_cursor_color',          '#00ffff' );
	$size         = (int) get_option( 'kiro_cursor_size',     12 );
	$mobile       = (int) get_option( 'kiro_cursor_disable_mobile', 0 );
	$cursor_style = kiro_cursor_sanitize_style(     (string) get_option( 'kiro_cursor_style',     'dot-ring' ) );
	$animation    = kiro_cursor_sanitize_animation( (string) get_option( 'kiro_cursor_animation', 'none'     ) );
	$styles       = kiro_cursor_get_valid_styles();
	$animations   = kiro_cursor_get_valid_animations();

	?>
	<div class="wrap kiro-admin-wrap">

		<h1>
			<span style="font-size:1.4em;vertical-align:middle;margin-right:6px;">🖱️</span>
			<?php esc_html_e( 'Kiro Cursor Settings', 'kiro-cursor' ); ?>
		</h1>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'Settings saved successfully!', 'kiro-cursor' ); ?></strong></p>
			</div>
		<?php endif; ?>

		<form method="post" id="kiro-cursor-settings-form">

			<?php wp_nonce_field( 'kiro_cursor_save_settings', 'kiro_cursor_nonce' ); ?>

			<?php /* ── SECTION 1: Cursor Style Picker ──────────────────────────── */ ?>
			<h2 class="kiro-section-title">
				<?php esc_html_e( '1. Choose Cursor Style', 'kiro-cursor' ); ?>
			</h2>
			<p class="description" style="margin-bottom:16px;">
				<?php esc_html_e( 'Select one of 8 predefined cursor shapes. The style controls both the dot and ring appearance.', 'kiro-cursor' ); ?>
			</p>

			<div class="kiro-style-grid" id="kiro-style-grid">
				<?php foreach ( $styles as $slug => $meta ) : ?>
					<label class="kiro-style-card <?php echo ( $cursor_style === $slug ) ? 'is-selected' : ''; ?>"
					       data-slug="<?php echo esc_attr( $slug ); ?>">

						<input type="radio"
						       name="kiro_cursor_style"
						       value="<?php echo esc_attr( $slug ); ?>"
						       <?php checked( $cursor_style, $slug ); ?>
						       class="kiro-style-radio">

						<span class="kiro-style-preview"><?php echo esc_html( $meta['preview_emoji'] ); ?></span>
						<span class="kiro-style-label"><?php echo esc_html( $meta['label'] ); ?></span>
						<span class="kiro-style-desc"><?php echo esc_html( $meta['desc'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>

			<?php /* ── SECTION 2: Animation Preset ──────────────────────────────── */ ?>
			<h2 class="kiro-section-title" style="margin-top:32px;">
				<?php esc_html_e( '2. Choose Animation Preset', 'kiro-cursor' ); ?>
			</h2>
			<p class="description" style="margin-bottom:16px;">
				<?php esc_html_e( 'Layered on top of the style. Animations run continuously or respond to movement.', 'kiro-cursor' ); ?>
			</p>

			<div class="kiro-anim-grid" id="kiro-anim-grid">
				<?php foreach ( $animations as $slug => $meta ) : ?>
					<label class="kiro-anim-card <?php echo ( $animation === $slug ) ? 'is-selected' : ''; ?>"
					       data-slug="<?php echo esc_attr( $slug ); ?>">

						<input type="radio"
						       name="kiro_cursor_animation"
						       value="<?php echo esc_attr( $slug ); ?>"
						       <?php checked( $animation, $slug ); ?>
						       class="kiro-anim-radio">

						<span class="kiro-anim-label"><?php echo esc_html( $meta['label'] ); ?></span>
						<span class="kiro-anim-desc"><?php echo esc_html( $meta['desc'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>

			<?php /* ── SECTION 3: Base Settings ─────────────────────────────────── */ ?>
			<h2 class="kiro-section-title" style="margin-top:32px;">
				<?php esc_html_e( '3. Base Settings', 'kiro-cursor' ); ?>
			</h2>

			<table class="form-table kiro-form-table">

				<tr>
					<th scope="row">
						<label for="kiro_cursor_color">
							<?php esc_html_e( 'Cursor Color', 'kiro-cursor' ); ?>
						</label>
					</th>
					<td>
						<input type="color"
						       id="kiro_cursor_color"
						       name="kiro_cursor_color"
						       value="<?php echo esc_attr( $color ); ?>">
						<span id="kiro-color-value" style="margin-left:8px;font-family:monospace;">
							<?php echo esc_html( $color ); ?>
						</span>
						<p class="description">
							<?php esc_html_e( 'Primary color for the cursor dot, ring, and glow effects.', 'kiro-cursor' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="kiro_cursor_size">
							<?php esc_html_e( 'Cursor Dot Size (px)', 'kiro-cursor' ); ?>
						</label>
					</th>
					<td>
						<input type="range"
						       id="kiro_cursor_size"
						       name="kiro_cursor_size"
						       value="<?php echo esc_attr( $size ); ?>"
						       min="4" max="60" step="1"
						       style="width:200px;vertical-align:middle;">
						<span id="kiro-size-value" style="margin-left:8px;font-weight:600;">
							<?php echo esc_html( $size ); ?>px
						</span>
						<p class="description">
							<?php esc_html_e( 'Dot diameter in pixels (4–60). Ring scales automatically at 4×.', 'kiro-cursor' ); ?>
						</p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<?php esc_html_e( 'Disable on Mobile', 'kiro-cursor' ); ?>
					</th>
					<td>
						<label>
							<input type="checkbox"
							       name="kiro_cursor_disable_mobile"
							       value="1"
							       <?php checked( $mobile, 1 ); ?>>
							<?php esc_html_e( 'Hide custom cursor on touch / mobile devices.', 'kiro-cursor' ); ?>
						</label>
					</td>
				</tr>

			</table>

			<p class="submit">
				<input type="submit"
				       name="kiro_cursor_save"
				       class="button button-primary button-large"
				       value="<?php esc_attr_e( 'Save Settings', 'kiro-cursor' ); ?>">
			</p>

		</form>

	</div><!-- .kiro-admin-wrap -->

	<?php /* ── Inline admin CSS + JS (settings page only) ────────────────────── */ ?>
	<style>
		/* ─── Admin Card Grid ─────────────────────────────────────────────── */
		.kiro-section-title {
			font-size: 1.1em;
			font-weight: 700;
			border-bottom: 2px solid #2271b1;
			padding-bottom: 6px;
			color: #2271b1;
		}

		.kiro-style-grid,
		.kiro-anim-grid {
			display: grid;
			gap: 12px;
		}

		.kiro-style-grid {
			grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
		}

		.kiro-anim-grid {
			grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
		}

		.kiro-style-card,
		.kiro-anim-card {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 6px;
			padding: 16px 12px;
			border: 2px solid #ddd;
			border-radius: 10px;
			cursor: pointer;
			background: #fff;
			transition: border-color .2s, box-shadow .2s, background .2s;
			text-align: center;
			position: relative;
		}

		.kiro-style-card input[type="radio"],
		.kiro-anim-card input[type="radio"] {
			/* Visually hidden but accessible to screen readers */
			position: absolute;
			opacity: 0;
			width: 0;
			height: 0;
		}

		.kiro-style-card:hover,
		.kiro-anim-card:hover {
			border-color: #2271b1;
			box-shadow: 0 0 0 3px rgba(34,113,177,.15);
		}

		.kiro-style-card.is-selected,
		.kiro-anim-card.is-selected {
			border-color: #2271b1;
			background: #f0f6fc;
			box-shadow: 0 0 0 3px rgba(34,113,177,.25);
		}

		.kiro-style-card.is-selected::after,
		.kiro-anim-card.is-selected::after {
			content: '✓';
			position: absolute;
			top: 6px;
			right: 8px;
			font-size: 14px;
			color: #2271b1;
			font-weight: 700;
		}

		.kiro-style-preview {
			font-size: 2.2em;
			line-height: 1;
		}

		.kiro-style-label,
		.kiro-anim-label {
			font-weight: 700;
			font-size: .9em;
			color: #1d2327;
		}

		.kiro-style-desc,
		.kiro-anim-desc {
			font-size: .75em;
			color: #666;
			line-height: 1.3;
		}
	</style>

	<script>
	(function() {
		// ── Card selection highlight for style picker ──────────────────────
		document.querySelectorAll('.kiro-style-card').forEach(function(card) {
			card.addEventListener('click', function() {
				document.querySelectorAll('.kiro-style-card').forEach(function(c) {
					c.classList.remove('is-selected');
				});
				card.classList.add('is-selected');
				card.querySelector('input[type="radio"]').checked = true;
			});
		});

		// ── Card selection highlight for animation picker ──────────────────
		document.querySelectorAll('.kiro-anim-card').forEach(function(card) {
			card.addEventListener('click', function() {
				document.querySelectorAll('.kiro-anim-card').forEach(function(c) {
					c.classList.remove('is-selected');
				});
				card.classList.add('is-selected');
				card.querySelector('input[type="radio"]').checked = true;
			});
		});

		// ── Live size display ──────────────────────────────────────────────
		var sizeSlider = document.getElementById('kiro_cursor_size');
		var sizeLabel  = document.getElementById('kiro-size-value');
		if (sizeSlider && sizeLabel) {
			sizeSlider.addEventListener('input', function() {
				sizeLabel.textContent = this.value + 'px';
			});
		}

		// ── Live color hex display ─────────────────────────────────────────
		var colorPicker = document.getElementById('kiro_cursor_color');
		var colorLabel  = document.getElementById('kiro-color-value');
		if (colorPicker && colorLabel) {
			colorPicker.addEventListener('input', function() {
				colorLabel.textContent = this.value;
			});
		}
	})();
	</script>
	<?php
}
