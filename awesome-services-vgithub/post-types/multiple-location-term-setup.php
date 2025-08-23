<?php

function render_location_meta_box($object, $box) {
    $locations_context = $box['args']['locations_context'];
    $locations_taxonomy = $locations_context['taxonomy'];
    $location_schema = $locations_context['schema'];
    $current_location_term_id = '';
    $current_location_term_name = '';
    $post_id = null;

    if ($object instanceof \WP_Post) {
        $post = $object;
        $post_id = $post->ID;
        $current_location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
        if ($current_location_term_id !== '') {
            $term_object = get_term_by('id', $current_location_term_id, $locations_taxonomy);
            $current_location_term_name = $term_object->name;
        }
    } elseif ($object instanceof \WP_Term) {
        $term = $object;
        $current_location_term_id = $term->term_id;
        $current_location_term_name = $term->name;
    }

    $nonce = wp_create_nonce('dibraco_save_location_meta');
    ?>

    <div class="location-term-form">
        <input type="hidden" name="dibraco_location_meta_nonce" value="<?= $nonce; ?>" />
        <?php
        if ($current_location_term_id !== '') {
            $prefix = 'location_';
            $location_sections = initialize_dafields($prefix);
            $hours_of_operation_field_keys = get_hours_array_keys();
            $social_media_field_keys = get_option('custom_social_media_keys');
            $hours_of_operation_data = get_term_meta($current_location_term_id, 'hours_of_operation', true) ?? [];
            $social_media_data = get_term_meta($current_location_term_id, 'social_media', true) ?? [];
            $all_location_meta = [];

            foreach ($location_sections as $section_key => $section_data) {
                if ($section_key === 'hours_of_operation') {
                    foreach ($hours_of_operation_field_keys as $field_key) {
                        $all_location_meta[$field_key] = $hours_of_operation_data[$field_key] ?? '';
                    }
                    continue;
                } elseif ($section_key === 'social_media') {
                    foreach ($social_media_field_keys as $field_key => $no_need) {
                        $all_location_meta[$field_key] = $social_media_data[$field_key] ?? '';
                    }
                    continue;
                } else {
                        $fields_in_section = $section_data['fields'];
                        foreach ($fields_in_section as $field_key => $field_data) {
                         $meta_value = get_term_meta($current_location_term_id, $field_key, true);
                        if ($meta_value === '') {
                            $meta_value = $field_data['value'] ?? '';
                        }
                        $all_location_meta[$field_key] = $meta_value;
                    }
                }
            }
            $individual_fields = ['location_logo', 'schema'];
            $enabled_context_names = get_option('enabled_context_names');
            if (in_array('employee', $enabled_context_names)) {
                $individual_fields[] = 'location_manager';
            }
            foreach ($individual_fields as $field) {
                $meta_value = get_term_meta($current_location_term_id, $field, true);
                if ($field === 'location_logo' && $meta_value === '') {
                    $meta_value = get_option('company_info')['company_logo'];
                } elseif ($field === 'schema' && $meta_value === '') {
                    $meta_value = $location_schema;
                }
                $all_location_meta[$field] = $meta_value;
            }
            echo FormHelper::generateField('doesnt_matter', ['type' => 'starttracking', 'meta_array' => $all_location_meta]);
            ?>
            <div id="image-fields" class="dibraco-section">
                <?= FormHelper::generateField('location_logo', ['type' => 'image']) ?>
                </div>
				<div class='indi-fields'>
                    <?php
                    echo FormHelper::generateField('schema', ['type' => 'select', 'options' => get_company_schema_types()]);
                    if (in_array('employee', $enabled_context_names)) {
                        echo FormHelper::generateField('location_manager', ['type' => 'select', 'options' => get_employee_posts_for_select_options()]);
                    }
                    if (!$post_id) {
                        echo "<label style='display:block;'>Location Link URL</label>";
                        echo "<div style='background-color:lightgrey; font-size:1.2em; padding:3px;' class='dibraco-text'>" . get_term_meta($current_location_term_id, 'location_link_url', true) . "</div>";
                        $location_post_id_temp = get_term_meta($current_location_term_id, 'location_post_id', true);
                        $description_value = '';
                        if (!empty($location_post_id_temp)){
                            $location_post_id_temp = (int)$location_post_id_temp;
                            $description_value = get_post_meta($location_post_id_temp, 'da_about_blurb', true);
                        }
                        echo FormHelper::generateField('da_about_blurb', ['type' => 'textarea', 'value' => $description_value]);
                    }
                    ?>
				</div>
            <?php
            foreach ($location_sections as $section_key => $section_data) {
             echo FormHelper::generateVisualSection($section_key, $section_data);
            }

            if (!$post_id) {
                $related_unique_contexts = $locations_context['related_unique_contexts'];
                if (!empty($related_unique_contexts)) {
                    render_related_unique_context_tables($related_unique_contexts, $current_location_term_id);
                }

                $related_type_contexts = $locations_context['related_type_contexts'];
                if (!empty($related_type_contexts)) {
                    render_related_type_context_tables($related_type_contexts, $current_location_term_id);
                }
            }

            echo FormHelper::generateField('trackerend', ['type' => 'endtracking']);
        } else {
            echo '<p>Please select a location term for this post to view and edit its details. The location term selection box is usually provided by WordPress in a separate panel.</p>';
        }
        echo "</div>";
}


