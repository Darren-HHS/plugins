<?php

/**
 * Class Ept_Properties_Categories_Grid
 */
class Ch_Homepage_Cat_links extends SiteOrigin_Widget {

	/**
	 * Ept_Properties_Categories_Grid constructor.
	 */
	function __construct() {

		parent::__construct(
			'ch-homepage-cat-links',
			__( 'Homepage list of regions', 'ept' ),
			array(
				'has_preview' => false,
				'description' => __( 'Displays list of regions.', 'ept' ),
			),
			array(),
			false,
			plugin_dir_path( __FILE__ )
		);
	}

	/**
	 * @return array
	 */
	public function get_widget_form() {

		$locations = $this->get_locations();

		return array(
			'title' => array(
				'type' => 'text',
				'label' => __('Title', 'ept'),
			),
			'regions' => array(
				'type' => 'repeater',
				'item_name'  => __( 'Region', 'ept' ),
				'item_label' => array(
					'update_event' => 'change',
					'value_method' => 'val'
				),
				'fields' => array(

					'category' => array(
						'type' => 'select',
						'label' => __( 'Region', 'ept' ),
						'options' => $locations,
					),

					'link' => array(
						'type' => 'link',
						'label' => __( 'Link', 'ept' ),
						'description' => __( 'Link to region page', 'ept' )
					),

          'count_properties' => array(
            'type' => 'text',
            'label' => __('Count properties', 'ept'),
            'description' => 'Where %d is count of Properties dynamically added from API'
          ),

        'subareas' => array(
			  'type' => 'repeater',
			  'item_name'  => __( 'Sub areas', 'ept' ),
			  'item_label' => array(
				  'update_event' => 'change',
				  'value_method' => 'val'
			  ),
			  'fields' => array(
				  'sub_title' => array(
					  'type' => 'text',
					  'label' => __( 'Title', 'ept' ),
				  ),

				  'link' => array(
					  'type' => 'link',
					  'label' => __( 'Link', 'ept' ),
					  'description' => __( 'Link to sub area page', 'ept' )
				  ),
			  ),
		  ),

				),
			),
		);
	}
	public function get_locations() {
		$roh_api_key = esc_attr(get_option('roh-api-key'));
		$roh_client_id = esc_attr(get_option('roh-client-id'));
		$P_ApiId = esc_attr(get_option('roh-filter-id-1'));
		$url = "https://webapi.resales-online.com/V6/SearchLocations";
		$data = array(
			"p1" => $roh_client_id,
			"p2" => $roh_api_key,
			"P_ApiId" => $P_ApiId,
			'P_SortType' => 1,
		);
		$query_url = sprintf("%s?%s", $url, http_build_query($data));

		$response = wp_remote_get($query_url);

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return array(); // Return empty array on error
		}

		$api = wp_remote_retrieve_body($response);
		$api_resp = json_decode($api);

		if (json_last_error() !== JSON_ERROR_NONE || !isset($api_resp->LocationData->ProvinceArea->Locations->Location)) {
			return array(); // Return empty array if JSON is invalid or structure is not as expected
		}

		$PropLoc = $api_resp->LocationData->ProvinceArea->Locations->Location;

		if (empty($PropLoc) || !is_array($PropLoc)) {
			return array(); // Return empty array if PropLoc is not a valid array for array_combine
		}
		
		// Ensure all values in $PropLoc are scalar and suitable for array keys and values
		$validPropLoc = array_filter($PropLoc, function($value) {
			return is_scalar($value);
		});

		if (count($PropLoc) !== count($validPropLoc)) {
			// If there were non-scalar values, decide on handling:
			// Option 1: Return only valid ones (might cause issues if other code expects all original items)
			// Option 2: Return empty / log error (safer if consistency is key)
			// For now, returning empty if any invalid items found, to be safe.
			return array();
		}


		return array_combine($validPropLoc, $validPropLoc);
	}

	public function get_count_by_location($location) {
		$PropCount = 0; // Default to 0
		$roh_api_key = esc_attr(get_option('roh-api-key'));
		$roh_client_id = esc_attr(get_option('roh-client-id'));
		$P_ApiId = esc_attr(get_option('roh-filter-id-1'));
		$url = "https://webapi.resales-online.com/V6/SearchProperties";
		$data = array(
			"p1" => $roh_client_id,
			"p2" => $roh_api_key,
			"P_ApiId" => $P_ApiId,
			'P_Location' => $location,
		);
		$query_url = sprintf("%s?%s", $url, http_build_query($data));
		
		$response = wp_remote_get($query_url);

		if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
			return $PropCount; // Return default on error
		}

		$api = wp_remote_retrieve_body($response);
		$api_resp = json_decode($api);

		if (json_last_error() === JSON_ERROR_NONE && isset($api_resp->QueryInfo->PropertyCount)) {
			$PropCount = (int) $api_resp->QueryInfo->PropertyCount;
		}

		return $PropCount;
	}

	/**
	 * @param $instance
	 * @param $args
	 *
	 * @return array
	 */
	function get_template_variables( $instance, $args ) {

		return wp_parse_args( $instance, array(
			'title' => '',
			'terms' => array(),
			'items_per_row' => 'auto',
		) );
	}

	/**
	 * @param $instance
	 * @param $args
	 * @param $template_vars
	 * @param $css_name
	 *
	 * @return string
	 */
	public function get_html_content( $instance, $args, $template_vars, $css_name ) {

		$regions = $template_vars['regions'] ?? array();

		if ( !empty($regions) && is_array($regions) ) :

			ob_start();

			echo $args['before_widget'];

			$locations = $this->get_locations();
			?>
      <div id="section-sitemap">
        <div class="content-width">
          <div id="sitemap">
						<?php foreach ($regions as $key => $region) : ?>
							<?php
								$region_link = $region['link'] ?? '#';
								$region_category_key = $region['category'] ?? null;
								// Ensure $region_category_key is a string or int for array lookup
								$region_name = (is_scalar($region_category_key) && isset($locations[$region_category_key])) ? $locations[$region_category_key] : ($region_category_key ?? 'Unknown');
								$region_count_properties_format = $region['count_properties'] ?? '%d Properties';
								// Use $region_category_key for API call if it's the expected identifier, or $region_name if the name itself is the identifier
								$property_count = $this->get_count_by_location($region_category_key); // Assuming API expects the key not the display name
							?>
              <div class="column">
                <a class="city ch-area-js"
                   href="<?php echo esc_url($region_link); ?>"><?php echo esc_html($region_name); ?>
                  <span><?php echo esc_html(sprintf($region_count_properties_format, $property_count)); ?></span></a>
								<?php
								$subareas = $region['subareas'] ?? array();
								if (!empty($subareas) && is_array($subareas)) :
								?>
                <span class="ch-sub-areas-js">
                  <?php foreach ($subareas as $subarea) : ?>
										<?php
											$subarea_link = $subarea['link'] ?? '#';
											$subarea_title = $subarea['sub_title'] ?? '';
										?>
                    <a href="<?php echo esc_url($subarea_link); ?>"><?php echo esc_html($subarea_title); ?></a>
                  <?php endforeach; ?>
                </span>
								<?php endif; ?>
              </div>
						<?php endforeach; ?>
          </div>
        </div>
      </div>
			<?php echo $args['after_widget'];

			return ob_get_clean();
		endif;
		return ''; // Return empty string if no regions
	}


}
