<?php

/**
 * Plugin name: Estatik Custom Import
 * Version: 1.0
 * Author: Estatik
 * Author URI: https://estatik.net
 */

add_filter( 'upload_mimes', 'exi_custom_upload_xml' );

/**
 * @param $mimes
 *
 * @return array
 */
function exi_custom_upload_xml($mimes) {
	$mimes = array_merge( $mimes, array( 'xml' => 'application/xml' ) );
	return $mimes;
}

function esm_register_tax() {
	register_taxonomy( 'esm_locations', 'properties', array(
		'hierarchical' => true,
		'label'                 => 'Locations'
	) );
}
add_action( 'init', 'esm_register_tax', 11 );
add_action( 'init', 'esm_register_tax', 9 );

add_action( 'plugins_loaded', function() {

	add_filter( 'es_import_class_name', function() { return 'Exi_Xml_Import'; } );

	/**
	 * Class Exi_Xml_Import.
	 */
	class Exi_Xml_Import extends Es_Import {

		/**
		 * @param int $index
		 *
		 * @return bool|Es_Property
		 * @throws Exception
		 */
		public function one( $index = 0 ) {

// 			add_filter( 'intermediate_image_sizes', function() {
// 				return array();
// 			}, 20 );

			// Get data provider.
			$data_provider = $this->get_data_provider();

			$post_id = null;

			// If data provider is correct.
			if ( $data_provider instanceof Es_Data_Provider_Interface ) {

				// Get property from file using line number.
				$item = $data_provider->get_item( $index );

				// If item is correct
				if ( ! empty( $item ) ) {

					$system = (array) $item->system;

					$temp_id = $system['ID'];

					$system = wp_parse_args( $system, array(
						'post_title' => '',
						'post_status' => '',
						'post_content' => '',
						'post_excerpt' => '',
						'post_name' => '',
						'post_type' => 'properties'
					) );

					unset( $system['ID'], $system['post_author'] );

					$posts = get_posts( array(
						'fields' => 'ids',
						'post_type' => 'properties',
						'post_status' => 'any',
						'meta_key' => 'es_temp_id',
						'meta_value' => $temp_id
					) );

					if ( ! empty( $posts[0] ) ) {
						$system['ID'] = $posts[0];
					}

					if ( empty( $posts[0] ) ) {
						$post_id = wp_insert_post( $system, true );

						if ( $post_id && ! is_wp_error( $post_id ) ) {
							$property = es_get_property( $post_id );

							update_post_meta( $post_id, 'es_temp_id', $temp_id );

							$meta = (array)$item->meta;
							$gallery = (array)$item->gallery;
							$terms = (array)$item->terms;
							$locations = (array)$item->locations;

							try {
								if ( ! empty( $terms ) ) {
									foreach ( $terms as $tax => $terms_list ) {
										$terms_list = (array) $terms_list;

										if ( ! empty( $terms_list['term'] ) ) {
											wp_set_object_terms( $post_id, $terms_list['term'], $tax, true );
										}
									}
								}
							} catch( Exception $e ) {}

							foreach ( $meta as $key => $value ) {
								if ( is_object( $value ) || $key == 'es_property_gallery' ) continue;
								update_post_meta( $post_id, $key, $value );
							}

							foreach ( $locations as $type => $term_name ) {
								$term_id = wp_set_object_terms( $post_id, $term_name, 'esm_locations', true );

								if ( ! is_wp_error( $term_id ) && $term_id ) {
									foreach ( $term_id as $tid ) {
										update_term_meta( $tid, 'esm_location_type', $type );
									}

								}
							}

							if ( ! empty( $gallery['image'] ) ) {
								$file = array();
								$gallery_images = array();

								try {
								foreach ( $gallery['image'] as $url ) {

									$file['name'] = basename( $url );
									$file['tmp_name'] = download_url( $url );

									if ( ! is_wp_error( $file['tmp_name'] ) ) {
										$attachmentId = media_handle_sideload( $file,  $post_id );

										if ( ! is_wp_error( $attachmentId ) ) {
											$gallery_images[] = $attachmentId;
										}
									}
								}
									} catch (Exception $e) {}

								if ( ! empty( $gallery_images ) ) {

									$gallery_images = array_map( 'intval', $gallery_images );
									$gallery_images = array_filter( $gallery_images );

									$property->save_field_value( 'gallery', $gallery_images );

									if ( ! empty( $gallery_images[0] ) ) {
										set_post_thumbnail( $property->getID(), $gallery_images[0] );
									}
								}
							}

							if ( ! empty( $meta['es_property_address_components'] ) ) {
								$property->save_address_components( json_decode( $item->meta['es_property_address_components'] ) );
							}
						}
					} else {
						$property = es_get_property( $posts[0] );
					}
				}
			}

			return ! empty( $property ) ? $property : false;
		}
	}

	/**
	 * Class Exi_Xml_Data_Provider.
	 */
	class Exi_Xml_Data_Provider extends Es_Data_Provider {

		protected $_xml;
		protected $data = array();

		/**
		 * Esm_Xml_Data_Provides constructor.
		 *
		 * @param $file
		 * @param array $options
		 */
		public function __construct( $file, array $options = array() ) {
			parent::__construct( $file, $options );
			$this->_xml = simplexml_load_file( $this->_file, 'SimpleXMLElement', LIBXML_NOCDATA );

			if ( ! empty( $this->_xml->post ) ) {
				foreach ( $this->_xml->post as $post ) {
					$data[] = $post;
				}

				$this->data = $data;

				unset( $this->_xml );
			}
		}

		/**
		 * Return list of all items.
		 *
		 * @return array
		 */
		public function get_items() {
			return $this->data;
		}

		/**
		 * Return item using index.
		 *
		 * @param $index
		 *
		 * @return mixed
		 */
		public function get_item( $index ) {
			return ! empty( $this->data[ $index ] ) ? $this->data[ $index ] : null;
		}

		/**
		 * Return num of items.
		 *
		 * @return mixed
		 */
		public function get_count() {
			return ! empty( $this->data ) ? count( $this->data ) : 0;
		}
	}

	add_filter( 'es_import_data_providers', function( $provides ) {

		$provides['xml'] = 'Exi_Xml_Data_Provider';

		return $provides;
	} );
} );

