<?php
/**
 * Plugin Name: Pepperjam Pixel
 * Plugin URI: https://www.pepperjam.com/
 * Description: Extend the WooCommerce platform with Pepperjam pixel.
 * Version: 1.1.1
 * Author: Pepperjam <publisher-support@pepperjam.com>
 * Author URI: https://pepperjam.com
 * Text Domain: pepperjam
 * License: GPLv2 or later
 * Domain Path: languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Pepperjam_Pixel_Plugin' ) ) :

/**
 * Main Pepperjam_Pixel Class.
 *
 * @class Pepperjam_Pixel
 * @version	1.1
 */
final class Pepperjam_Pixel_Plugin {

	/**
	 * Pepperjam_Pixel_Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '1.1.1';

	/**
	 * Integration Class name that will be extending the WooCommerce Integration Class.
	 * 
	 * @var string
	 */
	private $_integration_class_name = 'PJ_Pixel_Integration';

	/**
	 * WooCommerce Integration id.
	 * @var string
	 */
	private $_integration_id = 'pepperjam_pixel';

	/**
	 * The single instance of the class.
	 *
	 * @var Pepperjam_Pixel_Plugin
	 */
	private static $_instance = null;

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'pepperjam' ), '4.5' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'pepperjam' ), '4.5' );
	}

	/**
	 * Pepperjam_Pixel_Plugin Constructor.
	 */
	private function __construct() {
		$this->define_constants();

		// Load plugin text domain
		add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// WooCommerce Installation Check.
		if ( class_exists( 'WC_Integration' ) && defined( 'WOOCOMMERCE_VERSION' ) && version_compare( 
			WOOCOMMERCE_VERSION, '2.1-beta-1', '>=' ) ) {

			include_once 'includes/class-pj-pixel-integration.php';
            include_once 'includes/class-pj-tagcontainer.php';
            new PJ_TagContainer();

			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
		} else {
			add_action( 'admin_notices', array( $this, 'woocommerce_required_notice' ) );
		}
	}

	/**
	 * Auto-load in-accessible properties on demand.
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( in_array( $key, array( 'integration_class_name', 'integration_id' ) ) ) {
			$key = "_{$key}";
			return $this->{$key};
		}
	}

	/**
	 * Main Pepperjam_Pixel_Plugin Instance.
	 *
	 * Ensures only one instance of Pepperjam_Pixel_Plugin is loaded or can be loaded.
	 *
	 * @return Pepperjam_Pixel_Plugin - Main instance.
	 */
	public static function get_instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Define PJ Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir();

		$this->define( 'PJ_PLUGIN_FILE', __FILE__ );
		$this->define( 'PJ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'PJ_LOG_DIR', $upload_dir['basedir'] . '/pj-logs/' );
        $this->define( 'PJ_CLICK_ID_COOKIE', 'pepperjam_click_ids' );
        $this->define( 'PJ_CLICKID_URL_PARAM', 'clickid' );
	}

    /**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		$locale = apply_filters( 'plugin_locale', get_locale(), 'pepperjam' );

		load_textdomain( 'pepperjam', trailingslashit( WP_LANG_DIR ) . 'pepperjam/pepperjam-' . $locale . '.mo' );
		load_plugin_textdomain( 'pepperjam', false, plugin_basename( dirname( plugin_basename( __FILE__ ) ) . '/languages/' ) );
	}

	/**
	 * WooCommerce missing/required notice.
	 *
	 * @return string
	 */
	public function woocommerce_required_notice() {
		echo '<div class="error"><p>' . sprintf( __( 'Pepperjam Pixel requires a recent version of %s.', 'pepperjam' ), '<a href="https://woocommerce.com/" target="_blank">' . __( 'WooCommerce', 'pepperjam' ) . '</a>' ) . '</p></div>';
	}

	/**
	 * Add a new integration to WooCommerce.
	 *
	 * @param  array $integrations WooCommerce integrations.
	 */
	public function add_integration( $integrations ) {
		$integrations[] = $this->_integration_class_name;

		return $integrations;
	}

	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}
}

endif;

/**
 * Main instance of Pepperjam_Pixel_Plugin.
 * @return Pepperjam_Pixel_Plugin
 */
function PJ() {
	return Pepperjam_Pixel_Plugin::get_instance();
}

add_action( 'plugins_loaded', array( 'Pepperjam_Pixel_Plugin', 'get_instance' ), 0 );