function dibraco_save_location_meta($post_id, $locations_taxonomy) {
    if (!dibraco_verify_post_save_request('dibraco_location_meta_nonce', 'dibraco_save_location_meta')){return;}
    $new_term_id = $_POST["{$locations_taxonomy}_term"];
    dibraco_enforce_one_connector_term_per_connector_post($post_id, $new_term_id, $locations_taxonomy, 'location_post_id', 'location_link_url');
    if ($new_term_id !== '') {
    handle_save_location_term_meta($new_term_id, $locations_taxonomy);
    }
}

function handle_save_location_term_meta($term_id, $taxonomy) {
if (!dibraco_verify_post_save_request('dibraco_location_meta_nonce', 'dibraco_save_location_meta')){return;}
    $tracking_flag = false;
    $submitted_data = $_POST;
    $hours_of_operation_field_keys = get_hours_array_keys();
    $day_map = get_dibraco_day_map();
    $social_media_field_keys = get_option('custom_social_media_keys');
    $meta_to_save = [];
    $hours_of_operation_data = [];
    $social_media_data = [];
    foreach ($submitted_data as $fieldname => $value) {
        if ($fieldname === 'tracking_started') {
            $tracking_flag = true;
            continue;
        }
        if (!$tracking_flag) {
            continue;
        }
        if ($fieldname === 'tracking_finished') {
            break;
        }
        if (in_array($fieldname, $hours_of_operation_field_keys)) {
            $hours_of_operation_data[$fieldname] = $value;
        } elseif (in_array($fieldname, $social_media_field_keys)) { 
            $social_media_data[$fieldname] = $value;
        } else {
        if ($fieldname !== 'tracking_started' && $fieldname !== 'tracking_finished' && $fieldname !== 'dibraco_location_meta_nonce' && $fieldname !== '_wp_http_referer' && !str_starts_with($fieldname, 'submit')) {
                $meta_to_save[$fieldname] = $value;
            }
        }
    }  
    $linked_post_id = get_term_meta($term_id, 'location_post_id', true);
    if (!empty($linked_post_id)) {
        $linked_post_id = (int)$linked_post_id;
        $blurb_value = $meta_to_save['da_about_blurb']; 
        update_post_meta($linked_post_id, 'da_about_blurb', $blurb_value);
    }
    unset($meta_to_save['da_about_blurb']); 


    if ($hours_of_operation_data['open_247'] === '1') {
        foreach ($day_map as $full => $abbr) {
            $hours_of_operation_data["open_{$full}"] = '1';
            $hours_of_operation_data["{$abbr}_open_hour"] = '';
            $hours_of_operation_data["{$abbr}_close_hour"] = '';
        }
    } else {
        foreach ($day_map as $full => $abbr) {
            if ($hours_of_operation_data["open_{$full}"] !== '1') {
                $hours_of_operation_data["{$abbr}_open_hour"] = '';
                $hours_of_operation_data["{$abbr}_close_hour"] = '';
            }
        }
    }
    update_term_meta($term_id, 'hours_of_operation', $hours_of_operation_data);
    update_term_meta($term_id, 'social_media', $social_media_data);
    foreach ($meta_to_save as $key => $value) {
        update_term_meta($term_id, $key, $value);
    }

    $street_address = $meta_to_save['street_address'];
    $city = $meta_to_save['city'];
    $state = $meta_to_save['state'];
    $zipcode = $meta_to_save['zipcode'];

    if (!empty($city) && !empty($state)) {
        $geo = get_lat_long_from_osm_2($street_address, $city, $state, $zipcode);
        if ($geo && isset($geo['lat']) && isset($geo['long'])) {
            update_term_meta($term_id, 'latitude', $geo['lat']);
            update_term_meta($term_id, 'longitude', $geo['long']);
        }
    }
	$location_term = get_term($term_id);
	$kml_content = generate_custom_kml_file([$term_id], esc_html($location_term->name));
    update_term_meta($term_id, 'kml_content', $kml_content);
}


