<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Pepperjam Pixel Integration
 *
 * Allows for a Pepperjam Pixel to be added to the 'Thank You' page.
 *
 * @class   PJ_Pixel_Integration
 * @extends WC_Integration
 */
class PJ_Pixel_Integration extends WC_Integration {

	/**
	 * If logging has been enabled for this integration.
	 * 
	 * @var boolean
	 */
	private static $_is_logging;

	/**
	 * If testmode is turned on.
	 * 
	 * @var boolean
	 */
	private static $_is_testmode;

	/**
	 * WC_Logger instance.
	 * 
	 * @var WC_Logger
	 */
	private static $_loggger = null;

	/**
	 * Cached option values.
	 * 
	 * @var array
	 */
	private static $_options = array();

	/**
	 * Init and hook in the integration.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id                    = PJ()->integration_id;
		$this->method_title          = __( 'Pepperjam Pixel', 'pepperjam' );
		$this->method_description    = __( 'The Pepperjam Pixel is a small HTML snippet which is generally placed on an order confirmation or "thank you" page after a successful transaction.  It sends the information into the dynamic commissioning system at that time.', 'pepperjam' );
		$this->dismissed_config_notice = get_option( 'pj_pixel_dismissed_config_notice' );

		// Load the settings
		$this->init_form_fields();
		$this->init_settings();
		$this->init_options();

		self::$_is_testmode = ( 'yes' === $this->pj_testmode );

		$this->global_fns();

		// Display a configuration notice
		if ( is_admin() ) {
			include_once( 'class-pj-pixel-config-notice.php' );
			PJ_Pixel_Config_Notice::get_instance( $this->dismissed_config_notice, $this->pj_program_id );
		}

		// Admin Options
		add_filter( 'woocommerce_tracker_data', array( $this, 'track_options' ) );
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options') );
		add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'show_options_info') );
		
		// Use if admin dependency scripts/styles are necessary
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_assets') );

		// Tracking code
		add_action( 'wp_footer', array( $this, 'maybe_display_pj_pixel' ), 99 );

        // Manage click_id cookie
        add_action('init', array($this, 'maybe_write_click_id_cookie'));

		// Test mode meta box for orders
		if ( self::$_is_testmode ) {
			add_action( 'add_meta_boxes', array( $this, 'add_testing_data_to_admin_orders' ), 45 );
		}
	}

	public function add_testing_data_to_admin_orders() {
		add_meta_box( 'pj_pixel_testing_shop_orders', __( 'Pepperjam Pixel Testing', 'pepperjam' ), array( $this, 'display_pixel_test_in_order_admin' ), 'shop_order', 'normal', 'low' );
	}

	/**
	 * Expose global (prefixed) functions for ease of use.
	 */
	public function global_fns() {
		if ( ! function_exists('pj_log') ) {
			function pj_log( $message ) {
				return PJ_Pixel_Integration::log( $message );
			}

			function pj_log_var( $variable ) {
				return PJ_Pixel_Integration::log_variable( $variable );
			}
		}
	}

	/**
	 * Handle plugin log requests.
	 * 
	 * @param  string $message Message to log
	 */
	public static function log( $message ) {
		if ( ! self::$_is_logging ) {
			return;
		}

		if ( is_null( self::$_loggger ) ) {
			if ( ! class_exists( 'WC_Logger' ) ) {
				if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
					error_log( 'PJ_Pixel_Integration: Error: WC_PLUGIN_FILE not defined.  Is WooCommerce installed?' );
					return;
				}
				include_once( plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/class-wc-logger.php' );
			}
			self::$_loggger = new WC_Logger();
		}
		self::$_loggger->add( 'pepperjam', $message );
	}

	public static function log_variable( $variable ) {
		if (is_string($variable)) {
			return PJ_Pixel_Integration::log( 'Variable (string): ' . $variable );
		} else {
			return PJ_Pixel_Integration::log( 'Variable: ' . json_encode($variable) );
		}
	}

