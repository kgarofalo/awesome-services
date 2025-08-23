<?php
require_once AWESOME_SERVICES_PATH . 'awesome-services.php';
function carolina_display_repeater_data_shortcode() {
    $post_id = get_the_ID();
    $repeater_data = get_post_meta( $post_id, '_list_repeater', true );
    if (!is_array( $repeater_data )) {
        return '';
    }

    $output = '';
    $has_items = false;
    if ( isset( $repeater_data['da_list_repeater_row_count'] ) && $repeater_data['da_list_repeater_row_count'] > 0 ) {
        for ( $index = 0; $index < $repeater_data['da_list_repeater_row_count']; $index++ ) {
            $item_key = "da_list_repeater[{$index}][item]";
            if (isset( $repeater_data[ $item_key ] ) && ! empty( $repeater_data[ $item_key ] ) ) {
                $has_items = true;
                break; 
            }
        }
    }
    if (! empty( $repeater_data['da_list_title'] ) || $has_items ) {
        if ( ! empty( $repeater_data['da_list_title'] ) ) {
            $output .= '<h3>' . esc_html( $repeater_data['da_list_title'] ) . '</h3>';
        }
        if ( $has_items ) {
            $output .= '<ul>';
            for ( $index = 0; $index < $repeater_data['da_list_repeater_row_count']; $index++ ) {
                $item_key = "da_list_repeater[{$index}][item]";
                if ( isset( $repeater_data[ $item_key ] ) && ! empty( $repeater_data[ $item_key ] ) ) {
                    $output .= '<li>' .  $repeater_data[ $item_key ]  . '</li>';
                }
            }
            $output .= '</ul>';
        }
    }
    return $output;
}
add_shortcode( 'dibraco_display_repeater_data', 'carolina_display_repeater_data_shortcode' );

function dibraco_get_all_field_definitions() {
    static $fields = null;

    if ($fields !== null) {
        return $fields;
    }
    $fields = [];
    $simple_fields = array_merge(
        get_banner_fields(),
        get_contact_fields(),
        get_section_title_fields(),
        get_about_fields()
    );

    $image_fields = array_merge(
        get_landscape_image_fields(),
        get_portrait_image_fields(),
		get_term_icon_field()
    );

    foreach (array_merge($simple_fields, $image_fields) as $key => $config) {
        if (strpos($key, '_lock') === false) {
            $fields[$key] = ['storage' => 'meta', 'type' => $config['type']];
        }
    }

	foreach (get_employee_fields()['employee-fields']['fields'] as $key => $config) {
        $fields[$key] = ['storage' => 'array', 'array_key' => 'employee-fields', 'type' => $config['type']];
    }

    foreach (get_certification_fields()['certification_data']['fields'] as $key => $config) {
        $fields[$key] = ['storage' => 'array', 'array_key' => 'employee_certification_data', 'type' => $config['type']];
    }
    return $fields;
}
function dibraco_master_shortcode_handler($atts) {
    $shortcode_tag = $atts['field_key'];
    $all_fields = dibraco_get_all_field_definitions();
    if (!isset($all_fields[$shortcode_tag])) {
        return "";
    }
    $post_id = get_the_ID();
    if (!$post_id) {
        return ''; 
    }
    $field_info = $all_fields[$shortcode_tag];
    $storage_type = $field_info['storage'];
    $field_type = $field_info['type'];
    $value = '';
    if ($storage_type === 'meta') {
        $value = get_post_meta($post_id, $shortcode_tag, true);
    } elseif ($storage_type === 'array') {
        $array_key = $field_info['array_key'];
        $data_array = get_post_meta($post_id, $array_key, true);
        if (is_array($data_array) && isset($data_array[$shortcode_tag])) {
            $value = $data_array[$shortcode_tag];
        }
    }
    if (empty($value)) {
        return ''; 
    }
    if ($field_type === 'image') {
        $size = isset($atts['size']) ? sanitize_text_field($atts['size']) : 'large';
        if (isset($atts['output']) && $atts['output'] === 'url') {
            $image_url = wp_get_attachment_image_url($value, $size);
            return esc_url($image_url);
        }

        return wp_get_attachment_image($value, $size);
    }

    if ($field_type === 'wysiwyg') {
        return apply_filters('the_content', $value);
    }

    return esc_html($value);
}

