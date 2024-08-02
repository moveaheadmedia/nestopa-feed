<?php
/**
 * Plugin Name: Nestopa Feed
 * Plugin URI: https://github.com/moveaheadmedia/nestopa-feed
 * Description: A plugin to create XML feeds for the Property post type to be used on the Nestopa platform.
 * Version: 1.0.0
 * Author: Ali Sal
 * Author URI: https://moveaheadmedia.co.uk
 * License: GPLv2 or later
 * Text Domain: nestopa-feed
 */

// Schedule the event on plugin activation
register_activation_hook( __FILE__, 'nestopa_feed_activate' );


// Hook to run the function on the scheduled event
add_action( 'nestopa_feed_generate_xml_event', 'nestopa_feed_generate_xml' );

// Clear the scheduled event on plugin deactivation
register_deactivation_hook( __FILE__, 'nestopa_feed_deactivate' );

// Hook to add admin menu
add_action( 'admin_menu', 'nestopa_feed_add_admin_menu' );

// Add settings link to the plugins page
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'nestopa_feed_settings_link' );


function nestopa_feed_deactivate() {
	$timestamp = wp_next_scheduled( 'nestopa_feed_generate_xml_event' );
	wp_unschedule_event( $timestamp, 'nestopa_feed_generate_xml_event' );
}

function nestopa_feed_activate() {
	if ( ! wp_next_scheduled( 'nestopa_feed_generate_xml_event' ) ) {
		wp_schedule_event( time(), 'hourly', 'nestopa_feed_generate_xml_event' );
	}
}

