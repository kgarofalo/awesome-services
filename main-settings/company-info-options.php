<?php
if (!shortcode_exists('current_year')) {
    add_shortcode('current_year', function() {
        return date('Y'); 
    });
}
function initialize_schema_options() {
    return [
        'Service' => 'Service', 'Person' => 'Person',
        'JobPosting' => 'Job Posting', 'LocalBusiness' => 'Local Business', 
        'HomeAndConstructionBusiness' => 'Home and Construction Business', 'Electrician' => 'Electrician', 
        'GeneralContractor' => 'General Contractor', 'HVACBusiness' => 'HVAC Business', 
        'HousePainter' => 'House Painter', 'Locksmith' => 'Locksmith', 'MovingCompany' => 'Moving Company', 
        'Plumber' => 'Plumber', 'RoofingContractor' => 'Roofing Contractor', 'Attorney' => 'Attorney', 
        'Notary' => 'Notary', 'LegalService' => 'Legal Service', 'Bakery' => 'Bakery', 'BarOrPub' => 'Bar or Pub', 
        'CafeOrCoffeeShop' => 'Cafe or Coffee Shop', 'Restaurant' => 'Restaurant', 
        'FoodEstablishment' => 'Food Establishment', 'MedicalClinic' => 'Medical Clinic', 
        'MedicalOrganization' => 'Medical Organization', 'Organization' => 'Organization'
    ];
}
function get_non_profit_type() {
    $values = [
        '501 a', '501 c1', '501 c2', '501 c3', '501 c4', '501 c5', '501 c6', '501 c7', '501 c8', '501 c9',
        '501 c10', '501 c11', '501 c12', '501 c13', '501 c14', '501 c15', '501 c16', '501 c17', '501 c18', '501 c19',
        '501 c20', '501 c21', '501 c22', '501 c23', '501 c24', '501 c25', '501 c26', '501 c27', '501 c28',
        '501 d', '501 e', '501 f', '501 k', '501 n', '501 q', '527'
    ];
    $result = [];
    foreach ($values as $value) {
        $key = 'Nonprofit' . str_replace(' ', '', $value);
        $result[$key] = $value;
    }
    return $result;
}
function get_pages_for_contact_about(){
$pages_options = [];
    $pages = get_posts([
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'post_status'    => 'publish',
        'suppress_filters' => false
    ]);

    if ($pages) {
        foreach ($pages as $page) {
            $value = $page->ID;
            $pages_options[$value] = $page->post_title;
        }
    }

    return $pages_options;
}

function get_employee_posts_for_select_options() {
    $employee_options = [];
    $enabled_contexts = get_option('enabled_contexts');
    $employee_post_type = $enabled_contexts['employee']['post_type'] ?? '';

    if ($employee_post_type && post_type_exists($employee_post_type)) {
        $employees = get_posts([
            'post_type'        => $employee_post_type,
            'posts_per_page'   => -1, 
            'orderby'          => 'title',
            'order'            => 'ASC',
            'post_status'      => 'publish', 
            'suppress_filters' => false 
        ]);

        if ($employees) {
            foreach ($employees as $employee) {
                $employee_options[$employee->ID] = $employee->post_title;
            }
        }
    }

    return $employee_options;
}
function get_company_schema_types() {
    return [
        'LocalBusiness' => 'Local Business', 'HomeAndConstructionBusiness' => 'Home and Construction Business', 'AccountingService' => 'Accounting Service',
        'Electrician' => 'Electrician', 'GeneralContractor' => 'General Contractor', 'HVACBusiness' => 'HVAC Business',
        'HousePainter' => 'House Painter', 'Locksmith' => 'Locksmith', 'MovingCompany' => 'Moving Company',
        'Plumber' => 'Plumber', 'RoofingContractor' => 'Roofing Contractor', 'Bakery' => 'Bakery',
        'BarOrPub' => 'Bar or Pub', 'CafeOrCoffeeShop' => 'Cafe or Coffee Shop', 'Restaurant' => 'Restaurant',
        'FoodEstablishment' => 'Food Establishment', 'MedicalClinic' => 'Medical Clinic',
        'MedicalOrganization' => 'Medical Organization', 'LegalService' => 'Legal Service',
        'Attorney' => 'Attorney', 'Notary' => 'Notary', 'Organization' => 'Organization', 'Corporation' => 'Corporation',
    ];
}

