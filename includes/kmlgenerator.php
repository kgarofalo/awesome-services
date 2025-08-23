<?php
function prepare_saved_maps_table_data() {
    $saved_maps = get_option('dibraco_saved_kml_maps', []);
    $table_rows = [];
    if (!empty($saved_maps)) {
        foreach ($saved_maps as $config_id) {
            if (!get_option($config_id)) {
                continue;
            }
            $map_name = get_kml_config_map_name($config_id); 
            $shortcode = '[filtered_locations_list config_id="' . esc_attr($config_id) . '"]';
            $download_url = add_query_arg('download_custom_kml', $config_id, home_url('/'));

            $delete_form = '<form method="POST" action="" style="display:inline-block; margin-left: 5px;">';
            $delete_form .= '<input type="hidden" name="dibraco_action" value="delete_kml_config">';
            $delete_form .= '<input type="hidden" name="config_id" value="' . esc_attr($config_id) . '">';
            $delete_form .= wp_nonce_field('delete_kml_config_' . $config_id, '_wpnonce', true, false);
            $delete_form .= '<button type="submit" class="button" onclick="return confirm(\'Are you sure you want to delete this map configuration?\');" style="color: #a00; border-color: #a00;">Delete</button>';
            $delete_form .= '</form>';

            $cell1 = esc_html($map_name);
            $cell2 = '<input type="text" value="' . esc_attr($shortcode) . '" readonly style="width: 100%;">';
            $cell3 = '<a href="' . esc_url($download_url) . '" class="button">Download KML</a>' . $delete_form;

            $table_rows[] = [$cell1, $cell2, $cell3];
        }
    }
    return [
        'title'   => '',
        'headers' => ['Map Name', 'Shortcode', 'Actions'],
        'styles'  => ['width: 30%;', 'width: 40%;', 'width: 30%;'],
        'rows'    => $table_rows,
         'colspan' => 3,
    ];
}

