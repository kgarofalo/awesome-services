<?php

function my_custom_kml_menu_hook() {
 $status = get_option('locations_areas_status');
    if ($status === 'multi_locations' || $status === 'both') {
        add_submenu_page(
            'relationships',                  
            'KML Map Generator',              
            'KML Generator',                
            'manage_options',                 
            'kml-generator',                  
            'render_kml_generator_page'       
        );
    }
}
add_action('admin_menu', 'my_custom_kml_menu_hook', 15);

function render_kml_generator_page() {
    ?>
    <div class="wrap">
        <h1>KML Map Generator</h1>

        <?php
        if (isset($_GET['status']) && $_GET['status'] === 'created' && isset($_GET['config_id'])) {
            $config_id = sanitize_key($_GET['config_id']);
            if (get_option($config_id)) {
               $download_url = add_query_arg('download_custom_kml', $config_id, home_url('/'));
                $shortcode = '[filtered_locations_list config_id="' . esc_attr($config_id) . '"]';
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
                        $shortcode = '[filtered_locations_list config_id="' . esc_attr($config_id) . '"]';
                        $download_url = add_query_arg('download_custom_kml', $config_id, home_url('/'));
                        $delete_nonce = wp_create_nonce('delete_kml_config_' . $config_id);
                        $delete_url = admin_url('admin.php?action=delete_kml_config&config_id=' . $config_id . '&_wpnonce=' . $delete_nonce);
                        ?>
                        <tr>
                            <td><?php echo esc_html($map_name); ?></td>
                            <td><input type="text" value="<?php echo esc_attr($shortcode); ?>" readonly style="width: 100%;"></td>
                            <td>
                                <a href="<?php echo esc_url($download_url); ?>" class="button">Download KML</a>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button" onclick="return confirm('Are you sure you want to delete this map configuration?');" style="color: #a00; border-color: #a00;">Delete</a>
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
        <form method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="generate_filtered_kml">
            <?php wp_nonce_field('generate_filtered_kml_action', 'kml_filter_nonce'); ?>
            <p>Select filters below to generate a new KML map and shortcode.</p>
            <?php
            foreach ($all_taxonomies as $taxonomy) {
                $terms = get_terms(['taxonomy' => $taxonomy->name, 'hide_empty' => true]);
                if (!empty($terms)) {
                    ?>
                    <fieldset style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; max-width: 500px;">
                        <legend style="font-weight: bold; padding: 0 5px;"><?php echo esc_html($taxonomy->label); ?></legend>
                        <?php foreach ($terms as $term) { ?>
                            <label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="filters[<?php echo esc_attr($taxonomy->name); ?>][]" value="<?php echo esc_attr($term->term_id); ?>"> <?php echo esc_html($term->name); ?></label>
                        <?php } ?>
                    </fieldset>
                    <?php
                }
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
    if (empty($filters)) {
        return 'Invalid Map Configuration';
    }
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
    if (empty($hours) || !is_array($hours)) return '';
    if ($hours['open_247'] === "1") {
        return '<div style="font-size:14px;"><b>Hours:</b> Open 24/7</div>';
    }
    $grouped_hours = da_get_grouped_opening_hours($hours);
    $html = '<div style="font-size:14px;"><b>Hours:</b>';
    foreach ($grouped_hours as $group) {
        $day_count = count($group['days']);
        $label = ($day_count > 1) ? $group['days'][0] . ' - ' . end($group['days']) : $group['days'][0];
        if ($group['range'] === 'Closed') {
            $html .= '<br>' . esc_html("{$label}: {$group['range']}");
        } else {
            $html .= '<br>' . esc_html("{$label}:") . '<br>' . esc_html($group['range']);
        }
    }
    $html .= '</div>';
    return $html;
}

function da_get_display_data($data, $source_type) {
    $details = [];
    if ($source_type === 'location') {
        $entity_id = $data;
        $details['name'] = get_term_field('name', $entity_id);
        $details['link_url'] = get_term_meta($entity_id, 'location_link_url', true);
        $details['latitude'] = get_term_meta($entity_id, 'latitude', true);
        $details['longitude'] = get_term_meta($entity_id, 'longitude', true);
        $details['phone'] = get_term_meta($entity_id, 'phone_number', true);
        $details['hours'] = get_term_meta($entity_id, 'hours_of_operation', true);
        $image_id = get_term_meta($entity_id, 'exterior_image', true);
        $logo_id = get_term_meta($entity_id, 'location_logo', true);
        $details['place_id'] = get_term_meta($entity_id, 'place_id', true);
        $details['logo_url'] = '';
        $details['image_url'] = '';
        if (!empty($logo_id)){
           $logo_id = (int)$logo_id;
           $details['logo_url'] = wp_get_attachment_url($logo_id);
        }
        if (!empty($image_id)){
           $image_id = (int)$image_id;
            $details['image_url'] =  wp_get_attachment_url($image_id, 'medium');
        }
        $details['address_parts'] = da_get_address_data($entity_id);
        $details['additional_phone'] = '';
        if (get_term_meta($entity_id, 'second_phone', true) === '1') {
            $details['additional_phone'] = get_term_meta($entity_id, 'additional_phone', true);
        }
        $post_id = get_term_meta($entity_id, 'location_post_id', true);
        $details['description'] = '';  
        if (!empty($post_id)){
            $post_id = (int)$post_id;
            $details['description'] = get_post_meta((int)$post_id, 'da_banner_description', true)??'';
        }
        $enabled_context_names = get_option('enabled_context_names');
        $details['manager_id'] = '';
        if (in_array('employee', $enabled_context_names)) {
            $details['manager_id'] = get_term_meta($entity_id, 'location_manager', true);
        }
        return $details;
    } else {
        $info = $data;
        $details['name'] = $info['name'];
        $details['link_url'] = home_url('/');
        $details['phone'] = $info['phone_number'];
        $details['hours'] = $info['hours_of_operation'];
        $details['place_id'] = $info['place_id'] ?? '';
        $image_id = $info['exterior_image'];
        $logo_id = $info['company_logo'];
        $details['description'] = $info['company_description'];
        $details['address_parts'] = da_get_address_data(); 
        $details['logo_url'] = '';
        $details['image_url'] = '';
        if (!empty($logo_id)){
           $logo_id = (int)$logo_id;
           $details['logo_url'] = wp_get_attachment_url($logo_id);
        }
        if (!empty($image_id)){
            $image_id = (int)$image_id;
            $details['image_url'] =  wp_get_attachment_url($image_id, 'medium');
        }
        $details['additional_phone'] = '';
        if ($info['second_phone'] === "1") {
           $details['additional_phone'] = $info['additional_phone'];
        }
    }
    return $details;
}

function generate_kml_entity_description($data, $source_type = 'location') {
    $company_info = get_option('company_info');
    if ($source_type === 'location') {
        $details = da_get_display_data($data, $source_type);
        $title = $details['name'];
        $manager_id = $details['manager_id'];
        if (!empty($manager_id)) {
            $employee = get_post_meta($manager_id, 'employee_data', true);
            $manager_given_name   = $employee['given_name'];
            $manager_family_name  = $employee['family_name'];
            $manager_work_email   = $employee['work_email'];
            $manager_work_phone   = $employee['work_phone'];
            $manager_job_title    = $employee['job_title'];
            $manager_portrait_id  = get_post_meta($manager_id, 'dibraco_portrait_1', true);
            if (!empty($manager_portrait_id)){
                $manager_portrait_id = (int)$manager_portrait_id;
                $manager_portrait_url = wp_get_attachment_image_url((int)$manager_portrait_id, 'full');
            }
        }
    } else {
        $details = da_get_display_data($data, $source_type);
        $title = $data['name'];
    }
    $address = $details['address_parts'];
    $address_line_1 = trim(implode(' ', array_filter([$address['street_address'], $address['street_address_2']])));
    $address_line_2 = trim(implode(', ', array_filter([$address['city'], $address['state'], $address['zipcode'], $address['addy_country']])));
    $full_html = '<div style="max-width: 360px;">';
    $full_html .= '<table class="dibraco-kml-infowindow" border="0" cellpadding="5" cellspacing="0" style="width: 100%; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.3;">';

    $full_html .= '<tr>';
    $full_html .= '<td colspan="2" style="padding-top: 10px; padding-bottom:10px; border-bottom:1px solid #eee;">';
    $full_html .= '<table border="0" cellpadding="0" cellspacing="0"><tr>';
    if ($details['logo_url']) {
        $full_html .= '<td valign="middle"><img src="' . esc_url($details['logo_url']) . '" alt="Logo" style="max-width:40px; height:auto; vertical-align:middle;"></td>';
    }
    $full_html .= '<td valign="middle" style="padding-left:10px;"><b style="font-size:14px;">' . $title . '</b></td>';
    $full_html .= '</tr></table>';
    $full_html .= '</td>';
    $full_html .= '</tr>';

    $full_html .= '<tr>';
    $full_html .= '<td width="35%" valign="top" style="padding-right: 5px; padding-top:5px;">';
    $main_info_left = '';
    if ((!empty($address_line_1)) || (!empty($address_line_2))) {
        $main_info_left .= '<div style="font-weight:bold; text-decoration:underline; margin-bottom:5px;">Our Address</div>';
        if (!empty($address_line_1)) {
            $main_info_left .= esc_html($address_line_1) . '<br>';
        }
        if (!empty($address_line_2)) {
            $main_info_left .= esc_html($address_line_2);
        }
        $main_info_left .= '<br><br>';
    }
    if (!empty($details['phone'])) {
        $main_info_left .= '<b>Phone:</b><br><a href="' . esc_url(format_telephone_for_link($details['phone'])) . '">' . esc_html(format_telephone_for_display($details['phone'])) . '</a>';
    }
    if (!empty($details['additional_phone'])) {
        $main_info_left .= '<b>Alt. Phone:</b><br><a href="' . esc_url(format_telephone_for_link($details['additional_phone'])) . '">' . esc_html(format_telephone_for_display($details['additional_phone'])) . '</a>';
    }
    $main_info_left .= da_generate_opening_hours_html($details['hours']);
    $full_html .= $main_info_left;
    $full_html .= '</td>';
    $full_html .= '<td width="65%" valign="top" style="padding-left: 5px; padding-top:15px;">';
    $main_info_right = '';
    if (!empty($details['image_url'])) {
$main_info_right = '<img src="' . esc_url($details['image_url']) . '" alt="Exterior" style="max-width: 234px; width: 100%; height: auto; display: block; border: 1px solid #ccc; padding: 3px;">';
    }
    $full_html .= $main_info_right;
    $full_html .= '</td>';
    $full_html .= '</tr>';
    $link_url = $details['link_url'];
    if (!empty($link_url)) {
        $full_html .= '<tr>';
        $full_html .= '<td colspan="2" style="text-align: center; padding: 5px 0; border-top: 1px solid #eee; color:black; font-size:12px;">';
        $full_html .= ($source_type === 'location') ? '<a href="' . esc_url($link_url) . '">Visit Location Page</a>' : '<a href="' . esc_url($link_url) . '">Visit Our Website</a>';
        $full_html .= '</td>';
        $full_html .= '</tr>';
    }
     $place_id = $details['place_id'];
    if (!empty($place_id)) {
        $Maps_url = 'https://www.google.com/maps/place/?q=place_id:' . urlencode($place_id);
        $full_html .= '<tr>';
        $full_html .= '<td colspan="2" style="text-align: center; padding: 5px 0; border-top: 1px solid #eee; font-size:12px;">';
        $full_html .= '<a href="' . esc_url($Maps_url) . '" target="_blank" rel="noopener noreferrer">View on Google Maps & See Reviews</a>';
        $full_html .= '</td>';
        $full_html .= '</tr>';
    }
    if (!empty($details['description'])) {
        $description = esc_html($details['description']);
        $description_words = explode(' ', $description);
        if (count($description_words) > 40) {
            $description = implode(' ', array_slice($description_words, 0, 40)) . '...';
        }
        $full_html .= '<tr>';
        $full_html .= '<td colspan="2" style="border-top:1px solid #eee; padding-top:10px;">';
        $full_html .= '<div style="margin-bottom:10px; font-size:12px;">' . wpautop($description) . ' <a href="' . esc_url($details['link_url']) . '" style="font-size:12px; color:#0073e6; text-decoration:none;">Read more</a></div>';
        $full_html .= '</td></tr>';
    }
    if (!empty($manager_given_name) || !empty($manager_portrait_url)) {
        $full_html .= '<tr>';
        $full_html .= '<td colspan="2" style="border-top:1px solid #eee; padding-top:5px;">';
        $full_html .= '<div style="font-weight:bold; text-decoration:underline; margin-bottom:5px;">Your Location Contact</div>';
        $full_html .= '<table border="0" cellpadding="0" cellspacing="0"><tr>';
        $full_html .= '<td width="40%" valign="top" style="max-width:180px; padding-right: 5px;">';
        $portrait_cell = '';
        if (!empty($manager_portrait_url)) {
            $portrait_cell = '<img src="' . esc_url($manager_portrait_url) . '" alt="Market Manager" max-width:"234px" width="100%" style="height:auto; border:1px solid #ccc; padding:2px;">';
        }
        $full_html .= $portrait_cell;
        $full_html .= '</td>';
        $full_html .= '<td width="57%" valign="top" style="max-width: 180px; padding-right:5px; padding-left:5px;">';
        $details_cell = '';
        if (!empty($manager_given_name)) {
            $full_name = esc_html($manager_given_name);
            if (!empty($manager_family_name)) $full_name .= ' ' . esc_html($manager_family_name);
            $details_cell .= '<b>' . $full_name . '</b><br>';
        }
        if (!empty($manager_job_title)) $details_cell .= '<i>' . esc_html($manager_job_title) . '</i><br><br>';
        if (!empty($manager_work_phone)) $details_cell .= '<b>Phone:</b> ' . esc_html(format_telephone_for_display($manager_work_phone)) . '<br>';
        if (!empty($manager_work_email)) $details_cell .= '<b>Email:</b> <a href="mailto:' . esc_attr($manager_work_email) . '">' . esc_html($manager_work_email) . '</a>';
        $full_html .= $details_cell;
        $full_html .= '</td>';
        $full_html .= '</tr></table>';

        if (!empty($manager_work_phone) && !empty($manager_given_name)) {
            $call_link = format_telephone_for_link($manager_work_phone);
            $full_html .= '<a href="' . esc_url($call_link) . '" style="background-color:#606770; color:white; padding:5px 5px; text-align:center; text-decoration:none; display:inline-block; border-radius:4px;">Call ' . esc_html($manager_given_name) . '</a>';
        }
        $full_html .= '</td>';
        $full_html .= '</tr>';
    }

    $full_html .= '</table></div>';

    return "<![CDATA[{$full_html}]]>";
}


function get_kml_doc_header($status, $map_name, $location_term_id = '') {
    $company_info = get_option('company_info');
	$kml = '<?xml version="1.0" encoding="UTF-8"?><kml xmlns="https://www.opengis.net/kml/2.2"><Document>';
    $kml .= '<name>' . esc_html($map_name) . '</name>';
    $company_style_id = 'company_marker_style';
    $marker_icon_url = '';
    if (!empty($company_info['map_pin'])) {
        $company_map_pin_id = (int) $company_info['map_pin'];
        if ($company_map_pin_id > 0) {
            $marker_icon_url = wp_get_attachment_url($company_map_pin_id);
        }
    }
    if (!empty($marker_icon_url)) {
        $kml .= "<Style id=\"{$company_style_id}\"><IconStyle><Icon><href>" . esc_url($marker_icon_url) . "</href></Icon></IconStyle></Style>";
    }
    if ($status ==='both' || $status ==='multi_areas'){
    $kml .= "<Style id=\"service_area_polygon_style\"><LineStyle><color>ff0000ff</color><width>3</width></LineStyle><PolyStyle><color>330000ff</color></PolyStyle></Style>";
    }
 if (!$location_term_id){
    if (!empty($company_info['latitude']) && !empty($company_info['longitude'])) {
        $description = generate_kml_entity_description($company_info, 'company');
        $kml .= "<Placemark><name>" . esc_html($company_info['name']) . "</name>";
        $kml .= "<description>{$description}</description>";
        if (!empty($marker_icon_url)) {
            $kml .= "<styleUrl>#{$company_style_id}</styleUrl>";
        }
        $kml .= "<Point><coordinates>{$company_info['longitude']},{$company_info['latitude']},0</coordinates></Point></Placemark>";
    }
	}
    return $kml;
}


function generate_master_kml_file() {
    $status = get_option('locations_areas_status');
    $enabled = get_option('enabled_connector_contexts');
    $company_info = get_option('company_info');
    $main_term_id = $enabled['locations']['main_term'] ?? null;
    $ignore_main_term = !empty($enabled['locations']['ignore_main_term']) && $enabled['locations']['ignore_main_term'] === '1';
    $kml = get_kml_doc_header($status, $company_info['name'] ?? 'Company Map');
    
    $marker_icon_url = '';
    if (!empty($company_info['map_pin'])) {
        $company_map_pin_id = (int) $company_info['map_pin'];
        if ($company_map_pin_id > 0) {
            $marker_icon_url = wp_get_attachment_url($company_map_pin_id);
        }
    }
        switch ($status) {
        case 'multi_locations':
            $location_tax = $enabled['locations']['taxonomy'];
            $location_terms = get_terms(['taxonomy' => $location_tax, 'hide_empty' => false]);
            foreach ($location_terms as $location_term) {
                if ($ignore_main_term && $main_term_id && $location_term->term_id == $main_term_id) continue;
                $lat = get_term_meta($location_term->term_id, 'latitude', true);
                $lon = get_term_meta($location_term->term_id, 'longitude', true);
                if (!$lat || !$lon) continue;

                $description = generate_kml_entity_description($location_term->term_id, 'location');
                $kml .= "<Placemark><name>" . esc_html($company_info['name'] . ' - ' . $location_term->name) . "</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>#company_marker_style</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
            }
            break;

        case 'multi_areas':
            $service_tax = $enabled['service_areas']['taxonomy'];
            $service_area_terms = get_terms(['taxonomy' => $service_tax, 'hide_empty' => true]);
            $coordinate_pairs = [];
            foreach ($service_area_terms as $area) {
                $area_lat = get_term_meta($area->term_id, 'latitude', true);
                $area_lon = get_term_meta($area->term_id, 'longitude', true);
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
                    $kml .= '<Placemark><name>' . esc_html($company_info['name'] . ' - Total Service Area') . '</name>';
                    $kml .= "<styleUrl>#service_area_polygon_style</styleUrl>";
                    $kml .= '<Polygon><outerBoundaryIs><LinearRing><coordinates>';
                    $kml .= implode(' ', $formatted_coords);
                    $kml .= '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
                }
            }
            break;

        case 'both':
            $location_tax = $enabled['locations']['taxonomy'];
            $all_location_terms = get_terms(['taxonomy' => $location_tax, 'hide_empty' => false]);
            foreach ($all_location_terms as $location_term) {
                if ($ignore_main_term && $main_term_id && $location_term->term_id == $main_term_id) continue;
                $lat = get_term_meta($location_term->term_id, 'latitude', true);
                $lon = get_term_meta($location_term->term_id, 'longitude', true);
                if (!$lat || !$lon) continue;

                $description = generate_kml_entity_description($location_term->term_id, 'location');
                $kml .= '<Folder><name>' . esc_html($location_term->name) . '</name>';
                $kml .= "<Placemark><name>" . esc_html($company_info['name'] . ' - ' . $location_term->name) . "</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>#company_marker_style</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
                
                $associated_area_ids = get_term_meta($location_term->term_id, 'associated_act_terms', true);
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
                            $kml .= '<Placemark><name>' . esc_html($company_info['name'] . ' - ' . $location_term->name . ' Service Area') . '</name>';
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

    $initial_center_lat = null;
    $initial_center_lon = null;
    $initial_map_zoom = 13; 
    $locations_areas_status = get_option('locations_areas_status', 'none');
    $current_post_id = get_the_ID();   
    $location_term_id = da_get_location_term_or_default($current_post_id);
    $company_info = get_option('company_info');
    $custom_marker_url = '';
    if (!empty($company_info['map_pin'])) {
        $custom_marker_url = wp_get_attachment_url((int)$company_info['map_pin']);
    }
    if ($location_term_id) {
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
		return '';
	}
	$map_id = 'osm_map_' . str_replace('.', '_', uniqid()); 
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
    wp_enqueue_script( 'leaflet-omnivore', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet-omnivore/0.3.4/leaflet-omnivore.min.js', ['leaflet-js'], '0.3.4', true );

    $map_vars = [
        'mapId'        => $map_id,
        'kmlSource'    => $kml_source_data, 
        'isKmlUrl'     => $is_kml_url,      
        'initialLat'   => floatval($initial_center_lat), 
        'initialLon'   => floatval($initial_center_lon), 
        'initialZoom'  => $initial_map_zoom,
        'customMarkerUrl' => $custom_marker_url
    ];
    wp_add_inline_script( 'leaflet-js', 'var ' . esc_js($map_id) . 'Vars = ' . json_encode($map_vars) . ';' );

    ob_start();
    ?>
    <div id="<?php echo esc_attr($map_id); ?>" style="width: <?php echo esc_attr($map_width); ?>; height: <?php echo esc_attr($map_height); ?>; max-width: <?php echo esc_attr($map_max_width); ?>; border: 0; outline: none;"></div>
<script>
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
    $location_term_id = $term_ids[0];
	$location_term = get_term($location_term_id);
	$location_term_name = $location_term->name;
	if($location_term_name === $map_name){
	  $kml = get_kml_doc_header($status, $map_name, $location_term_id);
	}
	else{
	 $kml = get_kml_doc_header($status, $map_name, '');	
	}
    $marker_icon_url = '';
    if (!empty($company_info['map_pin'])) {
        $marker_icon_url = wp_get_attachment_url((int)$company_info['map_pin']);
    }
        foreach ($term_ids as $term_id) {
                   
            $lat = get_term_meta($location_term->term_id, 'latitude', true);
            $lon = get_term_meta($location_term->term_id, 'longitude', true);
            if (!$lat || !$lon) continue;

            if ($status === 'both') {
                $description = generate_kml_entity_description($location_term->term_id, 'location');
                $kml .= '<Folder><name>' . esc_html($location_term->name) . '</name>';
                $kml .= "<Placemark><name>" . esc_html($company_info['name'] . ' - ' . $location_term->name) . "</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>#company_marker_style</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
                
                $associated_area_ids = get_term_meta($location_term->term_id, 'associated_act_terms', true);
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
                            $kml .= '<Placemark><name>' . esc_html($company_info['name'] . ' - ' . $location_term->name . ' Service Area') . '</name>';
                            $kml .= "<styleUrl>#service_area_polygon_style</styleUrl>";
                            $kml .= '<Polygon><outerBoundaryIs><LinearRing><coordinates>' . implode(' ', $formatted_coords) . '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
                        }
                    }
                }
                $kml .= '</Folder>';
            } else {
                $description = generate_kml_entity_description($location_term->term_id, 'location');
                $kml .= "<Placemark><name>" . esc_html($company_info['name'] . ' - ' . $location_term->name) . "</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>#company_marker_style</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
            }
        }
    
    $kml .= '</Document></kml>';
    return $kml;
}

function render_location_term_card($location_term, $display_areas = 'no') {
    if (!$location_term || is_wp_error($location_term)) return '';

    $term_id = $location_term->term_id;
    $lat = get_term_meta($term_id, 'latitude', true);
    $lon = get_term_meta($term_id, 'longitude', true);
    if (!$lat || !$lon) return '';

    $name = esc_html($location_term->name);
    $link_url = get_term_meta($term_id, 'location_link_url', true);
    $phone = get_term_meta($term_id, 'phone_number', true);
    $additional_phone = get_term_meta($term_id, 'additional_phone', true);
    $address_lines = array_filter([
        get_term_meta($term_id, 'street_address', true),
        get_term_meta($term_id, 'street_address_2', true),
        implode(' ', array_filter([get_term_meta($term_id, 'city', true) . ',', get_term_meta($term_id, 'state', true), get_term_meta($term_id, 'zipcode', true)]))
    ]);
    $hours = get_term_meta($term_id, 'hours_of_operation', true);
    $area_term_ids = []; 
    $service_areas_html = '';
    if ($display_areas ==='yes' && get_option('locations_areas_status') ==='both'){
        $area_term_ids = get_term_meta($term_id, 'associated_act_terms', true) ?? [];
        if (!empty($area_term_ids)){
            $area_links = [];
            foreach ($area_term_ids as $area_id) {
                $area_term_name = get_term($area_id)->name;
                $area_link_url = get_term_meta($area_id, 'service_area_link_url', true);
                $area_links[] = '<a style="font-size: 13px;" href="' . esc_url($area_link_url) . '">' . $area_term_name . '</a>';
        }
            $service_areas_html .= '<div style="border-top: 1px dashed #eee;">';
$service_areas_html .= '<span style="font-weight: bold; font-size: 14px;">Service Areas:</span> ';
$service_areas_html .= implode(', ', $area_links);
$service_areas_html .= '</div>';
    }
    }
    $manager_html = '';
    $manager_id = get_term_meta($term_id, 'location_manager', true);
    if (!empty($manager_id) && is_numeric($manager_id)) {
        $employee = get_post_meta($manager_id, 'employee_data', true);
		$manager_given_name = $employee['given_name'];
		$manager_family_name = $employee['family_name'];
		$manager_work_phone = $employee['work_phone'];
        $full_manager_name = trim(esc_html($manager_given_name) . ' ' . esc_html($manager_family_name));

        if (!empty($full_manager_name) || !empty($manager_work_phone)) {
            $manager_html .= '<div style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #eee; color: #666;">';
            $manager_html .= '<span style="font-weight: bold; color: #333;">' . $full_manager_name . '</span><br>';
            if (!empty($manager_work_phone)) {
                $manager_html .= '<a href="' . esc_url(format_telephone_for_link($manager_work_phone)) . '" style="color: #0073aa; text-decoration: none;">' . esc_html(format_telephone_for_display($manager_work_phone)) . '</a>';
            }
            $manager_html .= '</div>';
        }
    }
    
    $output = '<div style="line-height: 1.4; font-family: Arial, sans-serif; font-size: 14px; color: #555; padding: 5px; background-color: #f9f9f9;">';
    $output .= '<h4 style="margin: 0; font-size: 16px; color: #222;">' . $name . '</h4>';
    if (!empty($address_lines)) $output .= '<p style="margin: 0;">' . implode('<br>', array_map('esc_html', $address_lines)) . '</p>';
    if (!empty($phone)) $output .= '<p style="margin: 0;"><a href="' . esc_url(format_telephone_for_link($phone)) . '" style="color: #0073aa; text-decoration: none;">' . esc_html(format_telephone_for_display($phone)) . '</a></p>';
    if (!empty($additional_phone)) $output .= '<p style="margin: 0;"><a href="' . esc_url(format_telephone_for_link($additional_phone)) . '" style="color: #0073aa; text-decoration: none;">' . esc_html(format_telephone_for_display($additional_phone)) . '</a></p>';
    if (!empty($hours)) $output .= '<div style="font-size: 14px; color: #666; margin-top: 5px;">' . da_generate_opening_hours_html($hours) . '</div>';
    $output .= $manager_html;
    $output .= $service_areas_html;
    if (!empty($link_url)) $output .= '<p style="margin: 5px 0;"><a href="' . esc_url($link_url) . '" style="color: #0073aa; text-decoration: none;">Visit Location Page</a></p>';
    $output .= '</div>';

    return $output;
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
    $xml .=     '<loc>' . esc_url($kml_url) . '</loc>';
    $xml .=     '<lastmod>' . $lastmod_date . '</lastmod>';
    $xml .=   '</url>';
    $xml .= '</urlset>';

    echo $xml;
    exit();
}
function handle_kml_generation_request() {
    if (!isset($_POST['kml_filter_nonce']) || !wp_verify_nonce($_POST['kml_filter_nonce'], 'generate_filtered_kml_action')) {
        wp_die('Security check failed.');
    }
    $filters = $_POST['filters'] ?? [];
    if (empty(array_filter($filters))) {
        wp_redirect(admin_url('admin.php?page=kml-generator&error=no_filters'));
        exit();
    }
    $sanitized_filters = [];
    foreach ($filters as $taxonomy => $term_ids) {
        if (!empty($term_ids)) {
            $sanitized_filters[sanitize_key($taxonomy)] = array_map('intval', $term_ids);
        }
    }
    $config_id = 'kml_config_' . wp_generate_password(12, false);
    update_option($config_id, $sanitized_filters, 'no');

    $saved_maps = get_option('dibraco_saved_kml_maps', []);
    $saved_maps[] = $config_id;
    update_option('dibraco_saved_kml_maps', array_unique($saved_maps), 'no');

    $redirect_url = admin_url('admin.php?page=kml-generator&config_id=' . $config_id . '&status=created');
    wp_redirect($redirect_url);
    exit();
}
add_action('admin_post_generate_filtered_kml', 'handle_kml_generation_request');
function handle_kml_config_delete() {
    if (!isset($_GET['config_id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'delete_kml_config_' . $_GET['config_id'])) {
        wp_die('Security check failed.');
    }

    $config_id = sanitize_key($_GET['config_id']);
    delete_option($config_id);
    $saved_maps = get_option('dibraco_saved_kml_maps', []);
    $saved_maps = array_diff($saved_maps, [$config_id]);
    update_option('dibraco_saved_kml_maps', $saved_maps, 'no');
    
    $redirect_url = admin_url('admin.php?page=kml-generator&status=deleted');
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
                'taxonomy' => sanitize_key($taxonomy),
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
            if (!is_wp_error($term_ids)) {
                return array_unique($term_ids);
            }
        }
    }

    return [];
}
function filtered_locations_list_shortcode($atts) {
    $enabled_context = get_option('enabled_connector_contexts')['locations'];
    $location_taxonomy_slug =$enabled_context['taxonomy'];
    $location_post_type = $enabled_context['post_type'];
    $location_term_ids = [];
    $atts = $atts ?? [];
    if (!empty($atts['config_id'])) {
        $location_term_ids = get_filtered_location_ids_from_config(sanitize_key($atts['config_id']));
    } 
    else if (!empty($atts) && is_array($atts)) {
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

    if (empty($location_terms) || is_wp_error($location_terms)) {
        return '<p>No locations were found.</p>';
    }
        $cards_html = '';
    foreach ($location_terms as $location_term) {
        $cards_html .= render_location_term_card($location_term);
    }

    if(empty($cards_html)) {
         return '<p>No locations with valid coordinates match the selected criteria.</p>';
    }
    $output = '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 0; list-style: none;">';
    $output .= $cards_html;
    $output .= '</div>';

    return $output;
}
add_shortcode('filtered_locations_list', 'filtered_locations_list_shortcode');

function dibraco_custom_endpoint_listener() {
    if (isset($_GET['download_custom_kml']) && !empty($_GET['download_custom_kml'])) {
        $config_id = sanitize_key($_GET['download_custom_kml']);
        
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
    $atts = shortcode_atts(['areas' => 'no', 'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false], $atts, 'my_locations_list');
    $service_areas = $atts['areas']; 
    $company_info = get_option('company_info');
    $location_taxonomy_slug = get_option('enabled_connector_contexts')['locations']['taxonomy'];
    $main_term_id = $enabled['locations']['main_term'] ?? null;
    $ignore_main_term = $enabled['locations']['ignore_main_term'];
    $output = '';
        $corp_name = esc_html($company_info['name']);
        $corp_phone = $company_info['phone_number'];
        $corp_address_lines = array_filter([
            $company_info['street_address'],
            $company_info['street_address_2'],
            implode(' ', array_filter([$company_info['city'], $company_info['state'], $company_info['zipcode']]))]);
        $output .= '<div style="grid-column: 1 / -1; margin-bottom: 10px; line-height: 1.4; font-family: Arial, sans-serif; font-size: 14px; color: #555; padding: 5px; background-color: #f9f9f9;">';
        $output .= '<h4 style="margin: 0; font-size: 16px; color: #222;">' . $corp_name . '</h4>';
        if (!empty($corp_address_lines)) {
            $output .= '<p style="margin: 0;">' . implode('<br>', array_map('esc_html', $corp_address_lines)) . '</p>';
        }
        if (!empty($corp_phone)) {
            $output .= '<p style="margin: 0;"><a href="' . esc_url(format_telephone_for_link($corp_phone)) . '" style="color: #0073aa; text-decoration: none;">' . esc_html(format_telephone_for_display($corp_phone)) . '</a></p>';
        }
        $output .= '</div>';
    $location_terms = get_terms(['taxonomy' => $location_taxonomy_slug, 'orderby' => $atts['orderby'], 'order' => $atts['order'], 'hide_empty' => $atts['hide_empty']]);

    if (empty($location_terms)) {
        return '';
    }

    $cards_html = '';
    foreach ($location_terms as $location_term) {
        if (($ignore_main_term ==="1") && $main_term_id && $location_term->term_id == $main_term_id) continue;
        $cards_html .= render_location_term_card($location_term, $service_areas);
    }
    
    if(empty($cards_html)) {
         return '';
    }

    $output .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 0; list-style: none;">' . $cards_html . '</div>';
    return $output;
}
add_shortcode('my_locations_list', 'my_locations_list_shortcode');