function migrate_social_media_fields($current_company_values, $social_media_keys){
$social_media_nested_array =[];
     foreach ($social_media_keys as $key) {
        $social_media_nested_array[$key] = $current_company_values[$key]; 
        unset($current_company_values[$key]);
        }
    $current_company_values['social_media'] = $social_media_nested_array; 
    update_option('company_info', $current_company_values);
    return $current_company_values; 
}

function initialize_dafields($request_from_where = '') {
    $schema_options = get_company_schema_types();
    $cert_fields = [];
    if($request_from_where ===''){
       $status = get_option('locations_areas_status')??'none';
        $show_address_on_org = "1";
        if ($status === 'multi_locations' || $status === 'both' || $status==='multi_locations') {
        $show_address_on_org = get_option('company_info')['show_address_on_org'] ??"1";
          }
       $value247 = '';
        if ($show_address_on_org ==='1'){
            $value247 = get_option('company_info')['hours_of_operation']['open_247']??'0';
        }
        if($show_address_on_org ==='0'){
           $schema_options = ['Organization' => 'Organization', 'NGO'=> 'NGO', 'Corporation' => 'Corporation', 'MedicalOrganization' => 'Medical Organization'];
        }
       $business_data = get_company_info_only_fields($schema_options);
    }
    if ($request_from_where ==='location_'){
        $locations_context = get_option('enabled_connector_contexts')['locations'];
        $po_id = (int)get_the_ID();
        $location_id ='';
        if ($po_id !==0 && $po_id !=='0' && (!empty($po_id))){
            $locations_taxonomy = $locations_context['taxonomy'];
            $location_id = dibraco_get_current_term_id_for_post($po_id,  $locations_taxonomy);
        }
        if ($location_id ===''){
            $location_id=(int) $_GET['tag_ID'];
        }
        if (!empty($location_id)){
            $value247 = get_term_meta($location_id, 'hours_of_operation', true)['open_247'];
        }
         if($locations_context['has_certification']==="1"){
           $cert_fields = get_certification_fields();
        }
        $business_data = get_location_only_fields($schema_options); 
    }
    
    $image_fields = get_location_or_company_image_fields($request_from_where);
    $contact_info = get_contact_info_fields($request_from_where);
    $social_media = get_social_media_fields();
    $address = get_address_fields();
    $hours_of_operation = get_hours_of_operation_fields();
    $da_fields = [
        'image-fields' => ['type' => 'field_group', 'fields' => $image_fields],
        'business_information'=> ['type' => 'field_group', 'fields'=> $business_data],
        'contact_info' => ['type' => 'visual_section', 'fields' => $contact_info],
        'social_media' => ['type' => 'visual_section', 'storage' => '1', 'fields' => $social_media],
        'address' => ['type' => 'visual_section', 'fields' => $address],
        'hours_of_operation' => ['type' => 'visual_split', 'storage' => '1', 'condition'=> ['field'=> 'open_247', 'values' => ['0'], 'current_value' =>$value247], 'fields' =>$hours_of_operation],
          ] + $cert_fields + [
    ];
    if ($request_from_where === 'location_') {
        if ($locations_context['landscape_images'] === "1") {
            $da_fields += get_term_landscape_fields();
         }
        if (($locations_context['portrait_images']) === "1") {
            $da_fields += get_term_portrait_fields();
        }
   } else {
        if ($show_address_on_org ==='0'){
           unset($da_fields['address']);
           unset($da_fields['hours_of_operation']);
           unset($da_fields['contact_info']['fields']['place_id']);
           unset($da_fields['contact_info']['fields']['gmb_map_link']);
           unset($da_fields['image-fields']['fields']['exterior_image']);
           }
        }
    return $da_fields;
}

