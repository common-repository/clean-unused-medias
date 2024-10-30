<?php
	//
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

	//
	global $wp_query, $wpdb, $medias_meta_keys;

	//
	$lnjcm_medias_per_page = ( ! empty( $_POST['lnjcm_medias_per_page'] ) ) ? $_POST['lnjcm_medias_per_page'] : 12;
	$medias_used           = array( -1 );
	$medias_keyword        = array();
	$sql_keyword           = '';

	//
if ( empty( $_POST['paged'] ) || $_POST['paged'] == 1 ) {
	$paged  = 1;
	$offset = 0;
} else {
	$paged  = $_POST['paged'];
	$offset = ( $_POST['paged'] - 1 ) * $lnjcm_medias_per_page;
}

	//
	$error         = 1;
	$message_error = __( 'Error. Parameters missing.', 'clean-unused-medias' );
	$message       = '';

	//
	ob_start();

if ( ! check_ajax_referer( 'lnjcm_list_medias', 'lnjcm_list_medias_nonce', false ) ) {
	$message_error = __( 'Error. Access denied.', 'clean-unused-medias' );
} else {
	//
	$error         = 0;
	$message_error = '';

	// keyword
	if ( ! empty( $_POST['lnjcm_medias_keyword'] ) ) {
		$medias_keyword = array( -1 );
		//
		$sql            = '
	        	SELECT DISTINCT `ID`
	        	FROM `' . $wpdb->posts . "`
	        	WHERE
	        		`post_type` = 'attachment'
	        	AND (
	        			`post_title` LIKE '%" . $_POST['lnjcm_medias_keyword'] . "%'
	        		OR	`post_name` LIKE '%" . $_POST['lnjcm_medias_keyword'] . "%'
	        		OR	`post_content` LIKE '%" . $_POST['lnjcm_medias_keyword'] . "%'
	        	)
	    		ORDER BY `post_date_gmt` DESC
	        ";
		$medias_keyword = array_merge( $medias_keyword, $wpdb->get_col( $sql ) );
		$sql_keyword    = ' AND `ID` IN (' . implode( ',', $medias_keyword ) . ')';
	}

	// medias favicon
	$medias_favicon = array();
	if ( ! empty( $_POST['lnjcm_medias_not_theme_customise'] ) ) {
		$media_favicon = lnjcm_get_option( 'site_icon' );
		if ( ! empty( $media_favicon ) ) {
			$medias_favicon[] = $media_favicon;
		}
	}

	// featured medias
	$featured_medias = array();
	if ( ! empty( $_POST['lnjcm_medias_not_thumb'] ) ) {
		$sql             = 'SELECT DISTINCT `meta_value` FROM `' . $wpdb->postmeta . "` WHERE `meta_key` = '_thumbnail_id'";
		$featured_medias = $wpdb->get_col( $sql );
	}

	// related medias
	$related_medias = array();
	if ( ! empty( $_POST['lnjcm_medias_not_related'] ) ) {
		$sql            = 'SELECT DISTINCT `ID` FROM `' . $wpdb->posts . "` WHERE `post_parent` > 0 AND `post_type` = 'attachment'";
		$related_medias = $wpdb->get_col( $sql );
	}

	// medias in content
	$in_content_medias = array();
	if ( ! empty( $_POST['lnjcm_medias_not_in_content'] ) ) {
		$in_content_medias = lnjcm_get_option( 'lnjcm_medias_in_content_ids' );
		$in_content_medias = ( empty( $in_content_medias ) ) ? array() : unserialize( $in_content_medias );
	}

	// medias in postmeta
	$in_postmeta_medias = array();
	if ( ! empty( $_POST['lnjcm_medias_not_in_postmeta'] ) ) {
		$in_postmeta_medias = lnjcm_get_option( 'lnjcm_medias_in_postmeta_ids' );
		$in_postmeta_medias = ( empty( $in_postmeta_medias ) ) ? array() : unserialize( $in_postmeta_medias );
	}

	// medias in usermeta
	$in_usermeta_medias = array();
	if ( ! empty( $_POST['lnjcm_medias_not_in_usermeta'] ) ) {
		$in_usermeta_medias = lnjcm_get_option( 'lnjcm_medias_in_usermeta_ids' );
		$in_usermeta_medias = ( empty( $in_usermeta_medias ) ) ? array() : unserialize( $in_usermeta_medias );
	}

	// medias in option
	$in_option_medias = array();
	if ( ! empty( $_POST['lnjcm_medias_not_in_option'] ) ) {
		$in_option_medias = lnjcm_get_option( 'lnjcm_medias_in_option_ids' );
		$in_option_medias = ( empty( $in_option_medias ) ) ? array() : unserialize( $in_option_medias );
	}

	// medias in ACF
	$acf_medias = array();
	if ( ! empty( $_POST['lnjcm_medias_not_in_acf'] ) ) {
		//
		$sql = '
	        	SELECT DISTINCT `meta_value`
	        	FROM `' . $wpdb->postmeta . '`
	        	WHERE
	        		`meta_key` IN (' . implode( ',', $medias_meta_keys ) . ")
	        	AND `meta_value` REGEXP '[0-9]+'
	        ";
		// echo $sql;
		$acf_medias = $wpdb->get_col( $sql );
	}

	//
	$medias_used = array_merge( $medias_used, $medias_favicon, $featured_medias, $related_medias, $in_content_medias, $in_postmeta_medias, $in_usermeta_medias, $in_option_medias, $acf_medias );
	$medias_used = array_unique( $medias_used );

	//
	$sql        = '
        	SELECT DISTINCT `ID`
        	FROM `' . $wpdb->posts . "`
        	WHERE
        		`post_type` = 'attachment'
        	AND `ID` NOT IN (" . implode( ',', $medias_used ) . ')
        	' . $sql_keyword . '
    		ORDER BY `post_date_gmt` DESC
        ';
	$all_medias = $wpdb->get_results( $sql );
	$total      = sizeof( $all_medias );

	//
	$sql   .= 'LIMIT ' . $offset . ', ' . $lnjcm_medias_per_page;
	$medias = $wpdb->get_results( $sql );

	//
	if ( sizeof( $medias ) == 0 ) {
		_e( 'No result matching to filters.', 'clean-unused-medias' );
	} else {
		printf( _n( '<strong>%s</strong> media found.', '<strong>%s</strong> medias found.', $total, 'clean-unused-medias' ), $total );
		echo '<hr />';
		echo '
	        	<p>
	        		<input type="button" class="button" id="btn-select-medias" name="btn-select-medias" value="' . __( 'Check / Uncheck all', 'clean-unused-medias' ) . '" />
					<input type="submit" class="button button-primary" name="btn-delete-medias" value="' . __( 'Delete selected medias', 'clean-unused-medias' ) . '" />
		            <span class="result"></span>
				</p>
    		';
		echo '<hr />';
		echo '<div class="lnjcm-medias">';
		//
		foreach ( $medias as $media ) {
			//
			echo '<div class="lnjcm-media">';
			$attachment = get_post( $media->ID );
			$_url       = wp_get_attachment_url( $attachment->ID );
			$thumb      = wp_get_attachment_image_src( $media->ID, array( 150, 150 ) );
			echo '<a href="' . $_url . '" target="_blank"><img src="' . $thumb[0] . '" alt="Thumb" /></a>';
			echo '<h3><label for="lnjcm_diam_' . $media->ID . '"><input type="checkbox" id="lnjcm_diam_' . $media->ID . '" name="lnjcm_diam[]" value="' . $media->ID . '" />#' . $attachment->ID . '</label></h3>';
			echo '<p><a href="' . $_url . '" target="_blank">' . $attachment->post_title . '</a></p>';
			echo '<p><a href="' . get_edit_post_link( $attachment->ID ) . '" target="_blank">' . __( 'Edit media', 'clean-unused-medias' ) . '</a></p>';
			echo '<p>' . __( 'Uploaded on', 'clean-unused-medias' ) . ' ' . date_i18n( lnjcm_get_option( 'date_format' ), strtotime( $attachment->post_date ) ) . ', ' . date_i18n( lnjcm_get_option( 'time_format' ), strtotime( $attachment->post_date ) ) . '</a></p>';
			//
			$lnjcm_used_in = get_post_meta( $media->ID, 'lnjcm_used_in', 1 );
			$lnjcm_used_in = ( ! is_array( $lnjcm_used_in ) || empty( $lnjcm_used_in ) ) ? array() : $lnjcm_used_in;
			//
			if ( lnjcm_is_favicon( $media->ID ) ) {
				$lnjcm_used_in[] = 'favicon'; }
			if ( lnjcm_is_featured_media( $media->ID ) ) {
				$lnjcm_used_in[] = 'featured'; }
			if ( lnjcm_is_related_media( $media->ID ) ) {
				$lnjcm_used_in[] = 'related'; }
			if ( lnjcm_is_ACF_media( $media->ID ) ) {
				$lnjcm_used_in[] = 'ACF'; }
			//
			sort( $lnjcm_used_in );
			//
			$lnjcm_used_in = array_unique( $lnjcm_used_in );
			//
			if ( sizeof( $lnjcm_used_in ) > 0 ) {
				$lnjcm_used_in_display = array();
				echo '<p class="lnjcm-tags">';
				foreach ( $lnjcm_used_in as $lnjcm_tag ) {
					$lnjcm_used_in_display[] = '<a href="#' . $lnjcm_tag . '" class="lnjcm-tag" data-media-id="' . $media->ID . '">#' . $lnjcm_tag . '</a>';
				}
				echo implode( ' ', $lnjcm_used_in_display );
				echo '</p>';
			} else {
				echo '<p class="lnjcm-tags">';
				echo __( 'This media is used anywhere.', 'clean-unused-medias' ) . ' <a href="#nope" class="lnjcm-tag" data-media-id="' . $media->ID . '">' . __( 'You can check it again.', 'clean-unused-medias' ) . '</a>';
				echo '</p>';
			}
			echo '</div>';
		}
		echo '</div>';

		//
		$page_links = paginate_links(
            array(
				'base'      => admin_url() . '%#%',
				'format'    => '/page/%#%',
				'prev_text' => __( '&laquo; previous', 'clean-unused-medias' ),
				'next_text' => __( 'next &raquo;', 'clean-unused-medias' ),
				'total'     => ceil( $total / $lnjcm_medias_per_page ),
				'current'   => $paged,
            )
        );
		//
		if ( $page_links ) {
			//
			echo '<hr class="clear"/>';
			echo '<div class="lnjcm-nav">';
			echo $page_links;
			//
			echo '</div>';
		}
	}
}

	//
	$message = ob_get_contents();
	ob_end_clean();

    //
	header( 'Content-Type: text/javascript' );

	//
	$array = array(
		'error'         => $error,
  		'message'       => $message,
  		'message_error' => $message_error,
	);

	//
	echo json_encode( $array );