add_filter( 'wp_get_attachment_url', 'exi_get_attachment_url', 10, 2 );

/**
 * Return attachment remote image url.
 *
 * @param $url
 * @param $post_id
 *
 * @return string
 */
function exi_get_attachment_url( $url, $post_id ) {

	if ( get_post_meta( $post_id, 'exi_media_remote_image' ) ) {
		$post = get_post( $post_id );

		return $post->guid;
	}

	return $url;
}

add_action('init', 'esm_migrations');

function esm_migrations() {

	if ( ! get_option( 'esm_locations_migrations12345671' ) ) {

		$zones = array(
			array(
				'type' => 'area',
				'name' => 'Costa del Sol',
				'childs' => array(
					array(
						'type' => 'region',
						'name' => 'Costa del Sol East',
						'childs' => array(

							array(
								'type' => 'town',
								'name' => 'Benalmadena',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'Arroyo de la Miel',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Torremuelle',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Benalmadena Costa',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Torrequebrada',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Benalmadena Pueblo',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Puerto Marina ',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Capellania',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Reserva del Higueron',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Rancho Domingo',
									),

								),
							),

							array(
								'type' => 'town',
								'name' => 'Fuengirola',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'Los Boliches',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Torreblanca del Sol',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Carvajal',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Fuengirola Center',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Sierrezuela',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Castillo Sohail Myramar',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Puerto Deportivo',
									),
								),
							),

							array(
								'type' => 'town',
								'name' => 'Mijas',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'Cerros del Aguila',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Riviera del Sol',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Torrenueva',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Faro',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Cala de Mijas',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Mijas Golf',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Calahonda',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Coto',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Cala Golf',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Las Lagunas',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Miraflores',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Mijas Pueblo',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Campo Mijas',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Valtocado',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Las Lagunas de Mijas',
									),
								),
							),

							array(
								'type' => 'town',
								'name' => 'Torremolinos',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'Montemar',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Torremolinos Centre',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Carihuela',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Playamar',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Los Alamos',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Pinar',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Pinillo',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Bajondilo',
									),
								),
							),
						),
					),

					array(
						'type' => 'region',
						'name' => 'Costa del Sol Central',
						'childs' => array(

							array(
								'type' => 'town',
								'name' => 'Benahavis',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'La Quinta',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Los Flamingos',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Zagaleta',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Alqueria',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Monte Mayor',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Marbella Club Golf Resort',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Madronal',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Monte Halcones',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Paraiso Alto',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Los Arqueros',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Heredia',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Capitan',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Capanes Sur',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Reserva de Alcucuz',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Los Almendros',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Benahavis Pueblo',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Benatalaya',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Carretera de Ronda',
									),
								),
							),

							array(
								'type' => 'town',
								'name' => 'Istan',
								'childs' => array(),
							),

							array(
								'type' => 'town',
								'name' => 'Marbella',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'Marbella Centre',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Nueva Andalucia',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Marbella Golden Mile',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Puerto Banus',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Elviria',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Las Chapas',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'El Rosario',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Los Monteros',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Carib Playa',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Rio Real',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Cabopino',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Nagueles',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Costabella',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Marbesa',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Sierra Blanca',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Artola',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Bahia de Marbella',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Mairena',
									),
								),
							),

							array(
								'type' => 'town',
								'name' => 'San Pedro Alcantara',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'San Pedro Center',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Guadalmina Alta',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'San Pedro Playa',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Guadalmina Baja',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Cortijo Blanco',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Nueva Alcantara',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Linda Vista',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Valle del Sol',
									),
								),
							),

						),
					),

					array(
						'type' => 'region',
						'name' => 'Costa del Sol West',
						'childs' => array(

							array(
								'type' => 'town',
								'name' => 'Estepona',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'El Paraiso',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'New Golden Mile',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Cancelada',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Atalaya',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Estepona Town',
									),
								),
							),

							array(
								'type' => 'town',
								'name' => 'La Duquesa',
								'childs' => array(),
							),

							array(
								'type' => 'town',
								'name' => 'Manilva',
								'childs' => array(),
							),

							array(
								'type' => 'town',
								'name' => 'San Luis de Sabinillas',
								'childs' => array(),
							),

							array(
								'type' => 'town',
								'name' => 'San Roque',
								'childs' => array(),
							),

							array(
								'type' => 'town',
								'name' => 'Sotogrande',
								'childs' => array(
									array(
										'type' => 'urbanisation',
										'name' => 'Marina de Sotogrande',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'La Reserva',
									),
									array(
										'type' => 'urbanisation',
										'name' => 'Sotogrande Alto',
									),
								),
							),

						),
					),

					array(
						'type' => 'region',
						'name' => 'Malaga Province Inland',
						'childs' => array(

							array(
								'type' => 'town',
								'name' => 'Alameda',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Alcaucin',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Alfarnate',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Alfarnatejo',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Algarrobo',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Algatocin',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Alhaurin de la Torre',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Alhaurin el Grande',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Almachar',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Almogia',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Alora',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Alozaina',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Antequera',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Archez',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Archidona',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Ardales',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Arenas',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Arriate',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Axarquia',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Benamargosa',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Benamocarra',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Cajiz',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Campillos',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Ca単ete la Real',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Canillas de Aceituno',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Canillas de Albaida',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Carratraca',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Cartama',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Casabermeja',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Casarabonela',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Casares',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Chilches',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Coin',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Colmenar',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Comares',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Competa',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Cortes de la Frontera',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Corumbela',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Cuevas de San Marcos',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Cutar',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'El Borge',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'El Burgo',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'El Chorro',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Frigiliana',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Fuente de Piedra',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Gaucin',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Genalguacil',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Gibralgalia',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Guaro',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Humilladero',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Iznate',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Jimera de Libar',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Jubrique',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Juzcar',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'La Cala del Moral',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Lagos',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Los Romanes',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Macharaviaya',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Moclinejo',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Mollina',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Monda',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Mondron',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Ojen',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Periana',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Pizarra',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Puente don Manuel',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Riogordo',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Ronda',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Rubite',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Salares',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Sayalonga',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Sedella',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Sierra de Yeguas',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Teba',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Tolox',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Torre de Benagalbon',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Totalan',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Trapiche',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Triana',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Venta Baja',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Villafranco del Guadalhorce',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Villanueva de Algaidas',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Villanueva de la Concepcion',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Villanueva del Rosario',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Villanueva del Trabuco',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Vi単uela',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Yunquera',
								'childs' => array(),
							),

						),
					),

					array(
						'type' => 'region',
						'name' => 'Malaga City',
						'childs' => array(
							array(
								'type' => 'town',
								'name' => 'Malaga Center',
							),
							array(
								'type' => 'town',
								'name' => 'Churriana',
							),
							array(
								'type' => 'town',
								'name' => 'Campanillas',
							),
							array(
								'type' => 'town',
								'name' => 'Malaga East',
							),
							array(
								'type' => 'town',
								'name' => 'Carretera de Cadiz',
							),
							array(
								'type' => 'town',
								'name' => 'Ciudad Jardin',
							),
							array(
								'type' => 'town',
								'name' => 'Cruz de Humilladero',
							),
							array(
								'type' => 'town',
								'name' => 'Teatinos',
							),
							array(
								'type' => 'town',
								'name' => 'Palma Palmilla',
							),
							array(
								'type' => 'town',
								'name' => 'Bailen',
							),
							array(
								'type' => 'town',
								'name' => 'Puerto de la Torre',
							),
						)
					),

					array(
						'type' => 'region',
						'name' => 'Malaga Province East',
						'childs' => array(
							array(
								'type' => 'town',
								'name' => 'Algarrobo-Costa',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Almayate',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Almayate Alto',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Benajarafe',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Caleta de Velez',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'El Morche',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Maro',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Mezquitilla',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Nerja',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Pinares de San Anton',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Rincon de la Victoria',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Torre del Mar',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Torrox',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Torrox Costa',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Valle-Niza',
								'childs' => array(),
							),
							array(
								'type' => 'town',
								'name' => 'Velez-Malaga',
								'childs' => array(),
							),
						),
					),
				),
			),

			array(
				'type' => 'area',
				'name' => 'Costa Blanca',
			),

			array(
				'type' => 'area',
				'name' => 'Ibiza',
			),

			array(
				'type' => 'area',
				'name' => 'Mallorca',
			),

			array(
				'type' => 'area',
				'name' => 'Madrid',
			),
		);

		$data = array();

		$zones = esm_insert_location( $zones );

		update_option( 'esm_locations_migrations12345671', 1 );
	}
}