function company_info_options_page() { 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['submit_action'] === 'save_company_info_options') {
        handle_save_company_info();
}

    $template_fields = initialize_dafields();
    error_log(print_r($template_fields,true));
    $storage_keys = dibraco_extract_nested_arrays_test($template_fields);
        error_log(print_r($storage_keys,true));

    $all_values = get_option('company_info', []);
        error_log(print_r($all_values,true));

    foreach($storage_keys as $container_name => $storage_array_key){
        if (is_array($storage_array_key)){
             if (array_key_exists($container_name, $all_values)) {
              $all_values = array_merge($all_values, $all_values[$container_name]); 
              $storage_keys = array_merge($storage_keys,$storage_keys[$container_name]);
              unset($all_values[$container_name]);
              unset($storage_keys[$container_name]);
            }
        }
    }    
    $mapped_values = array_intersect_key($all_values, $storage_keys);

    ?>
    <div class="wrap">
    <form id="company-info-form" method="post" class="dibraco">
        <h1>Company Info</h1>
        <?php wp_nonce_field('update_company_info_nonce', 'company_info_nonce'); 
        FormHelper::generateField('who_cares', ['type' => 'valueinjector', 'meta_array' => $mapped_values]);
        echo FormHelper::generateVisualSection('location-main-form', ['fields' => $template_fields]);
        FormHelper::generateField('who_cares', ['type' => 'injectionend']);
        ?>
        <p><button type="submit" name="submit_action" value="save_company_info_options" class="button button-primary">Save Company </button></p>
    </form>
     </div>
    <?php
}
function handle_save_company_info() {
    if (!isset($_POST['company_info_nonce']) || !wp_verify_nonce($_POST['company_info_nonce'], 'update_company_info_nonce')) {
        return;
    }

    $all_fields = initialize_dafields();
    $storage_keys = dibraco_extract_nested_arrays_test($all_fields);
    $data_to_save = [];

   foreach ($storage_keys as $container_name => $field_name) {
          if (!is_array($field_name)){
               $data_to_save[$field_name] = $_POST[$field_name];
          }
          if (is_array($field_name)){
             foreach ($field_name as $field_name => $field_value){
                $data_to_save[$container_name][$field_name] = $_POST[$field_value];
             }
               if ($container_name === 'hours_of_operation') {
            $day_map = get_dibraco_day_map();
            if ($data_to_save['hours_of_operation']['open_247'] === '1') {
                foreach ($day_map as $full => $abbr) {
                    $data_to_save['hours_of_operation'][$abbr.'_open_hour'] = '';
                    $data_to_save['hours_of_operation'][$abbr.'_close_hour'] = '';
                    $data_to_save['hours_of_operation']['open_'.$full] = '1';
                }
            } else {
                foreach ($day_map as $full => $abbr) {
                    if ($data_to_save['hours_of_operation']['open_'.$full] !== '1') {
                        $data_to_save['hours_of_operation'][$abbr.'_open_hour'] = '';
                        $data_to_save['hours_of_operation'][$abbr.'_close_hour'] = '';
                    }
                }
            }
        }
    }
}
   $show_address_on_org ="1";
    $status = get_option('company_info')['locations_areas_status'];
    if ($status === 'multi_locations' || $status === 'both') {
        $show_address_on_org = get_option('company_info')['show_address_on_org'] ??"1";
    }
           if ($show_address_on_org ==='1'){
               if ($data_to_save['place_id'] !== '') {
                    $place_id = $data_to_save['place_id'];
                    $data_to_save['gmb_map_link'] = 'https://www.google.com/maps/place/?q=place_id:' . urlencode($place_id);
                }

            $normal_url = '';
            $street_url = '';

            if (!empty($data_to_save['city']) && !empty($data_to_save['state'])) {
                $geo = get_lat_long_from_osm_2(
                $data_to_save['street_address'],
                $data_to_save['city'],
                $data_to_save['state'],
                $data_to_save['zipcode']
                );

                $street_address = $data_to_save['street_address'];
              if (!empty($data_to_save['street_address_2'])) {
                 $street_address = "{$data_to_save['street_address']} {$data_to_save['street_address_2']}";
              }

        $address_query = implode(', ', array_filter([
            $data_to_save['name'],
            $street_address,
            $data_to_save['city'],
            $data_to_save['state'],
            $data_to_save['zipcode'],
        ]));

        $base_url   = 'https://maps.google.com/maps?q=' . urlencode($address_query);
        $normal_url = $base_url . '&z=14';
        $coords_query = '';

        if ($geo) {
            $data_to_save['latitude']  = $geo['lat'];
            $data_to_save['longitude'] = $geo['long'];

            if (isset($geo['boundingbox'])) {
                $polygon_json = json_encode($geo['boundingbox']);
                $data_to_save['bounding_box'] = $polygon_json;
            }

            $coords_query = $geo['lat'] . ',' . $geo['long'];
        }

        if ($coords_query !== '') {
            $streetview_params = [
                'q'      => $address_query,
                'cbll'   => $coords_query,
                'cbp'    => '12,235,,0,5',
                'layer'  => 'c',
                'output' => 'svembed',
            ];
            $street_url = 'https://maps.google.com/maps?' . http_build_query($streetview_params);
            }
        }
            $data_to_save['normal_map'] = $normal_url;
            $data_to_save['street_map'] = $street_url;
        }
    update_option('company_info', $data_to_save);

    $redirect_url = admin_url('admin.php?page=dibraco-relationships-company-info&status=updated');
    wp_safe_redirect($redirect_url);
    exit;
}


