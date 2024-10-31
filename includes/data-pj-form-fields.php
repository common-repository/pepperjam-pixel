<?php 
/**
 * Data for options page.
 */

return array(
	'pj_program_id' => array(
		'title' 		=> __( 'Program ID', 'pepperjam' ),
		'description' 	=> __( 'Your program ID (Required). Your Account Manager will have this information.', 'pepperjam' ),
		'type' 			=> 'text',
		'placeholder' 	=> __( 'Program ID (Required)', 'pepperjam' ),
		'default' 		=> ''
	),
	'pj_integration_type' => array(
		'title' 		=> __( 'Integration Type', 'pepperjam' ),
		'description' 	=> __( 'Type of integration (Required). Your Account Manager will have this information.', 'pepperjam' ),
		'type' 			=> 'select',
		'placeholder' 	=> __( 'Integration Type', 'pepperjam' ),
		'options' 		=> array(
				'DYNAMIC' => 'DYNAMIC'
			),
		'default' 		=> 'DYNAMIC'
	),
	'pj_tracking_url' => array(
		'title'         => __( 'Tracking Url', 'pepperjam' ),
		'description'   =>  __( 'Base URL used for pixel data.', 'pepperjam' ),
		'type'          => 'select',
		'options' 		=> array(
				'https://t.pepperjamnetwork.com' => 'https://t.pepperjamnetwork.com'
			),
		'default'       => 'https://t.pepperjamnetwork.com'
	),
    'pj_lookback_days' => array(
        'title'         => __( 'Lookback period', 'pepperjam' ),
        'description'   =>  __( 'Number of days.', 'pepperjam' ),
        'type'          => 'text',
        'options' 		=> array(
            'https://t.pepperjamnetwork.com' => 'https://t.pepperjamnetwork.com'
        ),
        'default'       => '60'
    ),
	'pj_date_implemented' => array(
		'title' 		=> __( 'Program Implementation Date', 'pepperjam' ),
		'placeholder' 	=> _x( 'YYYY-MM-DD', 'placeholder', 'pepperjam' ),
		'description' 	=> __( 'If provided, limits check of previous order history to the date and earlier.', 'pepperjam' ),
		'class' 		=> 'date-picker',
		'custom_attributes' => array(
			'pattern' 		=> "[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"
		)
	),
    'pj_tag_container_id' => array(
        'title'         => __( 'Tag Container ID', 'pepperjam' ),
        'description'   =>  __( 'Your ID can be found on the Tracking Integration page, under the Resources category within Pepperjam Network.', 'pepperjam' ),
        'type'          => 'text'
    ),
	// 'pj_logging' => array(
	// 	'label'         => __( 'Enable Development Logging', 'pepperjam' ),
	// 	'description'   => __( 'Logs order and pixel data to log file', 'pepperjam' ),
	// 	'type'          => 'checkbox',
	// 	'checkboxgroup' => '',
	// 	'default'       => 'no'
	// ),
	'pj_testmode' => array(
		'label'         => __( 'Enable Development Testmode', 'pepperjam' ),
		'description'   => __( 'Displays the pixel code, without processing, within the order admin summary.  On Thank You Page, processes as normal.', 'pepperjam' ),
		'type'          => 'checkbox',
		'checkboxgroup' => '',
		'default'       => 'no'
	)
);