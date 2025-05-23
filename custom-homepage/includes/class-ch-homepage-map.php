<?php

/**
 * Class Ept_Properties_Categories_Grid
 */
class Ch_Homepage_Map extends SiteOrigin_Widget {

	/**
	 * Ept_Properties_Categories_Grid constructor.
	 */
	function __construct() {

		parent::__construct(
			'ch-homepage-map',
			__( 'Homepage map', 'ept' ),
			array(
				'has_preview' => false,
				'description' => __( 'Displays map with regions of Costa Del Sol', 'ept' ),
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

					'region_name' => array(
						'type' => 'select',
						'label' => __( 'Region', 'ept' ),
						'options' => $this->get_regions(),
					),

					'color' => array(
						'type' => 'color',
						'label' => __( 'Color', 'ept' ),
						'description' => __( 'Link to region page', 'ept' )
					),

          'opacity' => array(
            'type' => 'measurement',
            'label' => __('Opacity', 'ept'),
            'default' => '30%',
            'units' => array('%'),
          ),

					'link' => array(
						'type' => 'link',
						'label' => __( 'Link', 'ept' ),
						'description' => __( 'Link to region page', 'ept' )
					),


				),
			),
	  'ports' => array(
		  'type' => 'repeater',
		  'item_name'  => __( 'Ports', 'ept' ),
		  'item_label' => array(
			  'update_event' => 'change',
			  'value_method' => 'val'
		  ),
		  'fields' => array(

			  'latlng' => array(
				  'type' => 'text',
				  'label' => __( 'Coordinates(Lat, Lng)', 'ept' ),
			  ),
		  ),
	  ),
	  'airports' => array(
		  'type' => 'repeater',
		  'item_name'  => __( 'Airports', 'ept' ),
		  'item_label' => array(
			  'update_event' => 'change',
			  'value_method' => 'val'
		  ),
		  'fields' => array(

			  'latlng' => array(
				  'type' => 'text',
				  'label' => __( 'Coordinates(Lat, Lng)', 'ept' ),
			  ),
		  ),
	  ),
	  'cities' => array(
		  'type' => 'repeater',
		  'item_name'  => __( 'Cities', 'ept' ),
		  'item_label' => array(
			  'update_event' => 'change',
			  'value_method' => 'val'
		  ),
		  'fields' => array(
			  'name' => array(
				  'type' => 'text',
				  'label' => __( 'City name', 'ept' ),
			  ),
	    'latlng' => array(
		    'type' => 'text',
		    'label' => __( 'Coordinates(Lat, Lng)', 'ept' ),
	    ),
	    'circle' => array(
		    'type' => 'measurement',
		    'default' => '7px',
		    'label' => __( 'Circle size(px)', 'ept' ),
	      'units' => array('px'),
	    ),
		  ),
	  ),
		);
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

