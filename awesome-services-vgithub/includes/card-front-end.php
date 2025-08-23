<?php
require_once AWESOME_SERVICES_PATH . 'awesome-services-main.php';

add_action( 'init', 'awesome_services_register_dynamic_cards_pattern' );
function awesome_services_register_dynamic_cards_pattern() {
    if ( function_exists( 'register_block_pattern' ) ) {
        register_block_pattern(
            'awesome-services/auto-cards',
            [
                'title'       => __( 'Auto-Populate Service Cards', 'awesome-services' ),
                'description' => _x( 'Insert your display_cards shortcode automatically.', 'Block pattern description', 'awesome-services' ),
                'categories'  => [ 'awesome-services', 'widgets' ],
                'content'     => "
<!-- wp:shortcode -->
[display_cards context=\"main_service\" variant=\"0\"]
<!-- /wp:shortcode -->
"
            ]
        );
    }
}

function get_type_posts_for_cards_no_connector($context_data, $selected_terms, $status, $post_per_term){
    $cards = [];
    $context_name = $context_data['context_name'];
    $post_type = $context_data['post_type'];
    $type_taxonomy = $context_data['taxonomy'];
    $connector_term = '';
    if ($status === "multi_locations" || $status === "both") {
        $locations_context = get_option('enabled_connector_contexts')['locations'];
        $ignore_main_term = $locations_context['ignore_main_term'];
        if ($ignore_main_term !=="1"){
            $main_term = $locations_context['main_term'] ?? '';
            if ($main_term !== '') {
                $main_term = (int) $main_term;
                $connector_term = get_term($main_term)->name;
            }
        }
    }
    if (empty($connector_term)) {
        $connector_term = get_option('company_info')['city'] ?? '';
    }
    if ($post_per_term !== "1") {
        foreach ($selected_terms as $term_id) {
        $term_name = get_term($term_id)->name;
        $posts = get_posts([
        'post_type'      => $post_type,
        'posts_per_page' => -1,
        'tax_query'      => [[
            'taxonomy' => $type_taxonomy,
            'field'    => 'term_id',
            'terms'    => [$term_id],
        ]],
    ]);
    if (empty($posts)){ return $cards;}
         foreach ($posts as $post) {
        $related_post_id = $post->ID;
        $related_post_title = get_the_title($related_post_id);
        $cards[] = ['related_post_id' => $related_post_id, 'related_post_title' => $related_post_title, 'related_post_url' => get_permalink($related_post_id), 'related_term_name' => $term_name ];

        }
    }
      return $cards;
    } else {
        $post_map_key = "{$context_name}_main_posts";
        $main_post_map = get_option($post_map_key);
        $main_post_ids = array_intersect_key($main_post_map, array_flip($selected_terms));
        foreach ($main_post_ids as $type_term_id => $main_post_id) {
            $related_post_title = get_the_title($main_post_id);
            $term_name = get_term($type_term_id)->name;
            if (!empty($connector_term)){
             $related_post_title = "{$term_name} In {$connector_term}";
            }
            $cards[] = ['related_post_id' => $main_post_id, 'related_post_title' => $related_post_title, 'related_post_url' => get_permalink($main_post_id), 'related_term_name' => $term_name];
        }
        return $cards;
    }
    return $cards;
}

function get_location_posts_for_cards ($context_data){
    $cards = [];
    $locations_taxonomy = $context_data['taxonomy'];
    $all_location_terms = get_terms(['taxonomy' => $locations_taxonomy, 'hide_empty' => true, 'fields' => 'ids']);
        if (!empty($all_location_terms)) {
          foreach ($all_location_terms as $term_id) {
            $post_id = get_term_meta($term_id, "location_post_id", true);
            $link_url = get_term_meta($term_id, "location_link_url", true);
                if ($post_id && $link_url) {
                    $cards[] = [
                        'related_post_id'    => $post_id,
                        'related_post_title' => get_the_title($post_id),
                        'related_post_url'   => $link_url
                    ];
                }
            }
    }
 return $cards;
}
function get_service_area_posts_for_cards ($context_data, $status, $current_post_id){
    $cards = [];
    $service_areas_taxonomy = $context_data['taxonomy'];
    if ($status ==='multi_areas'){
        $all_service_area_terms = get_terms(['taxonomy' => $service_areas_taxonomy, 'hide_empty' => true, 'fields' => 'ids']);
        if (empty($all_service_area_terms)) {return $cards;}
        foreach ($all_service_area_terms as $term_id) {
            $post_id = get_term_meta($term_id, "service_area_post_id", true);
            $link_url = get_term_meta($term_id, "service_area_link_url", true);
                if ($current_post_id === $post_id) {continue;}
                if ($post_id && $link_url) {
                    $cards[] = [
                        'related_post_id'    => $post_id,
                        'related_post_title' => get_the_title($post_id),
                        'related_post_url'   => $link_url
                    ];
                }
            }
        return $cards;
    }
    else {
        $service_area_term_id = dibraco_get_current_term_id_for_post($current_post_id, $service_areas_taxonomy);
        $location_term_id ='';
        if ($service_area_term_id !== '') {
             $location_term_id = get_term_meta($service_area_term_id, 'area_parent_location_term', true);
             $location_term_id = (int)$location_term_id;
        }
        if ($location_term_id ===''){
            $locations_context = get_option('enabled_connector_contexts')['locations'];
            $locations_taxonomy = $locations_context['taxonomy'];
            $location_term_id = dibraco_get_current_term_id_for_post($current_post_id, $locations_taxonomy);
        }
        if ($location_term_id === ''){
            return get_location_posts_for_cards($locations_context);
        }
        $act_term_ids = get_term_meta($location_term_id, 'associated_act_terms', true);
            if (empty($act_term_ids)) {
                        return get_location_posts_for_cards($locations_context);
                    }
              foreach ($act_term_ids as $act_term_id) {
                            $post_id = get_term_meta($act_term_id, "service_area_post_id", true);
                            $link_url = get_term_meta($act_term_id, "service_area_link_url", true);
                            if ($post_id && $link_url) {
                                $cards[] = [
                                    'related_post_id'    => $post_id,
                                    'related_post_title' => get_the_title($post_id),
                                    'related_post_url'   => $link_url
                                ];
                            }
                        }
        return $cards;
}
}

function get_unique_posts_for_router ($context_data, $target_context_name, $status, $current_post_id){
    $connector_term_id ='';
    switch ($status){
        case 'multi_areas':
        case 'multi_locations':
            $connector_type = 'locations';
            if ($status === 'multi_areas') {
                $connector_type = 'service_areas';
            }
        $connector_taxonomy = get_option('enabled_connector_contexts')[$connector_type]['taxonomy'];
        $connector_term_id = dibraco_get_current_term_id_for_post($current_post_id, $connector_taxonomy);
        if ($connector_term_id ===''){
           return get_unique_posts_no_connector($context_data);
        } else {
           return get_unique_posts_per_connector($target_context_name, $connector_term_id);
        }
      break;
       case 'both':
          $connector_taxonomy = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
          $connector_term_id = dibraco_get_current_term_id_for_post($current_post_id, $connector_taxonomy);
          if ($connector_term_id ===''){
              $connector_taxonomy = get_option('enabled_connector_contexts')['locations']['taxonomy'];
              $connector_term_id = dibraco_get_current_term_id_for_post($current_post_id, $connector_taxonomy);
          }
          if ($connector_term_id ===''){
             return get_unique_posts_no_connector($context_data);
          } else {
             return get_unique_posts_per_connector($target_context_name, $connector_term_id); 
          }
}}
function get_unique_posts_per_connector($context_name, $connector_term_id) {
    $meta_key = "related_unique_{$context_name}";
    $posts_data = get_term_meta($connector_term_id, $meta_key, true);
    if (empty($posts_data)) {
        return []; 
    }
    $related_posts = [];
    foreach ($posts_data as $post_id => $post_data) {
        $related_posts[] = [
            'post_id'           => $post_id,               
            'related_post_url'  => $post_data['related_post_url'],
            'related_post_title'=> $post_data['related_post_title'],
        ];
    }
    return $related_posts; 
}
function get_unique_posts_no_connector($context_data){
  $cards = [];
  $post_type = $context_data['post_type'];
  $posts = get_posts(['post_type' => $post_type, 'posts_per_page' => -1]);
        if (empty($posts)){return $cards;}
        foreach ($posts as $post) {
            $cards[] = [
                'related_post_id' => $post->ID, 
                'related_post_title' => get_the_title($post->ID), 
                'related_post_url' => get_permalink($post->ID)  
            ];
        }
       return $cards;
}
function get_type_posts_router($context_data, $target_context_name, $status, $current_post_id, $selected_terms, $post_per_term){
      switch ($status) {
        case 'multi_areas':
        case 'multi_locations':
            $connector_type = 'locations';
            if ($status === 'multi_areas') {
                $connector_type = 'service_areas';
            }
            $connector_context = get_option('enabled_connector_contexts')[$connector_type];
            $connector_taxonomy = $connector_context['taxonomy'];
            $connector_term_id = dibraco_get_current_term_id_for_post($current_post_id, $connector_taxonomy);
            if ($connector_term_id === '') {
                return get_type_posts_for_cards_no_connector($context_data, $selected_terms, $status, $post_per_term);
            } else {
                 if ($post_per_term === '1'){
                        return get_type_post_per_term($target_context_name, $connector_term_id, $selected_terms);
                    } else {
                    return get_type_rows_multi_post($target_context_name, $connector_term_id, $selected_terms);
                }
            }
           break;
        case 'both':
          $connector_taxonomy = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
          $connector_term_id = dibraco_get_current_term_id_for_post($current_post_id, $connector_taxonomy);
          if ($connector_term_id ===''){
              $connector_taxonomy = get_option('enabled_connector_contexts')['locations']['taxonomy'];
              $connector_term_id = dibraco_get_current_term_id_for_post($current_post_id, $connector_taxonomy);
          }
          if ($connector_term_id ===''){
              return get_type_posts_for_cards_no_connector($context_data, $selected_terms, $status, $post_per_term);
          } else {
                 if ($post_per_term === '1'){
                    return get_type_post_per_term($target_context_name, $connector_term_id, $selected_terms);
                    } else {
                    return get_type_rows_multi_post($target_context_name, $connector_term_id, $selected_terms);
                }
            }
    }    
}

function get_the_context_by_status($context_type, $target_context_name, $context_data, $current_post_id, $status, $card_style_settings){
switch ($context_type){
    case 'connector':    
    if ($target_context_name ==='locations'){
       return  get_location_posts_for_cards ($context_data);
    }
    else {
       return get_service_area_posts_for_cards ($context_data, $status, $current_post_id);
    }
    break;
    case 'unique':
       if ($status === 'none'){
        return get_unique_posts_no_connector($context_data);
       } else {
       return get_unique_posts_for_router($context_data, $target_context_name, $status, $current_post_id);
       }
    break;
    case 'type':
        $type_taxonomy = $context_data['taxonomy'];
        $post_per_term = $context_data['post_per_term'];
        $selected_terms = [];
        if ($card_style_settings !==''){
        $selected_terms = $card_style_settings['selected_terms'];
        }
        if (empty($selected_terms)){
            $selected_terms = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true, 'fields' => 'ids']);
        }
        if ($status === 'none'){
        return get_type_posts_for_cards_no_connector($context_data, $selected_terms, $status, $post_per_term);
       } else {
        return get_type_posts_router ($context_data, $target_context_name, $status, $current_post_id, $selected_terms, $post_per_term);

       }
    }
}
function dibraco_cards_shortcode_handler($atts) {
	$atts = shortcode_atts([ 'context' => 'main_service', 'variant' => '0', ], $atts, 'display_cards');
    $variant_index = $atts['variant']; 
    $target_context_name = $atts['context'];
    $enabled_context = get_option('enabled_contexts')[$target_context_name];
    if ($enabled_context === '') {
        return 'Context Is Not Enabled Or Does Not Exist.';
    }
    $settings_key = "{$target_context_name}_card_styles_$variant_index";
    $card_style_settings = get_option($settings_key);
    if (empty($card_style_settings) && $variant_index !== '0') {
        $settings_key = "{$target_context_name}_card_styles_0";
        $card_style_settings = get_option($settings_key);
    }
    if (empty($card_style_settings)) {
        return 'Card Style Settings Do Not Exist';
    }
    $status = get_option('locations_areas_status');
    $context_data = $enabled_context;
    $context_type = $enabled_context['context_type'];
    $current_post_id = get_the_ID();
    $cards = get_the_context_by_status($context_type, $target_context_name, $context_data, $current_post_id, $status, $card_style_settings);
     
    $css_context_name  = str_replace('_', '-', $target_context_name); 

    $uploads = wp_upload_dir();
    $css_file = $uploads['basedir'] . "/awesome-services/css/card-{$css_context_name}-{$variant_index}.css";
    $css_url = $uploads['baseurl'] . "/awesome-services/css/card-{$css_context_name}-{$variant_index}.css";
    if (file_exists($css_file)) {
        wp_enqueue_style("card-style-{$css_context_name}-{$variant_index}", $css_url);
    
    }
$cards_html = '';
$cards_html .= '<div class="' . esc_attr("{$css_context_name}-cards-section-{$variant_index}") . '">';

foreach ($cards as $card) {
     $button_title = $card['related_term_name'] ?? '';
      if ($button_title === '') {
        $button_title = $card['related_post_title'];
     }
    $card_html = '<div class="' . esc_attr("{$css_context_name}-card-section") . '">';

    $sortable_content = [];

    if ($card_style_settings['cards_show_title'] === '1') {
    $heading_tag = $card_style_settings['title_heading_type']; 
    $title_html = '<div class="' . esc_attr("{$css_context_name}-title-section") . '">' . '<' . tag_escape($heading_tag) . ' class="card-title">' . '<a class="card-title-link" href="' . esc_url($card['related_post_url']) . '" title="' . esc_attr($card['related_post_title']) . '" aria-label="' . esc_attr($card['related_post_title']) . '">' . esc_html($card['related_post_title']) . '</a>' . '</' . tag_escape($heading_tag) . '>' . '</div>';
    $sortable_content[$card_style_settings['cards_title_position']] = $title_html;
    } 
    if ($card_style_settings['cards_show_image'] === '1') {
        $image_field = $card_style_settings['cards_image_field'];
        $image_id = get_post_meta($card['related_post_id'], $image_field, true);
        if (empty($image_id)) {
            $image_id = get_post_thumbnail_id($card['related_post_id']);
        }

        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
           $sortable_content[$card_style_settings['cards_image_position']] = '<div class="'.esc_attr("{$css_context_name}-image-section card-image-wrap").'"><img class="card-image" src="'.esc_url($image_url).'" alt="'.esc_attr($card['related_post_title']).'" title="'.esc_attr($card['related_post_title']).'"></div>';

        }
    }
    if ($card_style_settings['cards_show_description'] === '1') {
        $description = get_post_meta($card['related_post_id'], 'da_banner_description', true);
        
        if (!empty($description)) {
            $sortable_content[$card_style_settings['cards_description_position']] = '<p class="' . esc_attr("{$css_context_name}-description-section card-description") . '">' . wp_strip_all_tags($description) . '</p>';
        }
    }
    if ($card_style_settings['cards_show_button'] === '1') {
      $sortable_content[$card_style_settings['cards_button_position']] = '<a href="' . esc_url($card['related_post_url']) . '"  class="' . esc_attr("{$css_context_name}-button card-button") . '" title="' . esc_attr($button_title) . '" aria-label="' . esc_attr($button_title) . '">' . esc_html($button_title) . '</a>';
    } 
    ksort($sortable_content);

    $card_html .= implode('', array_values($sortable_content));
    $card_html .= '</div>';

    $cards_html .= $card_html;
}