function dibraco_register_all_dynamic_shortcodes() {
    $all_fields = dibraco_get_all_field_definitions();
    foreach ($all_fields as $field_key => $config) {
        add_shortcode($field_key, function($atts = []) use ($field_key) {
            $atts['field_key'] = $field_key;
            return dibraco_master_shortcode_handler($atts);
        });
        if (substr($field_key, -2) === '_p') {
            $alias_key = substr($field_key, 0, -2) . '_paragraph';

            add_shortcode($alias_key, function($atts = []) use ($field_key) {
                $atts['field_key'] = $field_key;
                return dibraco_master_shortcode_handler($atts);
            });
        }
    }
}
add_action('init', 'dibraco_register_all_dynamic_shortcodes');
function display_post_taxonomy_terms_list($atts) {
    $atts = shortcode_atts(array('taxonomy' => 'category'), $atts, 'post_taxonomy_terms' );
    $taxonomy_slug = $atts['taxonomy'];
    $post_id = get_the_ID();
    $terms = get_the_terms($post_id, $taxonomy_slug);
    if ($terms && !is_wp_error($terms)) {
        echo '<ul>';
        foreach ($terms as $term) {
            echo '<li>' . esc_html($term->name) . '</li>';
        }
        echo '</ul>';
    }
    return '';
}
add_shortcode('post_taxonomy_terms', 'display_post_taxonomy_terms_list');


function dibraco_location_map_shortcode($atts) {
    $atts = shortcode_atts([
        'view'      => 'normal',
        'loc'       => '',
        'width'     => '100%',
        'height'    => '400px',
        'max_width' => '600px'
    ], $atts, 'dibraco_location_map');

    $view_type = $atts['view'];
    $location_slug = $atts['loc'];
    $post_id = get_the_ID();
    if (!$post_id) {
        return '';
    }
    if ($view_type === 'street'){
        $key = 'street_map';
    } else {
        $key = 'normal_map';
    }
    $location_term_id = da_get_location_term_or_default($post_id, $location_slug);
    $map_embed_url ='';
    if ($location_term_id) {
       $map_embed_url = get_term_meta($location_term_id, $key, true );
    } else {
      $map_embed_url = get_option('company_info')[$key]??'';
    }

if ($map_embed_url === '') { return ''; }
if ($view_type === 'satellite') {
    $map_embed_url .= '&t=k';
}
if ($view_type !=='street'){
 $map_embed_url .= '&output=embed';
}
$iframe_style = sprintf(
        'style="width: %s; height: %s; max-width: %s; border: 0;"',
        esc_attr($atts['width']),
        esc_attr($atts['height']),
        esc_attr($atts['max_width'])
    );

    $iframe_html = '<div class="dibraco-location-map"><iframe src="' . esc_url($map_embed_url) . '" ' . $iframe_style . ' allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>';
    return $iframe_html;
}
add_shortcode('dibraco_location_map', 'dibraco_location_map_shortcode');