	function get_regions() {
	  return array(
	    'manilva' => __( 'Manilva', 'ept' ),
	    'casares' => __( 'Casares', 'ept' ),
	    'estepona' => __( 'Estepona', 'ept' ),
	    'benahavis' => __( 'Benahavis', 'ept' ),
	    'istan' => __( 'Istán', 'ept' ),
	    'mijas' => __( 'Mijas', 'ept' ),
	    'marbella' => __( 'Marbella', 'ept' ),
	    'ojen' => __( 'Ojén', 'ept' ),
	    'fuengirola' => __( 'Fuengirola', 'ept' ),
	    'benalmadena' => __( 'Benalmádena', 'ept' ),
	    'torremolinos' => __( 'Torremolinos', 'ept' ),

    );
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
		$region_list = $this->get_regions();

		// Ensure regions is an array and not empty before processing
		if ( ! empty( $template_vars['regions'] ) && is_array( $template_vars['regions'] ) ) {
			foreach ($template_vars['regions'] as $key => $region) {
				$post_id_raw = $region['link'] ?? '';
				$post_id = str_replace('post: ', '', $post_id_raw);
				$permalink = get_permalink( $post_id );
				$template_vars['regions'][$key]['link'] = $permalink ?: '#'; // Default to '#' if permalink is invalid

				$region_name_key = $region['region_name'] ?? null;
				$template_vars['regions'][$key]['title'] = (is_scalar($region_name_key) && isset($region_list[$region_name_key])) ? $region_list[$region_name_key] : ($region_name_key ?? 'Unknown');
				
				// Ensure color and opacity have defaults if not set
				$template_vars['regions'][$key]['color'] = $region['color'] ?? '#0000FF'; // Default blue
				$template_vars['regions'][$key]['opacity'] = $region['opacity'] ?? '50%'; // Default 50%
			}
		} else {
			$template_vars['regions'] = []; // Ensure regions is an empty array if not set or not an array
		}

		// Ensure other parts of template_vars are at least empty arrays if not set for JSON encoding
		$template_vars['ports'] = $template_vars['ports'] ?? [];
		$template_vars['airports'] = $template_vars['airports'] ?? [];
		$template_vars['cities'] = $template_vars['cities'] ?? [];


		// Only proceed if there's something to display, primarily regions.
		if ( ! empty( $template_vars['regions'] ) ) :
			$data = json_encode($template_vars);
			ob_start();

			echo $args['before_widget'];

			?>
    <script>
        (function ($) {
            'use strict';

            $(document).ready(function () {
                let data = <?php echo $data; ?>;
                let map;

                function initMap() {
                    map = new google.maps.Map(document.getElementById("ch-homepage-map"), {
                        zoom: 11,
                        //center: {lat: 36.462401, lng: -5.010935},
			center: {lat: 36.537451, lng: -4.922794},
                        zoomControl: false, 
                        scrollwheel: false,
						disableDoubleClickZoom: true,
						minZoom:11,
						maxZoom:11,
                        styles: [
  {
    "elementType": "labels",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "administrative",
    "elementType": "geometry",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "administrative.land_parcel",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "administrative.neighborhood",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "poi",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "road",
    "elementType": "labels.icon",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  },
  {
    "featureType": "transit",
    "stylers": [
      {
        "visibility": "off"
      }
    ]
  }
],
                    });
                    if (data.regions && Array.isArray(data.regions)) {
                        for (let r = 0; r < data.regions.length; r++) {
                            let regionData = data.regions[r];
                            let $regionName = regionData.region_name;
                            let $regionTitle = regionData.title;
                            let $regionLink = regionData.link;
                            let $regionNameUpp = $regionName.charAt(0).toUpperCase() + $regionName.slice(1);
                            let $regionColor = regionData.color || '#0000FF'; // Default color
                            
                            let opacityValue = 0.5; // Default opacity
                            if (typeof regionData.opacity === 'string' && regionData.opacity.includes('%')) {
                                opacityValue = parseFloat(regionData.opacity.replace('%', '')) / 100;
                            } else if (typeof regionData.opacity === 'number') {
                                opacityValue = regionData.opacity; // Assuming it's already a decimal if a number
                            }

                            let regionPaths;
                            switch ($regionNameUpp) {
                                case 'Manilva': regionPaths = getManilvaPaths(); break;
                                case 'Casares': regionPaths = getCasaresPaths(); break;
                                case 'Estepona': regionPaths = getEsteponaPaths(); break;
                                case 'Benahavis': regionPaths = getBenahavisPaths(); break;
                                case 'Istan': regionPaths = getIstanPaths(); break; // Corrected from Istan to Istán if getIstanPaths exists
                                case 'Mijas': regionPaths = getMijasPaths(); break;
                                case 'Marbella': regionPaths = getMarbellaPaths(); break;
                                case 'Ojen': regionPaths = getOjenPaths(); break; // Corrected from Ojen to Ojén
                                case 'Fuengirola': regionPaths = getFuengirolaPaths(); break;
                                case 'Benalmadena': regionPaths = getBenalmadenaPaths(); break; // Corrected from Benalmadena to Benalmádena
                                case 'Torremolinos': regionPaths = getTorremolinosPaths(); break;
                                default: regionPaths = []; console.error('Unknown region:', $regionNameUpp);
                            }
                            setMapOptions($regionTitle, regionPaths, $regionColor, opacityValue, '#FFFFFF', $regionLink);
                        }
                    }

                    //Airplane.
                    if (data.airports && Array.isArray(data.airports)) {
                        const image = {
                            url: "<?php echo esc_url(ECHP_URL . '/assets/plane.svg'); ?>",
                            scaledSize : new google.maps.Size(30, 30),
                        };
                        for (let k = 0; k < data.airports.length; k++) {
                            if (data.airports[k] && typeof data.airports[k].latlng === 'string') {
                                const coordinates = data.airports[k].latlng.split(",").map(coord => parseFloat(coord.trim()));
                                if (coordinates.length === 2 && !isNaN(coordinates[0]) && !isNaN(coordinates[1])) {
                                    new google.maps.Marker({
                                        position: {lat: coordinates[0], lng: coordinates[1]},
                                        map,
                                        icon: image,
                                    });
                                } else {
                                    console.error('Invalid coordinates for airport:', data.airports[k].latlng);
                                }
                            }
                        }
                    }


                    //Boats.
                    if (data.ports && Array.isArray(data.ports)) {
                        const imageBoat = {
                            url: "<?php echo esc_url(ECHP_URL . '/assets/boat.svg'); ?>",
                            scaledSize : new google.maps.Size(30, 30),
                        };
                        for (let b = 0; b < data.ports.length; b++) {
                             if (data.ports[b] && typeof data.ports[b].latlng === 'string') {
                                const coordinates = data.ports[b].latlng.split(",").map(coord => parseFloat(coord.trim()));
                                if (coordinates.length === 2 && !isNaN(coordinates[0]) && !isNaN(coordinates[1])) {
                                    new google.maps.Marker({
                                        position: {lat: coordinates[0], lng: coordinates[1]},
                                        map,
                                        icon: imageBoat,
                                    });
                                } else {
                                    console.error('Invalid coordinates for port:', data.ports[b].latlng);
                                }
                            }
                        }
                    }


                    //Cities.
                    if (data.cities && Array.isArray(data.cities)) {
                        for (let c = 0; c < data.cities.length; c++) {
                            let cityData = data.cities[c];
                            if (cityData && typeof cityData.latlng === 'string' && typeof cityData.circle === 'string' && typeof cityData.name === 'string') {
                                let infowindow = new google.maps.InfoWindow();
                                let $circleSize = parseInt(cityData.circle.replace('px', ''), 10);
                                if (isNaN($circleSize)) $circleSize = 7; // Default size

                                const imageCity = {
                                    url: "<?php echo esc_url(ECHP_URL . '/assets/circle.svg'); ?>",
                                    scaledSize : new google.maps.Size($circleSize, $circleSize),
                                };
                                const coordinates = cityData.latlng.split(",").map(coord => parseFloat(coord.trim()));
                                if (coordinates.length === 2 && !isNaN(coordinates[0]) && !isNaN(coordinates[1])) {
                                    let marker = new google.maps.Marker({
                                        position: {lat: coordinates[0], lng: coordinates[1]},
                                        map,
                                        icon: imageCity,
                                    });
                                    infowindow.setContent('<div class="ch-city ' + cityData.name.toLowerCase() + '">' + escapeHtml(cityData.name) + '</div>');
                                    infowindow.open(map, marker);
                                } else {
                                     console.error('Invalid coordinates for city:', cityData.latlng);
                                }
                            }
                        }
                    }
                    
                    function escapeHtml(unsafe) {
                        return unsafe
                             .replace(/&/g, "&amp;")
                             .replace(/</g, "&lt;")
                             .replace(/>/g, "&gt;")
                             .replace(/"/g, "&quot;")
                             .replace(/'/g, "&#039;");
                    }

                    var windowWidth = window.screen.width;
                    if (windowWidth < 600 )
                    {
                      google.maps.event.addListener(window, 'resize', function() {
    google.maps.event.trigger(map, 'resize');

  });
                        //map.setZoom(9);
			  //map.setCenter({lat: 36.490047, lng: -4.900278});
                        //map.setZoom(11);
			  //map.setCenter({lat: 36.537451, lng: -4.922794});
                    }
    map.setCenter({lat: 36.537451, lng: -4.922794});
                }

                initMap();

                var globalZIndex = 1;

                function setMapOptions($polygonName, $polygonPaths, $fillColor, $fillOpacity, $strokeColor, $regionLink) {
                    const popUp = new google.maps.InfoWindow({
                        content: "<span>" + $polygonName + "</span>"
                    });
                    // Construct the polygon.
                    $polygonName = new google.maps.Polygon({
                        paths: $polygonPaths,
                        strokeColor: $strokeColor,
                        strokeWeight: 4,
                        fillColor: $fillColor,
                        fillOpacity: $fillOpacity,
                    });

                    $polygonName.setMap(map);

                    google.maps.event.addListener($polygonName, 'click', function (event) {
                        window.location.replace($regionLink);
                    });

                    google.maps.event.addListener($polygonName, 'mouseover', function (event) {
                        var bounds = new google.maps.LatLngBounds();
                        var i;
                        for (i = 0; i < $polygonPaths.length; i++) {
                            bounds.extend($polygonPaths[i]);
                        }

                        this.setOptions({
                            strokeColor: $fillColor,
                            strokeOpacity: 0.8,
                            zIndex: globalZIndex++
                        });
                    });

                    google.maps.event.addListener($polygonName, 'mouseout', function (event) {

                        this.setOptions({
                            strokeColor: '#FFFFFF',
                            fillColor: $fillColor,
                        });
                    });
                }

                function getManilvaPaths() {
                    return [
                        {lat: 36.38801064648186, lng: -5.257374802797775},
                        {lat: 36.38154824884429, lng: -5.265285424837445},
                        {lat: 36.38133507300839, lng: -5.271544340230729},
                        {lat: 36.38157972570544, lng: -5.272476566813578},
                        {lat: 36.36955417696191, lng: -5.27836099751041},
                        {lat: 36.35607686276863, lng: -5.294033694898421},
                        {lat: 36.3289641405124, lng: -5.311845983581499},
                        {lat: 36.32885104898519, lng: -5.303572723939142},
                        {lat: 36.33622978226442, lng: -5.290030279387801},
                        {lat: 36.33580769845542, lng: -5.275401420405601},
                        {lat: 36.32450116564429, lng: -5.265016119527782},
                        {lat: 36.32309259402983, lng: -5.257181266211688},
                        {lat: 36.31756194294933, lng: -5.251950527659091},
                        {lat: 36.31227186993841, lng: -5.25397933693334},
                        {lat: 36.31032535764368, lng: -5.251358882755271},
                        {lat: 36.31047328327294, lng: -5.247811639827249},
                        {lat: 36.31405683149778, lng: -5.245999669448169},
                        {lat: 36.34875752330373, lng: -5.231622930510092},
                        {lat: 36.37282782717919, lng: -5.221405392779727},
                        {lat: 36.38801064648186, lng: -5.257374802797775},
                    ];
                }

                function getCasaresPaths() {
                    return [
                        {lat: 36.37278916856813, lng: -5.22137923818049},
                        {lat: 36.37974044990234, lng: -5.211309600561433},
                        {lat: 36.38598183232783, lng: -5.206185151163703},
                        {lat: 36.40990694273461, lng: -5.216632718334504},
                        {lat: 36.42280285175105, lng: -5.220100373009259},
                        {lat: 36.47591409123082, lng: -5.205551200590357},
                        {lat: 36.4806795638039, lng: -5.209160963617757},
                        {lat: 36.50940416002359, lng: -5.237187300302613},
                        {lat: 36.50902678447263, lng: -5.268361757953003},
                        {lat: 36.51479886573007, lng: -5.26832684079193},
                        {lat: 36.5116381619841, lng: -5.273371488299874},
                        {lat: 36.51443751753895, lng: -5.280097683888686},
                        {lat: 36.5036375587266, lng: -5.27942480897324},
                        {lat: 36.50367761435315, lng: -5.282807611125266},
                        {lat: 36.48127587568377, lng: -5.294432225685783},
                        {lat: 36.47606860850894, lng: -5.299608405933486},
                        {lat: 36.4704541817789, lng: -5.309369425039348},
                        {lat: 36.43013175008534, lng: -5.35475072067265},
                        {lat: 36.41979157619526, lng: -5.356664812227825},
                        {lat: 36.41421595388814, lng: -5.347014565000623},
                        {lat: 36.39198783083572, lng: -5.334181640755988},
                        {lat: 36.36798013893585, lng: -5.333760085883928},
                        {lat: 36.34060888888688, lng: -5.314341775732347},
                        {lat: 36.32931419606266, lng: -5.319501976619895},
                        {lat: 36.32898438455217, lng: -5.311877669259091},
                        {lat: 36.35595650897912, lng: -5.294167449776794},
                        {lat: 36.36955281548243, lng: -5.278379545483727},
                        {lat: 36.38158797017105, lng: -5.272476821670513},
                        {lat: 36.3813277837716, lng: -5.27159856961827},
                        {lat: 36.38154945758583, lng: -5.26531446981192},
                        {lat: 36.38801105483024, lng: -5.257372312066035},
                        {lat: 36.37278916856813, lng: -5.22137923818049},
                    ];
                }

                function getEsteponaPaths() {
                    return [
                        {lat: 36.41278974515433, lng: -5.179263180060251},
                        {lat: 36.41513910496764, lng: -5.170481524334653},
                        {lat: 36.41651890826229, lng: -5.166032027024303},
                        {lat: 36.41311990522495, lng: -5.163243212040818},
                        {lat: 36.41293358680634, lng: -5.157606372415651},
                        {lat: 36.42794696325755, lng: -5.135028863653385},
                        {lat: 36.42720559182931, lng: -5.129419906795243},
                        {lat: 36.42863470947884, lng: -5.121560501951128},
                        {lat: 36.43308266200163, lng: -5.109102193207843},
                        {lat: 36.43935278530594, lng: -5.101052321047809},
                        {lat: 36.44064770385234, lng: -5.096670843967228},
                        {lat: 36.44931999068742, lng: -5.083779230679578},
                        {lat: 36.4522438413703, lng: -5.069138726072964},
                        {lat: 36.44923155614192, lng: -5.060360011602145},
                        {lat: 36.46069270857366, lng: -5.034304537274753},
                        {lat: 36.45919574943518, lng: -5.005499145474328},
                        {lat: 36.49103368625335, lng: -5.016149979174936},
                        {lat: 36.469563796418, lng: -5.050049226839494},
                        {lat: 36.47127123058065, lng: -5.075540005165387},
                        {lat: 36.48687057596044, lng: -5.086653969258922},
                        {lat: 36.5174219538465, lng: -5.120174314347246},
                        {lat: 36.52471773472106, lng: -5.14271011219418},
                        {lat: 36.52934705348243, lng: -5.147981106799558},
                        {lat: 36.52994137479203, lng: -5.164197136824102},
                        {lat: 36.51442848534466, lng: -5.184257521838692},
                        {lat: 36.50397761713926, lng: -5.183410600629459},
                        {lat: 36.47590420559028, lng: -5.205573353635425},
                        {lat: 36.42282272906008, lng: -5.220067268140962},
                        {lat: 36.40983080495212, lng: -5.216587029762149},
                        {lat: 36.38598284191945, lng: -5.206174780315544},
                        {lat: 36.41278974515433, lng: -5.179263180060251},
                    ];
                }

                function getBenahavisPaths() {
                    return [
                        {lat: 36.52471912571932, lng: -5.142688089677749},
                        {lat: 36.5174104722275, lng: -5.120160427084256},
                        {lat: 36.48687585264124, lng: -5.086648674254048},
                        {lat: 36.47126746300651, lng: -5.07553924919752},
                        {lat: 36.46957260633489, lng: -5.050039585808092},
                        {lat: 36.50770158196111, lng: -4.989972575182239},
                        {lat: 36.5196386913717, lng: -4.988447803382538},
                        {lat: 36.5345804841944, lng: -4.973125572746716},
                        {lat: 36.54041509014765, lng: -4.98297141001736},
                        {lat: 36.55785450633591, lng: -4.987563733979584},
                        {lat: 36.56978485318588, lng: -4.999933004507779},
                        {lat: 36.59008437338807, lng: -5.007294978088163},
                        {lat: 36.60328994305322, lng: -5.02673136054235},
                        {lat: 36.62451650888732, lng: -5.027521445890367},
                        {lat: 36.62916627331401, lng: -5.040189868491804},
                        {lat: 36.63659226211705, lng: -5.047457272481361},
                        {lat: 36.6293599665354, lng: -5.054803176871127},
                        {lat: 36.6065928557444, lng: -5.064755010993917},
                        {lat: 36.59779900773758, lng: -5.063566912329536},
                        {lat: 36.5936989717684, lng: -5.078833873170807},
                        {lat: 36.57677622945791, lng: -5.087720340719776},
                        {lat: 36.56703299418037, lng: -5.102895780181482},
                        {lat: 36.56657196923934, lng: -5.108926362095144},
                        {lat: 36.55362668146948, lng: -5.108878615475755},
                        {lat: 36.53852047879163, lng: -5.123335575717133},
                        {lat: 36.52471912571932, lng: -5.142688089677749},
                    ];
                }

                function getIstanPaths() {
                    return [
                        {lat: 36.5345790206775, lng: -4.97312622739619},
                        {lat: 36.54055083605926, lng: -4.941563500699887},
                        {lat: 36.55286604948113, lng: -4.924315528732496},
                        {lat: 36.59482139723123, lng: -4.906653426425383},
                        {lat: 36.59814716254841, lng: -4.899320640547064},
                        {lat: 36.60641471689306, lng: -4.896624231442407},
                        {lat: 36.6134521756565, lng: -4.911929823411965},
                        {lat: 36.63450779371277, lng: -4.919916868276212},
                        {lat: 36.63584246986107, lng: -4.938542331665731},
                        {lat: 36.63011987431955, lng: -4.947981800753741},
                        {lat: 36.63448806972561, lng: -4.950740289350934},
                        {lat: 36.64052938987891, lng: -4.97849655490739},
                        {lat: 36.6362261694076, lng: -4.99705538099799},
                        {lat: 36.64542659847946, lng: -5.003872266741848},
                        {lat: 36.65363222800269, lng: -5.020456688497252},
                        {lat: 36.64438674696849, lng: -5.029081203043951},
                        {lat: 36.63953913882954, lng: -5.047156985320854},
                        {lat: 36.63659339816198, lng: -5.047456055608138},
                        {lat: 36.62916638699981, lng: -5.0401781472831},
                        {lat: 36.62451114774473, lng: -5.027515961116771},
                        {lat: 36.60328467316848, lng: -5.026719368335812},
                        {lat: 36.5901117881754, lng: -5.00730371247188},
                        {lat: 36.56977628584639, lng: -4.9999271814146},
                        {lat: 36.55785968460933, lng: -4.987559633330294},
                        {lat: 36.54041224167059, lng: -4.982962881265674},
                        {lat: 36.5345790206775, lng: -4.97312622739619},
                    ];
                }

                function getMijasPaths() {
                    return [
                        {lat: 36.57475509800901, lng: -4.778913760582826},
                        {lat: 36.56200959105138, lng: -4.774135348794103},
                        {lat: 36.54663240912199, lng: -4.751686024515804},
                        {lat: 36.52311817772766, lng: -4.737296714006723},
                        {lat: 36.51612495174611, lng: -4.732125057684064},
                        {lat: 36.50901692223478, lng: -4.737340603625332},
                        {lat: 36.50303833657545, lng: -4.734626431250451},
                        {lat: 36.49522274872363, lng: -4.737369105675482},
                        {lat: 36.4865756674575, lng: -4.7306370463055},
                        {lat: 36.48654442405229, lng: -4.72607148349331},
                        {lat: 36.48642042830229, lng: -4.722245123163077},
                        {lat: 36.48661403368853, lng: -4.71952106906058},
                        {lat: 36.48784052951237, lng: -4.715585194746304},
                        {lat: 36.48736362556556, lng: -4.712702681804696},
                        {lat: 36.48748376662481, lng: -4.711320151800348},
                        {lat: 36.4885639121854, lng: -4.708774134112311},
                        {lat: 36.49007856791945, lng: -4.705311720704287},
                        {lat: 36.49013615531908, lng: -4.702111136701},
                        {lat: 36.48910968484735, lng: -4.700041026027381},
                        {lat: 36.49142971909047, lng: -4.691433122781117},
                        {lat: 36.50309066883011, lng: -4.676866577033999},
                        {lat: 36.5041445310431, lng: -4.669238232856404},
                        {lat: 36.50684346592377, lng: -4.662005842942795},
                        {lat: 36.50547577836848, lng: -4.65441082821624},
                        {lat: 36.50670074724162, lng: -4.638535497891638},
                        {lat: 36.52026212799316, lng: -4.628965230246793},
                        {lat: 36.53126369246472, lng: -4.637390083556069},
                        {lat: 36.5397820545228, lng: -4.631872825983142},
                        {lat: 36.54639765932836, lng: -4.634251486431483},
                        {lat: 36.5597231404878, lng: -4.629664469754799},
                        {lat: 36.59127082117851, lng: -4.605874406195063},
                        {lat: 36.58791137777913, lng: -4.598362626534147},
                        {lat: 36.59856669293244, lng: -4.594640128574527},
                        {lat: 36.61312732503676, lng: -4.601065937631391},
                        {lat: 36.62536677565314, lng: -4.625149049397278},
                        {lat: 36.62092598786038, lng: -4.632244956177761},
                        {lat: 36.6238259363515, lng: -4.643304347070667},
                        {lat: 36.61290517487794, lng: -4.648757427939163},
                        {lat: 36.61624795325218, lng: -4.680506357159237},
                        {lat: 36.60638555972879, lng: -4.695524605501312},
                        {lat: 36.59706537437948, lng: -4.726144563450926},
                        {lat: 36.58976912564219, lng: -4.773784041441095},
                        {lat: 36.57475509800901, lng: -4.778913760582826},
                    ];
                }

                function getMarbellaPaths() {
                    return [
                        {lat: 36.49106061218696, lng: -5.016158856769626},
                        {lat: 36.45921481294214, lng: -5.005495034244191},
                        {lat: 36.47777936112664, lng: -4.977404165518364},
                        {lat: 36.47806721584666, lng: -4.972080195559931},
                        {lat: 36.48276207218701, lng: -4.964659209796336},
                        {lat: 36.48506747670905, lng: -4.958118147105855},
                        {lat: 36.48414490332277, lng: -4.953025741000655},
                        {lat: 36.49495053517252, lng: -4.942788909478076},
                        {lat: 36.49924385845131, lng: -4.935574938723696},
                        {lat: 36.49989302882311, lng: -4.928332156702443},
                        {lat: 36.49936541802852, lng: -4.924954660270499},
                        {lat: 36.505306614614, lng: -4.913544335172858},
                        {lat: 36.50596647646795, lng: -4.898888889334671},
                        {lat: 36.50780816594526, lng: -4.863408885713661},
                        {lat: 36.49889520097926, lng: -4.816520125133628},
                        {lat: 36.48212748582943, lng: -4.740306483112466},
                        {lat: 36.4865839009677, lng: -4.730653113998395},
                        {lat: 36.4952401069367, lng: -4.737384100248905},
                        {lat: 36.50302900014089, lng: -4.73461965162543},
                        {lat: 36.50900995005886, lng: -4.737342896800741},
                        {lat: 36.51612929650982, lng: -4.732125035365756},
                        {lat: 36.52310174234393, lng: -4.737298675991537},
                        {lat: 36.54567840078744, lng: -4.804390032995878},
                        {lat: 36.5424770837603, lng: -4.80960598386209},
                        {lat: 36.53072203323559, lng: -4.852847881586353},
                        {lat: 36.5311336975785, lng: -4.867964758609671},
                        {lat: 36.54668254729236, lng: -4.8896577446623},
                        {lat: 36.54601616878347, lng: -4.915581604185487},
                        {lat: 36.55286500911642, lng: -4.924315014546212},
                        {lat: 36.54055436365218, lng: -4.941558754737664},
                        {lat: 36.53457880825415, lng: -4.973124821749321},
                        {lat: 36.51964083081337, lng: -4.988436234927645},
                        {lat: 36.5076943930473, lng: -4.989980058341672},
                        {lat: 36.49106061218696, lng: -5.016158856769626},
                    ];
                }

                function getOjenPaths() {
                    return [
                        {lat: 36.55286589139315, lng: -4.924314084196705},
                        {lat: 36.54601803927458, lng: -4.915574703290332},
                        {lat: 36.54668238114233, lng: -4.889654001873321},
                        {lat: 36.53113592035558, lng: -4.867968300003439},
                        {lat: 36.53072065229509, lng: -4.852856607712155},
                        {lat: 36.54248407216075, lng: -4.809569104033069},
                        {lat: 36.54568105879628, lng: -4.804385697009228},
                        {lat: 36.52310183821575, lng: -4.737299259893364},
                        {lat: 36.54663573852541, lng: -4.751686012890674},
                        {lat: 36.56200804984933, lng: -4.774136567260791},
                        {lat: 36.57474195081604, lng: -4.778916109330922},
                        {lat: 36.58977588923234, lng: -4.773786611370654},
                        {lat: 36.59504895615805, lng: -4.786397500929112},
                        {lat: 36.59129223839242, lng: -4.810963474247031},
                        {lat: 36.58251498780711, lng: -4.828444989275063},
                        {lat: 36.59438589491084, lng: -4.83639012418959},
                        {lat: 36.59719333888403, lng: -4.854850785905461},
                        {lat: 36.60261951198389, lng: -4.868700140818138},
                        {lat: 36.59981637774176, lng: -4.886042398312256},
                        {lat: 36.60641738387836, lng: -4.896615892317896},
                        {lat: 36.59815312829712, lng: -4.899312072725561},
                        {lat: 36.59482407124329, lng: -4.906642250600369},
                        {lat: 36.55286589139315, lng: -4.924314084196705},
                    ];
                }

                function getFuengirolaPaths() {
                    return [
                        {lat: 36.53049487298302, lng: -4.624297385257656},
                        {lat: 36.53806167954059, lng: -4.620598201892362},
                        {lat: 36.53864155460381, lng: -4.619132096557986},
                        {lat: 36.539193101995, lng: -4.618722507490563},
                        {lat: 36.54277985530565, lng: -4.616784961282139},
                        {lat: 36.54414921663242, lng: -4.61721090078164},
                        {lat: 36.54550291094519, lng: -4.616864711320372},
                        {lat: 36.54608785084312, lng: -4.615709930375415},
                        {lat: 36.55400296709011, lng: -4.61102508647347},
                        {lat: 36.56069240923238, lng: -4.606109468846926},
                        {lat: 36.5634907939579, lng: -4.602510473248195},
                        {lat: 36.56378934560087, lng: -4.599461625558622},
                        {lat: 36.57074484072947, lng: -4.590291111104257},
                        {lat: 36.57790399938892, lng: -4.598357483584571},
                        {lat: 36.58791242153895, lng: -4.598363558343836},
                        {lat: 36.59126614399961, lng: -4.605878401163669},
                        {lat: 36.55973103354018, lng: -4.629658507512788},
                        {lat: 36.54639782784548, lng: -4.634245433598667},
                        {lat: 36.53978622538808, lng: -4.631871796289432},
                        {lat: 36.53126526543499, lng: -4.63739053792751},
                        {lat: 36.52025676542266, lng: -4.62895849555383},
                        {lat: 36.52538710420569, lng: -4.626424013767018},
                        {lat: 36.53049487298302, lng: -4.624297385257656},
                    ];
                }

                function getBenalmadenaPaths() {
                    return [
                        {lat: 36.57788191421637, lng: -4.56394475228617},
                        {lat: 36.57992793917263, lng: -4.552721698440924},
                        {lat: 36.57946223994141, lng: -4.544107918143504},
                        {lat: 36.57941942592199, lng: -4.53960176078621},
                        {lat: 36.5808837351582, lng: -4.534975117914791},
                        {lat: 36.58556007612278, lng: -4.53224814985292},
                        {lat: 36.58909039379353, lng: -4.527972787998725},
                        {lat: 36.59137786318011, lng: -4.524115379149416},
                        {lat: 36.59157370870265, lng: -4.521372801187113},
                        {lat: 36.59372504565121, lng: -4.520349731688614},
                        {lat: 36.59508804044685, lng: -4.516592691877945},
                        {lat: 36.59241887716611, lng: -4.5126355348742},
                        {lat: 36.59684166954148, lng: -4.508279423004032},
                        {lat: 36.60070694525564, lng: -4.508940552672382},
                        {lat: 36.60215791293552, lng: -4.511947028819211},
                        {lat: 36.60866330890315, lng: -4.538139644459199},
                        {lat: 36.62240806852754, lng: -4.552745890858163},
                        {lat: 36.62193330544925, lng: -4.564507372681232},
                        {lat: 36.61312285144263, lng: -4.601060599461086},
                        {lat: 36.5985636992387, lng: -4.59463702171175},
                        {lat: 36.58790854525897, lng: -4.59836030732694},
                        {lat: 36.57790302775728, lng: -4.598347710470103},
                        {lat: 36.57074279072393, lng: -4.590286251099678},
                        {lat: 36.57297772758165, lng: -4.586473338913303},
                        {lat: 36.5741781197493, lng: -4.583107307603591},
                        {lat: 36.57388086999556, lng: -4.581317082722763},
                        {lat: 36.57468567129252, lng: -4.577977401129621},
                        {lat: 36.57574388417603, lng: -4.576344722039766},
                        {lat: 36.57760691472193, lng: -4.568594430235424},
                        {lat: 36.57672618583634, lng: -4.56777757872727},
                        {lat: 36.57788191421637, lng: -4.56394475228617},
                    ];
                }

                function getTorremolinosPaths() {
                    return [
                        {lat: 36.62193587868573, lng: -4.564470207706615},
                        {lat: 36.62241020398014, lng: -4.55274900991005},
                        {lat: 36.60867018197115, lng: -4.538143791372113},
                        {lat: 36.6050526340056, lng: -4.523601525685947},
                        {lat: 36.60215728312171, lng: -4.511944782185102},
                        {lat: 36.60070230650305, lng: -4.508938168642099},
                        {lat: 36.64477489619492, lng: -4.476654301790974},
                        {lat: 36.65425120384774, lng: -4.498330919283227},
                        {lat: 36.63830344258002, lng: -4.51281016271215},
                        {lat: 36.64111450531823, lng: -4.515330336534689},
                        {lat: 36.63655041128278, lng: -4.53303081955243},
                        {lat: 36.64124260669396, lng: -4.539784148174583},
                        {lat: 36.62193587868573, lng: -4.564470207706615},
                    ];
                }
            });
        })(jQuery);

    </script>
			<div id="section-sitemap">
				<div class="content-width">
					<div id="ch-homepage-map" style="width: 100%; height: 800px;">
					</div>
				</div>
			</div>



			<?php echo $args['after_widget'];

			return ob_get_clean();
		endif; // End !empty($template_vars['regions'])
		return ''; // Return empty string if no regions to display
	}


}