function get_lat_long_from_osm_2($street = '', $city = '', $state = '', $postal_code = '', $country ='') {
    $params = [];
    if (empty($city) || empty($state)) {
        error_log('OSM Geocoding: Function failed because city or state was empty.');
        return null;
    }
    if (!empty($street)) $params['street'] = urlencode($street);
    if (!empty($city)) $params['city'] = urlencode($city);
    if (!empty($state)) $params['state'] = urlencode($state);
    if (!empty($postal_code)) $params['postalcode'] = urlencode($postal_code);
    if (!empty($country)) $params['country'] = urlencode($country);
    if (empty($country)) $params['country'] = 'US';
    
    $query_string = http_build_query($params);
    
    // Always ask for the polygon data, regardless of whether it's a location or service area.
    $url = "https://nominatim.openstreetmap.org/search?{$query_string}&format=jsonv2";

    $args = [
        'headers' => [
            'User-Agent' => 'WordPress/' . home_url(),
        ]
    ];
    $response = wp_remote_get($url, $args);
    
    if (is_wp_error($response)) {
        error_log('OSM Geocoding WP_Error: ' . $response->get_error_message());
        return null;
    }

    $response_body = wp_remote_retrieve_body($response);

    if (!empty($response_body)) {
        $data = json_decode($response_body);
        if (!empty($data) && !empty($data[0])) {
                $result = [
                'lat'  => $data[0]->lat,
                'long' => $data[0]->lon,
                'boundingbox' => $data[0]->boundingbox
            ];
            if (isset($data[0]->geojson) && isset($data[0]->geojson->coordinates)) {
                // If it does, add the polygon data to our result.
                $result['polygon'] = $data[0]->geojson->coordinates;
            }

            return $result;
        }
    }
    error_log('OSM Geocoding Error: Failed to get valid coordinates from response. Response Body: ' . $response_body);
    return null;
}
function company_info_address_shortcode() {
    $company_info = get_option('company_info');
    $street_address = $company_info['street_address'];
    $street_address_2 = $company_info['street_address_2'];
    $city = $company_info['city'];
    $state = $company_info['state'];
    $zipcode = $company_info['zipcode'];
    $address = "{$street_address}";
    if (!empty($street_address_2)) {
        $address .= " {$street_address_2}";
    }
    $address .= "<br>{$city}, {$state} {$zipcode}";
    return $address;
}

