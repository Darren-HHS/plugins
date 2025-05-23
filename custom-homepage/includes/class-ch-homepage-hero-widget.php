<?php
class Ch_Homepage_Hero extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'ch_homepage_hero', // Base ID
			esc_html__( 'Estatik Homepage hero with search', 'wiidoo-resales-widget-plugin' ), // Name
			array( 'description' => esc_html__( 'Estatik custom widget', 'wiidoo-resales-widget-plugin' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.$filtr_id
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		$filtr_id = $instance['filtr_id'];
		$price_min = !empty($instance['price_min']) ? ' price_min="'. $instance['price_min'] .'"' : '';
		$price_max = !empty($instance['price_max']) ? ' price_max="'. $instance['price_max'] .'"' : '';
		$price_step = !empty($instance['price_step']) ? ' price_step="'. $instance['price_step'] .'"' : '';
		$url = !empty($instance['url']) ? ' url="'. $instance['url'] .'"' : '';
		$search_fields = implode(',', $instance['search'] ?? []);
		//	echo esc_html__( '***form here***'.$filtr_id, 'wiidoo-resales-widget-plugin' );
    ?>
    <div class="ch-hero" style="background-image: url('<?php echo esc_url($instance['image_uri']); ?>'); background-size: cover;">
        <div class="widget-wrapper">
		<?php
    if ( ! empty( $instance['filtr_id'] ) ) {
      $title = !empty($instance['title']) ? $instance['title'] : 'Properties';
      echo $args['before_title'] . $title . $args['after_title'];
    }
    echo do_shortcode( '[rohc_c_f_menu_sliders filtr_id="'.$filtr_id.'"'.$price_min . $price_max . $price_step.' sngl_pg_slug="single-property" filtr_id2="3172" filtr_lbl2="Short Term Rentals" filtr_id3="3175" filtr_lbl3="Long Term Rentals" search_fields=' . $search_fields . $url . ']' ); ?>
        </div>
    </div>
    <?php
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {

		$title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'Please fill in', 'wiidoo-resales-widget-plugin' );
		$filtr_id = ! empty( $instance['filtr_id'] ) ? $instance['filtr_id'] : esc_html__( 'Please fill in', 'wiidoo-resales-widget-plugin' );
		$price_min = ! empty( $instance['price_min'] ) ? $instance['price_min'] : esc_html__( 'Please fill in', 'wiidoo-resales-widget-plugin' );
		$price_max = ! empty( $instance['price_max'] ) ? $instance['price_max'] : esc_html__( 'Please fill in', 'wiidoo-resales-widget-plugin' );
		$price_step = ! empty( $instance['price_step'] ) ? $instance['price_step'] : esc_html__( 'Please fill in', 'wiidoo-resales-widget-plugin' );
		$url = ! empty( $instance['url'] ) ? $instance['url'] : esc_html__( 'Please fill in', 'wiidoo-resales-widget-plugin' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'wiidoo-resales-widget-plugin' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'filtr_id' ) ); ?>"><?php esc_attr_e( 'Filter ID:', 'wiidoo-resales-widget-plugin' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'filtr_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'filtr_id' ) ); ?>" type="text" value="<?php echo esc_attr( $filtr_id ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>"><?php esc_attr_e( 'Search page url:', 'wiidoo-resales-widget-plugin' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'url' ) ); ?>" type="text" value="<?php echo esc_attr( $url ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'price_min' ) ); ?>"><?php esc_attr_e( 'Price min:', 'wiidoo-resales-widget-plugin' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'price_min' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'price_min' ) ); ?>" type="text" value="<?php echo esc_attr( $price_min ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'price_max' ) ); ?>"><?php esc_attr_e( 'Price max:', 'wiidoo-resales-widget-plugin' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'price_max' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'price_max' ) ); ?>" type="text" value="<?php echo esc_attr( $price_max ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'price_step' ) ); ?>"><?php esc_attr_e( 'Price step:', 'wiidoo-resales-widget-plugin' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'price_step' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'price_step' ) ); ?>" type="text" value="<?php echo esc_attr( $price_step ); ?>">
		</p>
		<fieldset style="border: solid 1px black;"> <legend>Disable / Enable search fields:</legend>
		<div class="search-field-wrapper" style="display: flex; flex-wrap: wrap;">
			<?php foreach ($this->get_search_fields() as $key => $name) : ?>
				<div class="search-field" style="flex-basis:15%; margin: 5px;">
					<input class="checkbox" id="<?php echo $this->get_field_id($key); ?>" name="<?php echo $this->get_field_name("search"); ?>[]" type="checkbox" value="<?php echo $key; ?>" <?php checked(in_array($key, $instance["search"] ?? [])); ?> />
					<label for="<?php echo $this->get_field_id($key); ?>"><?php echo $name; ?></label>
				</div>
			<?php endforeach; ?>
		</div>
    </fieldset>
    <p>
      <label for="<?= $this->get_field_id( 'image_uri' ); ?>">Image</label>
      <img class="<?= $this->id ?>_img" src="<?= (!empty($instance['image_uri'])) ? $instance['image_uri'] : ''; ?>" style="margin:0;padding:0;max-width:20%;display:block"/>
      <input type="text" class="widefat <?= $this->id ?>_url" name="<?= $this->get_field_name( 'image_uri' ); ?>" value="<?= $instance['image_uri'] ?? ''; ?>" style="margin-top:5px;" />
      <input type="button" id="<?= $this->id ?>" class="button button-primary js_custom_upload_media" value="Upload Image" style="margin-top:5px;" />
    </p>
    <script>
        jQuery(document).ready(function ($) {
            function media_upload(button_selector) {
                var _custom_media = true,
                    _orig_send_attachment = wp.media.editor.send.attachment;
                $('body').on('click', button_selector, function () {
                    var button_id = $(this).attr('id');
                    wp.media.editor.send.attachment = function (props, attachment) {
                        if (_custom_media) {
                            $('.' + button_id + '_img').attr('src', attachment.url);
                            $('.' + button_id + '_url').val(attachment.url);
                        } else {
                            return _orig_send_attachment.apply($('#' + button_id), [props, attachment]);
                        }
                    }
                    wp.media.editor.open($('#' + button_id));
                    return false;
                });
            }
            media_upload('.js_custom_upload_media');
        });
    </script>
		<?php
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['filtr_id'] = ( ! empty( $new_instance['filtr_id'] ) ) ? sanitize_text_field( $new_instance['filtr_id'] ) : '';
		$instance['price_min'] = ( ! empty( $new_instance['price_min'] ) ) ? sanitize_text_field( $new_instance['price_min'] ) : '';
		$instance['price_max'] = ( ! empty( $new_instance['price_max'] ) ) ? sanitize_text_field( $new_instance['price_max'] ) : '';
		$instance['price_step'] = ( ! empty( $new_instance['price_step'] ) ) ? sanitize_text_field( $new_instance['price_step'] ) : '';
		$instance['url'] = ( ! empty( $new_instance['url'] ) ) ? sanitize_text_field( $new_instance['url'] ) : '';
		$instance['image_uri'] = strip_tags( $new_instance['image_uri'] );
		$instance['search'] = array();
		if ( ! empty( $new_instance['search'] ) && is_array( $new_instance['search'] ) ) {
			foreach ( $new_instance['search'] as $search_key ) {
				$instance['search'][] = sanitize_text_field( $search_key );
			}
		}
		return $instance;
	}

	public function get_search_fields() {
		$fields = [
			'plocs' => 'Location',
			'ptypei' => 'Property Type',
			'beds2' => 'Bedrooms',
			'baths2' => 'Bathrooms',
			'price2' => 'Price',
			'p_Setting' => 'Setting',
			'p_Orientation' => 'Setting Orientation',
			'p_Condition' => 'Condition',
			'p_Pool' => 'Pool',
			'p_Climate' => 'Climate Control',
			'p_Views' => 'Views',
			'p_Features' => 'Features',
			'p_Furniture' => 'Furniture',
			'p_Kitchen' => 'Kitchen',
			'p_Garden' => 'Garden',
			'p_Security' => 'Security',
			'p_Parking' => 'Parking',
			'p_Utilities' => 'Utilities',
			'p_Category' => 'Category',
			'p_PV' => 'Plots and Ventures',
			'p_Rentals' => 'Rentals',
			'virtualtours' => 'Virtual Tours',
			'p_PropertyListing' => 'Property Listing',
		];
		return $fields;
	}

	public function get_advanced_search_fields() {
		$fields = [
			'p_Setting' => 'Setting',
			'p_Orientation' => 'Setting Orientation',
			'p_Condition' => 'Condition',
			'p_Pool' => 'Pool',
			'p_Climate' => 'Climate Control',
			'p_Views' => 'Views',
			'p_Features' => 'Features',
			'p_Furniture' => 'Furniture',
			'p_Kitchen' => 'Kitchen',
			'p_Garden' => 'Garden',
			'p_Security' => 'Security',
			'p_Parking' => 'Parking',
			'p_Utilities' => 'Utilities',
			'p_Category' => 'Category',
			'p_PV' => 'Plots and Ventures',
			'p_Rentals' => 'Rentals',
			'virtualtours' => 'Virtual Tours',
			'p_PropertyListing' => 'Property Listing',
		];
		return $fields;
	}

} // class Wiidoo_Resales_Widget