function esm_insert_location( $zones, $parent = 0 ) {

	$l = array();

	foreach ( $zones as $key => $zone ) {

		$term = wp_insert_term( $zone['name'], 'esm_locations', array(
			'parent' => ! empty( $parent ) ? $parent : '',
		) );

		update_term_meta( $term['term_id'], 'esm_location_type', $zone['type'] );


		if ( ! empty( $zone['childs'] ) ) {
			$zones[$key]['childs'] = esm_insert_location( $zone['childs'], $term['term_id'] );
		}
	}

	return $zones;
}

add_action( 'esm_locations_edit_form_fields', 'esm_taxonomy_edit_field', 10 );
add_action( 'esm_locations_add_form_fields', 'esm_taxonomy_add_field', 10 );

/**
 * @param $term
 */
function esm_taxonomy_edit_field( $term ) {

	$value = get_term_meta( $term->term_id, 'esm_location_type', 1 ); ?>

	<tr class="form-field">
		<th scope="row" valign="top"><label><?php _e( 'Location Type', 'es-plugin' ); ?></label></th>
		<td>
			<select name="term_meta[type]" id="" required>
				<option value="">Choose location type</option>
				<option <?php selected( $value, 'region' ); ?> value="region">Region</option>
				<option <?php selected( $value, 'town' ); ?> value="town">Town</option>
				<option <?php selected( $value, 'urbanisation' ); ?> value="urbanisation">Urbanisation</option>
			</select>
		</td>
	</tr>
	<?php
}