function service_areas_shortcode($atts) {
    $atts = shortcode_atts(['mode' => 'comma'], $atts);
    $status = get_option('locations_areas_status');
    $mode = $atts['mode'];
    if ($status === 'none') {
        return '';
    }
    $enabled = get_option('enabled_connector_contexts');
    switch ($status) {
        case 'multi_locations':
            $location_tax = $enabled['locations']['taxonomy'];
            return render_all_terms($location_tax, 'location_link_url', $mode);
        case 'multi_areas':
            $service_area_tax = $enabled['service_areas']['taxonomy'];
            return render_all_terms($service_area_tax, 'service_area_link_url', $mode);
        case 'both':
             $location_tax = $enabled['locations']['taxonomy'];
             $service_area_tax = $enabled['service_areas']['taxonomy'];
            return render_combined_mode($location_tax, $service_area_tax, $mode);
        default:
            return '';
    }
}

function render_all_terms($taxonomy, $meta_key, $mode) {
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    $links = [];
    foreach ($terms as $term) {
        $links[] = generate_connector_term_links($term, $meta_key);
    }
    return format_links_output($links, $mode);
}
function generate_connector_term_links($term, $meta_target) {
    $term_url = get_term_meta($term->term_id, $meta_target, true);
    $current_url = get_permalink(get_the_ID());
    if ($term_url && trailingslashit($term_url) !== trailingslashit($current_url)) {
        return "<a href='" . esc_url($term_url) . "'>" . esc_html($term->name) . "</a>";
    }
    return esc_html($term->name);
}
function render_combined_mode($location_tax, $service_area_tax, $mode) {
    $location_term_id = da_get_location_term_or_default(get_the_ID());
    if ($location_term_id) {
        return render_location_specific($location_term_id, $location_tax, $service_area_tax, $mode);
    }
    return render_global_combined($location_tax, $service_area_tax, $mode);
}

function render_location_specific($location_term_id, $location_tax, $service_area_tax, $mode) {
    $assignments = get_option('act_to_lct_assignments', []);
    $associated_ids = $assignments[(int)$location_term_id] ?? [];
    if (empty($associated_ids)) {
        return render_global_combined($location_tax, $service_area_tax, $mode);
    }
    $service_area_links = [];
    foreach ($associated_ids as $id) {
        $term = get_term($id, $service_area_tax);
        $service_area_links[] = generate_connector_term_links($term, 'service_area_link_url');
    }
    $location_term = get_term($location_term_id, $location_tax);
    $location_link = generate_connector_term_links($location_term, 'location_link_url');
    $final_links = [];
    shuffle($service_area_links);
    if ($mode === 'list') {
        $final_links = array_merge([$location_link], $service_area_links);
    } else {
        $final_links = array_merge($service_area_links, [$location_link]);
    }
    return format_links_output($final_links, $mode);
}

function render_global_combined($location_tax, $service_area_tax, $mode) {
    $max_total_items = 20;
    $locations = get_terms(['taxonomy' => $location_tax, 'hide_empty' => true]);
    $location_links = [];
    $location_name_lookup = [];
    foreach ($locations as $location) {
        $location_links[] = generate_connector_term_links($location, 'location_link_url');
        $location_name_lookup[strtolower($location->name)] = true;
    }
    $service_area_limit = $max_total_items - count($location_links);
    $service_area_links = [];
    if ($service_area_limit > 0) {
        $all_service_areas = get_terms(['taxonomy' => $service_area_tax, 'hide_empty' => false]);
        foreach ($all_service_areas as $service_area) {
            if (!isset($location_name_lookup[strtolower($service_area->name)])) {
                $service_area_links[] = generate_connector_term_links($service_area, 'service_area_link_url');
            }
            if (count($service_area_links) >= $service_area_limit) {
                break;
            }
        }
    }
$final_links = [];
shuffle($service_area_links);
    if ($mode === 'list') {
        $final_links = array_merge($location_links, $service_area_links);
    } else {
        $final_links = array_merge($service_area_links, $location_links);
    }
    return format_links_output($final_links, $mode);
}

