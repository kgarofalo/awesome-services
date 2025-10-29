<?php
function render_kml_generator_page() {
    ?>
    <div class="wrap">
        <h1>KML Map Generator</h1>
        <?php
        if (isset($_GET['status']) && $_GET['status'] === 'created' && isset($_GET['config_id'])) {
            $config_id = $_GET['config_id'];
            if (get_option($config_id)) {
               $download_url = add_query_arg('download_custom_kml', $config_id, home_url('/'));
                $shortcode = '[filtered_locations_list config_id="' . $config_id . '"]';
                echo '<div id="message" class="updated notice is-dismissible"><p><strong>Your custom map configuration has been saved.</strong></p><p><strong>Download Link:</strong><br><a href="'.esc_url($download_url).'">'.esc_url($download_url).'</a></p><p><strong>Shortcode:</strong><br><input type="text" value="'.esc_attr($shortcode).'" readonly style="width:100%;max-width:500px;padding:5px;"></p></div>';
            }
        }
        if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
            echo '<div id="message" class="updated notice is-dismissible"><p>Map configuration deleted successfully.</p></div>';
        }
        if (isset($_GET['error']) && $_GET['error'] === 'no_filters') {
            echo '<div id="message" class="error notice is-dismissible"><p>Error: You must select at least one filter to generate a map.</p></div>';
        }
        ?>
        <h2>Saved Maps</h2>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th style="width: 30%;">Map Name</th>
                    <th style="width: 40%;">Shortcode</th>
                    <th style="width: 30%;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $saved_maps = get_option('dibraco_saved_kml_maps', []);
                if (!empty($saved_maps)) {
                    foreach ($saved_maps as $config_id) {
                        if (!get_option($config_id)) continue; 
                        $map_name = get_kml_config_map_name($config_id);
                        $shortcode = '[filtered_locations_list config_id="' . $config_id . '"]';
                        $download_url = add_query_arg('download_custom_kml', $config_id, home_url('/'));
                        $delete_nonce = wp_create_nonce('delete_kml_config_' . $config_id);
                        $delete_url = admin_url('admin.php?action=delete_kml_config&config_id=' . $config_id . '&_wpnonce=' . $delete_nonce);
                        ?>
                        <tr>
                            <td><?= $map_name; ?></td>
                            <td><input type="text" value="<?= $shortcode; ?>" readonly style="width: 100%;"></td>
                            <td>
                                <a href="<?php echo $download_url; ?>" class="button">Download KML</a>
                                <a href="<?php echo $delete_url; ?>" class="button" onclick="return confirm('Are you sure you want to delete this map configuration?');" style="color: #a00; border-color: #a00;">Delete</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="3">No saved maps found.</td></tr>';
                }
                ?>
            </tbody>
        </table>

        <h2 style="margin-top: 40px;">Create New Map</h2>
        <?php
        $enabled = get_option('enabled_connector_contexts');
        $location_post_type = $enabled['locations']['post_type'];
        $location_taxonomy_slug = $enabled['locations']['taxonomy'];
        $all_taxonomies = get_object_taxonomies($location_post_type, 'objects');
        ?>
        <form method="POST" action="<?= admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="generate_filtered_kml">
            <?php wp_nonce_field('generate_filtered_kml_action', 'kml_filter_nonce'); ?>
            <p>Select filters below to generate a new KML map and shortcode.</p>
            <?php
            foreach ($all_taxonomies as $taxonomy) {
                $terms = get_terms(['taxonomy' => $taxonomy->name, 'hide_empty' => true]);
                    ?>
                    <fieldset style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; max-width: 500px;">
                        <legend style="font-weight: bold; padding: 0 5px;"><?= $taxonomy->label; ?></legend>
                        <?php foreach ($terms as $term) { ?>
                            <label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="filters[<?= $taxonomy->name; ?>][]" value="<?= $term->term_id; ?>"> <?= $term->name; ?></label>
                        <?php } ?>
                    </fieldset>
                    <?php
                    }
            ?>
            <?php submit_button('Generate New Map & Shortcode'); ?>
        </form>
    </div>
    <?php
}
function get_kml_config_map_name($config_id) {
    $company_info = get_option('company_info');
    $default_name = ($company_info['name'] ?? 'Company') . ' Map';
    $filters = get_option($config_id);
    $all_selected_term_ids = [];
    foreach($filters as $term_ids) {
        $all_selected_term_ids = array_merge($all_selected_term_ids, $term_ids);
    }

    if (!empty($all_selected_term_ids) && count($all_selected_term_ids) <= 3) {
        $term_names = [];
        foreach ($all_selected_term_ids as $term_id) {
            $term = get_term($term_id);
            if ($term && !is_wp_error($term)) {
                $term_names[] = $term->name;
            }
        }
        if (!empty($term_names)) {
            return implode(' & ', $term_names) . ' Locations';
        }
    }
    return $default_name;
}
function da_generate_opening_hours_html($hours) {
    if ($hours['open_247'] === "1") {
        return '<div style="font-size:14px;"><b>Hours:</b> Open 24/7</div>';
    }
    $grouped_hours = da_get_grouped_opening_hours($hours);
    $html = '<div style="font-size:14px;"><b>Hours:</b>';
    foreach ($grouped_hours as $group) {
        $day_count = count($group['days']);
        $label = ($day_count > 1) ? "{$group['days'][0]} - " . end($group['days']) : $group['days'][0];
        if ($group['range'] === 'Closed') {
            $html .= "<br>{$label}: {$group['range']}";
        } else {
            $html .= "<br>{$label}:<br>{$group['range']}";
        }
    }
    $html .= '</div>';
    return $html;
}
 function get_kml_image_url($url ='') {
    if ($url ==='') {return;}
    if (is_numeric($url)) {
        $url = wp_get_attachment_url($url);
    }
        return $url;
    }