$cards_html .= '</div>';

return $cards_html;
}
add_shortcode('display_cards', 'dibraco_cards_shortcode_handler');



function get_type_post_per_term($context_name, $term_id, $selected_terms) {
    $meta_key = "related_type_{$context_name}";
    $saved_data = get_term_meta($term_id, $meta_key, true);
    $related_posts = [];
    foreach ($saved_data as $type_term_id => $entry) {
        if (in_array($type_term_id, $selected_terms)) {
            $term_name = get_term($type_term_id)->name;
            if (!empty($entry['related_post_id'])) {
                $url = $entry['related_post_url'];
                $post_id = $entry['related_post_id']; 
            } else {
                $url = $entry['fallback_url'];
                $post_id = url_to_postid($url); 
            }
            $title = $entry['related_post_title'];
            $related_posts[$type_term_id] = [
                'related_post_id' => $post_id,
                'related_post_title' => $title,
                'related_post_url' => $url,
                'related_term_name' => $term_name
            ];
        }
    }

    return $related_posts;
}

function get_type_rows_multi_post($context_name, $term_id, $selected_terms) {
    $meta_key = "related_type_{$context_name}";
    $full_data = get_term_meta($term_id, $meta_key, true);
    $related_posts = [];
    foreach ($full_data as $type_term_id => $posts) {
        if (in_array($type_term_id, $selected_terms)) {
            $term_name = get_term($type_term_id)->name;
            foreach ($posts as $post_id => $entry) {
                $url = $entry['related_post_url']; 
                $related_posts[] = [
                    'related_post_id' => $post_id,
                    'related_post_url' => $url,
                    'related_post_title' => $entry['related_post_title'],
                    'related_term_name' => $term_name
                ];
            }
        }
    }
    return $related_posts;
}