function format_links_output($links, $mode = 'comma') {
    if ($mode === 'list') {
        $html = '<div class="marketcitylist"><ul>';
        foreach ($links as $link) {
            $html .= '<li>' . $link . '</li>';
        }
        return $html . '</ul></div>';
    }
    $count = count($links);
    if ($count === 0) {return ''; }
    if ($count === 1) {return $links[0]; }
    if ($count === 2) {return implode(' and ', $links); }
    $last = array_pop($links);
    return implode(', ', $links) . ", and {$last}";
}
add_shortcode('service_areas', 'service_areas_shortcode');

function da_get_location_term_or_default($post_id, $location_slug = '') {
	$status = get_option('locations_areas_status');
	if (($status !== 'both') && ($status !== 'multi_locations')) {
		return null;
	}
	$connector_contexts = get_option( 'enabled_connector_contexts');
	$location_tax = $connector_contexts['locations']['taxonomy'];
    $service_area_tax = ''; 
	if ($status === 'both'){
	$service_area_tax = $connector_contexts['service_areas']['taxonomy'];
    }
	if ($location_slug) {
		$term = get_term_by('slug', $location_slug, $location_tax);
		if ($term && !is_wp_error($term)) {
			return(int)$term->term_id;
		}
	}
	$loc_term = dibraco_get_current_term_id_for_post($post_id, $location_tax);
    if ($loc_term !=='') {
        return $loc_term;
    }
    if ($service_area_tax !== ''){
	$area_term = dibraco_get_current_term_id_for_post($post_id, $service_area_tax);
	if ($area_term !=='') {
		$loc_term = get_term_meta($area_term, 'area_parent_location_term', true) ?? '';
		if ($loc_term !=='') {
			return $loc_term;
		    }
	    }
    }
	return null;
}
function register_dynamic_social_media_link_shortcodes() {
    $social_keys = get_option('custom_social_media_keys'); 
    foreach ($social_keys as $key) {
        add_shortcode($key . '_link', function($atts) use ($key) {
            $atts = shortcode_atts(['loc' => ''], $atts);
            $location_slug = $atts['loc'];
            $post_id = get_the_ID();
            $link = ''; 
            $location_term_id = da_get_location_term_or_default($post_id, $location_slug);
            if ($location_term_id) {
               $term_social_media_data = get_term_meta($location_term_id, 'social_media', true) ?? [];
               $link = $term_social_media_data[$key] ?? ''; 
            }
             if (empty($link)) {
                  $options = get_option('company_info');
                $company_social_media_data = $options['social_media'] ?? []; 
                $link = $company_social_media_data[$key] ?? '';
        }
        return $link;
    });
}
}
add_action('init', 'register_dynamic_social_media_link_shortcodes');


function da_map_embed_shortcode($atts) {
    $atts = shortcode_atts(['loc' => ''], $atts);
    $location_slug = $atts['loc'];  
    $post_id = get_the_ID();    
    $location_term_id = da_get_location_term_or_default($post_id, $location_slug);
     if ($location_term_id) {
            $term_map_embed = get_term_meta($location_term_id, 'google_map_embed', true);
        return $term_map_embed;
        }
    return get_option('company_info')['google_map_embed'];
}
add_shortcode('da_map_embed', 'da_map_embed_shortcode');


