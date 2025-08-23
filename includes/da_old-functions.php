<?php


function disable_wpautop_for_arf_shortcode($content) {
    $content = preg_replace('/<p>\\s*(\\[arf[^\]]*\\])\\s*<\\/p>/i', '$1', $content);
    return $content;
}
add_filter('the_content', 'disable_wpautop_for_arf_shortcode', 10);

function arf_add_instructions_link($links) {
    $instructions_link = '<a href="' . esc_url(admin_url('admin.php?page=relationship-instructions')) . '">Instructions</a>';
    array_unshift($links, $instructions_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'arf_add_instructions_link');

function company_info_instructions_content() {
    $screen = get_current_screen();
    if ($screen->id !="relationships_page_relationship-instructions") {
        return;  
    }
}

function dibraco_display_related_list_comma_combined() {
    $all_items = [];

    for ($relationship_number = 1; $relationship_number <= 2; $relationship_number++) {
        $da_get_related_fields = dibraco_get_related_posts($relationship_number);

        if (empty($da_get_related_fields)) {
            continue; // Skip if no related posts found for this relationship
        }

        foreach ($da_get_related_fields as $related_field) {
            $text = $related_field['related_type_term']; // Using the related_type_term
            $title = $related_field['post_title']; // Using the post_title for the title attribute
            $item_format = '<a href="' . esc_url($related_field['related_link']) . '" title="' . esc_attr($title) . '">' . esc_html($text) . '</a>';
            $all_items[] = $item_format;
        }
    }

    $all_items[] = '<a href="/site-development/" title="Site Development">Site Development</a>';
    shuffle($all_items);
    if (count($all_items) > 1) {
        $last_item = array_pop($all_items); 
        $output = implode(', ', $all_items) . ', and ' . $last_item;
    } else {
        $output = implode('', $all_items); // Only one item, just output it
    }

    return !empty($output) ? $output : 'No related posts found.';
}

add_shortcode('da_all_relationship_terms', 'dibraco_display_related_list_comma_combined');
function acf_related_fields_shortcode($atts) {
    $atts = shortcode_atts(array(
        'field' => '',
        'tax' => '',
        'subfield' => '',
        'type' => '',
        'pt' => 'rank_math_locations',
        'options' => false,
    ), $atts, 'arf');

    if (!$atts['subfield'] || (!$atts['field'] && !$atts['tax'] && !$atts['options'])) {
        return '';
    }

    // Check if ACF is installed and active
    if (function_exists('get_field')) {
        $current_post_id = get_the_ID();
        $related_post = null;

        // Variant 1: Find related post via post object field
        if ($atts['field'] && !$atts['options']) {
            $related_post_object = get_field($atts['field'], $current_post_id);
            if ($related_post_object) {
                $related_post = $related_post_object;
            }
        }

        // Variant 2: Find related post via taxonomy and specified post type
        if (!$related_post && $atts['tax'] && !$atts['options']) {
            $taxonomy_name = $atts['tax'];
            $terms = wp_get_post_terms($current_post_id, $taxonomy_name);
            if (!empty($terms)) {
                $term_ids = wp_list_pluck($terms, 'term_id');
                $related_posts = get_posts(array(
                    'post_type' => $atts['pt'],
                    'tax_query' => array(
                        array(
                            'taxonomy' => $taxonomy_name,
                            'field' => 'term_id',
                            'terms' => $term_ids,
                        )
                    )
                ));
                if ($related_posts) {
                    $related_post = $related_posts[0]; // Get the first related post
                }
            }
        }

        // Variant 3: Fetch field value from the options page with matching taxonomy term
        if ($atts['options'] && $atts['tax']) {
            if (get_the_terms(get_the_ID(), $atts['tax'])[0]->slug === get_field($atts['tax'], 'option')) {
                $subfield_value = get_field($atts['subfield'], 'option');
            }
        }

        if ($related_post) {
            $subfield_value = get_field($atts['subfield'], $related_post->ID);
            $post_title = get_the_title($related_post->ID);
            $post_permalink = get_permalink($related_post->ID);

            // Check if a custom shortcode exists for the subfield
            if (shortcode_exists('da_' . $atts['subfield'])) {
                // Process the custom shortcode and get the output
                $subfield_value = do_shortcode('[da_' . $atts['subfield'] . ' post_id="' . $related_post->ID . '"]');
            }

            // Output as mailto link for email addresses
            if ($atts['type'] === 'email') {
                return '<a href="mailto:' . esc_attr($subfield_value) . '">' . esc_html($subfield_value) . '</a>';
            }

            // Output as tel link for phone numbers
            if ($atts['type'] === 'phone') {
                return '<a href="tel:+1-' . esc_attr($subfield_value) . '">' . esc_html($subfield_value) . '</a>';
            }

            if ($atts['type'] === 'link') {
             return '<a href="' . esc_url($post_permalink) . '">' . esc_html($subfield_value) . '</a>';
            } else {
                return $subfield_value;
            }
        } elseif (isset($subfield_value)) {
            // This handles the case when the subfield value is fetched from the options page
            // Output as mailto link for email addresses
            if ($atts['type'] === 'email') {
                return '<a href="mailto:' . esc_attr($subfield_value) . '">' . esc_html($subfield_value) . '</a>';
            }

            // Output as tel link for phone numbers
            if ($atts['type'] === 'phone') {
                return '<a href="tel:+1-' . esc_attr($subfield_value) . '">' . esc_html($subfield_value) . '</a>';
            }

            if ($atts['type'] === 'link') {
                return '<a href="' . esc_url($subfield_value) . '">' . esc_html($subfield_value) . '</a>';
            } else {
                return $subfield_value;
            }
        }
    }

    return null;
}
add_shortcode('arf', 'acf_related_fields_shortcode');