/**
 * @param $xonomy_slug
 */
function esm_taxonomy_add_field( $taxonomy_slug ) {
	?>
	<div class="form-field">
		<label><?php _e( 'Location Type', 'es-plugin' ); ?></label>
		<select name="term_meta[type]" id="" required>
			<option value="">Choose location type</option>
			<option value="region">Region</option>
			<option value="town">Town</option>
			<option value="urbanisation">Urbanisation</option>
		</select>
	</div>
	<?php
}

add_action("create_esm_locations", 'esm_save_custom_taxonomy_meta');
add_action("edited_esm_locations", 'esm_save_custom_taxonomy_meta');

/**
 * @param $term_id
 */
function esm_save_custom_taxonomy_meta( $term_id ) {
	if ( ! current_user_can('edit_term', $term_id) ) return;

	if ( ! wp_verify_nonce( $_POST['_wpnonce'], "update-tag_$term_id" ) && ! wp_verify_nonce( $_POST['_wpnonce_add-tag'], "add-tag" ) ) return;

	if ( empty( $_POST['term_meta']['type'] ) ) {
		delete_term_meta( $term_id, 'esm_location_type' );
	} else {
		update_term_meta( $term_id, 'esm_location_type', $_POST['term_meta']['type'] );
	}

	return $term_id;
}