	/**
	 * Loads all of our options for this plugin
	 */
	public function init_options() {
		foreach ( array_keys($this->form_fields) as $option ) {
			self::$_options[$option] = $this->{$option} = $this->get_option( $option );
		}
	}

	/**
	 * Tells WooCommerce which settings to display under the "integration" tab
	 */
	public function init_form_fields() {

		$this->form_fields = include('data-pj-form-fields.php');
	}

	/**
	 * Shows some additional help text after saving the settings
	 */
	function show_options_info() {
		$this->method_description .= "<div class='notice notice-info'><p>" . __( 'Please consult with your Pepperjam Account manager regarding your changes and any testing or verification that may be necessary to ensure proper program tracking.', 'pepperjam' ) . "</p></div>";

		$request_key = $this->plugin_id . $this->id . '_pj_program_id';

		if ( isset( $_REQUEST[$request_key] ) && $_REQUEST[$request_key] ) {
			$this->method_description .= "<div class='notice notice-info'><p>" . __( 'Please note, for the Pepperjam Pixel to trigger properly, you will need to use a payment gateway that redirects the customer back to a WooCommerce order received/thank you page.', 'pepperjam' ) . "</div>";
		}
	}

	/**
	 * Hooks into woocommerce_tracker_data and tracks whether or not the program_id has been provided (not the actual id).
	 */
	function track_options( $data ) {
		$data['pj-pixel-integration'] = array(
			'program_id_provided' 	=> ( (bool) $this->pj_program_id )
		);
		return $data;
	}

	/**
	 *
	 */
	function load_admin_assets() {
		$screen = get_current_screen();

		if ( 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		if ( empty( $_GET['tab'] ) ) {
			return;
		}

		if ( 'integration' !== $_GET['tab'] ) {
			return;
		}

		// Enqueue any admin settings here, if necessary
		$wc_admin = include( WC()->plugin_path() . '/includes/admin/class-wc-admin-assets.php' );
		$wc_admin->admin_scripts();
		wp_enqueue_script( 'wc-admin-meta-boxes' );
	}

	/**
	 * Determine if a Pepperjam Pixel should be displayed.
	 */
	public function maybe_display_pj_pixel() {
		global $wp;

		// Check for program id and order received page
		if ( ! $this->pj_program_id || ! is_order_received_page() ) {
			return;
		}

		if ( is_order_received_page() ) {

			$order_id = isset( $wp->query_vars['order-received'] ) ? $wp->query_vars['order-received'] : 0;

			if ( 0 < $order_id && 1 != get_post_meta( $order_id, '_pj_pixel_displayed', true ) ) {
			
				include_once( 'class-pj-pixel.php' );
			
				$pixel = PJ_Pixel::get_instance( $order_id, self::$_options );
				echo $pixel;
			
				if ( !self::$_is_testmode ) {
					update_post_meta( $order_id, '_pj_pixel_displayed', 1 );
				}
			}
		}
	}

	public function display_pixel_test_in_order_admin( $post ) {
		include_once( 'class-pj-pixel.php' );

		$pixel = PJ_Pixel::get_instance( $post->ID, self::$_options );
		echo $pixel;
	}

    public function maybe_write_click_id_cookie()
    {
        $url_params = array_change_key_case($_GET);
        if(isset($url_params[PJ_CLICKID_URL_PARAM]))
        {
            $click_id = $url_params[PJ_CLICKID_URL_PARAM];
            $click_ids = array();

            if(isset($_COOKIE[PJ_CLICK_ID_COOKIE]))
            {
                $click_ids = $this->get_click_id_cookie_as_array();
            }

            // Put click id into an associative array so that any existing click id with the same value is overwritten
            $click_ids[$click_id] = array('timestamp' => time(), 'click_id' => $click_id);

            $cookie_expiration_unix_time = time()+60*60*24*365*10; // 10 year expiration (more or less)
            setcookie(PJ_CLICK_ID_COOKIE, json_encode($click_ids), $cookie_expiration_unix_time, '/');
        }
    }

    public static function get_click_id_cookie_as_array()
    {
        return json_decode(stripslashes($_COOKIE[PJ_CLICK_ID_COOKIE]), true);
    }
}