function da_reviews_shortcode($atts) {
    $atts = shortcode_atts(['loc' => ''], $atts);
    $location_slug = $atts['loc'];
    $post_id = get_the_ID();    
    $location_term_id = da_get_location_term_or_default($post_id, $location_slug);
        if ($location_term_id) {
            $term_reviews = get_term_meta($location_term_id, 'location_reviews_shortcode', true);
            return $term_reviews;
        }
     return get_option('company_info')['company_reviews_shortcode'];
}
add_shortcode('da_reviews', 'da_reviews_shortcode');
function da_get_grouped_opening_hours($hours) {
    $days_map = get_dibraco_day_map();
    $logic_map = [];
    foreach ($days_map as $day_full => $day_short) {
        if ($hours['open_' . $day_full] === '1') {
            $opens = da_format_time_ampm($hours[$day_short . '_open_hour']);
            $closes = da_format_time_ampm($hours[$day_short . '_close_hour']);
            $logic_map[ucfirst($day_short)] = "{$opens} - {$closes}";
        } else {
            $logic_map[ucfirst($day_short)] = 'Closed';
        }
    }
    $grouped_ranges = [];
    $current_range_key = null;
    foreach ($logic_map as $day => $range) {
        if ($range !== $current_range_key) {
            $grouped_ranges[] = ['days' => [$day], 'range' => $range];
            $current_range_key = $range;
        } else {
            $last_key = count($grouped_ranges) - 1;
            $grouped_ranges[$last_key]['days'][] = $day;
        }
    }
    return $grouped_ranges;
}
function da_display_opening_hours($atts) {
    $atts = shortcode_atts(['loc' => '', 'output' => 'ranges'], $atts);
    $location_slug = $atts['loc'];
    $location_term_id = da_get_location_term_or_default(get_the_ID(), $location_slug);

    if ($location_term_id) {
        $hours = get_term_meta($location_term_id, 'hours_of_operation', true);
    } else {
        $hours = get_option('company_info')['hours_of_operation'];
    }

    if (empty($hours)) { return ''; }

    if ($hours['open_247'] === "1") {
        return 'Open 24/7';
    }

    $output_lines = [];
    if (strtolower($atts['output']) === 'list') {
        $days_map = get_dibraco_day_map();
        foreach ($days_map as $day_full => $day_short) {
            $day_label = ucfirst($day_short);
            if ($hours['open_' . $day_full] === '1') {
                $opens = da_format_time_ampm($hours[$day_short . '_open_hour']);
                $closes = da_format_time_ampm($hours[$day_short . '_close_hour']);
                $output_lines[] = "<strong>{$day_label}</strong><br>{$opens} - {$closes}";
            } else {
                $output_lines[] = "<strong>{$day_label}:</strong> Closed";
            }
        }
    } else { 
        $grouped_hours = da_get_grouped_opening_hours($hours);
        foreach ($grouped_hours as $group) {
            $first_day = reset($group['days']);
            $day_label = (count($group['days']) > 1) ? $first_day . ' - ' . end($group['days']) : $first_day;
            
            if ($group['range'] === 'Closed') {
                $output_lines[] = "<strong>{$day_label}:</strong> {$group['range']}";
            } else {
                $output_lines[] = "<strong>{$day_label}</strong><br>{$group['range']}";
            }
        }
    }
    return implode('<br>', $output_lines);
}
function da_format_time_ampm($time_24) {
    if (empty($time_24)) return '';
    $timestamp = strtotime($time_24);
    return $timestamp ? date('g:i A', $timestamp) : 'N/A';
}
add_shortcode('da_opening_hours', 'da_display_opening_hours');

function da_logo_url_shortcode($atts) {
	$atts = shortcode_atts(['loc' => ''], $atts);
	$location_slug = $atts['loc'];
	$post_id = get_the_ID();
	$location_term_id = da_get_location_term_or_default( $post_id, $location_slug);
	if ($location_term_id) {
		$location_logo_id = get_term_meta( $location_term_id, 'location_logo', true);
		if ($location_logo_id !=='') {
			$logo_url = wp_get_attachment_url((int)$location_logo_id);
			if (!empty($logo_url)) {
			return $logo_url;
		}
	}
	}
	$company_logo = get_option('company_info')['company_logo'];
	if ($company_logo !==''){
	return wp_get_attachment_url((int)$company_logo);
	}
	return;
}
add_shortcode('da_logo_url', 'da_logo_url_shortcode');

function da_get_address_data($location_term_id = '') {
    $address_keys = ['street_address', 'street_address_2', 'city', 'state', 'zipcode', 'addy_country'];
    $address_parts = [];
    if (!empty($location_term_id)) {
        foreach ($address_keys as $key) {
            $address_parts[$key] = get_term_meta($location_term_id, $key, true);
        }
          $validation_fields = [
            $address_parts['street_address'],
            $address_parts['city'],
            $address_parts['state']
        ];
        $valid_field_count = count(array_filter($validation_fields));
        if ($valid_field_count < 2) {
            $location_term_id = '';
        }
    }
    if (empty($location_term_id)) {
        $company_info = get_option('company_info');
        foreach ($address_keys as $key) {
            $address_parts[$key] = $company_info[$key];
        }
    }
    return $address_parts;
}