function dibraco_get_processed_context_data($context_name) {
    $enabled_contexts = get_option('enabled_contexts');
    if (!isset($enabled_contexts[$context_name])) {
        return []; 
    }
    
    $context_data = $enabled_contexts[$context_name];
    $context_type = $context_data['context_type'];
    $context_name = $context_data['context_name'];
    $status = get_option('locations_areas_status');
    $card_style_settings = '';
    $current_post_id = get_the_ID();
    return get_the_context_by_status($context_type, $context_name, $context_data, $current_post_id, $status, $card_style_settings);
}
function dibraco_display_related_posts_list($atts) {
    $atts = shortcode_atts(['context' => 'main_service', 'bullets' => 'yes'], $atts);
    $cards = dibraco_get_processed_context_data($atts['context']);
    if (empty($cards)) {
        return 'No related posts found.';
    }
    $listStyle = ($atts['bullets'] === 'no') ? 'style="list-style-type: none;"' : '';
    $output = '<ul ' . $listStyle . '>';
    foreach ($cards as $card) {
        $output .= '<li><a href="' . esc_url($card['related_post_url']) . '">' . esc_html($card['related_post_title']) . '</a></li>';
    }
    $output .= '</ul>';
    return $output;
}
add_shortcode('da_related_posts_list', 'dibraco_display_related_posts_list');


function dibraco_display_related_list_comma($atts) {
      $atts = shortcode_atts([
        'context' => 'main_service', 
        'separator'    => ',' 
    ], $atts, 'da_related_list_comma');
    $cards = dibraco_get_processed_context_data($atts['context']);
$separator = $atts['separator'];
    if (empty($cards)) {
        return 'No related posts found.';
    }

    $output = '';
    $total_items = count($cards);
   foreach($cards as $index => $post_data){
      $the_title = $post_data['related_post_title'];
      $related_link = $post_data['related_post_url'];

        $output .= '<a href="' . esc_url($related_link) . '" title="Read more about ' . esc_attr($the_title) . '" aria-label="Read more about ' . esc_attr($the_title) . '">' . esc_html($the_title) . '</a>';
            if ($index < $total_items - 2) {
            $output .= $separator . ' ';
        } elseif ($index == $total_items - 2) {
            $output .= $separator . ' and ';
        }
    }

    return $output;
}
add_shortcode('da_related_list_comma', 'dibraco_display_related_list_comma');
function dibraco_enqueue_frontend_styles() {
    wp_register_style(
        'da-photo-hover-style', 
        AWESOME_SERVICES_URL . 'front-end-css/da-photo-hover.css', 
        [], 
        '1.0' 
    );
}
add_action('wp_enqueue_scripts', 'dibraco_enqueue_frontend_styles');

function da_related_photo_hover_shortcode($atts) {
    $attributes = shortcode_atts(['context' => 'main_service'], $atts); 
    $cards = dibraco_get_processed_context_data($attributes['context']);
    if (empty($cards)) return 'No related posts found.';
    wp_enqueue_style('da-photo-hover-style');
    $output = '<div class="da-related-photo-hover-container">';
    shuffle($cards);
    $enabled_contexts = get_option('enabled_type_contexts');
    $context_config = $enabled_contexts[$attributes['context']];
    foreach ($cards as $card) {
        $post_id = $card['related_post_id'];
        $related_link = $card['related_post_url'];
        $display_text = $card['related_post_title'];
        $image_options = [];
        if ($context_config['repeater_images'] === '1') {
            $img1 = get_post_meta($post_id, 'dibraco_landscape_1', true);
            $img2 = get_post_meta($post_id, 'dibraco_landscape_2', true);
            if (!empty($img1)) $image_options[] = wp_get_attachment_url($img1);
            if (!empty($img2)) $image_options[] = wp_get_attachment_url($img2);
        }
        $featured_img = get_the_post_thumbnail_url($post_id, 'full');
        if (!empty($featured_img)) $image_options[] = $featured_img;
        $final_img = esc_url($image_options ? $image_options[array_rand($image_options)] : '');
        $output .= '<a href="' . esc_url($related_link) . '" class="da-related-photo-link" aria-label="' . esc_attr($display_text) . '" title="' . esc_attr($display_text) . '">';
        $output .= '<img src="' . $final_img . '" alt="' . esc_attr($display_text) . '">';
        $output .= '<div class="da-related-photo-title">' . esc_html($display_text) . '</div>';
        $output .= '</a>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('da_related_photo_hover', 'da_related_photo_hover_shortcode');