/**
 * @param $terms
 * @param $post_id
 * @param $taxonomy
 *
 * @return array
 */
function esm_get_the_terms( $terms, $post_id, $taxonomy ) {
	if ( $taxonomy == 'esm_locations' && ! empty( $terms ) ) {
		foreach ( $terms as $key => $term ) {
			$display_label = get_term_meta( $term->term_id, 'esm_location_type', true );
			$terms[ $key ]->name = $display_label ? $display_label : $terms[ $key ]->name;
		}
	}
	return $terms;
}

/**
 * @param $fields
 *
 * @return array
 */
function esm_get_widget_fields( $fields ) {

	$fields[] = 'region';
	$fields[] = 'town';
	$fields[] = 'urbanisation';

	return $fields;
}
add_filter( 'es_get_widget_fields', 'esm_get_widget_fields' );

/**
 * @param $fields
 *
 * @return mixed
 */
function esm_property_fields( $fields ) {

	$fields['region'] = array(
		'label' => __( 'Region', 'esm' ),
		'type' => 'list',
		'prompt' => __( 'Choose region', 'esm' ),
		'values_callback' => array(
			'callback' => 'get_terms',
			'args' => array( 'esm_locations', array( 'hide_empty' => false, 'fields' => 'id=>name', 'meta_key' => 'esm_location_type', 'meta_value' => 'region' ) ),
		),
		'options' => array(
			'class' => 'js-dep-location js-field-region',
			'data-type' => 'town',
			'data-label' => __( 'Choose Region', 'esm' ),
		),
	);

	$fields['town'] = array(
		'label' => __( 'Town', 'esm' ),
		'type' => 'list',
		'prompt' => __( 'Choose town', 'esm' ),
		'options' => array(
			'class' => 'js-dep-location js-field-town',
			'data-type' => 'urbanisation',
			'data-label' => __( 'Choose Town', 'esm' ),
		),
	);

	$fields['urbanisation'] = array(
		'label' => __( 'Urbanisation', 'esm' ),
		'type' => 'list',
		'prompt' => __( 'Choose urbanisation', 'esm' ),
		'options' => array(
			'class' => 'js-dep-location js-field-urbanisation',
			'data-label' => __( 'Choose Urbanisation', 'esm' ),
		),
	);

	return $fields;
}
add_filter( 'es_property_get_fields', 'esm_property_fields' );

/**
 * @return void
 */
function esm_ajax_get_dep_locations() {

	if ( ! empty( $_GET['term_id'] ) ) {

		$term_id = $_GET['term_id'];

		$args = array(
			'taxonomy' => 'esm_locations',
			'hide_empty' => false,
			'fields' => 'id=>name',
			'get' => 'all',
			'meta_query' => array(
				array(
					'key' => 'esm_location_type',
					'value' => $_GET['type']
				),
			),
		);

		if ( $term_id != '-1' ) {
			$args['parent'] = $term_id;
		}

		$terms = get_terms( $args );

		$options = '';

		if ( $terms ) {
			foreach ( $terms as $id => $term ) {
				$options .= sprintf( "<option value='%s'>%s</option>", $id, $term );
			}
		}

		wp_die( $options );
	}
}
add_action( 'wp_ajax_esm_get_dep_locations', 'esm_ajax_get_dep_locations' );
add_action( 'wp_ajax_nopriv_esm_get_dep_locations', 'esm_ajax_get_dep_locations' );