function da_address_shortcode($atts) {
    $atts = shortcode_atts(['loc' => '', 'country' => ''], $atts);
    $location_slug = $atts['loc'];
    $post_id = get_the_ID();
    $location_term_id = da_get_location_term_or_default($post_id, $location_slug);
    $parts = da_get_address_data($location_term_id);
    $output = '';
    if (!empty($parts['street_address'])) {
        if (!empty($parts['street_address_2']) && strlen($parts['street_address_2']) < 10) {
            $output .= $parts['street_address'] . ' ' . $parts['street_address_2'] . '<br>';
        } else {
            $output .= $parts['street_address'] . '<br>';
            if (!empty($parts['street_address_2'])) {
                $output .= $parts['street_address_2'] . '<br>';
            }
        }
    }
    if (!empty($parts['city']) && !empty($parts['state'])) {
        $output .= $parts['city'] . ', ' . $parts['state'];
        if (!empty($parts['zipcode'])) {
            $output .= ' ' . $parts['zipcode'];
        }
        $output .= '<br>';
    }
    if ($atts['country'] === 'yes' && !empty($parts['addy_country'])) {
        $output .= $parts['addy_country'];
    }
    return $output; 
}
add_shortcode('da_address', 'da_address_shortcode');


function parse_us_telephone_number($telephone) {
    $num = preg_replace('/[^0-9]/', '', $telephone);
    if (strlen($num) === 10) { $num = '1' . $num; }
        if (strlen($num) !== 11 || substr($num, 0, 1) !== '1') {return null;}
        $area_code = substr($num, 1, 3);
        $toll_free_codes = ['800', '888', '877', '866', '855', '844', '833'];
        return ['country' => '1', 'area' => $area_code, 'prefix' => substr($num, 4, 3), 'line' => substr($num, 7), 'is_toll_free' => in_array($area_code, $toll_free_codes, true)];
}

if (!function_exists('format_telephone_for_display')) {
    function format_telephone_for_display($telephone) {
        $parts = parse_us_telephone_number($telephone);
        if (!$parts) return $telephone;
        if ($parts['is_toll_free']) {
            return "{$parts['country']}-{$parts['area']}-{$parts['prefix']}-{$parts['line']}";
        }
        return "{$parts['area']}-{$parts['prefix']}-{$parts['line']}";
    }
}

if (!function_exists('format_telephone_for_link')) {
    function format_telephone_for_link($telephone) {
        $parts = parse_us_telephone_number($telephone);
        if (!$parts) return 'tel:' . preg_replace('/[^0-9+]/', '', $telephone);
        return "tel:+{$parts['country']}{$parts['area']}{$parts['prefix']}{$parts['line']}";
    }
}

if (!function_exists('display_telephone_field')) {
function display_telephone_field($atts) {
        $atts = shortcode_atts(['type' => 'telephonelink', 'loc' => ''], $atts);
        $telephone = '';
        $location_term_id = da_get_location_term_or_default(get_the_ID(), $atts['loc']);
        if ($location_term_id) {
            $telephone = get_term_meta($location_term_id, 'phone_number', true);
        }
        if (empty($telephone)) {
            $telephone = get_option('company_info')['phone_number'];
        }
        if (empty($telephone)) {
            return '';
        }
        switch ($atts['type']) {
            case 'telephoneonly':
                return format_telephone_for_display($telephone);
            case 'telephonelinkonly':
                return esc_url(format_telephone_for_link($telephone));
            default: 
                $display_number = format_telephone_for_display($telephone);
                return sprintf(
                    '<a href="%s" class="tracked-phone-link" data-analytics-event="phone_click" data-event-label="%s">%s</a>',
                    esc_url(format_telephone_for_link($telephone)),
                    esc_attr($display_number),
                    esc_html($display_number)
                );
        }
    }
foreach (['telephonelink', 'telephoneonly', 'telephonelinkonly'] as $shortcode_name) {
        add_shortcode($shortcode_name, function (array $atts = []) use ($shortcode_name) {
            return display_telephone_field(array_merge($atts, ['type' => $shortcode_name]));
        });
    }
}

function display_email_field($atts) {
    $atts = shortcode_atts(['type' => 'link', 'loc' => ''], $atts);
    $location_slug = $atts['loc'];
    $location_term_id = da_get_location_term_or_default(get_the_ID(), $location_slug);
    $email_field = '';
    if ($location_term_id) {
        $email_field = get_term_meta($location_term_id, 'email_address', true);
    }
    if (empty($email_field)) {
        $email_field = get_option('company_info')['email_address'];
    }
    if (empty($email_field)) {
        return '';
    }
    if ($atts['type'] === 'link') {
        return sprintf(
            '<a href="mailto:%1$s" data-analytics-event="email_click" data-event-label="%1$s">%2$s</a>',
            esc_attr($email_field),
            esc_html($email_field)
        );
    } else {
        return esc_html($email_field);
    }
}
add_shortcode('da_email', 'display_email_field');
