<?php
	//
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	//
	global $wp_query, $wpdb;

	//
	$error                   = 1;
	$message                 = __( 'Error. Parameters missing.', 'clean-unused-medias' );
	$debug                   = '';
	$media_ids_deleted       = array();
	$media_ids_delete_failed = array();

	//
	ob_start();

if ( ! check_ajax_referer( 'lnjcm_do_delete_medias', 'lnjcm_do_delete_medias_nonce', false ) ) {
	$message = __( 'Error. Access denied.', 'clean-unused-medias' );
} else {
	$error   = 0;
	$message = __( 'All good.', 'clean-unused-medias' );
	if ( sizeof( $_POST['media_ids'] ) == 0 ) {
		$message = __( 'No medias were selected.', 'clean-unused-medias' );
	} else {
		foreach ( $_POST['media_ids'] as $media_id ) {
			if ( ! wp_delete_attachment( $media_id, 1 ) ) {
				$media_ids_delete_failed[] = $media_id;
			} else {
				$media_ids_deleted[] = $media_id;
			}
		}
		$message = sprintf( _n( '%s media deleted.', '%s medias deleted.', sizeof( $media_ids_deleted ), 'clean-unused-medias' ), sizeof( $media_ids_deleted ) );
		if ( sizeof( $media_ids_delete_failed ) > 0 ) {
			$message .= ( ! empty( $message ) ) ? ' ' : '';
			$message .= sprintf( _n( "%s media can't be deleted.", "%s medias can't be deleted.", sizeof( $media_ids_delete_failed ), 'clean-unused-medias' ), sizeof( $media_ids_delete_failed ) );
		}
	}
}

	//
	$debug = ob_get_contents();
	ob_end_clean();

    //
	header( 'Content-Type: text/javascript' );

	//
	$array = array(
		'error'                   => $error,
  		'message'                 => $message,
		'debug'                   => $debug,
		'media_ids_deleted'       => $media_ids_deleted,
		'media_ids_delete_failed' => $media_ids_delete_failed,
	);

	//
	echo json_encode( $array );