function company_info_phone_number_shortcode() {
    $company_info = get_option('company_info');
    $phone_number = $company_info['phone_number']; 
    $formatted_phone_number = preg_replace('/[^0-9]/', '', $phone_number); 
    $phone_link = "tel:+1-{$formatted_phone_number}";
    return '<a href="' . $phone_link . '">' . $phone_number . '</a>';
}

function company_info_city_state_shortcode() {
    $company_info = get_option('company_info');
    $city = $company_info['city'];
    $state = $company_info['state'];
    return "{$city}, {$state}";
}

function company_info_street_address_shortcode() {
    $company_info = get_option('company_info');
    $street_address = $company_info['street_address'];
    $street_address_2 = $company_info['street_address_2'];

    $address = "{$street_address}";
    if (!empty($street_address_2)) {
        $address .= " {$street_address_2}";
    }
    return $address;
}

function company_info_email_address_shortcode() {
    $company_info = get_option('company_info');
    $email_address = $company_info['email_address']; // Access 'contact_info' field
    $email_link = 'mailto:' . $email_address;
    return '<a href="' . $email_link . '">' . $email_address . '</a>';
}

function company_info_phone_url_shortcode() {
    $company_info = get_option('company_info');
    $phone_number = $company_info['phone_number']; 
    $formatted_phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    return "tel:+1-{$formatted_phone_number}";
}
function company_logo_shortcode() {
    $company_info = get_option('company_info');
    $logo_id = $company_info['company_logo']; 
    
    $logo_url = wp_get_attachment_url($logo_id);
        if ($logo_url) {
        return '<img src="' . $logo_url . '" alt="Company Logo" />';
    }
    
    return '';
}
function company_info_logo_url_shortcode() {
    $company_info = get_option('company_info');
    $logo_id = $company_info['company_logo']; 
        $logo_url = wp_get_attachment_url($logo_id);
    
    if ($logo_url) {
        return $logo_url;
    }
    return ''; 
}


function register_company_info_shortcodes() {
    $company_info = get_option('company_info',[]);

    add_shortcode('company_info_address', 'company_info_address_shortcode');
    add_shortcode('company_info_phone', 'company_info_phone_number_shortcode');
    add_shortcode('company_info_city_state', 'company_info_city_state_shortcode');
    add_shortcode('company_info_street_address', 'company_info_street_address_shortcode');
    add_shortcode('company_info_email', 'company_info_email_address_shortcode');
    add_shortcode('company_info_phone_url', 'company_info_phone_url_shortcode');
    add_shortcode('company_info_logo_url', 'company_info_logo_url_shortcode');
    add_shortcode('company_logo_url', 'company_info_logo_url_shortcode');
    add_shortcode('company_logo', 'company_logo_shortcode');
    foreach ($company_info as $key => $value) {
        if ($key !== 'hours_of_operation') { 
            add_shortcode("company_info_{$key}", function() use ($key, $company_info) {
                return $company_info[$key]; // Directly return the value of the field
            });
        }
    }
}
add_action('after_setup_theme', 'register_company_info_shortcodes');   


function allow_shortcodes_in_schema_and_paper( $data ) {
    if ( is_array( $data ) ) {
        $data = array_map( 'allow_shortcodes_in_schema_and_paper', $data );
    } else if ( is_string( $data ) ) {
        $data = do_shortcode( $data );
    }
    return $data;
}

function render_shortcodes_in_rank_math_preview( $text ) {
    $text = do_shortcode( $text );
    $text = apply_filters( 'the_content', $text );
    $text = str_replace( ']]>', ']]&gt;', $text );
    return $text;
}
// Filters for frontend and og:title and twitter:title
add_filter( 'rank_math/frontend/title', 'render_shortcodes_in_rank_math_preview', 9999 );
add_filter( 'rank_math/opengraph/title', 'render_shortcodes_in_rank_math_preview', 9999 );
add_filter( 'rank_math/twitter/title', 'render_shortcodes_in_rank_math_preview', 9999 );

// Filters for schema and JSON-LD
add_filter( 'rank_math/schema/before_save_data', 'allow_shortcodes_in_schema_and_paper', 9999 );
add_filter( 'rank_math/json_ld', 'allow_shortcodes_in_schema_and_paper', 9999 );