function nestopa_feed_generate_xml() {
	// Query to get all published products
	$args = array(
		'post_type'      => 'product', // Replace 'product' with your custom post type
		'post_status'    => 'publish',
		'posts_per_page' => - 1, // Get all posts
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		// Start XML string
		$xml = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
		$xml .= '<root>' . "\n";

		while ( $query->have_posts() ) {
			$query->the_post();

			$post_id      = get_the_ID();
			$post_title   = get_the_title();
			$post_content = get_the_content();
			$post_excerpt = get_the_excerpt();
			$post_meta    = get_post_meta( $post_id );
			// Get the SKU
			$sku = get_post_meta( $post_id, '_sku', true );
			// Retrieve the first term from the 'property-type' taxonomy
			$terms = get_the_terms( $post_id, 'property-type' );
			$type  = ! empty( $terms ) && ! is_wp_error( $terms ) ? esc_xml( strtolower( $terms[0]->name ) ) : '';
			// Convert prices to integers
			$price_sale = isset( $post_meta['price-sale'][0] ) ? nestopa_feed_convert_price_to_integer( $post_meta['price-sale'][0] ) : '';
			$price_rent = isset( $post_meta['price-rent'][0] ) ? nestopa_feed_convert_price_to_integer( $post_meta['price-rent'][0] ) : '';

			// size
			$size      = isset( $post_meta['size'][0] ) ? $post_meta['size'][0] : '';
			$size      = floatval( $size );
			$land_size = '';
			if ( $type == 'land' ) {
				$land_size = $size;
				$size      = '';
			}

			// Get featured image URL
			$featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' );

			// Get gallery images
			$gallery_image_urls = [];
			$gallery_ids        = get_post_meta( $post_id, '_product_image_gallery', true );
			if ( $gallery_ids ) {
				$gallery_ids = explode( ',', $gallery_ids );
				foreach ( $gallery_ids as $attachment_id ) {
					$gallery_image_urls[] = wp_get_attachment_url( $attachment_id );
				}
			}

			// Get project term
			$project_terms = get_the_terms( $post_id, 'project-name' );
			$project       = ! empty( $project_terms ) && ! is_wp_error( $project_terms ) ? esc_xml( $project_terms[0]->name ) : '';

			// Coordinates
			$address     = isset( $post_meta['map-longitude-and-latitude'][0] ) ? $post_meta['map-longitude-and-latitude'][0] : '';
			$coordinates = nestopa_feed_get_lat_lng_from_address_nominatim( $address );
			$lat         = 'null';
			$lng         = 'null';
			if ( $coordinates ) {
				$lat = $coordinates['lat'];
				$lng = $coordinates['lon'];
			}


			// Build XML for each product
			$xml .= '<property>' . "\n";
			$xml .= '    <id>' . esc_xml( $post_id ) . '</id>' . "\n";
			$xml .= '    <reference>' . esc_xml( $sku ) . '</reference>' . "\n";
			$xml .= '    <status>' . esc_xml( 'live' ) . '</status>' . "\n";
			$xml .= '    <type>' . esc_xml( $type ) . '</type>' . "\n";
			$xml .= '    <is_new>' . esc_xml( isset( $post_meta['_is_new'][0] ) ? $post_meta['_is_new'][0] : 'false' ) . '</is_new>' . "\n";
			$xml .= '    <currency>' . esc_xml( 'THB' ) . '</currency>' . "\n";
			$xml .= '    <price_sale>' . esc_xml( $price_sale ) . '</price_sale>' . "\n";
			$xml .= '    <price_rent>' . esc_xml( $price_rent ) . '</price_rent>' . "\n";
			$xml .= '    <beds>' . esc_xml( isset( $post_meta['bedrooms'][0] ) ? $post_meta['bedrooms'][0] : '' ) . '</beds>' . "\n";
			$xml .= '    <baths>' . esc_xml( isset( $post_meta['bathrooms'][0] ) ? $post_meta['bathrooms'][0] : '' ) . '</baths>' . "\n";
			$xml .= '    <interior_size>' . esc_xml( ( $size ) ) . '</interior_size>' . "\n";
			$xml .= '    <land_size>' . esc_xml( ( $land_size ) ) . '</land_size>' . "\n";
			$xml .= '    <parking>' . esc_xml( isset( $post_meta['_parking'][0] ) ? $post_meta['_parking'][0] : '' ) . '</parking>' . "\n";

			// Titles and descriptions
			$xml .= '    <titles>' . "\n";
			$xml .= '        <title lang="en"><![CDATA[ ' . esc_xml( $post_title ) . ']]></title>' . "\n"; // Adjust based on actual data
			$xml .= '    </titles>' . "\n";
			$xml .= '    <descriptions>' . "\n";
			$xml .= '        <description lang="en"><![CDATA[ ' . esc_xml( $post_content ) . ']]></description>' . "\n"; // Adjust based on actual data
			$xml .= '    </descriptions>' . "\n";

			// Images
			$xml .= '    <images>' . "\n";
			if ( $featured_image_url ) {
				$xml .= '        <image>' . esc_url( $featured_image_url ) . '</image>' . "\n";
			}
			foreach ( $gallery_image_urls as $url ) {
				$xml .= '        <image>' . esc_url( $url ) . '</image>' . "\n";
			}
			$xml .= '    </images>' . "\n";
			// Videos
			$xml .= '    <videos>' . "\n";
			// Add logic to retrieve videos if necessary
			$xml .= '    </videos>' . "\n";

			// Tours
			$xml .= '    <tours>' . "\n";
			// Add logic to retrieve tours if necessary
			$xml .= '    </tours>' . "\n";

			$xml .= '    <project>' . esc_xml( $project ) . '</project>' . "\n";
			$xml .= '    <gps_lat>' . esc_xml( $lat ) . '</gps_lat>' . "\n";
			$xml .= '    <gps_lon>' . esc_xml( $lng ) . '</gps_lon>' . "\n";
			$xml .= '    <url>' . esc_url( get_permalink() ) . '</url>' . "\n";

			// Features
			$xml .= '    <features>' . "\n";

			$features = [
				'kitchen',
				'television-with-netflix',
				'air-conditioner',
				'free-wireless-internet',
				'washer',
				'balcony-or-patio',
				'gym',
				'swimming-pool',
				'sauna',
				'steam-room',
				'golf-simulator',
				'private-spa',
				'shuttle',
				'private-lift',
				'meeting-rooms',
				'playground',
				'security',
				'parking-ev-parking',
				'garden-bbq',
				'cinema',
				'pet-friendly',
				'jacuzzi',
				'skyscraper-deck',
				'lounge',
				'cctv',
				'elevator'
			];

			foreach ( $features as $feature ) {
				$feature_data = unserialize( $post_meta[ $feature ][0] );
				$actual_data  = nestopa_feed_convert_array_format( $feature_data );
				if ( $actual_data['value'] != 'false' ) {
					$xml .= '        <feature>' . esc_xml( $actual_data['name'] ) . '</feature>' . "\n";
				}
			}
			$xml .= '    </features>' . "\n";

			$xml .= '</property>' . "\n";
		}

		$xml .= '</root>';

		// Save XML to file
		$file = plugin_dir_path( __FILE__ ) . 'nestopa-feed.xml'; // Adjust file path as needed
		file_put_contents( $file, $xml );

		// Save the timestamp of the last update
		update_option( 'nestopa_feed_last_update', current_time( 'mysql' ) );

	}

	wp_reset_postdata(); // Reset post data
}

