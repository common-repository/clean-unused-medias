<?php
	//
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	//
	global $wp_query, $wpdb;

	//
	$error    = 1;
	$message  = __( 'Error. Parameters missing.', 'clean-unused-medias' );
	$debug    = '';
	$complete = null;
	$pause    = null;
	$resume   = null;
	$reset    = null;

	//
	ob_start();

if ( ! check_ajax_referer( 'lnjcm_crawl_medias', 'lnjcm_crawl_medias_nonce', false ) ) {
	$message = __( 'Error. Access denied.', 'clean-unused-medias' );
} else {
	//
	$error = 0;

	// reset
	if ( ! empty( $_POST['lnjcm_crawl_medias_reset'] ) ) {
		//
		lnjcm_update_option( 'lnjcm_medias_in_content_index', null );
		lnjcm_update_option( 'lnjcm_medias_in_content_ids', null );
		lnjcm_update_option( 'lnjcm_medias_in_postmeta_ids', null );
		lnjcm_update_option( 'lnjcm_medias_in_usermeta_ids', null );
		lnjcm_update_option( 'lnjcm_medias_in_option_ids', null );
		lnjcm_update_option( 'lnjcm_medias_in_content_completed', null );
		lnjcm_update_option( 'lnjcm_medias_in_content_pause', null );
		//
		$sql = '
	        	DELETE FROM `' . $wpdb->postmeta . "`
	        	WHERE
	        		`meta_key` = 'lnjcm_used_in'
	    	";
		$wpdb->query( $sql );

		//
		$reset = 1;
		//
		$message = __( 'Reset crawl.', 'clean-unused-medias' );
	}

	// pause
	if ( ! empty( $_POST['lnjcm_crawl_medias_pause'] ) ) {
		//
		lnjcm_update_option( 'lnjcm_medias_in_content_pause', 1 );
		//
		$pause = 1;
		//
		$message = __( 'Pause crawl.', 'clean-unused-medias' );
	}

	// resume
	if ( ! empty( $_POST['lnjcm_crawl_medias_resume'] ) ) {
		//
		lnjcm_update_option( 'lnjcm_medias_in_content_pause', null );
		//
		$resume = 1;
		//
		$message = __( 'Resume crawl.', 'clean-unused-medias' );
	}
}

	//
	$debug = ob_get_contents();
	ob_end_clean();

    //
	header( 'Content-Type: text/javascript' );

	//
	$array = array(
		'error'   => $error,
  		'message' => $message,
		'debug'   => $debug,
		'pause'   => $pause,
		'resume'  => $resume,
		'reset'   => $reset,
	);

	//
	echo json_encode( $array );

