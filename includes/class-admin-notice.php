<?php
/**
 * Admin Notice Class
 *
 * Displays admin notice when temporary page mode is active.
 *
 * @package AlmostReadyTemporaryPage
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class ARTP_Admin_Notice
 */
class ARTP_Admin_Notice {

	/**
	 * Initialize the admin notice functionality.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'show_temporary_page_notice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_artp_deactivate_temporary_page', array( __CLASS__, 'ajax_deactivate_temporary_page' ) );
	}

	/**
	 * Show temporary page mode active notice.
	 */
	public static function show_temporary_page_notice() {
		// Only show on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Get fresh page status (bypass cache).
		$temporary_page = get_page_by_path( ARTP_Page_Creator::PAGE_SLUG, OBJECT, 'page' );
		
		if ( ! $temporary_page || 'publish' !== $temporary_page->post_status ) {
			return;
		}

		// Get edit page URL.
		$edit_url = get_edit_post_link( $temporary_page->ID );
		
		?>
		<div class="notice notice-warning is-dismissible artp-temporary-page-notice">
			<p>
				<strong><?php esc_html_e( '✨ Almost Ready Mode is Active.', 'almost-ready-temporary-page' ); ?></strong>
				<?php esc_html_e( 'Visitors see the temporary page. Only logged-in users can access the site.', 'almost-ready-temporary-page' ); ?>
			</p>
			<p>
				<button type="button" class="button button-primary artp-dropdown-toggle">
					<?php esc_html_e( 'Page Options', 'almost-ready-temporary-page' ); ?>
					<span class="dashicons dashicons-arrow-down-alt2" style="margin-left: 5px; margin-top: 3px;"></span>
				</button>
				<div class="artp-dropdown-menu" style="display: none;">
					<a href="<?php echo esc_url( $edit_url ); ?>" class="artp-dropdown-item">
						<span class="dashicons dashicons-edit"></span>
						<?php esc_html_e( 'Edit Temporary Page', 'almost-ready-temporary-page' ); ?>
					</a>
					<a href="#" class="artp-dropdown-item artp-deactivate-link">
						<span class="dashicons dashicons-hidden"></span>
						<?php esc_html_e( 'Deactivate Temporary Page', 'almost-ready-temporary-page' ); ?>
					</a>
				</div>
			</p>
		</div>
		<?php
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public static function enqueue_admin_scripts() {
		// Only enqueue on admin pages where the notice is shown.
		if ( ! is_admin() ) {
			return;
		}

		$temporary_page = ARTP_Page_Creator::get_temporary_page();
		if ( ! $temporary_page || 'publish' !== $temporary_page->post_status ) {
			return;
		}

		// Register and enqueue the inline style.
		wp_register_style( 'artp-admin-style', false );
		wp_enqueue_style( 'artp-admin-style' );
		wp_add_inline_style(
			'artp-admin-style',
			'.artp-dropdown-menu {
				position: absolute;
				background: #fff;
				border: 1px solid #c3c4c7;
				border-radius: 4px;
				box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
				margin-top: 8px;
				z-index: 1000;
				min-width: 250px;
			}
			.artp-dropdown-item {
				display: block;
				padding: 10px 15px;
				color: #2271b1;
				text-decoration: none;
				border-bottom: 1px solid #f0f0f1;
			}
			.artp-dropdown-item:last-child {
				border-bottom: none;
			}
			.artp-dropdown-item:hover {
				background: #f6f7f7;
				color: #135e96;
			}
			.artp-dropdown-item .dashicons {
				margin-right: 8px;
				color: #50575e;
			}
			.artp-dropdown-toggle {
				position: relative;
			}'
		);

		// Prepare localized script data.
		$script_data = array(
			'nonce'              => wp_create_nonce( 'artp_deactivate_temporary_page' ),
			'confirmMessage'     => __( 'Are you sure you want to deactivate the temporary page? Visitors will be able to access your site.', 'almost-ready-temporary-page' ),
			'errorMessage'       => __( 'Error deactivating temporary page. Please try again.', 'almost-ready-temporary-page' ),
		);

		// Register and enqueue the inline script.
		wp_register_script( 'artp-admin-script', false, array( 'jquery' ), ARTP_VERSION, true );
		wp_enqueue_script( 'artp-admin-script' );
		wp_add_inline_script(
			'artp-admin-script',
			'jQuery(document).ready(function($) {
				var artpData = ' . wp_json_encode( $script_data ) . ';

				// Toggle dropdown
				$(".artp-dropdown-toggle").on("click", function(e) {
					e.preventDefault();
					e.stopPropagation();
					$(".artp-dropdown-menu").slideToggle(200);
				});

				// Close dropdown when clicking outside
				$(document).on("click", function(e) {
					if (!$(e.target).closest(".artp-dropdown-toggle, .artp-dropdown-menu").length) {
						$(".artp-dropdown-menu").slideUp(200);
					}
				});

				// Handle deactivate link
				$(".artp-deactivate-link").on("click", function(e) {
					e.preventDefault();
					
					if (!confirm(artpData.confirmMessage)) {
						return;
					}

					$.ajax({
						url: ajaxurl,
						type: "POST",
						data: {
							action: "artp_deactivate_temporary_page",
							nonce: artpData.nonce
						},
						success: function(response) {
							if (response.success) {
								location.reload();
							} else {
								alert(artpData.errorMessage);
							}
						},
						error: function() {
							alert(artpData.errorMessage);
						}
					});
				});
			});'
		);
	}

	/**
	 * AJAX handler to deactivate temporary page mode.
	 */
	public static function ajax_deactivate_temporary_page() {
		// Check nonce.
		check_ajax_referer( 'artp_deactivate_temporary_page', 'nonce' );

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'almost-ready-temporary-page' ) ) );
		}

		// Deactivate temporary page mode.
		ARTP_Page_Creator::deactivate_temporary_page();

		wp_send_json_success( array( 'message' => __( 'Temporary page deactivated.', 'almost-ready-temporary-page' ) ) );
	}
}
