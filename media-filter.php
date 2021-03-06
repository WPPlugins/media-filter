<?php
/**
 * Plugin Name: Media Filter
 * Plugin URI: https://foxnet-themes.fi/downloads/media-filter/
 * Description: Media Filter adds image width and height, clickable author link and 'mine' link in Media Library (upload.php).
 * Version: 0.1.2
 * Author: Sami Keijonen
 * Author URI: https://foxnet-themes.fi/
 * Text Domain: media-filter
 * Domain Path: /languages
 * Contributors: samikeijonen
 * Thanks: Justin Tadlock
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU 
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume 
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without 
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package MediaFilter
 * @version 0.1.2
 * @author Sami Keijonen <sami.keijonen@foxnet.fi>
 * @copyright Copyright (c) 2014, Sami Keijonen
 * @license http://www.gnu.org/licenses/gpl-2.0.html
 */

/* Set up the plugin on the 'plugins_loaded' hook. */
add_action( 'plugins_loaded', 'media_filter_setup' );

/**
 * Plugin setup function.  Loads actions and filters to their appropriate hook.
 *
 * @since 0.1.0
 */
function media_filter_setup() {

	if( is_admin() ) {
	
		/* Load the translation of the plugin. */
		load_plugin_textdomain( 'media-filter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Add Sortable Width and Height Columns to the Media Library
		add_filter( 'manage_media_columns', 'media_filter_columns_register' );
		add_filter( 'manage_media_custom_column', 'media_filter_columns_display', 10, 2 );
		add_filter( 'manage_upload_sortable_columns', 'media_filter_columns_sortable' );
	
		// Add pdf mime type  
		add_filter( 'post_mime_types', 'media_filter_post_mime_types' );
	
		// Add 'mine' media
		add_filter( 'views_upload', 'media_filter_upload_views_filterable' );
	
	}
	
}

/*
 * Adding Width and Height columns
 *
 * @since 0.1.0
 */
function media_filter_columns_register( $columns ) {

	/* Add colums in media (upload.php). */
	$columns['media-filter-author'] = __( 'Author', 'media-filter' );
	$columns['media-filter-size'] = __( 'File Size', 'media-filter' );
	$columns['media-filter-width'] = __( 'Width', 'media-filter' );
	$columns['media-filter-height'] = __( 'Height', 'media-filter' );
	$date = $columns['date'];
	$comments = $columns['comments'];
	unset( $columns['date'] );
	unset( $columns['comments'] );
	$columns['comments'] = $comments; // make this column after author, width and height
	$columns['date'] = $date; // make this column after comments
	
	/* Remove original author. */
	unset( $columns['author'] );

	return $columns;
	
}


/*
 * Display the columns
 *
 * @since 0.1.0
 */
function media_filter_columns_display( $column_name, $post ) {
	
	/* Get metainfo from image. */
	$media_filter_meta = wp_get_attachment_metadata( get_the_ID() );
	
	/* Get File Size. */
	$media_filter_size = filesize( get_attached_file( get_the_ID() ) );
	if ( FALSE === $media_filter_size )
		$media_filter_size  = 0;
	else
		$media_filter_size = size_format( $media_filter_size, apply_filters( 'media_filter_size_format', 2 ) );

	switch( $column_name ) {

		/* If displaying the 'width' column. */
		case 'media-filter-width' :

			if ( !empty( $media_filter_meta['width'] ) )
				echo $media_filter_meta['width'];
			else
				echo __( '&nbsp;', 'media-filter' );

			break;

		/* If displaying the 'height' column. */
		case 'media-filter-height' :
			
		if ( !empty( $media_filter_meta['height'] ) )	
			echo $media_filter_meta['height'];
		else
			echo __( '&nbsp;', 'media-filter' );

			break;

		/* If displaying the 'size' column. */
		case 'media-filter-size' :
				
			echo $media_filter_size;

			break;
		
		/* If displaying the 'my-author' column. */
		case 'media-filter-author' :
		
		printf( '<a href="%s">%s</a>',
			esc_url( add_query_arg( array( 'author' => get_the_author_meta( 'ID' ) ), 'upload.php' ) ),
			get_the_author()
		);
		
			break;

		/* Just break out of the switch statement for everything else. */
		default :
			break;
			
	}
		
}

/*
 * Registering columns as sortable
 *
 * @since 0.1.0
 *
 * @todo: make columns width and height sortable, they are not yet.
 */
function media_filter_columns_sortable( $columns ) {

    $columns['media-filter-width'] = 'width';
    $columns['media-filter-height'] = 'height';
	$columns['media-filter-author'] = 'author';

    return $columns;
	
}

/*
 * Add pdf documents in mime types.
 *
 * @since 0.1.0
 */
function media_filter_post_mime_types( $post_mime_types ) {

	/* PDF is 'application/pdf', ZIP is 'application/zip'. */

	$post_mime_types['application/pdf'] = array( __( 'PDFs', 'media-filter' ), __( 'Manage PDFs', 'media-filter' ), _n_noop( 'PDF <span class="count">(%s)</span>', 'PDFs <span class="count">(%s)</span>', 'media-filter' ) );
	$post_mime_types['application/zip'] = array( __( 'ZIPs', 'media-filter' ), __( 'Manage ZIPs', 'media-filter' ), _n_noop( 'ZIP <span class="count">(%s)</span>', 'ZIPs <span class="count">(%s)</span>', 'media-filter' ) );
	$post_mime_types['text/plain'] = array( __( 'TXTs', 'media-filter' ), __( 'Manage TXTs', 'media-filter' ), _n_noop( 'TXT <span class="count">(%s)</span>', 'TXTs <span class="count">(%s)</span>', 'media-filter' ) );
	$post_mime_types['text/css'] = array( __( 'CSSs', 'media-filter' ), __( 'Manage CSSs', 'media-filter' ), _n_noop( 'CSS <span class="count">(%s)</span>', 'CSSs <span class="count">(%s)</span>', 'media-filter' ) );
	$post_mime_types['text/html'] = array( __( 'HTMLs', 'media-filter' ), __( 'Manage HTMLs', 'media-filter' ), _n_noop( 'HTML <span class="count">(%s)</span>', 'HTMLs <span class="count">(%s)</span>', 'media-filter' ) );
	$post_mime_types['application/msword'] = array( __( 'DOCs', 'media-filter' ), __( 'Manage DOCs', 'media-filter' ), _n_noop( 'DOC <span class="count">(%s)</span>', 'DOCs <span class="count">(%s)</span>', 'media-filter' ) );
	$post_mime_types['application/vnd.ms-powerpoint'] = array( __( 'PPTs', 'media-filter' ), __( 'Manage PPTs', 'media-filter' ), _n_noop( 'PPT <span class="count">(%s)</span>', 'PPTs <span class="count">(%s)</span>', 'media-filter' ) );
	$post_mime_types['application/vnd.ms-excel'] = array( __( 'XLSXs', 'media-filter' ), __( 'Manage XLSXs', 'media-filter' ), _n_noop( 'XLSX <span class="count">(%s)</span>', 'XLSXs <span class="count">(%s)</span>', 'media-filter' ) );
	
	/* Return the $post_mime_types variable. */
	return $post_mime_types;

}

/*
 * Add 'Mine' media file after mime type. Hook is views_upload.
 *
 * @since 0.1.0
 */
function media_filter_upload_views_filterable( $views ) {
	
	if ( isset( $_GET['author'] ) && $_GET['author'] == get_current_user_id() ) {
		
		/* Current class. */
		$media_filter_class = ' class="current"';
		
		/* Remove 'current' class from all-link. */
		add_action( 'admin_footer', 'media_filter_footer_scripts', 20 );
		
	}
	else {
		$media_filter_class = '';
	}
	
	/* Get total user count. */
	$media_filter_user_count = count_users();
	$media_filter_user_total = $media_filter_user_count['total_users'];
	
	/* Get current user attachment count. @link: http://codex.wordpress.org/Class_Reference/wpdb */
	global $wpdb;
	$media_filter_count_mine_attachment = $wpdb->get_var( $wpdb->prepare( "
	SELECT COUNT(*)
	FROM $wpdb->posts
	WHERE post_type = 'attachment'
	AND post_author = %s
	AND post_status != 'trash'
	", get_current_user_id() ) );
	
	/* Add 'mine' link only if there are more than one user and user have attachments. */
	if ( $media_filter_user_total > 1 && $media_filter_count_mine_attachment > 0 ) {

		$media_filter_views = array(
			'media-filter-mine' => sprintf( '<a %s href="%s">%s</a>', $media_filter_class, esc_url( add_query_arg( 'author', get_current_user_id(), 'upload.php' ) ), sprintf( _n( 'Mine <span class="count">(%s)</span>', 'Mine <span class="count">(%s)</span>', $media_filter_count_mine_attachment, 'media-filter' ), number_format_i18n( $media_filter_count_mine_attachment ) ) ) 
		);
	
		/* Return $views so that 'Mine' attachments are first. */
		return array_merge( $media_filter_views, $views );
	
	}
	else {
		return $views;
	}
	
}

/*
 * Remove 'current' class from all-link.
 *
 * @since 0.1.0
 */
function media_filter_footer_scripts() { ?>

<script type="text/javascript">
jQuery(document).ready(
	function() {
		jQuery( '.all a' ).removeClass('current');
	}
);
</script>

<?php }

?>