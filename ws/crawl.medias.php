<?php
//
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

//
global $wp_query, $wpdb;

//
$error                         = 1;
$message                       = __( 'Error. Parameters missing.', 'clean-unused-medias' );
$debug                         = '';
$complete                      = null;
$pause                         = null;
$resume                        = null;
$percent                       = 0;
$lnjcm_medias_in_content_index = 0;

//
ob_start();

if ( ! check_ajax_referer( 'lnjcm_crawl_medias', 'lnjcm_crawl_medias_nonce', false ) ) {
	// if (true) {
	$message = __( 'Error. Access denied.', 'clean-unused-medias' );
} else {
	//
	wp_clear_scheduled_hook( 'lnjcm_check_medias_in_content' );

	//
	$error = 0;

	// get nb total attachments
	$sql           = '
				SELECT DISTINCT `ID`
				FROM `' . $wpdb->posts . "`
				WHERE
					`post_type` = 'attachment'
				ORDER BY `post_date_gmt` DESC
			";
	$all_media_ids = $wpdb->get_col( $sql );
	$total         = sizeof( $all_media_ids );

	//
	$lnjcm_medias_in_content_index       = lnjcm_get_option( 'lnjcm_medias_in_content_index' );
	$lnjcm_medias_in_content_index       = ( empty( $lnjcm_medias_in_content_index ) ) ? 0 : $lnjcm_medias_in_content_index;
	$lnjcm_medias_in_content_index_first = $lnjcm_medias_in_content_index;

	//
	$percent = floor( $lnjcm_medias_in_content_index / $total * 100 );
	$percent = ( $percent > 100 ) ? 100 : $percent;

	//
	if ( lnjcm_get_option( 'lnjcm_medias_in_content_processing' ) ) {
		$message  = __( 'The crawl is already processing somewhere else (in WP-Cron or in another session).', 'clean-unused-medias' );
		$message .= sprintf( __( ' <strong>%1$s&percnt;</strong> (%2$s/%3$s) actually treated.', 'clean-unused-medias' ), $percent, $lnjcm_medias_in_content_index, $total );
		$message .= __( ' <a href="#lnjcm-refresh" class="lnjcm-refresh-result">Refresh the results</a> |', 'clean-unused-medias' );
		$message .= __( ' <a href="#lnjcm-pause" class="lnjcm-pause">Stop the crawler</a>', 'clean-unused-medias' );
	} else {
		//
		lnjcm_update_option( 'lnjcm_medias_in_content_processing', 1 );

		//
		if ( lnjcm_get_option( 'lnjcm_medias_in_content_pause' ) == 1 ) {
			$message  = __( 'The crawl was stopped.', 'clean-unused-medias' );
			$message .= sprintf( __( ' <strong>%1$s&percnt;</strong> (%2$s/%3$s) actually treated.', 'clean-unused-medias' ), $percent, $lnjcm_medias_in_content_index, $total );
			$message .= __( ' <a href="#lnjcm-recrawl" class="lnjcm-recrawl">Relaunch the crawl</a> or <a href="#lnjcm-resume" class="lnjcm-resume">resume</a> the lastest one</a>.', 'clean-unused-medias' );
			$message .= '<span class="lnjcm-loading-bar"><span style="width: ' . $percent . '%; background: #ffb900;"></span></span>';
			$pause    = 1;
		} else {
			// check if new medias uploaded since last crawl completed
			$lnjcm_medias_in_content_completed_date = lnjcm_get_option( 'lnjcm_medias_in_content_completed_date' );
			if ( ! empty( $lnjcm_medias_in_content_completed_date ) ) {
				$sql              = '
							SELECT DISTINCT `ID`
							FROM `' . $wpdb->posts . "`
							WHERE
								`post_type` = 'attachment'
							AND `post_date_gmt` > '" . $lnjcm_medias_in_content_completed_date . "'
							ORDER BY `post_date_gmt` DESC
						";
				$recent_media_ids = $wpdb->get_col( $sql );
				// if true, then resume
				if ( sizeof( $recent_media_ids ) > 0 ) {
					lnjcm_update_option( 'lnjcm_medias_in_content_completed', null );
					//
					$lnjcm_medias_in_content_index = $total - sizeof( $recent_media_ids );
					lnjcm_update_option( 'lnjcm_medias_in_content_index', $lnjcm_medias_in_content_index );
				}
			}

			// do it
			cron_lnjcm_check_medias_in_content();

			//
			$lnjcm_medias_in_content_index          = lnjcm_get_option( 'lnjcm_medias_in_content_index' );
			$lnjcm_medias_in_content_index          = ( empty( $lnjcm_medias_in_content_index ) ) ? 0 : $lnjcm_medias_in_content_index;
			$lnjcm_medias_in_content_completed      = lnjcm_get_option( 'lnjcm_medias_in_content_completed' );
			$lnjcm_medias_in_content_completed_date = lnjcm_get_option( 'lnjcm_medias_in_content_completed_date' );
			$lnjcm_medias_in_content_completed_date = get_date_from_gmt( $lnjcm_medias_in_content_completed_date, 'Y-m-d H:i:s' );

			//
			$percent = floor( $lnjcm_medias_in_content_index / $total * 100 );
			$percent = ( $percent > 100 ) ? 100 : $percent;

			//
			if ( ! empty( $lnjcm_medias_in_content_completed ) || $lnjcm_medias_in_content_index >= $total ) {
				//
				$message  = sprintf(
					__( 'The crawl was completed on %1$s at %2$s.', 'clean-unused-medias' ),
					date_i18n( lnjcm_get_option( 'date_format' ), strtotime( $lnjcm_medias_in_content_completed_date ) ),
					date_i18n( lnjcm_get_option( 'time_format' ), strtotime( $lnjcm_medias_in_content_completed_date ) )
				);
				$message .= sprintf( _n( ' %s media treated.', ' %s medias treated.', $total, 'clean-unused-medias' ), $total );
				$message .= __( ' <a href="#lnjcm-refresh" class="lnjcm-refresh-result">Refresh the results</a> |', 'clean-unused-medias' );
				$message .= __( ' <a href="#lnjcm-recrawl" class="lnjcm-recrawl">Relaunch the crawl</a>', 'clean-unused-medias' );
				$message .= '<span class="lnjcm-loading-bar"><span style="width: 100%; background: #46b450;"></span></span>';
				$complete = 1;
			} else {
				if ( lnjcm_get_option( 'lnjcm_medias_in_content_pause' ) == 1 ) {
					//
					$message  = __( 'The crawl was stopped.', 'clean-unused-medias' );
					$message .= sprintf( __( ' <strong>%1$s&percnt;</strong> (%2$s/%3$s) actually treated.', 'clean-unused-medias' ), $percent, $lnjcm_medias_in_content_index, $total );
					$message .= __( ' <a href="#lnjcm-recrawl" class="lnjcm-recrawl">Relaunch the crawl</a> or <a href="#lnjcm-resume" class="lnjcm-resume">resume</a> the lastest one</a>.', 'clean-unused-medias' );
					$message .= '<span class="lnjcm-loading-bar"><span style="width: ' . $percent . '%; background: #ffb900;"></span></span>';
					$pause    = 1;
				} else {
					//
					$message  = sprintf( __( '<strong>%1$s&percnt;</strong> (%2$s/%3$s) actually treated.', 'clean-unused-medias' ), $percent, $lnjcm_medias_in_content_index, $total );
					$message .= __( ' <a href="#lnjcm-refresh" class="lnjcm-refresh-result">Refresh the results</a> |', 'clean-unused-medias' );
					$message .= __( ' <a href="#lnjcm-pause" class="lnjcm-pause">Stop the crawler</a>', 'clean-unused-medias' );
					$message .= '<span class="lnjcm-loading-bar"><span style="width: ' . $percent . '%;"></span></span>';
				}
			}
		}
	}
}

//
$debug = ob_get_contents();
ob_end_clean();

//
lnjcm_update_option( 'lnjcm_medias_in_content_processing', null );

//
if ( ! wp_next_scheduled( 'lnjcm_check_medias_in_content' ) ) {
	wp_schedule_event( time(), 'minutely', 'lnjcm_check_medias_in_content' );
}

//
header( 'Content-Type: text/javascript' );

//
$array = array(
	'error'                         => $error,
	'message'                       => $message,
	'debug'                         => $debug,
	'complete'                      => $complete,
	'pause'                         => $pause,
	'resume'                        => $resume,
	'percent'                       => $percent,
	'lnjcm_medias_in_content_index' => $lnjcm_medias_in_content_index,
);

//
echo json_encode( $array );