function esm_enqueue_scripts() {
	wp_enqueue_script( 'esm_locations', plugin_dir_url( __FILE__ ) . '/locations.js', array( 'jquery' ) );
	wp_localize_script( 'esm_locations', 'Esm', array(
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
	) );
}
add_action( 'wp_enqueue_scripts', 'esm_enqueue_scripts' );


add_filter( 'ept_search_build_query_args', 'esm_search_build_query_args', 10, 1 );

function esm_search_build_query_args( $query_args ) {
    
    if ( ! empty( $_GET['es_search'] ) ) {
        $meta_query = ! empty( $query_args['meta_query'] ) ? $query_args['meta_query'] : array();

		if ( ! empty( $meta_query ) ) {
			foreach ( $meta_query as $key => $meta ) {
				if ( in_array( $meta['key'], array( 'es_property_town', 'es_property_region', 'es_property_urbanisation' ) ) ) {
					unset( $meta_query[ $key ] );
				}
			}
		}

		$tax = array();

		if ( ! empty( $_GET['es_search']['region'] ) ) {
			$tax[] = intval( $_GET['es_search']['region'] );
		}

		if ( ! empty( $_GET['es_search']['town'] ) ) {
			$tax[] = intval( $_GET['es_search']['town'] );
		}

		if ( ! empty( $_GET['es_search']['urbanisation'] ) ) {
			$tax[] = intval( $_GET['es_search']['urbanisation'] );
		}

		$tax = ! empty( $tax ) ? array_filter( $tax ) : $tax;
		
		$tax_query = ! empty( $query_args['tax_query'] ) ? $query_args['tax_query'] : array();

		if ( ! empty( $tax ) ) {
			
			$tax_query['relation'] = 'AND';
			
			foreach ( $tax as $tid ) {
			    $tax_query[] = array(
    				'taxonomy' => 'esm_locations',
    				'field' => 'id',
    				'terms' => $tid,
    			);
			}
		}
	
		
		$query_args['meta_query'] = $meta_query;
		$query_args['tax_query'] = $tax_query;
    }

    return $query_args;
}

/**
 * @param $query WP_Query
 */
function esm_pre_get_posts( $query ) {

	// If query is search.
	if ( ! empty( $_GET['es_search'] ) && is_array( $_GET['es_search'] ) && $query->is_search && ! is_admin() ) {

		$meta_query = $query->get( 'meta_query' );

		if ( ! empty( $meta_query ) ) {
			foreach ( $meta_query as $key => $meta ) {
				if ( in_array( $meta['key'], array( 'es_property_town', 'es_property_region', 'es_property_urbanisation' ) ) ) {
					unset( $meta_query[ $key ] );
				}
			}
		}

		$tax = array();

		if ( ! empty( $_GET['es_search']['region'] ) ) {
			$tax[] = intval( $_GET['es_search']['region'] );
		}

		if ( ! empty( $_GET['es_search']['town'] ) ) {
			$tax[] = intval( $_GET['es_search']['town'] );
		}

		if ( ! empty( $_GET['es_search']['urbanisation'] ) ) {
			$tax[] = intval( $_GET['es_search']['urbanisation'] );
		}

		$tax = ! empty( $tax ) ? array_filter( $tax ) : $tax;

		if ( ! empty( $tax ) ) {
			$tax_query = $query->get( 'tax_query' );
			
			$tax_query['relation'] = 'AND';
			
			foreach ( $tax as $tid ) {
			    $tax_query[] = array(
    				'taxonomy' => 'esm_locations',
    				'field' => 'id',
    				'terms' => $tid,
    			);
			}

			$query->set( 'tax_query', $tax_query );
		}

		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'pre_get_posts', 'esm_pre_get_posts', 21 );