// Filters for frontend description
add_filter( 'rank_math/frontend/description', 'do_shortcode', 9999 );
add_filter( 'rank_math/paper/auto_generated_description', 'apply_shortcodes_to_auto_generated_description', 9999 );

function convert_array_to_string_before_schema_filter( $data ) {
    if ( is_array( $data ) ) {
        $data = implode( '', $data );
    }
    return $data;
}


function render_migration_page() {
    // Handle form submission to trigger the company info migration
    if (isset($_POST['update_options']) && isset($_POST['company_info_nonce']) && wp_verify_nonce($_POST['company_info_nonce'], 'update_company_info_nonce')) {
        // Trigger the company info migration
        $update_results = migrate_company_info_options();
        $message = 'Update Results:<br>';
        foreach ($update_results as $result) {
            $message .= $result . '<br>';
        }
        echo '<div class="updated"><p>' . $message . '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Company Info Update</h1>
        <form method="POST">
            <?php wp_nonce_field('update_company_info_nonce', 'company_info_nonce'); ?>
            <input type="submit" name="update_options" class="button button-primary" value="Update Company Info Options" />
        </form>
    </div>
    <?php
}

function migrate_company_info_options() {
    $update_results = [];

    // List of old option names for non-hours company info
    $old_options = [
        'fax_number',
        'addy_country',
        'google_map_embed',
        'name',
        'phone_number',
        'email_address',
        'street_address',
        'street_address_2',
        'city',
        'state',
        'zipcode',
        'facebook',
        'gmb',
        'yelp',
        'instagram',
        'twitter',
        'bbb',
        'linkedin',
        'pinterest',
        // Add any other fields that need migration here
    ];

    // Initialize an array to hold the new company info
    $company_info = [];

    // Migrate non-hour fields (company info and social media links)
    foreach ($old_options as $old_option) {
        $value = get_option($old_option);
        if ($value !== false) {
            // Store each option directly in the company_info array
            $company_info[$old_option] = $value;

            // Log that the option was migrated
            $update_results[] = "Migrated: {$old_option} to company_info array.";

            // Delete the old option after migration
            delete_option($old_option);
        } else {
            $update_results[] = "Failed to migrate: {$old_option} (no value found).";
        }
    }

    $days_of_week = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    $hours_of_operation = [];

    foreach ($days_of_week as $day) {
        $open_hour = get_option("{$day}_open_hour");
        $close_hour = get_option("{$day}_close_hour");

        // If there's no open hour, skip this day
        if ($open_hour && $close_hour) {
            $hours_of_operation[$day] = [
                'open_hour' => $open_hour,
                'close_hour' => $close_hour,
            ];

            // Delete old options related to this day (open, close, AM/PM)
            delete_option("{$day}_open_hour");
            delete_option("{$day}_close_hour");
            delete_option("{$day}_open_ampm");
            delete_option("{$day}_close_ampm");

            $update_results[] = "Migrated: {$day} hours to hours_of_operation.";
        } else {
            $update_results[] = "Failed to migrate: {$day} (missing open/close hours).";
        }
    }

    // Migrate latitude and longitude
    $latitude = get_option('latitude');
    $longitude = get_option('longitude');
    if ($latitude !== false && $longitude !== false) {
        $company_info['latitude'] = $latitude;
        $company_info['longitude'] = $longitude;

        // Delete the old latitude and longitude options
        delete_option('latitude');
        delete_option('longitude');

        $update_results[] = 'Migrated: latitude and longitude to company_info array.';
    } else {
        $update_results[] = 'Failed to migrate: latitude/longitude (missing values).';
    }

    // Add hours of operation to the company info array
    if (!empty($hours_of_operation)) {
        $company_info['hours_of_operation'] = $hours_of_operation;
    }

    // Save the migrated data as a single option
    if (!empty($company_info)) {
        update_option('company_info', $company_info);
        $update_results[] = 'Successfully updated: All company info migrated to company_info option.';
    }

    return $update_results;
}
