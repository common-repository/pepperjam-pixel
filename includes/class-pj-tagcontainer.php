<?php
/**
 * Pepperjam Pixel
 *
 * $order_id exposed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class PJ_TagContainer
{
    /**
     * The user supplied Tag Container ID
     *
     * @var $tag_container_id
     */
    private $_tag_container_id;

    private $_tag_container_start_code = '<!--START Pepperjam CODE--> <noscript> <iframe src="//nojscontainer.pepperjam.com/<<identifier>>.html" width="1" height="1" frameborder="0"></iframe> </noscript> <script> (function(){ var a=document.createElement("script"); a.type="text/javascript", a.async=!0, a.src="//container.pepperjam.com/';
    private $_tag_container_end_code  = '.js"; var b=document.getElementsByTagName("head")[0]; if(b) b.appendChild(a,b); else { var b=document.getElementsByTagName("script")[0];b.parentNode.insertBefore(a,b) } })(); </script> <!--END Pepperjam CODE-->';


    /**
	 * Adds tag container hook if one is defined
     *
	 * @return void
	 */
	public function __construct() {
        add_action( 'wp_footer', array($this, 'maybe_insert_tag_container') );
		if (!$this->_tag_container_id) {
            $this->_tag_container_id = get_option('woocommerce_pepperjam_pixel_settings')['pj_tag_container_id'];
		}
	}

    public function maybe_insert_tag_container()
    {
        if(!empty($this->_tag_container_id))
        {
            echo $this->_tag_container_start_code . $this->_tag_container_id . $this->_tag_container_end_code;
        }
    }

}