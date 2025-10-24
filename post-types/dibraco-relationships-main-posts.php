<?php

function render_dibraco_main_posts_screen() {
    handle_save_main_posts_screen();

    echo '<div class="wrap"><h1>Main Posts Overview</h1>';
    echo '<form method="POST" action="">';
    wp_nonce_field('dibraco_save_main_posts', 'dibraco_main_posts_nonce');
    $enabled_type_contexts = get_option('enabled_type_contexts');
    
    foreach ($enabled_type_contexts as $type_context) {
        if ($type_context['post_per_term'] !== '1') {
            continue;
        }

        $type_context_name = $type_context['context_name'];
        $main_post_option_name = "{$type_context_name}_main_posts";
        $type_taxonomy = $type_context['taxonomy'];
        $type_post_type = $type_context['post_type'];
        $pages_represent = $type_context['pages_represent']?? '0';
        $main_post_map = get_option("$main_post_option_name");
        if($pages_represent ==='1'){
            $term_ids_post_ids = get_type_taxonomy_terms($type_taxonomy, 'page');
        } elseif ($pages_represent ==='0') {
            $term_ids_post_ids = get_type_taxonomy_terms($type_taxonomy, $type_post_type);
        }
        $type_term_ids = $term_ids_post_ids['type_term_ids'];
        $type_post_ids = $term_ids_post_ids['type_post_ids'];
        $count_main_post_map_terms = count($main_post_map);
        $count_active_type_term_ids = count($type_term_ids);
        $new_type_term_ids = array_diff($type_term_ids, array_keys($main_post_map));
        $term_ids_and_post_map = validate_main_post_map($main_post_map, $type_term_ids, $type_taxonomy, $type_post_ids);
        $main_post_map = $term_ids_and_post_map['main_post_map'];
        foreach ($new_type_term_ids as $new_type_term_id){
            $new_type_term_id = (int)$new_type_term_id;
            $main_post_map[$new_type_term_id] ='';
        }

        $context_label = ucwords(str_replace('_', ' ', $type_context_name));
        echo "<div class='context-section'><h2>{$context_label}</h2>";
            
        foreach ($type_term_ids as $type_term_id) {
            $term_post_options =[];
            $post_ids_for_term = get_objects_in_term($type_term_id, $type_taxonomy);
            $filtered_post_ids = array_intersect($post_ids_for_term, $type_post_ids);
            $term_name = get_term($type_term_id)->name;
            $current_main_post_id = $main_post_map[$type_term_id];
            foreach($filtered_post_ids as $post_id){
                $post_id=(int)$post_id;
                $post_title = get_the_title($post_id);
                $term_post_options[$post_id] = $post_title;
            }

            echo FormHelper::generateField("{$main_post_option_name}[{$type_term_id}]", [
                'type'    => 'select',
                'label'   => "Main Post For: {$term_name}",
                'value'   => $current_main_post_id,
                'options' => $term_post_options,
            ]);
        }
        echo '</div>'; // End context-section
    }

    submit_button('Save Main Posts');
    echo '</form></div>'; // End wrap
}

// -------------------------------------------------------------------------------------------------------------------

function handle_save_main_posts_screen() {
    // Check if the form has been submitted and verify security.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    if (!isset($_POST['dibraco_main_posts_nonce']) || !wp_verify_nonce($_POST['dibraco_main_posts_nonce'], 'dibraco_save_main_posts')) {
        wp_die('Invalid nonce.');
    }

    $enabled_type_contexts = get_option('enabled_type_contexts');

    foreach ($enabled_type_contexts as $type_context) {
        if ($type_context['post_per_term'] !== '1') {
            continue;
        }

        $type_context_name = $type_context['context_name'];
        $main_post_option_name = "{$type_context_name}_main_posts";
        
        $submitted_posts_by_term = $_POST[$main_post_option_name];
        
        $main_post_map = [];
        
        if (!empty($submitted_posts_by_term) && is_array($submitted_posts_by_term)) {
            foreach ($submitted_posts_by_term as $type_term_id => $type_post_id) {
                $type_term_id = (int) $type_term_id;
                $type_post_id = (int) $type_post_id;
                
                // Only save if a post was selected.
                if ($type_post_id > 0) {
                    $main_post_map[$type_term_id] = $type_post_id;
                }
            }
            // Update the single options key with the new data.
            update_option($main_post_option_name, $main_post_map);
        }
    }
    echo '<div class="notice notice-success is-dismissible"><p>Main posts saved successfully.</p></div>';
}