<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PJ_Pixel_Config_Notice class
 *
 * After install, displays configuration notice to assist in setup.
 */
class PJ_Pixel_Config_Notice {

	/**
	 * PJ_Pixel_Config_Notice class instance
	 * 
	 * @var PJ_Pixel_Config_Notice
	 */
	private static $_instance;

	/**
	 * If the notice has been dismissed or Program ID has been configured.
	 * 
	 * @var boolean
	 */
	private $_is_dismissed = false;

	/**
	 * Get the class instance
	 */
	public static function get_instance( $dismissed = false, $pj_program_id = '' ) {
		if ( is_null(self::$_instance ) ) {
			self::$_instance = new self( $dismissed, $pj_program_id );
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	private function __construct( $dismissed = false, $pj_program_id = '' ) {
		$this->_is_dismissed = (bool) $dismissed;
		if ( ! empty( $pj_program_id ) ) {
			$this->_is_dismissed = true;
		}

		// Notice dismissed or plugin assumed to be configured
		if ( true === $this->_is_dismissed ) {
			return;
		}

		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'admin_init', array( $this, 'dismiss_notice' ) );
	}

	/**
	 * Displays an info notice on WooCommerce settings pages
	 */
	public function notice() {
		$screen = get_current_screen();

		if ( ! in_array( $screen->base, array( 'woocommerce_page_wc-settings', 'plugins' ) ) || $screen->is_network || $screen->action ) {
			return;
		}

		$integration_url = esc_url( admin_url('admin.php?page=wc-settings&tab=integration&section=' . PJ()->integration_id) );
		$dismiss_url = $this->dismiss_url();

		$heading = __( 'Pepperjam Pixel &amp; WooCommerce', 'pepperjam' );
		$configure = sprintf( __( '<a href="%s">Configure Pepperjam Pixel settings in WooCommerce</a> to finish setting up this integration.' ), $integration_url );

		// Display notice
		echo '<div class="updated fade"><p><strong>' . $heading . '</strong> ';
		echo '<a href="' . esc_url( $dismiss_url ). '" title="' . __( 'Dismiss this notice.', 'pepperjam' ) . '"> ' . __( '(Dismiss)', 'pepperjam' ) . '</a>';
		echo '<p>' . $configure . "</p></div>\n";
	}

	/**
	 * Returns the url that the user clicks to remove the notice
	 * @return (string)
	 */
	function dismiss_url() {
		$url = admin_url( 'admin.php' );

		$url = add_query_arg( array(
			'page'      => 'wc-settings',
			'tab'       => 'integration',
			'wc-notice' => 'dismiss-notice-pj-pixel',
		), $url );

		return wp_nonce_url( $url, 'dismiss_notice_pj_pixel' );
	}

	/**
	 * Handles the dismiss action and updates site option accordingly
	 */
	function dismiss_notice() {
		if ( ! isset( $_GET['wc-notice'] ) ) {
			return;
		}

		if ( 'dismiss-notice-pj-pixel' !== $_GET['wc-notice'] ) {
			return;
		}

		if ( ! check_admin_referer( 'dismiss_notice_pj_pixel' ) ) {
			return;
		}

		update_option( 'pj_pixel_dismissed_config_notice', true );

		if ( wp_get_referer() ) {
			wp_safe_redirect( wp_get_referer() );
		} else {
			wp_safe_redirect( admin_url( 'admin.php?page=wc-settings&tab=integration' ) );
		}
	}

}