function my_osm_kml_map_shortcode($atts) {
if ( isset( $atts['max-width'] ) ) {
        $atts['max_width'] = $atts['max-width'];
}
$atts = shortcode_atts( array(
        'width'     => '100%',
        'height'    => '400px',
        'max_width' => '600px',
), $atts, 'my_osm_kml_map' );
$map_width = $atts['width'];
$map_height = $atts['height'];
$map_max_width = $atts['max_width'];
    $kml_source_data = '';
    $is_kml_url = false;   
$enabled = get_option('enabled_connector_contexts');
$company_info = get_option('company_info');
    $company_marker = get_kml_image_url($company_info['map_pin']);
   $show_address_on_org = get_option('company_info')['show_address_on_org'];
   if(($show_address_on_org) ==='0'){
    $default_term = $company_info['default_term'];
    }
    $initial_center_lat = null;
    $initial_center_lon = null;
    $initial_map_zoom = 13; 
    $locations_areas_status = get_option('locations_areas_status', 'none');
    $current_post_id = get_the_ID();   
    $location_term_id = da_get_location_term_or_default($current_post_id);
    $marker_icon_url = get_kml_image_url(get_term_meta($location_term_id. 'map_pin', true));
    if ($marker_icon_url ==='') {
           $marker_icon_url= $company_marker_icon;
    }

    if($location_term_id && ($location_term_id === $default_term)){
        $initial_center_lat = get_term_meta($location_term_id, 'latitude', true);
        $initial_center_lon = get_term_meta($location_term_id, 'longitude', true);
         $kml_source_data = get_kml_download_url(); 
         $initial_map_zoom = 15; 
        $is_kml_url = true; 
    }
    elseif ($location_term_id && ($location_term_id !== $default_term)) {
		$location_term_id = (int)$location_term_id;
        $kml_source_data = get_term_meta($location_term_id, 'kml_content', true); 
        $is_kml_url = false; 
      	$initial_center_lat = get_term_meta($location_term_id, 'latitude', true);
        $initial_center_lon = get_term_meta($location_term_id, 'longitude', true);
        $initial_map_zoom = 15; 
    } else {
        $kml_source_data = get_kml_download_url(); 
        $is_kml_url = true; 
        $initial_center_lat = $company_info['latitude']; 
        $initial_center_lon = $company_info['longitude'];
		switch ($locations_areas_status) {
            case 'none': 
                $initial_map_zoom = 15;
                break;
            case 'multi_locations': 
                $initial_map_zoom = 10;
                break;
            case 'multi_areas': 
                $initial_map_zoom = 10;
                break;
            case 'both': 
                $initial_map_zoom = 8;
                break;
        }
    }
    if (empty($kml_source_data)) {
		return '';
	}
	if((empty($initial_center_lat)) || (empty($initial_center_lon))){
	   
	}
	$map_id = 'osm_map_' . str_replace('.', '_', uniqid()); 
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
    wp_enqueue_script( 'leaflet-omnivore', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet-omnivore/0.3.4/leaflet-omnivore.min.js', ['leaflet-js'], '0.3.4', true );

    $map_vars = [
    'mapId' => $map_id,
    'kmlSource' => $kml_source_data,
    'isKmlUrl' => $is_kml_url,
    'initialLat' => floatval($initial_center_lat),
    'initialLon' => floatval($initial_center_lon),
    'initialZoom' => $initial_map_zoom,
    'customMarkerUrl' => $marker_icon_url
    ];
    wp_add_inline_script( 'leaflet-js', 'var ' . esc_js($map_id) . 'Vars = ' . json_encode($map_vars) . ';' );
    ob_start();
    ?>
<div id="<?php echo $map_id; ?>" style="width: <?php echo $map_width; ?>; height: <?php echo $map_height; ?>; max-width: <?php echo $map_max_width; ?>; border: 0; outline: none;"></div><script>
    document.addEventListener('DOMContentLoaded', function() {
    var mapVars = window.<?php echo esc_js($map_id); ?>Vars;
    var mapId = mapVars.mapId;
    var kmlSource = mapVars.kmlSource;
    var isKmlUrl = mapVars.isKmlUrl;
    var initialLat = mapVars.initialLat;
    var initialLon = mapVars.initialLon;
    var initialZoom = mapVars.initialZoom;
    var customMarkerUrl = mapVars.customMarkerUrl;
    var map = L.map(mapId).setView([initialLat, initialLon], initialZoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    var bindPopups = function(layer) {
        layer.eachLayer(function(subLayer) {
            if (subLayer.feature && subLayer.feature.properties && subLayer.feature.properties.description) {
                var popupOptions = {
                    minWidth: 360,
                    maxWidth: 400,
                    maxHeight: 300
                };
                subLayer.bindPopup(subLayer.feature.properties.description, popupOptions);
            }
        });
    };
    var processKmlLayer = function(kmlLayer) {
        kmlLayer.addTo(map);
        bindPopups(kmlLayer);
        var bounds = kmlLayer.getBounds();
        var isValid = bounds.isValid();
        if (isValid) {
            map.fitBounds(bounds, { padding: [50, 50] });
        }   
    };
    var customIcon;
    if (customMarkerUrl) {
        customIcon = L.icon({
            iconUrl: customMarkerUrl,
            iconRetinaUrl: customMarkerUrl, 
            iconSize: [32, 32], 
            iconAnchor: [16, 32], 
            popupAnchor: [0, -32] 
        });
    } else {
        customIcon = new L.Icon.Default(); 
    }

    var createCustomMarker = function(feature, latlng) {
        if (feature.geometry && feature.geometry.type === 'Point') {
            var marker = L.marker(latlng, { icon: customIcon });
            return marker;
        }
        return undefined; 
    };

    var omnivoreOptions = {
        pointToLayer: createCustomMarker
    };

    if (isKmlUrl) {
        var geoJsonLayer = omnivore.kml(kmlSource);
        geoJsonLayer.on('ready', function() {
            var kmlFeaturesLayer = L.geoJson(this.toGeoJSON(), omnivoreOptions);
            processKmlLayer(kmlFeaturesLayer);
        }).on('error', function(error) {
        });
    } else {
        try {
            var parsedLayer = omnivore.kml.parse(kmlSource, omnivoreOptions);
            processKmlLayer(parsedLayer);
        } catch (e) {
        }
    }
});
</script>

    <?php
    return ob_get_clean();
}
add_shortcode('my_osm_kml_map', 'my_osm_kml_map_shortcode');

function generate_custom_kml_file(array $term_ids, $map_name = 'Custom Map') {
    $status = get_option('locations_areas_status');
    $company_info = get_option('company_info');
    $company_name = $company_info['name'];
    $location_term_id = $term_ids[0];
    $location_term_name = get_term($location_term_id)->name;
    $company_marker_url = get_kml_image_url($company_info['map_pin']);
    if ($location_term_name === $map_name) {
        $marker_icon_url = get_kml_image_url(get_term_meta($location_term_id, 'map_pin', true));
        if (empty($marker_icon_url)) {
            $marker_icon_url = $company_marker_url;
        }
        $kml = get_kml_doc_header($status, $map_name, $location_term_id);
        $lat = get_term_meta($location_term_id, 'latitude', true);
        $lon = get_term_meta($location_term_id, 'longitude', true);
        $description = generate_kml_entity_description($location_term_id, 'location');
        $kml .= "<Placemark><name>{$company_name} - {$location_term_name}</name>";
        $kml .= "<description>{$description}</description>";
        if (!empty($marker_icon_url)) {
            $kml .= "<styleUrl>{$marker_icon_url}</styleUrl>";
        }
        $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
        $kml .= '</Document></kml>';
        return $kml;
    } else {
        $kml = get_kml_doc_header($status, $map_name, '');
    }

    foreach ($term_ids as $location_term_id) {
        $location_term_name = get_term($location_term_id)->name;
        $lat = get_term_meta($location_term_id, 'latitude', true);
        $lon = get_term_meta($location_term_id, 'longitude', true);
        if (!$lat || !$lon) continue;
        $marker_icon_url = get_kml_image_url(get_term_meta($location_term_id, 'map_pin', true));
        if (empty($marker_icon_url)) {
            $marker_icon_url = $company_marker_url;
        }
        if ($status === 'both') {
            $description = generate_kml_entity_description($location_term_id, 'location');
            $kml .= "<Folder><name>{$location_term_name}</name>";
            $kml .= "<Placemark><name>{$company_name} - {$location_term_name}</name>";
            $kml .= "<description>{$description}</description>";
            if (!empty($marker_icon_url)) {
                $kml .= "<styleUrl>{$marker_icon_url}</styleUrl>";
            }
            $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
            $associated_area_ids = get_term_meta($location_term_id, 'associated_act_terms', true);
            if (!empty($associated_area_ids) && is_array($associated_area_ids)) {
                $coordinate_pairs = [];
                foreach ($associated_area_ids as $area_id) {
                    $area_lat = get_term_meta($area_id, 'latitude', true);
                    $area_lon = get_term_meta($area_id, 'longitude', true);
                    if ($area_lat && $area_lon) $coordinate_pairs[] = [$area_lon, $area_lat];
                }
                if (!empty($coordinate_pairs)) {
                    $polygon_coords = create_geoshape_polygon_string_from_service_areas($coordinate_pairs);
                    $coords = explode(' ', $polygon_coords);
                    $formatted_coords = [];
                    for ($i = 0; $i < count($coords); $i += 2) {
                        $formatted_coords[] = "{$coords[$i]},{$coords[$i + 1]},0";
                    }
                    $kml .= "<Placemark><name>{$company_name} - {$location_term_name} Service Area</name>";
                    $kml .= "<styleUrl>#service_area_polygon_style</styleUrl>";
                    $kml .= '<Polygon><outerBoundaryIs><LinearRing><coordinates>' . implode(' ', $formatted_coords) . '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
                }
            }
            $kml .= "</Folder>";
        } else {
            $description = generate_kml_entity_description($location_term_id, 'location');
            $kml .= "<Placemark><name>{$company_name} - {$location_term_name}</name>";
            $kml .= "<description>{$description}</description>";
            if (!empty($marker_icon_url)) {
                $kml .= "<styleUrl>{$marker_icon_url}</styleUrl>";
            }
            $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
        }
    }
    $kml .= '</Document></kml>';
    return $kml;
}

function get_kml_download_url() {
    return add_query_arg(['download_kml' => 'true'], home_url('/'));
}

function dibraco_serve_kml_file() {
    header('Content-Type: application/vnd.google-earth.kml+xml');
    header('Content-Disposition: attachment; filename="locations.kml"');
    echo generate_master_kml_file();
    exit();
}


function dibraco_serve_geo_sitemap() {
    header('Content-Type: application/xml; charset=utf-8');
    $kml_url = get_kml_download_url();
    $lastmod_date = date('c');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:geo="http://www.google.com/geo/sitemap/1.0">';
    $xml .=   '<url>';
    $xml .=     '<loc>' . $kml_url . '</loc>';
    $xml .=     '<lastmod>' . $lastmod_date . '</lastmod>';
    $xml .=   '</url>';
    $xml .= '</urlset>';

    echo $xml;
    exit();
}
function handle_kml_generation_request() {
    if (!isset($_POST['kml_filter_nonce']) || !wp_verify_nonce($_POST['kml_filter_nonce'], 'generate_filtered_kml_action')) {
        return;    }
    $filters = $_POST['filters'] ?? [];
    if (empty(array_filter($filters))) {
        wp_redirect(admin_url('admin.php?page=dibraco-relationships-kml-generator&error=no_filters'));
        exit();
    }
    $sanitized_filters = [];
    foreach ($filters as $taxonomy => $term_ids) {
        if (!empty($term_ids)) {
            $sanitized_filters[$taxonomy] = array_map('intval', $term_ids);
        }
    }
    $config_id = 'kml_config_' . wp_generate_password(12, false);
    update_option($config_id, $sanitized_filters, 'no');
    $saved_maps = get_option('dibraco_saved_kml_maps', []);
    $saved_maps[] = $config_id;
    update_option('dibraco_saved_kml_maps', array_unique($saved_maps), 'no');
    $redirect_url = admin_url('admin.php?page=dibraco-relationships-kml-generator&config_id=' . $config_id . '&status=created');
    wp_redirect($redirect_url);
    exit();
}
add_action('admin_post_generate_filtered_kml', 'handle_kml_generation_request');
function handle_kml_config_delete() {
    if (!isset($_GET['config_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_kml_config_' . $_GET['config_id'])) {
      return;
    }

    $config_id = $_GET['config_id'];
    delete_option($config_id);
    $saved_maps = get_option('dibraco_saved_kml_maps', []);
    $saved_maps = array_diff($saved_maps, [$config_id]);
    update_option('dibraco_saved_kml_maps', $saved_maps, 'no');
    
    $redirect_url = admin_url('admin.php?page=dibraco-relationships-kml-generator&status=deleted');
    wp_redirect($redirect_url);
    exit();
}
add_action('admin_action_delete_kml_config', 'handle_kml_config_delete');


function get_filtered_location_ids_from_config($config_id) {
    $filters = get_option($config_id);
    if (empty($filters) || !is_array($filters)) {
        return [];
    }

    $enabled = get_option('enabled_connector_contexts');
    $location_post_type = $enabled['locations']['post_type'];
    $location_taxonomy_slug = $enabled['locations']['taxonomy'];

    $tax_query = ['relation' => 'AND'];
    foreach ($filters as $taxonomy => $term_ids) {
        if (!empty($term_ids)) {
            $tax_query[] = [
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => array_map('intval', $term_ids),
            ];
        }
    }
    if (count($tax_query) > 1) {
        $args = ['post_type' => $location_post_type, 'posts_per_page' => -1, 'tax_query' => $tax_query, 'fields' => 'ids'];
        $matching_post_ids = get_posts($args);
        if (!empty($matching_post_ids)) {
            $term_ids = wp_get_object_terms($matching_post_ids, $location_taxonomy_slug, ['fields' => 'ids']);
        }
    }
    return [];
}




function get_kml_doc_header($status, $map_name, $location_term_id = '') {
    $company_info = get_option('company_info');
	$kml = '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="https://www.opengis.net/kml/2.2"><Document>';
    $kml .= '<name>' . $map_name . '</name>';
    $company_name = $company_info['name'];
    $company_style_id = 'company_marker_style';
    $marker_icon_url = get_kml_image_url($company_info['map_pin']);
    $show_address_on_org = get_option('company_info')['show_address_on_org'] ??"1";
    if ($show_address_on_org !=='1'){
     $default_term = $company_info['default_term'];
     if (!empty($default_term)){
       $default_term_pin = get_kml_image_url(get_term_meta($default_term, 'map-pin', true));
         if (!empty($default_term_pin)) {
              $marker_icon_url = $default_term_pin;
             }
         }
    }
    if (!empty($marker_icon_url)) {
        $kml .= "<Style id=\"{$company_style_id}\"><IconStyle><Icon><href>" . $marker_icon_url . "</href></Icon></IconStyle></Style>";
    }
    if ($status ==='both' || $status ==='multi_areas'){
    $kml .= "<Style id=\"service_area_polygon_style\"><LineStyle><color>ff0000ff</color><width>3</width></LineStyle><PolyStyle><color>330000ff</color></PolyStyle></Style>";
    }
 if (!$location_term_id){
        $lat = $company_info['latitude'] ?? null;
        $lon = $company_info['longitude'] ?? null;
    if (!empty($lat) && !empty($lon)) {
        $description = generate_kml_entity_description($company_info, 'company');
        $kml .= "<Placemark><name>{$company_name}</name>";
        $kml .= "<description>{$description}</description>";
        if (!empty($marker_icon_url)) {
            $kml .= "<styleUrl>#{$company_style_id}</styleUrl>";
        }
        $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";    }
	}
    return $kml;
}


function generate_master_kml_file() {
    $status = get_option('locations_areas_status');
    $enabled = get_option('enabled_connector_contexts');
    $company_info = get_option('company_info');
    $company_name = $company_info['name'];
    $show_address_on_org = get_option('company_info')['show_address_on_org'];
    $default_term = '';
    if ($show_address_on_org ==='1'){
        $default_term = $company_info['default_term'];
    }
    $main_term_id = $enabled['locations']['main_term'] ?? null;
    $ignore_main_term = !empty($enabled['locations']['ignore_main_term']) && $enabled['locations']['ignore_main_term'] === '1';
    $kml = get_kml_doc_header($status, $company_info['name'] ?? 'Company Map');
    $company_marker_url = get_kml_image_url($company_info['map_pin']);        switch ($status) {
        case 'multi_locations':
            $location_tax = $enabled['locations']['taxonomy'];
            $location_terms = get_terms(['taxonomy' => $location_tax, 'hide_empty' => false, 'fields' => 'id=>name']);
            foreach ($location_terms as $location_term_id => $location_term_name) {
               if ($ignore_main_term && $main_term_id && $location_term_id == $main_term_id) continue;
                $lat = get_term_meta($location_term_id, 'latitude', true);
                $lon = get_term_meta($location_term_id, 'longitude', true);
                if (!$lat || !$lon) continue;
                $placemark_marker_url = $company_marker_url;
                $location_marker = get_kml_image_url(get_term_meta($location_term_id, 'map_pin', true));
                if(!empty($location_marker)){
                  $marker_icon_url = $location_marker;
                }
                $description = generate_kml_entity_description($location_term_id, 'location');
                $kml .= "<Placemark><name>{$company_name} - {$location_term_name}</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($placemark_marker_url)) {
                  $kml .= "<styleUrl>{$placemark_marker_url}</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
            }
            break;
        case 'multi_areas':
            $service_tax = $enabled['service_areas']['taxonomy'];
            $service_area_terma = get_terms(['taxonomy' => $service_tax, 'hide_empty' => false, 'fields' => 'id=>name']);
            $coordinate_pairs = [];
            foreach ($service_area_terma as $area_term_id => $area_term_name) {
                $area_lat = get_term_meta($area_term_id, 'latitude', true);
                $area_lon = get_term_meta($area_term_id, 'longitude', true);
                if ($area_lat && $area_lon) $coordinate_pairs[] = [$area_lon, $area_lat];
            }
            if (!empty($coordinate_pairs)) {
                $polygon_coords = create_geoshape_polygon_string_from_service_areas($coordinate_pairs);
                    $coords = explode(' ', $polygon_coords);
                    $formatted_coords = [];
                    for ($i = 0; $i < count($coords); $i += 2) {
                        $formatted_coords[] = "{$coords[$i]},{$coords[$i + 1]},0";
                    }
                    $kml .= "<Placemark><name>{$company_name} - Total Service Area</name>";
                    $kml .= "<styleUrl>#service_area_polygon_style</styleUrl>";
                    $kml .= '<Polygon><outerBoundaryIs><LinearRing><coordinates>';
                    $kml .= implode(' ', $formatted_coords);
                    $kml .= '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
                }
            break;

        case 'both':
            $location_tax = $enabled['locations']['taxonomy'];
           $location_terms = get_terms(['taxonomy' => $location_tax, 'hide_empty' => false, 'fields' => 'id=>name']);
            foreach ($location_terms as $location_term_id => $location_term_name) {
                if ($ignore_main_term && $main_term_id && $location_term_id == $main_term_id) continue;
                $lat = get_term_meta($location_term_id, 'latitude', true);
                $lon = get_term_meta($location_term_id, 'longitude', true);
                if (!$lat || !$lon) continue;
                $description = generate_kml_entity_description($location_term_id, 'location');
                $kml .= "<Folder><name>{$location_term_name}</name>";
                 $kml .= "<Placemark><name>{$company_name} - {$location_term_name}</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>{$marker_icon_url}</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
                
                $associated_area_ids = get_term_meta($location_term_id, 'associated_act_terms', true);
                if (!empty($associated_area_ids) && is_array($associated_area_ids)) {
                    $coordinate_pairs = [];
                    foreach ($associated_area_ids as $area_id) {
                        $area_lat = get_term_meta($area_id, 'latitude', true);
                        $area_lon = get_term_meta($area_id, 'longitude', true);
                        if ($area_lat && $area_lon) $coordinate_pairs[] = [$area_lon, $area_lat];
                    }
                    if (!empty($coordinate_pairs)) {
                        $polygon_coords = create_geoshape_polygon_string_from_service_areas($coordinate_pairs);
                        if (!empty($polygon_coords)) {
                            $coords = explode(' ', $polygon_coords);
                            $formatted_coords = [];
                            for ($i = 0; $i < count($coords); $i += 2) {
                                $formatted_coords[] = "{$coords[$i]},{$coords[$i + 1]},0";
                            }
                            $kml .= "<Placemark><name>{$company_name} - $location_term_name -  Service Area</name>";
                            $kml .= "<styleUrl>#service_area_polygon_style</styleUrl>";
                            $kml .= '<Polygon><outerBoundaryIs><LinearRing><coordinates>' . implode(' ', $formatted_coords) . '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
                        }
                    }
                }
                $kml .= '</Folder>';
            }
            break;
    }

    $kml .= '</Document></kml>';
    return $kml;
}
function filtered_locations_list_shortcode($atts) {
    $enabled_context = get_option('enabled_connector_contexts')['locations'];
    $location_taxonomy_slug =$enabled_context['taxonomy'];
    $location_post_type = $enabled_context['post_type'];
    $location_term_ids = [];
    $atts = $atts ?? [];
    if (!empty($atts['config_id'])) {
        $location_term_ids = get_filtered_location_ids_from_config($atts['config_id']);
    } 
    elseif (!empty($atts) && is_array($atts)) {
        $filters = [];
        $valid_taxonomies = get_object_taxonomies($location_post_type, 'objects');
        foreach ($atts as $taxonomy => $terms) {
            if (isset($valid_taxonomies[$taxonomy])) {
                $term_slugs = array_map('trim', explode(',', $terms));
                $term_ids = [];
                foreach ($term_slugs as $slug) {
                    $term = get_term_by('slug', $slug, $taxonomy);
                    if ($term) $term_ids[] = $term->term_id;
                }
                if (!empty($term_ids)) $filters[sanitize_key($taxonomy)] = $term_ids;
            }
        }
        if(!empty($filters)) {
            $config_id = 'kml_config_' . md5(json_encode($filters));
            if (false === get_option($config_id)) {
                update_option($config_id, $filters, 'no');
                $saved_maps = get_option('dibraco_saved_kml_maps', []);
                if (!in_array($config_id, $saved_maps)) {
                    $saved_maps[] = $config_id;
                    update_option('dibraco_saved_kml_maps', array_unique($saved_maps), 'no');
                }
            }
            $location_term_ids = get_filtered_location_ids_from_config($config_id);
        }
    }
    if (empty($location_term_ids)) {
        return '';
    }
    $location_terms = get_terms([
        'taxonomy'   => $location_taxonomy_slug,
        'include'    => $location_term_ids,
        'orderby'    => 'name',
        'order'      => 'ASC',
        'hide_empty' => false,
    ]);

        $cards_html = '';
    foreach ($location_terms as $location_term) {
        $cards_html .= render_location_term_card($location_term);
    }

    if(empty($cards_html)) {
    return;
    }
    $output = '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 0; list-style: none;">';
    $output .= $cards_html;
    $output .= '</div>';

    return $output;
}
add_shortcode('filtered_locations_list', 'filtered_locations_list_shortcode');

function da_get_display_data($data, $source_type) {
    if ($source_type === 'location') {
        $entity_id = $data;
        $term_meta_data = get_term_meta($entity_id, '', true);
        $term_meta_data = array_map('maybe_unserialize', array_map('current', $term_meta_data));
        $term_meta_data['name'] = get_term($entity_id)->name;
        $term_meta_data['phone_link'] ='';
        $term_meta_data['phone_display'] ='';
        $term_meta_data['additional_phone_link'] ='';
        $term_meta_data['additional_phone_display'] =''; 
         if (!empty($term_meta_data['phone_number'])) {
            $term_meta_data['phone_link'] = format_telephone_for_link($term_meta_data['phone_number']);
            $term_meta_data['phone_display'] = format_telephone_for_display($term_meta_data['phone_number']);
        }
        if ($term_meta_data['second_phone'] === '1' && !empty($term_meta_data['additional_phone'])) {
            $term_meta_data['additional_phone_link'] = format_telephone_for_link($term_meta_data['additional_phone']);
            $term_meta_data['additional_phone_display'] = format_telephone_for_display($term_meta_data['additional_phone']);
        }
        $term_meta_data['address'] = "{$term_meta_data['street_address']}";
        if (!empty($term_meta_data['street_address_2']) && strlen($term_meta_data['street_address_2']) < 10) {
            $term_meta_data['address'] = "{$term_meta_data['street_address']} {$term_meta_data['street_address_2']}";
        } elseif (!empty($term_meta_data['street_address_2']) && strlen($term_meta_data['street_address_2']) > 10) {
            $term_meta_data['address'] .= "<br>{$term_meta_data['street_address_2']}";
        }
        if($term_meta_data['addy_country'] ==='' || $term_meta_data['addy_country'] ==='US' || $term_meta_data['addy_country'] ==="USA"){
            $term_meta_data['address'] .= "<br>{$term_meta_data['city']}, {$term_meta_data['state']} {$term_meta_data['zipcode']}";
        }
        if(!empty($term_meta_data['addy_country']) && ($term_meta_data['addy_country'] !=='US' && $term_meta_data['addy_country'] !=="USA")){
            $term_meta_data['address'] .= "<br>{$term_meta_data['city']}, {$term_meta_data['state']} {$term_meta_data['zipcode']} {$term_meta_data['addy_country']}";
        }
        $term_meta_data['logo_url'] = get_kml_image_url($term_meta_data['location_logo']);
         $term_meta_data['image_url'] = get_kml_image_url($term_meta_data['exterior_image']);
   
        if (!empty($term_meta_data['location_post_id'])){
            $location_post_id = (int)$term_meta_data['location_post_id'];
            $term_meta_data['description'] = get_post_meta($location_post_id, 'da_about_blurb', true);
            if(empty($term_meta_data['description'])){
                $term_meta_data['description'] = get_post_meta($location_post_id, 'da_banner_description', true);
            }
        }
        $enabled_context_names = get_option('enabled_context_names');
        if (in_array('employee', $enabled_context_names)) {
            if (!empty($term_meta_data['location_manager'])){
                 $manager_post_id = (int)$term_meta_data['location_manager'];
                    $portrait_enabled = get_option('enabled_unique_contexts')['employee']['portrait_images'];
                  if ($portrait_enabled ==='1'){
                    $manager_portrait_url = get_kml_image_url(get_post_meta($manager_post_id, 'dibraco_portrait_1', true));
                    if (empty($manager_portrait_url)){
                         $manager_portrait_url = get_kml_image_url(get_post_meta($manager_post_id, 'dibraco_portrait_2', true));
                     }
                     $term_meta_data['manager_work_phone_display'] ='';
                     $term_meta_data['manager_work_phone_link'] ='';
                    $term_meta_data['manager_cell_display']='';
                    $term_meta_data['manager_cell_link']='';
                $manager_fields = get_post_meta($manager_post_id, 'employee-fields', true);
                  $term_meta_data['manager_name']  = "{$manager_fields['given_name']} {$manager_fields['family_name']}";
                  $term_meta_data['manager_work_email'] = $manager_fields['work_email'];
                 if (!empty($manager_fields['work_phone'])){
                     $term_meta_data['manager_work_phone_display'] = format_telephone_for_display($manager_fields['work_phone']);
                      $term_meta_data['manager_work_phone_link'] = format_telephone_for_link($manager_fields['work_phone']);
                  }
                if (!empty($manager_fields['cell_number'])){
                    $term_meta_data['manager_cell_display'] =  format_telephone_for_display($manager_fields['cell_number']);
                     $term_meta_data['manager_cell_link'] = format_telephone_for_link($manager_fields['cell_number']);
                    }
                  $term_meta_data['manager_job_title'] = $manager_fields['job_title'];
                   $term_meta_data['manager_image'] ='';
                  if ($manager_portrait_url){
                      $term_meta_data['manager_image'] = $manager_portrait_url;
                  }
            }
        }
        }
        return $term_meta_data;
    } else {
        $details = $data;
        $enabled_context_names = get_option('enabled_context_names');
        if (in_array('locations', $enabled_context_names)) {
             $enabled = get_option('enabled_connector_contexts');
        $show_address_on_org = get_option('company_info')['show_address_on_org'] ??"1";
        }
        if ($show_address_on_org ==="1"){
            $details['hours_of_operation'];
            $details['address'] = "{$details['street_address']}";
            if (!empty($details['street_address_2']) && strlen($details['street_address_2']) < 10) {
                $details['address'] = "{$details['street_address']} {$details['street_address_2']}";
            } elseif (!empty($details['street_address_2']) && strlen($details['street_address_2']) > 10) {
                $details['address'] .= "<br>{$details['street_address_2']}";
            }
            if($details['addy_country'] ==='' || $details['addy_country'] ==='US' || $details['addy_country'] ==="USA"){
                $details['address'] .= "<br>{$details['city']}, {$details['state']} {$details['zipcode']}";
            }
            if(!empty($details['addy_country']) && ($details['addy_country'] !=='US' && $details['addy_country'] !=="USA")){
                $details['address'] .= "<br>{$details['city']}, {$details['state']} {$details['zipcode']} {$details['addy_country']}";
            }
            $details['latitude'];
            $details['longitude'];
            $details['normal_map'];
            $details['exterior_image'];
            if (!empty($details['exterior_image'])){
                $image_id = (int)$details['exterior_image'];
                $details['image_url'] =  wp_get_attachment_url($image_id, 'medium');
            }
        }
        $details['hours_of_operation']='';
        $details['address']='';
        $details['image_url'] = get_kml_image_url($details['exterior_image']);
        $details['normal_map'] = '';
        $details['name'];
        $details['location_link_url'] = home_url('/');
        $details['company_description'];
        $details['logo_url'] = get_kml_image_url($details['company_logo']);
        $details['phone_link'] ='';
        $details['phone_display'] ='';
        $details['additional_phone_link'] ='';
        $details['additional_phone_display'] =''; 
         if (!empty($details['phone_number'])) {
            $details['phone_link'] = format_telephone_for_link($details['phone_number']);
            $details['phone_display'] = format_telephone_for_display($details['phone_number']);
        }
        if ($details['second_phone'] === '1' && !empty($details['additional_phone'])) {
            $details['additional_phone_link'] = format_telephone_for_link($details['additional_phone']);
            $details['additional_phone_display'] = format_telephone_for_display($details['additional_phone']);
        }
    }
    return $details;
}

function generate_kml_entity_description($data, $source_type = 'location') {
    $company_info = get_option('company_info');
    if ($source_type === 'location') {
        $details = da_get_display_data($data, $source_type);
        $title = $details['location_name'];
       
    } else {
        $details = da_get_display_data($data, $source_type);
        $title = $details['name'];
    }
  $full_html = "<div style='max-width: 360px;'>";
    $full_html .= "<table class='dibraco-kml-infowindow' border='0' cellpadding='5' cellspacing='0' style='width: 100%; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.3;'>";
    $full_html .= "<tr><td colspan='2' style='padding-top: 10px; padding-bottom:10px; border-bottom:1px solid #eee;'><table border='0' cellpadding='0' cellspacing='0'><tr>";
    if (!empty($details['logo_url'])) {
        $logo_url = $details['logo_url'];
        $full_html .= "<td valign='middle'><img src='{$logo_url}' alt='Logo' style='max-width:40px; height:auto; vertical-align:middle;'></td>";
    }
    $full_html .= "<td valign='middle' style='padding-left:10px;'><b style='font-size:14px;'>{$title}</b></td>";
    $full_html .= "</tr></table></td></tr>";
    $full_html .= "<tr><td width='35%' valign='top' style='padding-right: 5px; padding-top:5px;'>"; // Start Left TD
    if (!empty($details['address'])) {
        $address = $details['address'];
        $full_html .= "<div style='font-weight:bold; text-decoration:underline; margin-bottom:5px;'>Our Address</div>";
        $full_html .= $address;
        $full_html .= "<br><br>";
    }
    if (!empty($details['phone_link'])) {
        $phone_link = $details['phone_link'];
        $phone_display = $details['phone_display'];
        $full_html .= "<b>Phone:</b><br><a href='{$phone_link}'>{$phone_display}</a>";
    }
    if (!empty($details['additional_phone_link'])) {
        $additional_phone_link = $details['additional_phone_link'];
        $additional_phone_display = $details['additional_phone_display'];
        if (!empty($details['phone_link'])) {
            $full_html .= "<br>";
        }
        $full_html .= "<b>Alt. Phone:</b><br><a href='{$additional_phone_link}'>{$additional_phone_display}</a>";
    }
    $hours_html = da_generate_opening_hours_html($details['hours_of_operation']);
    $full_html .= $hours_html;

    $full_html .= "</td>"; 
    $full_html .= "<td width='65%' valign='top' style='padding-left: 5px; padding-top:15px;'>"; // Start Right TD
   if (!empty($details['image_url'])) {
        $image_url = $details['image_url'];
        $full_html .= "<img src='{$image_url}' alt='Exterior' style='max-width: 234px; width: 100%; height: auto; display: block; border: 1px solid #ccc; padding: 3px;'>";
    }
    $full_html .= "</td></tr>"; 
    $link_url = $details['location_link_url'];
    if (!empty($link_url)) {
        $full_html .= "<tr><td colspan='2' style='text-align: center; padding: 5px 0; border-top: 1px solid #eee; color:black; font-size:12px;'>";
        $full_html .= ($source_type === 'location') ? "<a href='{$link_url}'>Visit Location Page</a>" : "<a href='{$link_url}'>Visit Our Website</a>";
        $full_html .= "</td></tr>";
    }
    $normal_map = $details['normal_map'];
    if (!empty($normal_map)) {
        $full_html .= "<tr><td colspan='2' style='text-align: center; padding: 5px 0; border-top: 1px solid #eee; font-size:12px;'>";
        $full_html .= "<a href='{$normal_map}' target='_blank' rel='noopener noreferrer'>View on Google Maps & See Reviews</a>";     
        $full_html .= "</td></tr>";
      }
     if (!empty($details['description'])) {
        $description = $details['description'];
        $description_words = explode(' ', $description);
        if (count($description_words) > 40) {
            $description = implode(' ', array_slice($description_words, 0, 40)) . '...';
        }
        $read_more_link_url = $details['location_link_url'];
        $formatted_description = wpautop($description);
        $full_html .= "<tr><td colspan='2' style='border-top:1px solid #eee; padding-top:10px;'>";
        $full_html .= "<div style='margin-bottom:10px; font-size:12px;'>{$formatted_description} <a href='{$read_more_link_url}' style='font-size:12px; color:#0073e6; text-decoration:none;'>Read more</a></div>";
        $full_html .= "</td></tr>";
        }
       if ($source_type === 'location' && !empty($details['manager_name'])) {
        $full_html .= "<tr><td colspan='2' style='border-top:1px solid #eee; padding-top:5px;'>";
        $full_html .= "<div style='font-weight:bold; text-decoration:underline; margin-bottom:5px;'>Your Location Contact</div>";
        $full_html .= "<table border='0' cellpadding='0' cellspacing='0' style='width:100%;'><tr><td width='40%' valign='top' style='padding-right: 5px;'>";
        if (!empty($details['manager_image'])) {
            $manager_image = $details['manager_image'];
            $full_html .= "<img src='{$manager_image}' alt='Market Manager' style='max-width:130px; height:auto; display: block;'>"; // 130px max-width
        }
        $full_html .= "</td><td width='60%' valign='top' style='padding-left:5px;'>";
        $manager_name = $details['manager_name'];
        $manager_job_title = $details['manager_job_title'];
        $full_html .= "<b>{$manager_name}</b><br>";
        $full_html .= "<i>{$manager_job_title}</i><br><br>";
        if (!empty($details['manager_work_phone_display'])) {
            $manager_work_phone_display = $details['manager_work_phone_display'];
            $full_html .= "<b>Phone:</b> {$manager_work_phone_display}<br>";
        }
        if (!empty($details['manager_work_email'])) {
            $manager_work_email = $details['manager_work_email'];
            $full_html .= "<b>Email:</b> <a href='mailto:{$manager_work_email}'>{$manager_work_email}</a>";
        }
        $full_html .= "</td></tr></table>";
        if (!empty($details['manager_work_phone_link'])) {
            $manager_work_phone_link = $details['manager_work_phone_link'];
            $manager_given_name = $details['manager_given_name'] ?? explode(' ', $details['manager_name'])[0];
            $full_html .= "<div style='margin-top:10px;'><a href='{$manager_work_phone_link}' style='background-color:#606770; color:white; padding:5px; text-align:center; text-decoration:none;'>Call {$manager_given_name}</a></div>";
        }
        $full_html .= "</td></tr>";
     }
        $full_html .= "</table></div>";

    return "<![CDATA[{$full_html}]]>";
}
function render_location_term_card($location_term, $display_areas = 'no') {
    $location_term_id = $location_term->term_id;
    $term_meta_data = da_get_display_data($location_term_id, 'location'); 
    if (!$term_meta_data['latitude']) {
        return '';
    }
    $location_name = $term_meta_data['location_name'];
    $link_url = $term_meta_data['location_link_url'];
    $hours = $term_meta_data['hours_of_operation'];
    $service_areas_html = '';
    if ($display_areas === 'yes' && get_option('locations_areas_status') === 'both') {
        $area_term_ids = $term_meta_data['associated_act_terms'];
            $area_links = [];
            foreach ($area_term_ids as $area_id) {
                $area_term = get_term($area_id);
                $area_term_name = $area_term->name; 
                $area_link_url = get_term_meta($area_id, 'service_area_link_url', true);
                $area_links[] = "<a style='font-size: 13px' href='{$area_link_url}'>{$area_term_name}</a>";
                }
                $service_areas_html .= "<div style='border-top: 1px dashed #eee;'><span style='font-weight: bold; font-size: 14px;'>Service Areas: </span>";
                $service_areas_html .= implode(', ', $area_links);
                $service_areas_html .= "</div>";
        }
    $manager_html = '';
    $enabled_context_names = get_option('enabled_context_names');
    if (is_array($enabled_context_names) && in_array('employee', $enabled_context_names)) {
        if (!empty($term_meta_data['location_manager'])) {
            $manager_id = $term_meta_data['location_manager'];
            $employee = get_post_meta($manager_id, 'employee-fields', true);
                $given_name = $employee['given_name'];
                $family_name = $employee['family_name'];
                $full_name = trim("{$given_name} {$family_name}");
                $work_phone = $employee['work_phone'];
                if (!empty($full_name) || !empty($work_phone)) {
                    $manager_html .= "<div style='margin-top: 5px; padding-top: 5px; border-top: 1px dashed #eee;'>";
                    $manager_html .= "<span style='font-weight: bold; color: #333;'>{$full_name}</span><br>";
                    if ($work_phone) {
                        $phone_link = format_telephone_for_link($work_phone);
                        $phone_display = format_telephone_for_display($work_phone);
                        $manager_html .= "<a href='{$phone_link}'>{$phone_display}</a>";
                    }
                    $manager_html .= "</div>";
                }
        }
    }
    $output = "<div style='line-height: 1.4; font-family: Arial, sans-serif; font-size: 14px; padding: 5px; background-color: #f9f9f9;'>";
    $output .= "<h4 style='margin: 0;'>{$location_name}</h4>";
 
    if (!empty($term_meta_data['address'])) {
        $address = $term_meta_data['address'];
        $output .= "<p style='margin: 0;'>{$address}</p>";
    }
    if (!empty($term_meta_data['phone_link'])) {
        $phone_link = $term_meta_data['phone_link'];
        $phone_display = $term_meta_data['phone_display'];
        $output .= "<b>Phone:</b><br><a href='{$phone_link}'>{$phone_display}</a>";
    }
    if (!empty($term_meta_data['additional_phone_link'])) {
        $additional_phone_link = $term_meta_data['additional_phone_link'];
        $additional_phone_display = $term_meta_data['additional_phone_display'];
        if (!empty($term_meta_data['phone_link'])) {
             $output .= "<br>";
        }
        $output .= "<b>Alt. Phone:</b><br><a href='{$additional_phone_link}'>{$additional_phone_display}</a>";
    }
  
    if ($hours) {
        $hours_html = da_generate_opening_hours_html($hours);
        $output .= "<div style='font-size: 14px; color: #666; margin-top: 5px;'>{$hours_html}</div>";
    }
  
   if ($service_areas_html) {
        $output .= $service_areas_html;
    }

    if ($manager_html) {
        $output .= $manager_html;
    }

    if ($link_url) {
        $output .= "<p style='margin: 5px 0;'><a href='{$link_url}'>Visit Location Page</a></p>";
    }

    $output .= "</div>";
    return $output;
}
function dibraco_custom_endpoint_listener() {
    if (isset($_GET['download_custom_kml']) && !empty($_GET['download_custom_kml'])) {
        $config_id = 
        $_GET['download_custom_kml'];
        
        $final_location_term_ids = get_filtered_location_ids_from_config($config_id);
        $company_info = get_option('company_info');
        $map_name = ($company_info['name'] ?? 'Company') . ' Map';
        $filters = get_option($config_id);
        $all_selected_term_ids = [];
        if($filters) {
            foreach($filters as $term_ids) $all_selected_term_ids = array_merge($all_selected_term_ids, $term_ids);
        }
        if (!empty($all_selected_term_ids) && count($all_selected_term_ids) <= 3) {
            $term_names = [];
            foreach ($all_selected_term_ids as $term_id) {
                $term = get_term($term_id);
                if ($term && !is_wp_error($term)) $term_names[] = $term->name;
            }
            if (!empty($term_names)) $map_name = implode(' & ', $term_names) . ' Locations';
        }

        $kml_content = generate_custom_kml_file($final_location_term_ids, $map_name);
        $filename = sanitize_title($map_name) . '.kml';
        header('Content-Type: application/vnd.google-earth.kml+xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $kml_content;
        exit();
    }
    if (isset($_GET['download_kml']) && $_GET['download_kml'] === 'true') {
        dibraco_serve_kml_file();
    }
    if (isset($_GET['geo_sitemap']) && $_GET['geo_sitemap'] === 'true') {
        dibraco_serve_geo_sitemap();
    }
}
add_action('init', 'dibraco_custom_endpoint_listener');
function my_locations_list_shortcode($atts) {
    $atts = shortcode_atts(['areas' => 'no'], $atts, 'my_locations_list');
    $service_areas = $atts['areas']; 
    $company_info = get_option('company_info');
    $enabled = get_option('enabled_connector_contexts');
    $location_taxonomy_slug = $enabled['locations']['taxonomy'];
    $main_term_id = $enabled['locations']['main_term'];
    $show_address_on_org = get_option('company_info')['show_address_on_org'] ??"1";
    $ignore_main_term = $enabled['locations']['ignore_main_term'];
    $output = "<div style='display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 0; list-style: none;'>";

    if ($show_address_on_org === "1") {
        $company_name = $company_info['name'];
        $corp_phone = $company_info['phone_number'];
        $corp_address = '';
        if ($company_info['street_address']) { $corp_address .= $company_info['street_address']; }
        if ($company_info['street_address_2']) { if ($corp_address !== '') { $corp_address .= '<br>'; } $corp_address .= $company_info['street_address_2']; }
        $city_state_zip = trim($company_info['city'].' '.$company_info['state'].' '.$company_info['zipcode']);
        if ($city_state_zip !== '') { if ($corp_address !== '') { $corp_address .= '<br>'; } $corp_address .= $city_state_zip; }

        $output .= '<div style="grid-column: 1 / -1; margin-bottom: 10px; line-height: 1.4; font-family: Arial, sans-serif; font-size: 14px; color: #555; padding: 5px; background-color: #f9f9f9;">';
        $output .= '<h4 style="margin: 0; font-size: 16px; color: #222;">'.$company_name.'</h4>';
        if ($corp_address !== '') { $output .= '<p style="margin: 0;">'.$corp_address.'</p>'; }
        if (!empty($corp_phone)) { $output .= '<p style="margin: 0;"><a href="'.format_telephone_for_link($corp_phone).'" style="color: #0073aa; text-decoration: none;">'.format_telephone_for_display($corp_phone).'</a></p>'; }
        $output .= '</div>';
    }

    $location_terms = get_terms(['taxonomy' => $location_taxonomy_slug, 'hide_empty' => 'true']);
    $cards_html ='';
    foreach ($location_terms as $location_term) {
        if (($ignore_main_term === "1") && $main_term_id && $location_term->term_id == $main_term_id) continue;
        $cards_html .= render_location_term_card($location_term, $service_areas);
    }
    if ($cards_html === '') {
        return '';
    }
    $return = "{$output}{$cards_html}</div>";
    return $return;
}
add_shortcode('my_locations_list', 'my_locations_list_shortcode');