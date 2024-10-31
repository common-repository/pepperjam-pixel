<?php
/**
 * Pepperjam Pixel
 *
 * $order_id exposed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PJ_Pixel {

	/**
	 * WC_Coupon instances of coupons used in this order
	 * 
	 * @var WC_Coupon
	 */
	public $wc_coupons_used;

	/**
	 * If the script is running in test mode.
	 * 
	 * @var bool
	 */
	private $_is_testmode;

	/**
	 * WC_Order instance
	 * 
	 * @var WC_Order
	 */
	private $_wc_order;

	/**
	 * Container for coupon data.
	 * 
	 * @var Array
	 */
	private $_coupon_data;

	/**
	 * Container for product data.
	 * 
	 * @var Array
	 */
	private $_product_data;

	/**
	 * Order subtotal. Once set will not recalculate.
	 * 
	 * @var Float
	 */
	private $_order_subtotal;

	/**
	 * Regex for string replacement in order ids.
	 * 
	 * @var string
	 */
	private static $_regex_allowed_characters_order_id_as_not = '/[^A-Za-z0-9\-\_]/';
	
	/**
	 * Regex for string replacement in item ids.
	 * 
	 * @var string
	 */
	private static $_regex_allowed_characters_item_id_as_not = '/[^A-Za-z0-9\-\_\.\:]/';

	/**
	 * An array containing instances of PJ_Pixel indexed by order id.
	 *
	 * @var Array
	 */
	private static $_instances = array();

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
	 * Returns the code for the pixel html string.
	 * 
	 * @return string
	 */
	public function __tostring() {
		return $this->pixel_html;
	}

	/**
	 * Init and hook in the integration.
	 *
	 * @param Integer   $order_id   Order number to use for generating pixel data
	 * @param Array     $options    An array of loaded pj_ site option values
	 *
	 * @return void
	 */
	private function __construct( $order_id, $options ) {
		$this->_wc_order = new WC_Order( $order_id );
		if ( ! $options ) {
			$options = get_option( 'woocommerce_pepperjam_pixel_settings', array() );
		}
		$this->_load_helper_functions();
		$this->_load_options( $options );
		$this->create_pixel();
	}

	/**
	 * Utilize existing WooCommerce functions for manipulating order values.
	 */
	private function _load_helper_functions() {
		if ( ! defined( 'WC_PLUGIN_FILE' ) ) {
			pj_log( 'PJ_Pixel_Integration: Error: WC_PLUGIN_FILE not defined.  Is WooCommerce installed?' );
			return;
		}
		include_once( plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/wc-cart-functions.php' );
	}

	/**
	 * Main PJ_Pixel Instances.
	 *
	 * Ensures only one instance of PJ_Pixel is loaded or can be loaded per order id.
	 *
	 * @return PJ_Pixel
	 */
	public static function get_instance( $order_id, $options = array() ) {
		if ( ! isset( self::$_instances[$order_id] ) ) {
			self::$_instances[$order_id] = new self( $order_id, $options );
		}
		return self::$_instances[$order_id];
	}

	/**
	 * Normalizes values for third party ids.
	 * 
	 * @param  string  $third_party_id 	Third party id value to be normalized
	 * @param  boolean $is_item_id 		If the id is for items.  Additional allowed characters
	 * 
	 * @return string   Normalized id value
	 */
	public static function normalize_third_party_ids( $id, $is_item_id = false ) {
		$regex_str_not_chars = $is_item_id ? self::$_regex_allowed_characters_item_id_as_not : self::$_regex_allowed_characters_order_id_as_not;

		return preg_replace( $regex_str_not_chars, '', $id );
	}

	/**
	 * Loads options.  If option value recieved is not set, gets option from site settings.
	 * 
	 * @param  Array $options Array of received option values
	 */
	private function _load_options( $options ) {
		$this->program_id 		= isset( $options['pj_program_id'] ) 		? $options['pj_program_id'] 		: '';
		$this->integration_type = isset( $options['pj_integration_type'] ) 	? $options['pj_integration_type'] 	: '';
		$this->tracking_url 	= isset( $options['pj_tracking_url'] ) 		? $options['pj_tracking_url'] 		: '';
        $this->lookback_days 	= isset( $options['pj_lookback_days'] )     ? $options['pj_lookback_days'] 		: 60;
		$this->date_implemented = isset( $options['pj_date_implemented'] ) 	? $options['pj_date_implemented'] 	: '';
		$this->_is_testmode = ( isset( $options['pj_testmode'] ) && 'yes' === $options['pj_testmode'] );
	}

	/**
	 * Creates the pixel from the order data.
	 * 
	 * @return string The pixel html code
	 */
	public function create_pixel() {

		if ( ! isset( $this->pixel_html ) ) {

			if ( ! $this->is_order_valid() ) {
				return false;
			}

			// Base Data
			$base_params = array(
				'INT' 			=> $this->integration_type,
				'PROGRAM_ID' 	=> $this->program_id,
				'ORDER_ID' 		=> static::normalize_third_party_ids( $this->_wc_order->id )
			);

			// Is customer a new client
			$base_params['NEW_TO_FILE'] = $this->is_new_client();

			// Process Order Data
			$order_params = $this->_process_order_data();

            // Get comma delimited string of stored click_ids created within the lookback period
            $click_ids_in_cookie = PJ_Pixel_Integration::get_click_id_cookie_as_array();
            if(isset($click_ids_in_cookie))
            {
                $cutoffTimestamp = time() - 60*60*24*$this->lookback_days;
                $click_id_params = array();
                foreach($click_ids_in_cookie as $click_id => $data)
                {
                    if($data['timestamp'] > $cutoffTimestamp)
                    {
                        $click_id_params[] = $click_id;
                    }
                }
                $base_params['CLICK_ID'] = implode(",", $click_id_params);
            }

			// Merge data
			$pixel_params = array_merge( $base_params, $order_params );

			// Create pixel html
			$this->pixel_html = $this->_pixel_html_from_params( $pixel_params );

		}

		return $this->pixel_html;
	}

	/**
	 * Checks the validity of the WC_Order.
	 * 
	 * @return boolean
	 */
	public function is_order_valid() {
		return ( ($this->_wc_order instanceof WC_Order) || ! $this->_wc_order->has_status( 'failed' ) );
	}

	/**
	 * Determines if the order is from a new client based on the used billing email.
	 * 
	 * @return boolean
	 */
	public function is_new_client() {
		
		$orders_billing_email = $this->_wc_order->billing_email;
		
		$order_query_args = array(
			'post_type' 		=> 'shop_order',
			'post_status' 		=> array( 'wc-processing', 'wc-completed' ),
			'fields' 			=> 'ids',
			'meta_key' 			=> '_billing_email',
			'meta_value' 		=> $orders_billing_email,
			'posts_per_page' 	=> '-1'
		);

		if ( isset( $this->date_implemented ) && $this->date_implemented ) {
			$order_query_args['date_query'] = array(
				'after' => $this->date_implemented
			);
		}

		$order_query_r = new WP_Query($order_query_args);

		wp_reset_postdata();

		$orders_count_by_billing_email = $order_query_r->post_count;

		return ( 1 === $orders_count_by_billing_email );
	}

	/**
	 * Process the order data; coupons and products.
	 * 
	 * @return array 	Array of items related to coupons and products
	 */
	private function _process_order_data() {

		$order_params = array();

		if ( $this->_process_coupons() ) {
			$order_params['COUPON'] = $this->_process_coupons();
		}

		$order_subtotal = $this->_process_order_items();

		// Adjust global factor discount
		$factor_adjustment_cart_units 	= $this->_coupon_data['cart_discount_units'] / $order_subtotal;

        // CommerceStack: Cart percent discounts are included as part of $product['coupons'] below as of WooCommerce 3.x.
        // We therefore remove the global cart percent discount adjustment so that we don't double count if WC > 3.0.
        $wc_major_version = (int)substr(WC()->version, 0, 1);
        if($wc_major_version < 3)
        {
            $factor_adjustment_cart_percent = $this->_coupon_data['cart_discount_percent'] / 100;
            $total_factor_adjustment = ( 1 - ( $factor_adjustment_cart_units + $factor_adjustment_cart_percent ) );
        }
        else
        {
            $total_factor_adjustment = ( 1 - $factor_adjustment_cart_units );
        }

		foreach ( $this->_product_data as $index => $product ) {

			// Product numbering is base 1
			$product_number = $index + 1;

			$order_params['ITEM_ID' . $product_number] = self::normalize_third_party_ids( $product['id'], true );
			$order_params['ITEM_PRICE' . $product_number] = ( ( $product['price'] * $total_factor_adjustment ) - $product['coupons'] );
			if ( $product['category'] ) {
				$order_params['CATEGORY' . $product_number] = $product['category'];
			}
			$order_params['QUANTITY' . $product_number] = $product['quantity'];
		}

		return $order_params;
	}

	/**
	 * Gets an array of categories based on the product instance type and value.
	 * 
	 * @param  WC_Product $product_instance WC_Product instance of the product.
	 * @return array                   Array of normalized category names
	 */
	private function _get_category_data_from_product( $product_instance ) {

		$_categories_arr = array();

		$cat_terms = get_the_terms( $product_instance->id, 'product_cat' );

		if ( $cat_terms ) {
			foreach ( $cat_terms as $cat_obj ) {
				$_categories_arr[] = $cat_obj->name;
			}
		}

		// Normalize category names and format
		$_categories_arr = array_map( function($cat_name) {
			return PJ_Pixel::normalize_third_party_ids( $cat_name );
		}, $_categories_arr);

		return $_categories_arr;
	}

	/**
	 * Processes the ordered product items.
	 * 
	 * @return float 	Order subtotal
	 */
	private function _process_order_items() {

		// Coupons have to have been processed before order items
		if ( ! isset( $this->coupon_codes_used ) ) {
			$this->_process_coupons();
		}

		// Do not recalculate
		if ( ! isset( $this->_order_subtotal ) ) {

			// Base order subtotal
			$this->_order_subtotal = 0;
			
			$current_order_items = $this->_wc_order->get_items();

			$this->_product_data = array();
			foreach ( $current_order_items as $item ) {

				$_product = $this->_wc_order->get_product_from_item( $item );

				// Get category data arr
				$_categories_arr = $this->_get_category_data_from_product( $_product );

				// Get product id (sku > product id)
				$_id = !!$_product->get_sku() ? $_product->get_sku() : $product->id;

				// Get price of the product (without coupons), if there is a global sale price, this will be that price
				$_price = (float) $_product->get_price_excluding_tax();

				// Base discount for this item
				$discounted_amt = 0;

                // Product Qty
                $_qty = intval( $item['qty'] );

				// Check for any coupons that apply to this item and add to data
				foreach ( $this->wc_coupons_used as $wc_coupon ) {
					
					// Validate coupons used for this product
					if ( ! $wc_coupon->is_valid_for_product( $_product ) ) {
						continue;
					}

                    // CommerceStack: We don't have the original cart_item to give to WC_Coupon::get_discount_amount()
                    // below so it doesn't take the limit_usage_to_x_items into account. We borrow from it and
                    // take it into account ourselves here
                    if ( $wc_coupon->is_type( array( 'percent', 'percent_product', 'fixed_product' ) )  && $wc_coupon->limit_usage_to_x_items != '') {
                        $limit_usage_qty = min( $wc_coupon->limit_usage_to_x_items, $_qty );
                        $discount = ( $wc_coupon->get_discount_amount( $_price ) * $limit_usage_qty ) / $_qty;
                    }
                    else {
                        $discount = $wc_coupon->get_discount_amount( $_price );
                    }

                    $discounted_amt += $discount;
				}

				// Add to subtotal
				$this->_order_subtotal += ( $_price * $_qty );

				// Adjust price with any coupons used
				$this->_product_data[] = array(
					"id" 		=> $_id,
					"category" 	=> implode( ',', $_categories_arr ),
					"price" 	=> $_price,
					"coupons" 	=> $discounted_amt,
					"quantity" 	=> $_qty
				);
			}
		}

		return $this->_order_subtotal;
	}

	/**
	 * Sets the coupon data to be applied to this pixel's data.  Returns formatted list of coupon codes.
	 * 
	 * @return string Formatted list of coupon codes
	 */
	private function _process_coupons() {

		// Do not recalculate
		if ( ! isset( $this->coupon_codes_used ) ) {

			$this->_coupon_data = array(
				'cart_discount_percent' => 0,
				'cart_discount_units' 	=> 0
			);

			$coupon_codes_used_arr = $this->_wc_order->get_used_coupons();
			
			// Container for WC_Coupon instances
			$this->wc_coupons_used = array();

			foreach ( $coupon_codes_used_arr as $coupon_code ) {

				$wc_coupon = new WC_Coupon( $coupon_code );
				
				$this->wc_coupons_used[] = $wc_coupon;
				
				// Get CART discounts to be applied in final product pricing
				if ( $wc_coupon->is_type('fixed_cart') ) {

					$this->_coupon_data['cart_discount_units'] += (float) $wc_coupon->coupon_amount;
				
				} elseif ( $wc_coupon->is_type('percent') ) {
				
					$this->_coupon_data['cart_discount_percent'] += (float) $wc_coupon->coupon_amount;
				
				}
			
			}

			$this->coupon_codes_used = strtoupper( implode(',', $coupon_codes_used_arr) );
		}
		
		return $this->coupon_codes_used;
	}

	/**
	 * Creates the html iframe code for the pixel.  In test mode, returns html encoded for display only.
	 * 
	 * @param  array $params Paramters array for the src attribute
	 * @return string         Html string
	 */
	private function _pixel_html_from_params( $params ) {
		$iframe_html = sprintf( '<iframe src="%strack?%s" width="1" height="1" frameborder="0"></iframe>', trailingslashit( $this->tracking_url ), build_query( $params ) );

		if ( is_admin() && $this->_is_testmode ) {

			$wrapper = '<code style="display: block; background-color: #fbcfcf; border: 2px solid #ff2626;">%s</code>';

			$iframe_html = sprintf( $wrapper, htmlspecialchars($iframe_html) );

			// add line breaks for visibility
			$iframe_html = str_replace( '&amp;', '<br>&amp;', $iframe_html );
		}

		return $iframe_html;
	}
}