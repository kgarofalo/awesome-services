<?php
function dibraco_render_form_fields($fields) {
    foreach ($fields as $key => $config) {
        $input_name = $config['name'] ?? $key;
        ?>
        <p>
            <label>
                <input 
                    type="<?php echo esc_attr($config['type']); ?>" 
                    name="<?php echo esc_attr($input_name); ?>" 
                    value="<?php echo esc_attr($config['value']); ?>"
                >
                <?php echo esc_html($config['label']); ?>
            </label>
        </p>
        <?php
    }
}
function render_export_entities_page() {
    ?>
    <div class="wrap">
        <h1>Export Entities</h1>
        <p>This tool exports the full data entity for any selected context. For connectors and types, it exports term data with associated post(s). For unique contexts, it exports post data directly.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('export_entities_action', 'export_entities_nonce'); ?>
            <input type="hidden" name="action" value="export_entities_data">
            <hr>
            <h3>Export Options</h3>
            <?php 
            $main_export_options = [
                'export_company_info'        => ['name' => 'export_company_info', 'label' => 'Company Information', 'type' => 'checkbox', 'value' => '1'],
                'export_context_definitions' => ['name' => 'export_context_definitions', 'label' => 'Export All Enabled Context Definitions', 'type' => 'checkbox', 'value' => '1'],
            ];
            dibraco_render_form_fields($main_export_options); 
            ?>
            <hr>
            <h3>Contexts by Name</h3>
            <p>Select which individual contexts to export:</p>
            <?php 
            $context_fields = [];
            $all_contexts = get_option('enabled_context_names', []);
            foreach ($all_contexts as $context_name) {
                $context_fields[$context_name] = ['name' => 'contexts[]', 'label' => ucfirst(str_replace('_', ' ', $context_name)), 'type' => 'checkbox', 'value' => $context_name];
            }
            dibraco_render_form_fields($context_fields); 
            ?>
            
            <?php submit_button('Download Entities as JSON'); ?>
        </form>
    </div>
    <?php
}
function handle_export_entities_data() {
    if ( !current_user_can('manage_options') || !check_admin_referer('export_entities_action', 'export_entities_nonce') ) {
        wp_die('Invalid request', 'Error', ['response' => 403]);
    }
    $all_contexts = get_option('enabled_contexts', []);
    $export_data = [];
    if (isset($_POST['export_company_info']) && $_POST['export_company_info'] === "1") {
        $company_info = get_option('company_info', []);
        $main_post_maps = [];
        foreach ($all_contexts as $ctx_key => $ctx_cfg) {
            if ($ctx_cfg['context_type'] === 'type' && !empty($ctx_cfg['post_per_term']) && $ctx_cfg['post_per_term'] === '1') {
                $option_name = "{$ctx_key}_main_posts";
                $main_post_map = get_option($option_name, []);
                if (!empty($main_post_map)) {
                    $main_post_maps[$ctx_key] = $main_post_map;
                }
            }
        }
        if (!empty($main_post_maps)) {
            $company_info['main_post_maps'] = $main_post_maps;
        }
        $export_data['company_information'] = $company_info;
    }
        if (isset($_POST['export_context_definitions']) && $_POST['export_context_definitions'] === "1") {
        $export_data['enabled_contexts'] = $all_contexts;
    }

    if (isset($_POST['contexts']) && is_array($_POST['contexts'])) {
        $chosen_contexts = array_intersect_key($all_contexts, array_flip($_POST['contexts']));

        foreach ($chosen_contexts as $context_key => $context_config) {
            $context_type = $context_config['context_type'];
            $post_type = $context_config['post_type'];

            $export_data[$context_key] = [];

            if ($context_type === 'connector') {
                $taxonomy = $context_config['taxonomy'];
                $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
                if (empty($terms)) continue;
                foreach ($terms as $term) {
                    $export_data[$context_key][] = process_connector_entity($term, $context_config);
                }
            } else {
                $args = ['post_type' => $post_type, 'posts_per_page' => -1, 'post_status' => 'publish'];
                $posts = get_posts($args);
                foreach ($posts as $post) {
                    $export_data[$context_key][] = get_full_post_data($post->ID, $context_config);
                }
            }
        }
    }
    $filename = 'entities-export-' . gmdate('Ymd-His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
add_action('admin_post_export_entities_data', 'handle_export_entities_data');


function process_connector_entity($term, $context_config) {
 $context_name = $context_config['context_name'];
 $term_meta_keys = [];
 $term_meta = [];
$enabled_context_names = get_option('enabled_context_names', []);

if ($context_name === 'service_areas') {
    $term_meta_keys = ['service_area_post_id', 'city', 'state', 'latitude', 'longitude', 'service_area_link_url', 'service_area_post_id'];
    if (in_array('locations', $enabled_context_names)) {
        $term_meta_keys = array_merge($term_meta_keys, ['area_parent_location_term', 'area_parent_location_name']);
    }

} elseif ($context_name === 'locations') {
$all_sections = initialize_dafields('location_');

$term_meta = [];
$all_flat_keys = dibraco_extract_field_names_helper($all_sections);
$hours_fields = dibraco_extract_field_names_helper(['hours_of_operation' => $all_sections['hours_of_operation']]);
$social_fields = dibraco_extract_field_names_helper(['social_media' => $all_sections['social_media']]);
$flat_keys = array_diff($all_flat_keys, array_merge($hours_fields, $social_fields));
foreach ($flat_keys as $key) {
    $term_meta[$key] = get_term_meta($term->term_id, $key, true);
}
$term_meta['hours_of_operation'] = [];
foreach ($hours_fields as $key) {
    $term_meta['hours_of_operation'][$key] = get_term_meta($term->term_id, $key, true);
}

$term_meta['social_media'] = [];
foreach ($social_fields as $key) {
    $term_meta['social_media'][$key] = get_term_meta($term->term_id, $key, true);
}


    $term_meta_keys = array_merge($term_meta_keys, ['location_link_url', 'location_logo', 'schema', 'location_post_id']);

    if (in_array('service_areas', $enabled_context_names)) {
        $term_meta_keys[] = 'associated_act_terms';
    }
    if (in_array('employee', $enabled_context_names)) {
        $term_meta_keys[] = 'location_manager';
    }
}

foreach (array_keys($context_config['related_type_contexts'] ?? []) as $type_ctx_name) {
    $term_meta_keys[] = "related_type_{$type_ctx_name}";
}
foreach (array_keys($context_config['related_unique_contexts'] ?? []) as $uni_ctx_name) {
    $term_meta_keys[] = "related_unique_{$uni_ctx_name}";
}
foreach ($term_meta_keys as $meta_key) {
    $term_meta[$meta_key] = get_term_meta($term->term_id, $meta_key, true);
}
    $post_id_key = rtrim($context_name, 's') . '_post_id';
    $post_id = get_term_meta($term->term_id, $post_id_key, true);
    $post_data = ($post_id && get_post($post_id)) ? get_full_post_data($post_id, $context_config) : null;
    return [
        'term_id'      => $term->term_id,
        'name'         => $term->name,
        'slug'         => $term->slug,
        'post_id'      => $post_data['post_id'] ?? null,
        'post_title'   => $post_data['post_title'] ?? null,
        'post_url'     => $post_data['post_url'] ?? null,
        'post_meta'    => $post_data['post_meta'] ?? [],
        'term_meta'    => $term_meta,
    ];
}



function get_full_post_data($post_id, $context_config) {
    $post = get_post($post_id);
    if (!$post) return null;

    $post_meta_data = [];
    $fields_to_get = [];
    $context_name = $context_config['context_name'];
    $context_type = $context_config['context_type'];
    $status = get_option('locations_areas_status');
    if ($status !=='none' && $context_type !=='connector'){
        $related_connector_count = (int)$context_config['related_connector_count'];
        if ($related_connector_count > 0){
        $related_connectors = $context_config['related_connectors'];
         foreach ($related_connectors as $related_connector_name => $related_connector_data){
                $connector_taxonomy = $related_connector_data['taxonomy'];
                $connector_term_id = dibraco_get_current_term_id_for_post($post_id, $connector_taxonomy);
                    if ($connector_term_id) {
                        $term = get_term($connector_term_id, $connector_taxonomy);
                        $post_meta_data['related_connectors'][$related_connector_name] = ['term_id' => $term->term_id, 'name' => $term->name];
                    }
            }
        }
    }
    
    if ($context_config['dibraco_banner'] === '1') {
        $fields_to_get = array_merge($fields_to_get, array_keys(get_banner_fields()));
    }
    if ($context_config['contact_section'] === '1'){
        $fields_to_get = array_merge($fields_to_get, array_keys(get_contact_fields()));
    }
    if ($context_config['repeater_images'] === '1'){
        $fields_to_get = array_merge($fields_to_get, array_keys(get_landscape_image_fields($context_type)));
        foreach ($fields_to_get as $key => $field_name) {
            if (strpos($key, 'lock') !== false) {
                unset($fields_to_get[$key]);
            }
        }
    }
    if ($context_config['main_sections'] === '1') {
        $fields_to_get = array_merge($fields_to_get, array_keys(get_section_title_fields($context_name)));
    }
    if ($context_config['portrait_images'] === '1') {
    $portrait_fields = array_keys(get_portrait_image_fields($context_type));
    foreach ($portrait_fields as $key => $field_name) {
        if (strpos($field_name, 'lock') !== false) {
            unset($portrait_fields[$key]);
        }
    }
    $fields_to_get = array_merge($fields_to_get, $portrait_fields);
}
    if ($context_type ==='type' && $context_config['post_per_term']==="1" &&  $context_config['before_after'] === '1') {
        $fields_to_get = array_merge($fields_to_get, array_keys(get_before_after_repeater_fields()));
        foreach ($fields_to_get as $key => $field_name) {
            if (strpos($key, 'lock') !== false) {
                unset($fields_to_get[$key]);
            }
        }
    }
	if ($context_name === 'employee') {
       $post_meta_data['employee_data'] = get_post_meta($post_id, 'employee_data', true);
	}
    foreach ($fields_to_get as $field) {
        $post_meta_data[$field] = get_post_meta($post_id, $field, true);
    }

if ($context_name === 'jobs') {
    $final_key_value_pairs = get_post_meta($post_id, '_job_meta', true);
    $processed = [];
    $job_fields = da_get_job_fields();
     $job_field_keys =  dibraco_extract_field_names_recursive($job_fields);
    foreach ($final_key_value_pairs as $key => $value) {
        if (str_ends_with($key, '_row_count')) {
            $processed[$key] = $value;
        } else {
            $processed[$key] = $value;
    } 
}

    $post_meta_data['_job_meta'] = $processed;
}
    return [
        'post_id'    => $post->ID,
        'post_title' => $post->post_title,
        'post_url'   => get_permalink($post->ID),
        'post_meta'  => $post_meta_data,
    ];
}