function nestopa_feed_add_admin_menu() {
	add_options_page(
		'Nestopa Feed Settings',      // Page title
		'Nestopa Feed',               // Menu title
		'manage_options',              // Capability
		'nestopa-feed',               // Menu slug
		'nestopa_feed_settings_page'  // Callback function
	);
}

function nestopa_feed_settings_page() {
	?>
    <div class="wrap">
        <h1><?php _e( 'Nestopa Feed Settings', 'nestopa-feed' ); ?></h1>
        <form method="post" action="">
			<?php

			// Assuming the XML file path is known and accessible
			$xml_file_url = plugin_dir_url( __FILE__ ) . 'nestopa-feed.xml';
			// Add a nonce field for security
			wp_nonce_field( 'nestopa_feed_generate_now_action', 'nestopa_feed_generate_now_nonce' );
			?>
            <label><?php _e( 'The URL to the XML feed is:', 'nestopa-feed' ); ?>
                <input type="text" value="<?php echo esc_url( $xml_file_url ); ?>" disabled
                       style="width: 100%; max-width: 600px;">
            </label>
            <input type="submit" name="nestopa_feed_generate_now" class="button button-primary"
                   value="<?php _e( 'Generate Now', 'nestopa-feed' ); ?>">
        </form>

		<?php
		if ( isset( $_POST['nestopa_feed_generate_now'] ) && check_admin_referer( 'nestopa_feed_generate_now_action', 'nestopa_feed_generate_now_nonce' ) ) {
			nestopa_feed_generate_xml(); // Call the function to generate XML
			?>
            <div class="updated">
                <p><?php _e( 'XML feed generated successfully.', 'nestopa-feed' ); ?></p>
            </div>
			<?php
		}
		?>
        <h2><?php _e( 'Last Update', 'nestopa-feed' ); ?></h2>
        <p><?php
			$last_update = get_option( 'nestopa_feed_last_update', 'Not yet updated' );
			echo esc_html( $last_update );
			?></p>
    </div>
	<?php
}

function nestopa_feed_settings_link( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=nestopa-feed' ) ) . '">' . __( 'Settings', 'nestopa-feed' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

function nestopa_feed_convert_price_to_integer( $price ) {
	// Remove currency symbols and commas
	$cleaned_price = preg_replace( '/[^\d.]/', '', $price );

	// Convert to float
	$float_price   = floatval( $cleaned_price );
	$integer_price = intval( $float_price );

	// Return empty string if price is zero
	return $integer_price === 0 ? '' : $integer_price;
}

function nestopa_feed_convert_array_format( $input_array ) {
	// Ensure the input array is not empty and has exactly one element
	if ( is_array( $input_array ) && count( $input_array ) === 1 ) {
		// Extract the key and value from the input array
		$key   = key( $input_array );
		$value = current( $input_array );

		// Return the new formatted array
		return array(
			'name'  => $key,
			'value' => $value
		);
	}

	// Return an empty array if the input array is not in the expected format
	return array();
}

function nestopa_feed_get_lat_lng_from_address_nominatim( $address ) {
	return false;
}