function render_kml_generator_page() {
        if ( isset($_POST['dibraco_action']) ) {

        if ( $_POST['dibraco_action'] === 'delete_kml_config' ) {
            if ( isset($_POST['config_id'], $_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'delete_kml_config_' . $_POST['config_id']) ) {
            $config_id = $_POST['config_id'];
            delete_option($config_id);
            $saved_maps = get_option('dibraco_saved_kml_maps', []);
            $saved_maps = array_diff($saved_maps, [$config_id]);
            update_option('dibraco_saved_kml_maps', $saved_maps, 'no');
            wp_redirect(admin_url('admin.php?page=dibraco-relationships-kml-generator&status=deleted'));
            exit();
            }
        }
        elseif ( $_POST['dibraco_action'] === 'create_kml_config' ) {
            if (!isset($_POST['kml_filter_nonce']) || !wp_verify_nonce($_POST['kml_filter_nonce'], 'generate_filtered_kml_action')) {
                wp_die();
            }
            $filters = $_POST['filters'] ?? [];
            if (empty(array_filter($filters))) {
                wp_redirect(admin_url('admin.php?page=dibraco-relationships-kml-generator&error=no_filters'));
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
            wp_redirect(admin_url('admin.php?page=dibraco-relationships-kml-generator&config_id=' . $config_id . '&status=created'));
            exit();
            }
        }
    ?>
    <div class="wrap">
        <h1>KML Map Generator</h1>
        <?php
        if (isset($_GET['error']) && $_GET['error'] === 'no_filters') {
            echo '<div id="message" class="error notice is-dismissible"><p>Please select at least one filter to generate a map.</p></div>';
        }
        if (isset($_GET['status']) && $_GET['status'] === 'deleted') {
            echo '<div id="message" class="updated notice is-dismissible"><p>Map configuration deleted successfully.</p></div>';
        }
        if (isset($_GET['status']) && $_GET['status'] === 'created' && isset($_GET['config_id'])) {
            $shortcode = '[filtered_locations_list config_id="' . esc_attr($_GET['config_id']) . '"]';
            echo '<div id="message" class="updated notice is-dismissible"><p>Map created successfully! Shortcode: <input type="text" value="' . esc_attr($shortcode) . '" readonly style="width: 100%; max-width: 400px;"></p></div>';
        }
        ?>
        <h2>Saved Maps</h2>
        <?php
        $saved_maps_table_data = prepare_saved_maps_table_data();
        render_dibraco_admin_table($saved_maps_table_data);
        ?>
        <h2 style="margin-top: 40px;">Create New Map</h2>
        <?php
        $enabled = get_option('enabled_connector_contexts');
        $location_post_type = $enabled['locations']['post_type'];
        $all_taxonomies = get_object_taxonomies($location_post_type, 'objects');
        ?>
      <form method="POST" action="">
            <input type="hidden" name="dibraco_action" value="create_kml_config">
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

function get_filtered_location_ids_from_config($config_id) {
    $filters = get_option($config_id);
    $enabled = get_option('enabled_connector_contexts');
    $location_post_type = $enabled['locations']['post_type'];
    $location_taxonomy_slug = $enabled['locations']['taxonomy'];
    $tax_query = ['relation' => 'AND'];
    foreach ($filters as $taxonomy => $term_ids) {
        if (!empty($term_ids)) {
            $tax_query[] = ['taxonomy' => sanitize_key($taxonomy),'field'    => 'term_id', 'terms'    => array_map('intval', $term_ids),
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
    $config_id = $atts['config_id'] ?? '';
    if (empty($config_id) || get_option($config_id) === false) {
        return '';
    }
    $enabled_context = get_option('enabled_connector_contexts')['locations'];
    $location_taxonomy_slug = $enabled_context['taxonomy'];
    $location_post_type = $enabled_context['post_type'];
    $location_term_ids = get_filtered_location_ids_from_config($atts['config_id']);
     if (empty($location_term_ids)) {
        return '';
    }
    $cards_html = '';
    foreach ($location_term_ids as $location_term_id) {
        $cards_html .= render_location_term_card($location_term_id);
    }
    if (empty($cards_html)) {
        return '';
    }
    $output = '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 0; list-style: none;">';
    $output .= $cards_html;
    $output .= '</div>';
    return $output;
}
add_shortcode('filtered_locations_list', 'filtered_locations_list_shortcode');


function render_location_term_card($location_term_id, $display_areas = 'no') {
$lat = get_term_meta($location_term_id, 'latitude', true);
  if (empty($lat)) { return ''; }
    $details = da_get_display_data($location_term_id, 'location');
    $name = esc_html($details['name']);
    $link_url = $details['link_url'];
    $phone = $details['phone'];
    $additional_phone = $details['additional_phone']??'';
    $address_lines = array_filter([
        $details['address_parts']['street_address'],
        $details['address_parts']['street_address_2'],
        implode(' ', array_filter([$details['address_parts']['city'] . ',', $details['address_parts']['state'], $details['address_parts']['zipcode']]))
    ]);
    $hours = $details['hours'];
    $area_term_ids = []; 
    $service_areas_html = '';
    if ($display_areas ==='yes' && get_option('locations_areas_status') ==='both'){
        $area_term_ids = get_term_meta($location_term_id, 'associated_act_terms', true);
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
    $enabled_context_names = get_option('enabled_context_names');
    $manager_html = '';
    if (in_array('employee', $enabled_context_names)) {
    $employee = get_manager_info_for_display($location_term_id);
    if (!empty($employee)) {
		$manager_given_name = $employee['given_name'];
		$manager_family_name = $employee['family_name'];
		$manager_work_phone = $employee['work_phone'];
        $full_manager_name = trim(esc_html($manager_given_name) . ' ' . esc_html($manager_family_name));
        if (!empty($full_manager_name)) {
            $manager_html .= '<div style="margin-top: 5px; padding-top: 5px; border-top: 1px dashed #eee; color: #666;">';
            $manager_html .= '<span style="font-weight: bold; color: #333;">' . $full_manager_name . '</span><br>';
        }
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
function my_locations_list_shortcode($atts) {
    $atts = shortcode_atts(['areas' => 'no', 'show_main' => 'yes', 'orderby' => 'name', 'order' => 'ASC', 'hide_empty' => false], $atts, 'my_locations_list');
    $service_areas = $atts['areas']; 
    $show_main = $atts['show_main'];
    $company_info = get_option('company_info');
    $location_taxonomy_slug = get_option('enabled_connector_contexts')['locations']['taxonomy'];
    $main_term_id = $enabled['locations']['main_term'] ?? '';
    $ignore_main_term = $enabled['locations']['ignore_main_term'] ?? '';
    $output = '';
    if ($show_main ==='yes'){
        $company_name = esc_html($company_info['name']);
        $company_phone = $company_info['phone_number'];
        $company_address_lines = array_filter([
            $company_info['street_address'],
            $company_info['street_address_2'],
        implode(' ', array_filter([$company_info['city'], $company_info['state'], $company_info['zipcode']]))]);
        $output .= '<div style="grid-column: 1 / -1; margin-bottom: 10px; line-height: 1.4; font-family: Arial, sans-serif; font-size: 14px; color: #555; padding: 5px; background-color: #f9f9f9;">';
        $output .= '<h4 style="margin: 0; font-size: 16px; color: #222;">' . $company_name . '</h4>';
        if (!empty($company_address_lines)) {
            $output .= '<p style="margin: 0;">' . implode('<br>', array_map('esc_html', $company_address_lines)) . '</p>';
        }
        if (!empty($corp_phone)) {
            $output .= '<p style="margin: 0;"><a href="' . esc_url(format_telephone_for_link($company_phone)) . '" style="color: #0073aa; text-decoration: none;">' . esc_html(format_telephone_for_display($company_phone)) . '</a></p>';
        }
        $output .= '</div>';
    }
    $location_term_ids = get_terms(['taxonomy' => $location_taxonomy_slug, 'orderby' => $atts['orderby'], 'order' => $atts['order'], 'hide_empty' => $atts['hide_empty'], 'fields' => 'ids']);
    if (empty($location_term_ids)) {
        return '';
    }
    $cards_html = '';
    foreach ($location_term_ids as $location_term_id) {
    if (($ignore_main_term ==="1") && $main_term_id && $location_term_id === (int)$main_term_id) continue;
        $cards_html .= render_location_term_card($location_term_id, $service_areas);
    }
    if(empty($cards_html)) {
         return '';
    }
    $output .= '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 0; list-style: none;">' . $cards_html . '</div>';
    return $output;
}
add_shortcode('my_locations_list', 'my_locations_list_shortcode');


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
        $details['name'] = get_term_meta($entity_id, 'location_name', true);
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
           $details['logo_url'] = get_image_for_display($logo_id);
        }
        if (!empty($image_id)){
            $details['image_url'] =  get_image_for_display($image_id, 'medium');
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
            $details['description'] = get_post_meta((int)$post_id, 'da_about_blurb', true) ??'';
            if(empty($details['description'])){
            $details['description'] = get_post_meta((int)$post_id, 'da_banner_description', true);
            }
        }
    } else {
        $info = $data;
        $details['name'] = $info['name'];
        $details['link_url'] = home_url('/');
        $details['phone'] = $info['phone_number'];
        $details['hours'] = $info['hours_of_operation'];
        $details['place_id'] = $info['place_id'];
        $image_id = $info['exterior_image'];
        $logo_id = $info['company_logo'];
        $details['description'] = $info['company_description'];
        $details['address_parts'] = da_get_address_data(); 
        $details['logo_url'] = '';
        $details['image_url'] = '';
        if (!empty($logo_id)){
           $details['logo_url'] = get_image_for_display($logo_id);
        }
        if (!empty($image_id)){
            $details['image_url'] =  get_image_for_display($image_id, 'medium');
        }
        $details['additional_phone'] = '';
        if ($info['second_phone'] === "1") {
           $details['additional_phone'] = $info['additional_phone'];
        }
    }

    return array_filter($details);
}
function generate_kml_entity_description($data, $source_type = 'location') {
    $company_name = get_option('company_info')['name'];
    $details = da_get_display_data($data, $source_type);
    if ($source_type === 'location') {
        $details = da_get_display_data($data, $source_type);
        $location_term = get_term($data);
        $location_area_name = $location_term->name; 
        $location_office_name = $details['name'];
        $enabled_context_names = get_option('enabled_context_names');
        if (in_array('employee', $enabled_context_names)) {
           $employee = get_manager_info_for_display($data);
            if (!empty($employee)){
                $manager_given_name   = $employee['given_name'];
                $manager_family_name  = $employee['family_name'];
                $manager_work_email   = $employee['work_email'];
                $manager_work_phone   = $employee['work_phone'];
                $manager_job_title    = $employee['job_title'];
                $manager_portrait_url  = $employee['portrait_url'];
             }
        }
    } else {
        $title = $company_name;
    }
    $address = $details['address_parts'];
    $address_line_1 = trim(implode(' ', array_filter([$address['street_address'], $address['street_address_2']])));
    $address_line_2 = trim(implode(', ', array_filter([$address['city'], $address['state'], $address['zipcode'], $address['addy_country']])));
    $full_html = '<div style="max-width: 360px;">';
    $full_html .= '<table class="dibraco-kml-infowindow" border="0" cellpadding="5" cellspacing="0" style="width: 100%; font-family: Arial, Helvetica, sans-serif; font-size: 12px; line-height: 1.3;">';
    $full_html .= '<tr><td colspan="2" style="padding-top: 10px; padding-bottom:10px; border-bottom:1px solid #eee;">';
    $full_html .= '<table border="0" cellpadding="0" cellspacing="0"><tr>';
    if ($details['logo_url']) {
        $full_html .= '<td valign="middle"><img src="' . esc_url($details['logo_url']) . '" alt="Logo" style="max-width:40px; height:auto; vertical-align:middle;"></td>';
    }
    $full_html .= '<td valign="middle" style="padding-left:10px;"><b style="font-size:14px;">' . $title . '</b></td>';
    $full_html .= '</tr></table></td></tr><tr><td width="35%" valign="top" style="padding-right: 5px; padding-top:5px;">';
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
    $full_html .= '</td><td width="65%" valign="top" style="padding-left: 5px; padding-top:15px;">';
    $main_info_right = '';
    if (!empty($details['image_url'])) {
$main_info_right = '<img src="' . esc_url($details['image_url']) . '" alt="Exterior" style="max-width: 234px; width: 100%; height: auto; display: block; border: 1px solid #ccc; padding: 3px;">';
    }
    $full_html .= $main_info_right;
    $full_html .= '</td></tr>';
    $link_url = $details['link_url'];
    if (!empty($link_url)) {
        $full_html .= '<tr>';
        $full_html .= '<td colspan="2" style="text-align: center; padding: 5px 0; border-top: 1px solid #eee; color:black; font-size:12px;">';
        $full_html .= ($source_type === 'location') ? '<a href="' . esc_url($link_url) . '">Visit Location Page</a>' : '<a href="' . esc_url($link_url) . '">Visit Our Website</a>';
        $full_html .= '</td></tr>';
    }
     $place_id = $details['place_id'];
    if (!empty($place_id)) {
        $Maps_url = 'https://www.google.com/maps/place/?q=place_id:' . urlencode($place_id);
        $full_html .= '<tr><td colspan="2" style="text-align: center; padding: 5px 0; border-top: 1px solid #eee; font-size:12px;">';
        $full_html .= '<a href="' . esc_url($Maps_url) . '" target="_blank" rel="noopener noreferrer">View on Google Maps & See Reviews</a>';
        $full_html .= '</td></tr>';

    }
    if (!empty($details['description'])) {
        $description = esc_html($details['description']);
        $description_words = explode(' ', $description);
        if (count($description_words) > 40) {
            $description = implode(' ', array_slice($description_words, 0, 40)) . '...';
        }
        $full_html .= '<tr><td colspan="2" style="border-top:1px solid #eee; padding-top:10px;">';
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
        $full_html .= '</td><td width="57%" valign="top" style="max-width: 180px; padding-right:5px; padding-left:5px;">';
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
        $full_html .= '</td></tr></table>';

        if (!empty($manager_work_phone) && !empty($manager_given_name)) {
            $call_link = format_telephone_for_link($manager_work_phone);
            $full_html .= '<a href="' . esc_url($call_link) . '" style="background-color:#606770; color:white; padding:5px 5px; text-align:center; text-decoration:none; display:inline-block; border-radius:4px;">Call ' . esc_html($manager_given_name) . '</a>';
        }
    $full_html .= '</td></tr>';

    }

    $full_html .= '</table></div>';

    return "<![CDATA[{$full_html}]]>";
}
function get_image_for_display($image_id, $size ='medium'){
(int)$image_id;
 return wp_get_attachment_image_url($image_id, $size);

}
function get_manager_info_for_display($location_term_id) {
    $manager_id = get_term_meta($location_term_id, 'location_manager', true);
    if (empty($manager_id)) {
        return [];
    }
    $employee_data = get_post_meta($manager_id, 'employee_data', true);
    $manager_portrait_id = get_post_meta($manager_id, 'dibraco_portrait_1', true);
    $manager_portrait_url = '';
    if (!empty($manager_portrait_id)) {
        $manager_portrait_url = get_image_for_display((int)$manager_portrait_id, 'medium');
    }
    return [
        'given_name'      => $employee_data['given_name'],
        'family_name'     => $employee_data['family_name'],
        'work_email'      => $employee_data['work_email'],
        'work_phone'      => $employee_data['work_phone'],
        'job_title'       => $employee_data['job_title'], 
        'portrait_url'    => $manager_portrait_url,
    ];
}
function get_kml_doc_header($status, $map_name, $location_term_id = '') {
    $company_info = get_option('company_info');
    $stylesheet_url = esc_url(add_query_arg(['dibraco_kml_xsl' => 'true'], home_url('/')));
    $kml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $kml .= '<?xml-stylesheet type="text/xsl" href="' . $stylesheet_url . '"?>' . "\n";
    $kml .= '<kml xmlns="http://www.opengis.net/kml/2.2"><Document>';
    $kml .= '<name>' . esc_html($map_name) . '</name>';
    $company_style_id = 'company_marker_style';
    $marker_icon_url = '';
    if (!empty($company_info['map_pin'])) {
        if (!empty($company_map_pin_id)) {
            $marker_icon_url = get_image_for_display($company_map_pin_id, 'small');
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
    function get_location_kml_for_map($location_term_id, $company_name, $marker_icon_url =''){
                $lat = get_term_meta($location_term_id, 'latitude', true);
               if (!$lat) {return null;}
                $lon = get_term_meta($location_term_id, 'longitude', true);
                $location_name = get_term($location_term_id)->name;
                $description = generate_kml_entity_description($location_term_id, 'location');
                $kml = "<Placemark><name>" .  $company_name  . ' - ' . $location_name . "</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>#company_marker_style</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
                return $kml;
                }

function generate_master_kml_file() {
    $status = get_option('locations_areas_status');
    $enabled = get_option('enabled_connector_contexts');
    $company_info = get_option('company_info');
    $main_term_id = $enabled['locations']['main_term'];
    $ignore_main_term = $enabled['locations']['ignore_main_term'];
    $company_name = $company_info['name'];
    $kml = get_kml_doc_header($status, $company_name ?? 'Company Map');
    $marker_icon_url = '';
    $company_map_pin_id = $company_info['map_pin'];
    if (!empty($company_info['map_pin'])) {
           $marker_icon_url = get_image_for_display($company_map_pin_id, 'small');
    }
   switch ($status) {
        case 'multi_locations':
             $location_tax = $enabled['locations']['taxonomy'];
            $location_terms = get_terms(['taxonomy' => $location_tax, 'hide_empty' => false]);
             foreach ($location_terms as $location_term) {
               $location_term_id =  $location_term->term_id;
                   if ($ignore_main_term && $main_term_id && $location_term_id == $main_term_id) {continue;}
                 $kml .=  get_location_kml_for_map($location_term_id, $company_name, $marker_icon_url);
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
                    $kml .= get_polygon_coords_for_kml($coords, $company_name, $location_term);

                }
            }
            break;

        case 'both':
            $location_tax = $enabled['locations']['taxonomy'];
            $location_terms = get_terms(['taxonomy' => $location_tax, 'hide_empty' => false]);
           
             foreach ($location_terms as $location_term) {
               $location_term_id =  $location_term->term_id;
                   if ($ignore_main_term && $main_term_id && $location_term_id == $main_term_id) {continue;}
                 $kml .=  get_location_kml_for_map($location_term_id, $company_name, $marker_icon_url);
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
                           $kml .= get_polygon_coords_for_kml($coords, $company_name, $location_term);
                            
                        }
                    }
                }
                //$kml .= '</Folder>';
            }
            break;
    }

    $kml .= '</Document></kml>';
    return $kml;
}
function get_polygon_coords_for_kml($coords, $company_name, $location_term =[]){
$formatted_coords = [];
for ($i = 0; $i < count($coords); $i += 2) {
$formatted_coords[] = "{$coords[$i]},{$coords[$i + 1]},0";
    }
$location_term_name = 'total';
if (!empty($location_term)){
$location_term_name = $location_term->name;
}
$kml = '<Placemark><name>' . $company_name . ' - ' . $location_term_name . ' Service Area' . '</name>';
$kml .= "<styleUrl>#service_area_polygon_style</styleUrl>";
$kml .= '<Polygon><outerBoundaryIs><LinearRing><coordinates>' . implode(' ', $formatted_coords) . '</coordinates></LinearRing></outerBoundaryIs></Polygon></Placemark>';
   return $kml;                   
}

function my_osm_kml_map_shortcode($atts) {
    if ( isset( $atts['max-width'] ) ) {
        $atts['max_width'] = $atts['max-width'];
    }
    $atts = shortcode_atts( array(
        'width'       => '100%',
        'height'      => '400px',
        'max_width'   => '600px',
        'zoom'        => null, 
    ), $atts, 'my_osm_kml_map' );

    $map_width = $atts['width'];
    $map_height = $atts['height'];
    $map_max_width = $atts['max_width'];
    $shortcode_zoom = $atts['zoom'];
    $kml_source_data = '';
    $is_kml_url = false;
    $initial_center_lat = null;
    $initial_center_lon = null;
    $initial_map_zoom = 13; 
    $custom_marker_url = '';
    $status = get_option('locations_areas_status');
    $current_post_id = get_the_ID();
    $location_term_id = da_get_location_term_or_default($current_post_id);
    $company_info = get_option('company_info');
    if (!empty($company_info['map_pin'])) {
        $custom_marker_url = get_image_for_display($company_info['map_pin']);
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
                switch ($status) {
            case 'none':
                $initial_map_zoom = 15;
                break;
            case 'multi_locations':
            case 'multi_areas':
                $initial_map_zoom = 10;
                break;
            case 'both':
                $initial_map_zoom = 8;
                break;
        }
    }
    if (!is_null($shortcode_zoom) && is_numeric($shortcode_zoom)) {
        $initial_map_zoom = (int)$shortcode_zoom;
    }
    if (empty($kml_source_data) || empty($initial_center_lat) || empty($initial_center_lon)) {
        return '';
    }
    $map_id = 'osm_map_' . str_replace('.', '_', uniqid());
    wp_enqueue_style( 'leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );
    wp_enqueue_script( 'leaflet-omnivore', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet-omnivore/0.3.4/leaflet-omnivore.min.js', ['leaflet-js'], '0.3.4', true );

    $map_vars = [
        'mapId'             => $map_id,
        'kmlSource'         => $kml_source_data,
        'isKmlUrl'          => $is_kml_url,
        'initialLat'        => floatval($initial_center_lat),
        'initialLon'        => floatval($initial_center_lon),
        'initialZoom'       => $initial_map_zoom,
        'shortcodeZoom'     => $shortcode_zoom, // Pass the shortcode zoom to JS
        'customMarkerUrl'   => $custom_marker_url
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
        var shortcodeZoom = mapVars.shortcodeZoom;
        var customMarkerUrl = mapVars.customMarkerUrl;
        function initializeLeafletMap() {
            var map = L.map(mapId).setView([initialLat, initialLon], initialZoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);
            var bindPopups = function(layer) {
                layer.eachLayer(function(subLayer) {
                    if (subLayer.feature && subLayer.feature.properties && subLayer.feature.properties.description) {
                        var popupOptions = { minWidth: 360, maxWidth: 400, maxHeight: 300 };
                        subLayer.bindPopup(subLayer.feature.properties.description, popupOptions);
                    }
                });
            };
            var processKmlLayer = function(kmlLayer) {
                kmlLayer.addTo(map);
                bindPopups(kmlLayer);
                var bounds = kmlLayer.getBounds();
                                if (bounds.isValid() && shortcodeZoom === null) {
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
                    return L.marker(latlng, { icon: customIcon });
                }
                return undefined;
            };
            var omnivoreOptions = {
                pointToLayer: createCustomMarker
            };
            if (typeof omnivore !== 'undefined') {
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
                        console.error("Error parsing inline KML:", e);
                    }
                }
            }
        }
        var mapElement = document.getElementById(mapId);
        var observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.1
        };
        var mapObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    initializeLeafletMap();
                    observer.unobserve(entry.target); // Stop observing once loaded
                }
            });
        }, observerOptions);

        if (mapElement) {
            mapObserver.observe(mapElement);
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('my_osm_kml_map', 'my_osm_kml_map_shortcode');
function generate_custom_kml_file($term_ids, $map_name = 'Custom Map') {
    $status = get_option('locations_areas_status');
    $company_info = get_option('company_info');
    $location_term_id = $term_ids[0];
	$location_term = get_term($location_term_id);
	$location_term_name = $location_term->name;
	$company_name = $company_info['name'];
	if($location_term_name === $map_name){
	  $kml = get_kml_doc_header($status, $map_name, $location_term_id);
	}
	else{
	 $kml = get_kml_doc_header($status, $map_name, '');	
	}
    $marker_icon_url = '';
    if (!empty($company_info['map_pin'])) {
        $marker_icon_url = wp_get_attachment_url($company_info['map_pin']);
    }
        if ($status === 'both') {
             foreach ($term_ids as $term_id) {
                $latitude = get_term_meta($term_id, 'latitude', true);
                if (empty($latitude)){continue;}
                $longitude = get_term_meta($term_id, 'longitude', true);
                $description = generate_kml_entity_description($term_id, 'location');
                $kml .= '<Folder><name>' . esc_html($location_term->name) . '</name>';
                $kml .= "<Placemark><name>" . esc_html($company_name . ' - ' . $location_term->name) . "</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>#company_marker_style</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$longitude},{$latitude},0</coordinates></Point></Placemark>";
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
                               $kml .= get_polygon_coords_for_kml($coords, $company_name, $location_term);
                            }
                    }
                }
                $kml .= '</Folder>';
            } 
        }  else {
                $description = generate_kml_entity_description($location_term->term_id, 'location');
                $kml .= "<Placemark><name>" . esc_html($company_info['name'] . ' - ' . $location_term->name) . "</name>";
                $kml .= "<description>{$description}</description>";
                if (!empty($marker_icon_url)) {
                    $kml .= "<styleUrl>#company_marker_style</styleUrl>";
                }
                $kml .= "<Point><coordinates>{$lon},{$lat},0</coordinates></Point></Placemark>";
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
function dibraco_serve_dynamic_kml_stylesheet() {
    $company_info = get_option('company_info', []);
    $map_title = !empty($company_info['name']) ? esc_html($company_info['name']) . ' Locations' : 'Locations KML File';
    $sitemap_index_url = esc_url(home_url('/sitemap_index.xml')); 
    header('Content-Type: application/xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    ?>
    <xsl:stylesheet version="1.0"
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:kml="http://www.opengis.net/kml/2.2"
        exclude-result-prefixes="kml">
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes"/>
    <xsl:template match="/">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
            <title><?php echo $map_title; ?></title>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
            <style type="text/css">
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background-color: #f0f2f5; color: #444; margin: 0; padding: 20px; }
                .wrap { background: #fff; border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); max-width: 960px; margin: 0 auto; }
                .kml-header { background-color: #2271b1; color: #fff; padding: 20px 30px; }
                .kml-header h1 { color: #fff; margin: 0; font-size: 24px; }
                .kml-header p { margin: 5px 0 0; opacity: 0.9; }
                .kml-body { padding: 20px 30px; }
                a { color: #0073aa; text-decoration: none; }
                a:hover { text-decoration: underline; }
                .wp-list-table { border: 1px solid #c3c4c7; border-collapse: collapse; width: 100%; margin-top: 20px; }
                .wp-list-table th, .wp-list-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e7e7e7; }
                .wp-list-table thead th { background-color: #f6f7f7; font-weight: 600; }
                .wp-list-table tbody tr:nth-child(odd) { background-color: #f8f9fa; }
            </style>
        </head>
        <body>
            <div class="wrap">
                <div class="kml-header">
                    <h1>KML File</h1>
                    <p>This KML File is generated by your plugin. It is used to provide location information to Google.</p>
                </div>
                <div class="kml-body">
                    <p>This KML file contains <xsl:value-of select="count(kml:kml/kml:Document/kml:Placemark|kml:kml/kml:Document/kml:Folder/kml:Placemark)"/> Location(s).</p>
                    <p><a href="<?php echo $sitemap_index_url; ?>">&larr; Sitemap Index</a></p>

                    <table class="wp-list-table widefat striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                            </tr>
                        </thead>
                        <tbody>
                            <xsl:for-each select="//kml:Placemark">
                                <tr>
                                    <td><xsl:value-of select="kml:name"/></td>
                                    <td><xsl:value-of select="substring-before(substring-after(kml:Point/kml:coordinates, ','), ',')"/></td>
                                    <td><xsl:value-of select="substring-before(kml:Point/kml:coordinates, ',')"/></td>
                                </tr>
                            </xsl:for-each>
                        </tbody>
                    </table>
                </div>
            </div>
        </body>
        </html>
    </xsl:template>
    </xsl:stylesheet>
    <?php
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



function dibraco_custom_endpoint_listener() {
       if (isset($_GET['dibraco_kml_xsl']) && $_GET['dibraco_kml_xsl'] === 'true') {
        dibraco_serve_dynamic_kml_stylesheet();
    }

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
