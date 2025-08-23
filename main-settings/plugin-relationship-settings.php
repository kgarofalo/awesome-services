<?php
function define_initial_individual_contexts() {
    $contexts = [
          'locations' => [
            'context_type' => 'connector', 
            'fields' => [
                'enabled' => ['value' => get_option('locations_enabled', get_option('multiple_locations', "0"))],
                'post_type' => ['value' => get_option('locations_post_type', get_option('location_post_type', ''))],
                'taxonomy' => ['value' => get_option('locations_connector_tax', get_option('locations_connector_taxonomy', ''))],
                'schema' => ['value' => get_option('location_schema', 'Location')],
                'main_term' => ['value' => get_option('main_term', '')],
                'ignore_main_term' => ['value' => get_option('ignore_main_term', "0")]
            ]
        ],
        'service_areas' => [
            'context_type' => 'connector', 
            'fields' => [
                'enabled' => ['value' => get_option('service_areas', get_option('service_areas_enabled', "0"))],
                'post_type' => ['value' => get_option('service_areas_post_type', get_option('main_area_post_type', ''))],
                'taxonomy' => ['value' => get_option('service_areas_connector_tax', get_option('area_connector_tax', ''))],
                'schema' => ['value' => get_option('main_area_schema', 'Service')],
            ]
        ],
        'main_service' => [
            'context_type' => 'type',
            'fields' => [
                'enabled' => ['value' => get_option('main_service_enabled', get_option('main_service', "1"))],
                'schema' => ['value' => get_option('main_service_schema', 'Service')],
                'post_type' => ['value' => get_option('main_service_post_type', get_option('main_service_post_type', ''))],
                'taxonomy' => ['value' => get_option('main_service_type_taxonomy', get_option('main_type_tax', ''))]
            ]
        ],
        'second_service' => [
            'context_type' => 'type',
            'fields' => [
                'enabled' => ['value' => get_option('second_service_type', "0")],
                'schema' => ['value' => get_option('second_service_schema', 'Service')],
                'post_type' => ['value' => get_option('second_service_post_type', get_option('second_service_post_type', ''))],
                'taxonomy' => ['value' => get_option('second_service_type_taxonomy', get_option('second_type_tax', ''))]
            ]
        ],
        'jobs' => [
            'context_type' => 'type',
            'fields' => [
                'enabled' => ['value' => get_option('enable_jobs', "0")],
                'schema' => ['value' => get_option('job_posting_schema', 'JobPosting')],
                'post_type' => ['value' => get_option('jobs_post_type', get_option('job_post_type', ''))],
                'taxonomy' => ['value' => get_option('job_type_taxonomy', get_option('job_type_tax', ''))]
            ]
        ],
        'employee' => [
            'context_type' => 'unique',
            'fields' => [
                'enabled' => ['value' => "0"],
                'schema' => ['value' => 'Person'],
                'post_type' => ['value' => '']
            ]
        ]
    ];

    return $contexts;
}

function assemble_fields($context_name, $context_type, $field_keys = []) {
    $fields =[];
    static $post_type_options =null;
    if ($post_type_options===null){
    $post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
        $post_type_options = [];
        foreach ($post_types as $post_type => $post_type_obj) {
        if ($post_type_obj->rewrite !== false) {
            error_log("Querying for post type: " . $post_type);
            $post_check_query = new WP_Query(['post_type' => $post_type,'post_status' => 'publish','posts_per_page' => 1,'fields' => 'ids']);
            if (!empty( $post_check_query->posts)) {
                $post_type_options[$post_type] = $post_type_obj->label;
            }
        }
    }
    }
    $schema_options = initialize_schema_options();
    //bse fields that are received by all contexts will always have a value select fields values can be set to '' and they will resolve to please select
    $fields['enabled'] = ['type' => 'toggle', 'label' => "{$context_name} Enabled", 'value' => $field_keys['enabled'] ?? "1"];
    $fields['schema'] = ['type' => 'select', 'options' => $schema_options, 'value' => $field_keys['schema'] ?? ""];
    $fields['post_type'] = ['type' => 'select', 'options' => $post_type_options, 'value' => $field_keys['post_type'] ?? ""];
    //unique fields do not get a primary taxonomy thats what this is. 
    if ($context_type !== 'unique') {
        error_log("In assemble_fields: Calling awesome_get_taxonomies_for_post_type()");
        $taxonomy_options = awesome_get_taxonomies_for_post_type($fields['post_type']['value']);
        $fields['taxonomy'] = ['type' => 'select', 'options' => $taxonomy_options, 'value' => $field_keys['taxonomy'] ?? ''];
    }

    if ($context_name === 'locations') {
        $term_options = getTermOptions($fields['taxonomy']['value']);
        $fields['main_term'] = ['type' => 'select', 'options' => $term_options, 'value' => $field_keys['main_term'] ?? ''];
        $fields['ignore_main_term'] = ['type' => 'toggle', 'value'=> $field_keys['ignore_main_term'] ?? "0"];
    }

    $fields['primary_meta'] = ['type' => 'toggle', 'label' => 'Meta', 'value' => $field_keys['primary_meta'] ?? "1"];
    //fields setup
    $fields['dibraco_banner'] = ['type' => 'toggle', 'label' => 'Banner', 'value' => $field_keys['dibraco_banner'] ?? "1"];
     $fields['about_section'] = ['type' => 'toggle', 'label' => 'About Fields', 'value' => $field_keys['about_section'] ?? "1"];
    $fields['main_sections'] = ['type' => 'toggle', 'label' => 'Main', 'value' => $field_keys['main_sections'] ?? "1"];
    $fields['contact_section'] = ['type' => 'toggle', 'label' => 'Contact', 'value' => $field_keys['contact_section'] ?? "1"];
    //images setup
    $fields['portrait_images'] = ['type' => 'toggle', 'label' => 'Portrait Images', 'value' => $field_keys['portrait_images'] ?? "0"];
    $fields['repeater_images'] = ['type' => 'toggle', 'label' => 'Landscape Imgs', 'value' => $field_keys['repeater_images'] ?? "1"];

    if ($context_type === 'type') {
        //connector context terms will have one post per type term set up in their context tables if this is ==="1" 
        $fields['post_per_term'] = ['type' => 'toggle', 'label' => '1 Post Per Term', 'value' => $field_keys['post_per_term'] ?? "0"];
        //additional image field for a specific type will exist on terms but will be fed to posts of that term
        $fields['term_icon'] = ['type' => 'toggle', 'label' => 'Term Icon', 'value' => $field_keys['term_icon'] ?? "0"];
        $fields['before_after'] = ['type' => 'toggle', 'label' => 'Bef Aft', 'value' => $field_keys['before_after'] ?? "0"];
    }
    //vertification can belong to type posts as well as location terms as well as employee terms
    if ($context_type === 'type' || $context_name === 'employee' || $context_name === 'locations') {
        $fields['has_certification'] = ['type' => 'toggle', 'label' => 'Certification', 'value' => $field_keys['has_certification'] ?? "0"];
    }
    //only on newly created contexts can this exist
    if (isset($field_keys['remove'])) {
        $fields['remove'] = ['type' => 'button', 'class' => 'remove-context-button button-secondary'];
    }

    return $fields;
}

add_action('wp_ajax_build_individual_context', function() {
    $context_name = $_POST['new_context_name'];
    $context_type = $_POST['new_context_type'];
    $context_name = strtolower(str_replace(' ', '_', $context_name));
    $contexts = get_option('contexts');
    if (isset($contexts[$context_name])) {
        wp_send_json_error(['message' => 'Context already exists.']);
    }
    $field_keys = ['remove' => []];
    $new_fields = assemble_fields($context_name, $context_type, $field_keys);
    $subfields = [];
    $filtered_fields = [];
    foreach ($new_fields as $field_key => $field_data){
    if($field_key==='remove'){
     $filtered_fields[$field_key] = $field_data; 
    } else{
     $filtered_fields[$field_key] = $field_data['value'];
    }
     if ($field_key === 'enabled') {
         $fieldname = "{$context_name}_{$field_key}";
         $new_toggle_html = FormHelper::generateField($fieldname, $field_data);
          } else {
          $subfields[$field_key] = $field_data;
        }
    }
    $context = ['context_name' => $context_name, 'context_type' => $context_type, 'fields' => $filtered_fields];
    $contexts[$context_name] = $context;
    update_option('contexts', $contexts);
    $new_section_html = FormHelper::generateGroup($context_name, ['label' => $context_name, 'condition' => ['field' => "{$context_name}_enabled", 'values' => ["1"]], 'fields' => $subfields]);
    wp_send_json_success([
        'new_toggle_html'  => $new_toggle_html,
        'new_section_html' => $new_section_html
    ]);
});
function awesome_get_post_types() {
    if ($post_type_options === null) {

    $post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
    $post_type_options = [];
    foreach ($post_types as $post_type => $post_type_obj) {
        if ($post_type_obj->rewrite !== false) {
            error_log("Querying for post type: " . $post_type);
            $post_check_query = new WP_Query(['post_type' => $post_type,'post_status' => 'publish','posts_per_page' => 1,'fields' => 'ids']);
            if (!empty( $post_check_query->posts)) {
                $post_type_options[$post_type] = $post_type_obj->label;
            }
        }
    }
    }
    return  $post_type_options;
}
function awesome_get_taxonomies_for_post_type($post_type) {
    static $taxonomy_options_cache = [];
    if (!isset($taxonomy_options_cache[$post_type])) {
        $taxonomy_options = [];
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        foreach ($taxonomies as $taxonomy_slug => $taxonomy_obj) {
            $has_non_empty_term = get_terms(['taxonomy' => $taxonomy_slug, 'hide_empty' => true, 'fields' => 'ids', 'number' => 1]);
            if (!empty($has_non_empty_term)) {
                $taxonomy_options[$taxonomy_slug] = $taxonomy_obj->label;
            }
        }
        $taxonomy_options_cache[$post_type] = $taxonomy_options;
    }
    return $taxonomy_options_cache[$post_type];
}
add_action('wp_ajax_awesome_get_taxonomies_for_post_type', function() {
    wp_send_json_success(awesome_get_taxonomies_for_post_type($_POST['slug']));
});
function getTermOptions($taxonomy) {
    $options = [];
    if ($taxonomy ===''){ return $options;}
     error_log("Querying for terms in taxonomy: " . $taxonomy);
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    foreach ($terms as $term) {
        $options[$term->term_id] = $term->name;
    }
    return $options;
}
function handle_getTermObjects() {
    wp_send_json_success(getTermOptions($_POST['slug']));
}
add_action('wp_ajax_getTermObjects', 'handle_getTermObjects');
add_action('wp_ajax_remove_custom_context', 'remove_custom_context');
function remove_custom_context() {
  $context_name = $_POST['context_name'];
  $contexts = get_option('contexts');
  if (!isset($contexts[$context_name])) {
       return;
  }
  unset($contexts[$context_name]);
  update_option('contexts', $contexts);
  wp_send_json_success(['message' => "Context '{$context_name}' removed."]);
}
function render_relationships_settings_page() {
    $start_time = microtime(true);

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['relationships_settings_nonce'], 'save_relationships_settings')) {
        handle_save_relationships_settings(); 
    }

    $contexts = get_option('contexts', []);
    if (empty($contexts)) {
        $contexts = define_initial_individual_contexts();
        update_option('contexts', $contexts);
    }
    $enabledtogglesubfields = [];
    $contexts_config = [];

    foreach ($contexts as $context_name => $context_data) {
        $context_type    = $context_data['context_type'];
        $field_keys = $context_data['fields'];
        $context_name_string = $context_data['context_name'];
        if (!isset($contexts_config[$context_type])) {
            $contexts_config[$context_type] = [
                'type' => 'visual_section',
                'fields' => [],
            ];
        }
        $contexts_config[$context_type]['fields'][$context_name_string] = [
            'type' => 'group',
             'label' => ucwords(str_replace(['_', '-'], ' ', $context_name_string)),  
             'fields' =>[],
             'condition' => ['field' => "{$context_name_string}_enabled", 'values' => ["1"], 'current_value' => '']
            ];
     $field_keys = assemble_fields($context_name_string, $context_type, $field_keys);
     $filtered_fields = [];
        
      foreach ($field_keys as $field_key => $field_data) {
            if ($field_key === 'remove') {
            $filtered_fields[$field_key] = $field_data;
        } else {
            $filtered_fields[$field_key] = $field_data['value'];
        }
           if ($field_key === 'enabled') {
              $enabledtogglesubfields["{$context_name_string}_{$field_key}"] = $field_data;
              $contexts_config[$context_type]['fields'][$context_name_string]['condition']['current_value'] = $field_data['value'];

            } else {
              $contexts_config[$context_type]['fields'][$context_name_string]['fields'][$field_key] = $field_data;
            }
        }
        $contexts[$context_name_string] = ['context_name' => $context_name_string, 'context_type' => $context_type, 'fields' => $filtered_fields];
    }
        $locations = $contexts['locations'];
        unset($contexts['locations']);
        $contexts = ['locations' => $locations]+$contexts;
        update_option('contexts', $contexts);
        $enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', "0");
        
        $enabledtogglesubfields['enable_custom_fields_for_pages'] = [
            'type'  => 'toggle',
            'value' => $enable_custom_fields_for_pages_value,
            ];
   
    ?>
    <form method="post" action="<?= admin_url('admin.php?page=dibraco-relationships'); ?>">
        <div id="relationships-settings"> 
                    <?php wp_nonce_field('save_relationships_settings', 'relationships_settings_nonce'); ?>

            <div class="toggles-sidebar">
                <button type="button" id="add-context-btn" class="button field_group-secondary">Add Context</button>
                <div id="add-context-section" style="display: none;">
                    <?= FormHelper::generateTextInput('new_context_name', 'Context Name:', null); ?>
                    <?= FormHelper::generateRadioFieldset('new_context_type', 'Context Type:', 'type', ['unique' => 'Unique', 'type' => 'Type']); ?>
                    <button type="button" id="confirm-add-context" class="button button-secondary">Confirm</button>
                </div>
                <?= FormHelper::generateVisualSection('toggles_section', ['label'  => 'Context On/Off', 'fields' => $enabledtogglesubfields]); ?>
            </div>
            <?= FormHelper::generateVisualSection('contexts', ['fields' => $contexts_config]); ?>
            </div>
        <button type="submit" class="button button-primary">Submit</button>
    </form>
    <?php
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000000;
    error_log("Schema generation took " . number_format($execution_time, 2) . " microseconds.");
}

function handle_save_relationships_settings() {
    if (!wp_verify_nonce($_POST['relationships_settings_nonce'], 'save_relationships_settings')) {
        wp_die('Nonce verification failed or missing nonce.');
    }
    
    $contexts = get_option('contexts');
    $enabled_context_names = [];
    $front_part ='';
    if (preg_match('~^/(.+?)/%~', get_option('permalink_structure'), $matches)) {
        $front_part = $matches[1];
    }
    $enabled_contexts = [];
    foreach ($contexts as $context_name => $context_data) {
        if ($_POST["{$context_name}_enabled"] === "1") {
            if (($_POST["{$context_name}_post_type"]==='') || ((isset($_POST["{$context_name}_taxonomy"]) && ($_POST["{$context_name}_taxonomy"]==='')))) {
                    $message = "Context $context_name did not have a set post type or taxonomy";
                    wp_redirect(add_query_arg(['status' => 'error', 'message' => urlencode($message)], admin_url('admin.php?page=dibraco-relationships'))); 
                    exit;
                }
           $enabled_context_names[] = $context_name;
           $enabled_contexts[$context_name] = [
                'context_name' => $context_name,
                'context_type' => $context_data['context_type'],
            ];
        foreach ($context_data['fields'] as $field_key => $field_data) {
            if ($field_key === 'remove') continue;
               if ($field_key === 'enabled') {
                 $contexts[$context_name]['fields']['enabled'] = "1";
                continue;
                }
                $field_value = $_POST["{$context_name}_{$field_key}"];
                if ($field_key ==='post_type'){
                   $enabled_contexts[$context_name]['permalink_data']= dibraco_get_context_permalink_data($field_value, $front_part);
                }
                $contexts[$context_name]['fields'][$field_key] = $field_value;
                $enabled_contexts[$context_name][$field_key] = $field_value;
            }
        } else {
            foreach ($context_data['fields'] as $field_key => $field_data) {
                $field_value = $_POST["{$context_name}_{$field_key}"];
                if ($field_key === 'remove') continue;
                if ($field_key === 'enabled') {
                    $contexts[$context_name]['fields']['enabled'] = "0";
                } else {
                    $contexts[$context_name]['fields'][$field_key] = $field_value;
                }
            }
        }
    }
    update_option('contexts', $contexts);
   
 
    $locations_enabled = false;
    $service_areas_enabled = false;

    foreach ($enabled_contexts as $connector_context_name => $enabled_connector) {
        if ($enabled_connector['context_type'] !== 'connector') {
            continue;
        }
        $connector_post_type = $enabled_connector['post_type'];
        $connector_taxonomy = $enabled_connector['taxonomy'];
        $connector_schema = $enabled_connector['schema'];
        if ($connector_context_name === 'locations') {
            if ((!empty($enabled_connector['main_term']) && $enabled_connector['ignore_main_term']==="0")) {
                $main_term_id = (int)$enabled_connector['main_term'];
                update_option('main_term', $main_term_id);
            } else {
                delete_option('main_term');
            }
            $locations_enabled = true;
        }
        if ($connector_context_name === 'service_areas') {
            $service_areas_enabled = true;
        }

        $enabled_contexts[$connector_context_name]['related_type_contexts'] = [];
        $enabled_contexts[$connector_context_name]['related_unique_contexts'] = [];
        foreach ($enabled_contexts as $other_name => $other_data) {
            $other_type = $other_data['context_type'];
                if ($other_type !== 'type' && $other_type !== 'unique') {
                continue;           
            }
            if (!isset($enabled_contexts[$other_name]['related_connectors'])) {
                $enabled_contexts[$other_name]['related_connectors'] = [];   
            }
            $other_post_type = $other_data['post_type'];
            $schema = $other_data['schema'];
            $matches = in_array($connector_taxonomy, get_object_taxonomies($other_post_type, 'names'), true) || in_array($other_post_type, get_taxonomy($connector_taxonomy)->object_type, true);
            if (!$matches) { continue; }
            
         if ($other_type === 'type') {
                $enabled_contexts[$connector_context_name]['related_type_contexts'][$other_name] = [
                'type_name'     => $other_name,
                'schema' =>  $schema,
                'post_type' => $other_post_type,
                'taxonomy' => $other_data['taxonomy'],
                'post_per_term' => $other_data['post_per_term'],
             ];
        } else { 
                $enabled_contexts[$connector_context_name]['related_unique_contexts'][$other_name] = [
                'unique_name' => $other_name,
                'schema' =>  $schema,
                'post_type' => $other_post_type,
            ];
        }
        $enabled_contexts[$other_name]['related_connectors'][$connector_context_name] = [
            'connector_name' => $connector_context_name,
            'schema' => $connector_schema,
            'taxonomy' => $connector_taxonomy,
            'post_type' => $connector_post_type,
         ];
         if ($connector_context_name === "locations" && $ignore_main_term !=="1"){
            $enabled_contexts[$other_name]['related_connectors'][$connector_context_name][] = ['main_term' => $main_term_id];
         }
        }        
    } 
foreach ($enabled_contexts as $context_name => $context_data) {
    if ($context_data['context_type'] === 'type' || $context_data['context_type'] === 'unique') {
        $enabled_contexts[$context_name]['related_connector_count'] = 0;
        if (!empty($context_data['related_connectors'])) {
            $related_count = count($context_data['related_connectors']);
            $enabled_contexts[$context_name]['related_connector_count'] = $related_count;
            foreach ($context_data['related_connectors'] as $related_connector_name => $connector_info) {
                if ($context_data['context_type'] === 'type') {
                    $enabled_contexts[$related_connector_name]['related_type_contexts'][$context_name]['related_connector_count'] = $related_count;
                } elseif ($context_data['context_type'] === 'unique') {
                    $enabled_contexts[$related_connector_name]['related_unique_contexts'][$context_name]['related_connector_count'] = $related_count;
                }
            }
        }
    }
}    


$status = 'none';
$cleanup_contexts = ['locations', 'service_areas'];
$setup_contexts = [];
if ($locations_enabled === true && $service_areas_enabled === true) {
    $status = 'both';
    $cleanup_contexts = [];
    $setup_contexts = ['locations', 'service_areas'];
} elseif ($locations_enabled === true && $service_areas_enabled === false) {
    $status = 'multi_locations';
    $cleanup_contexts = ['service_areas'];
    $setup_contexts = ['locations'];
} elseif ($locations_enabled === false && $service_areas_enabled === true) {   
    $status = 'multi_areas';
    $setup_contexts = ['service_areas'];
    $cleanup_contexts = ['locations'];
}

update_option('locations_areas_status', $status);

$enabled_connector_contexts = [];
$enabled_type_contexts = [];
$enabled_unique_contexts = [];

foreach ($enabled_contexts as $context_key => $context_data_item) {
    $bucket_item_data = $context_data_item;
    unset($bucket_item_data['context_type']);
    if ($context_data_item['context_type'] === 'connector') {
        $enabled_connector_contexts[$context_key] = $bucket_item_data;
    } elseif ($context_data_item['context_type'] === 'type') {
        $enabled_type_contexts[$context_key] = $bucket_item_data;
    } elseif ($context_data_item['context_type'] === 'unique') {
        $enabled_unique_contexts[$context_key] = $bucket_item_data;
    }
}

foreach($enabled_type_contexts as $enabled_type_context => $context_data) {
    if ($context_data['post_per_term'] !=="1"){continue;}
    get_main_posts_for_options($context_data);
}

$current_enabled_connector_contexts = get_option('enabled_connector_contexts', []);
if (!empty($setup_contexts)) {
    foreach ($setup_contexts as $setup_connector_context_name) {
        $connector_post_type = $enabled_connector_contexts[$setup_connector_context_name]['post_type'];
        $connector_taxonomy = $enabled_connector_contexts[$setup_connector_context_name]['taxonomy'];
        $meta_prefix = rtrim($setup_connector_context_name, 's');
        $post_id_key = "{$meta_prefix}_post_id";
        $link_url_key = "{$meta_prefix}_link_url";
        dibraco_setup_connector_term_posts($connector_taxonomy, $connector_post_type, $post_id_key, $link_url_key);
        $related_unique_contexts = $enabled_connector_contexts[$setup_connector_context_name]['related_unique_contexts'];
        if (!empty($related_unique_contexts)) {
            foreach ($related_unique_contexts as $unique_context_name => $unique_context_data) {
                $unique_post_type = $unique_context_data['post_type'];
                setup_connected_unique_posts($connector_taxonomy, $unique_post_type, $unique_context_name);
            }
        }
        $related_type_contexts = $enabled_connector_contexts[$setup_connector_context_name]['related_type_contexts'];
        if (!empty($related_type_contexts)) {
            foreach ($related_type_contexts as $type_context_name => $type_context_data) {
                $type_post_type = $type_context_data['post_type'];
                $type_taxonomy = $type_context_data['taxonomy'];
                $post_per_term = $type_context_data['post_per_term'];
                $related_type_meta_key = "related_type_{$type_context_name}";
                $related_connector_count = $type_context_data['related_connector_count'];
                if ($post_per_term === "1") {
                    $service_area_taxonomy_for_locations = '';
                    if ($setup_connector_context_name === 'locations' && $related_connector_count === 2) {
                        $service_area_taxonomy_for_locations = $enabled_connector_contexts['service_areas']['taxonomy'];
                    }
                    dibraco_setup_type_post_list_with_fallback($connector_taxonomy, $type_post_type, $type_taxonomy, $related_connector_count, $type_context_name, $service_area_taxonomy_for_locations);
                } else {
                    setup_connected_type_posts_no_fallback($connector_taxonomy, $type_post_type, $type_taxonomy, $related_type_meta_key);
                }
            }
        }
    }
}

if (!empty($cleanup_contexts)){
foreach ($cleanup_contexts as $cleanup_context){
if (isset($current_enabled_connector_contexts[$cleanup_context])) {
     $current_taxonomy = $current_enabled_connector_contexts[$cleanup_context]['taxonomy'];
        $meta_prefix = rtrim($cleanup_context, 's');
        $post_id_key = "{$meta_prefix}_post_id";
        $link_url_key = "{$meta_prefix}_link_url";
        delete_term_meta_old_taxonomy($current_taxonomy, $post_id_key, $link_url_key);
        $current_related_unique_contexts = $current_enabled_connector_contexts[$cleanup_context]['related_unique_contexts'];
        $current_related_type_contexts   = $current_enabled_connector_contexts[$cleanup_context]['related_type_contexts'];
        if (!empty($current_related_unique_contexts)) {
            foreach ($current_related_unique_contexts as $name => $data) {
                $meta_key_to_delete = "related_unique_{$name}";
                delete_related_other_context_term_meta($current_taxonomy, $meta_key_to_delete);
            }
        }
        if (!empty($current_related_type_contexts)) {
            foreach ($current_related_type_contexts as $name => $data) {
                $meta_key_to_delete = "related_type_{$name}";
                delete_related_other_context_term_meta($current_taxonomy, $meta_key_to_delete);
            }
        }
    }
}
}
    $enable_custom_fields_pages = $_POST['enable_custom_fields_for_pages'];
    update_option('enable_custom_fields_for_pages', $enable_custom_fields_pages);
    update_option('enabled_connector_contexts', $enabled_connector_contexts);
    update_option('enabled_type_contexts', $enabled_type_contexts);
    update_option('enabled_unique_contexts', $enabled_unique_contexts);
    update_option('enabled_context_names', $enabled_context_names);
    $selected = get_option('selected_contexts',[]);
    if (!empty($selected)) {
        $cleaned = array_values(array_intersect($selected, $enabled_context_names));
    update_option('selected_contexts', $cleaned);
    }
    update_option('enabled_contexts', $enabled_contexts);
    wp_redirect(add_query_arg('status', 'success', admin_url('admin.php?page=dibraco-relationships')));
    exit;
}
add_action('admin_post_handle_save_relationships_settings', 'handle_save_relationships_settings');
function dibraco_setup_connector_term_posts( $taxonomy, $post_type, $post_id_meta_key, $post_url_meta_key ) {
    $all_terms = get_terms(['taxonomy' => $taxonomy,'hide_empty' => false, 'fields' => 'ids']);
    $all_posts = get_posts(['post_type'=> $post_type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
    $relationships = wp_get_object_terms($all_posts, $taxonomy, [
        'fields' => 'all_with_object_id',
    ]);
    wp_delete_object_term_relationships($all_posts, $taxonomy);
    $post_to_term = [];
    foreach($relationships as $relationship) {
        $post_id = $relationship->object_id;
        if(!isset($post_to_term[$post_id])) {
            $post_to_term[$post_id] = $relationship->term_id;
        }
    }
    $term_to_post = [];
    foreach($post_to_term as $post_id => $term_id) {
        wp_set_object_terms($post_id, (int)$term_id, $taxonomy, false);
        update_term_meta($term_id, $post_id_meta_key, $post_id);
        update_term_meta($term_id, $post_url_meta_key, get_permalink($post_id));
        $term_to_post[$term_id] = true;
    }
    foreach($all_terms as $term_id) {
        $city = get_term_meta( $term_id, 'city', true ) ?? '';
        if ($city === '') {
            $term_obj = get_term($term_id);
            update_term_meta( $term_id, 'city', $term_obj->name );
        }
        if(empty($term_to_post[$term_id])) {
            update_term_meta($term_id, $post_id_meta_key, '');
            update_term_meta($term_id, $post_url_meta_key, '');
        }
    }
}
function delete_term_meta_old_taxonomy($taxonomy, $post_id_meta_key, $post_url_meta_key){
    $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ]);
    if (!empty($terms)){
      foreach ($terms as $term) {
        delete_term_meta( $term->term_id, $post_id_meta_key );
        delete_term_meta( $term->term_id, $post_url_meta_key );
        } 
    }
}
function delete_related_other_context_term_meta($taxonomy, $meta_key_to_delete){
    $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false ]);
    if (!empty($terms)){
      foreach ($terms as $term) {
        delete_term_meta( $term->term_id, $meta_key_to_delete);
      }
}
}
function dibraco_get_related_post_ids($post_type, $tax_query, $posts_per_page = -1, $fields = 'ids', $exclude = []) {
    $query_args = ['post_type' => $post_type,'posts_per_page' => $posts_per_page,'tax_query' => $tax_query,'post_status' => 'publish','fields' => $fields ];
    if (!empty($exclude)) {
        $query_args['exclude'] = $exclude;
    }
    return get_posts($query_args);
}

/**
 * Normalize any "term IDs" input into a flat, numeric array of ints.
 * Accepts: ints, strings, CSV, plain arrays, or associative arrays.
 * If associative, we treat the **keys** as the term IDs.
 */
function dibraco_int_all_terms($terms) {
    $first_item = reset($terms);
    if (is_object($first_item)) {
        return $terms;
    }
    if (is_numeric($first_item)) {
        return array_map('intval', $terms);
    }
    $processed = [];
    foreach ($terms as $id => $name) {
        $processed[(int) $id] = $name;
    }
    return $processed;
}
function get_main_posts_for_options($context_data) {
    $context_name = $context_data['context_name'];
    $type_taxonomy = $context_data['taxonomy'];
    $type_post_type =$context_data['post_type'];
    $type_term_ids = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true, 'fields' => 'ids']);
    $type_term_ids = dibraco_int_all_terms($type_term_ids);
    $number_of_type_terms = count($type_term_ids);
    $related_connector_count = $context_data['related_connector_count'];
    if ($related_connector_count ===2 ){
        $locations_context = $context_data['related_connectors']['locations'];
        $locations_taxonomy = $locations_context['taxonomy'];
        $ignore_main_term = $locations_context['ignore_main_term'];
        if ($ignore_main_term ==="1"){
        $locations_main_term = $locations_context['main_term'] ?? '';
        }
    }
    $main_post_map = get_option("{$context_name}_main_posts");
    if (!empty($main_post_map)) {
        $main_post_map = validate_main_post_type_map($main_post_map, $type_taxonomy);
        $number_of_pairs = count($main_post_map);
        if ($number_of_type_terms !==$number_of_pairs){
            $terms_with_posts = array_keys($main_post_map);
            $type_term_ids = array_diff($type_term_ids, $terms_with_posts);
        } else {
            update_option("{$context_name}_main_posts", $main_post_map);
            return;
        }
    }   
    if (!empty($type_term_ids)){
        $taxonomy_in_page = in_array($type_taxonomy, get_object_taxonomies('page', 'names'), true) || in_array('page', get_taxonomy($type_taxonomy)->object_type, true);
         foreach ($type_term_ids as $type_term_id) {
            $main_post_id = figure_out_main_post_for_term($type_term_id, $type_post_type);
            $main_post = '';
            $base_tax_query = [['taxonomy' => $type_taxonomy, 'field' => 'term_id', 'terms' => $type_term_id]];
            if ($taxonomy_in_page) {
                $main_post = dibraco_get_related_post_ids('page', $base_tax_query, 1, 'ids');
            }
             if (empty($main_post)) {
                if ($related_connector_count === 2) {
                    if (!empty($locations_main_term)) {
                        $specific_tax_query = [
                            'relation' => 'AND',
                            $base_tax_query[0],
                            ['taxonomy' => $locations_taxonomy, 'field' => 'term_id', 'terms' => $locations_main_term],
                        ];
                        $main_post = dibraco_get_related_post_ids($type_post_type, $specific_tax_query, 1, 'ids');
                    }
                   if (empty($main_post)) {
                        $any_location_tax_query = ['relation' => 'AND', $base_tax_query[0],
                            ['taxonomy' => $locations_taxonomy, 'operator' => 'EXISTS'], ];
                        $main_post = dibraco_get_related_post_ids($type_post_type, $any_location_tax_query, 1, 'ids');
                    }
                }
                if (empty($main_post)) {
                    $main_post = dibraco_get_related_post_ids($type_post_type, $base_tax_query, 1, 'ids');
                        if (empty($main_post)) {
                        $main_post = dibraco_get_related_post_ids('any', $base_tax_query, 1, 'ids');
                    }
                }
                $main_post_id = (int) $main_post[0];
                update_term_meta($type_term_id, 'main_post_for_term', $main_post_id);
                $main_post_map[$type_term_id] = $main_post_id;
               
            }
         }
         $option_name = "{$context_name}_main_posts";
         update_option($option_name, $main_post_map);
         return;
    }
return; //catchall return
}

function validate_main_post_type_map($main_post_map, $type_taxonomy){
    $verified_pairs=[];
    foreach ($main_post_map as $type_term_id => $post_id) {
        $type_term_id = (int)$type_term_id;
        $post_id = (int)$post_id;
        $post_status= get_post_status($post_id); 
        if ($post_status ==='publish' && term_exists($type_term_id)){
            $posts_type_term_id = dibraco_get_current_term_id_for_post($post_id, $type_taxonomy);
            if ($type_term_id !==$posts_type_term_id){
                update_term_meta($type_term_id, 'main_post_for_term', '');
                continue;
            } else {
                $verified_pairs[$type_term_id] = $post_id;
                update_term_meta($type_term_id, 'main_post_for_term', $post_id);
                continue;
            }
        }
    }
    return $verified_pairs;
}

function setup_connected_unique_posts($connector_taxonomy, $unique_post_type, $unique_context_name) {
    $related_unique_meta_key = "related_unique_{$unique_context_name}";
    $connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
    $connector_terms = dibraco_int_all_terms($connector_terms); 
    foreach ($connector_terms as $connector_id => $connector_term_name) {
        $entries = [];
        $specific_tax_query = [
            ['taxonomy' => $connector_taxonomy, 'field' => 'term_id', 'terms' => $connector_id],
        ];
        $found_posts = dibraco_get_related_post_ids($unique_post_type, $specific_tax_query, -1, 'objects');
        if (!empty($found_posts)) {
            foreach ($found_posts as $post) {
                $post_id = $post->ID;
                $entries[$post_id] = [
                    'related_post_title' => $post->post_title,
                    'related_post_id'    => $post_id,
                    'related_post_url'   => get_permalink($post_id)
                ];
            }
        }
        update_term_meta($connector_id, $related_unique_meta_key, $entries);
    }
}

function dibraco_get_all_type_term_fallback_urls($source, $related_type_context_name) {
    $entries = [];
    if ($source === 'options') {
        $fallback_source = get_option("{$related_type_context_name}_main_posts");
            foreach ($fallback_source as $type_id => $post_id) {
                $post_id = (int)$post_id;
                $type_id =(int)$type_id;
                $fallback_url = get_permalink($post_id);
                $entries[$type_id]['fallback_url'] = $fallback_url;
            }
        } else {
           $fallback_source = get_term_meta($source, "related_type_$related_type_context_name", true);
            foreach ($fallback_source as $type_id => $parent_term_fauxpost_data) {
                $fallback_url = $parent_term_fauxpost_data['related_post_url'];
                if (empty($fallback_url)) {
                    $fallback_url = $parent_term_fauxpost_data['fallback_url'];
                }
                $entries[$type_id]['fallback_url']= $fallback_url;
            }
        }
    return $entries;
}
function dibraco_setup_type_post_list_with_fallback($connector_taxonomy, $type_post_type, $type_taxonomy, $related_connector_count, $related_type_context_name, $service_area_taxonomy_for_locations = '') {
    $connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
    $type_terms = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true, 'fields' => 'id=>name']);
    foreach ($connector_terms as $connector_id => $connector_term_name) {
    $related_type_meta_key = "related_type_{$related_type_context_name}";
        $connector_id =(int)$connector_id;
        $entries = [];
        $fallback_source = 'options';
        if ($related_connector_count===2 && $service_area_taxonomy_for_locations ===''){
            $area_parent_location_term = get_term_meta($connector_id, 'area_parent_location_term', true);
            if (!empty($area_parent_location_term)){
            $area_parent_location_term = (int)$area_parent_location_term;
            $fallback_source = $area_parent_location_term;
        }
        }
        $fallback_urls_array =  dibraco_get_all_type_term_fallback_urls($fallback_source, $related_type_context_name);
        foreach ($type_terms as $type_id => $type_term_name) {
            $type_id = (int)$type_id;
            $specific_tax_query = [
                'relation' => 'AND',
                ['taxonomy' => $connector_taxonomy, 'field' => 'term_id', 'terms' => $connector_id],
                ['taxonomy' => $type_taxonomy,      'field' => 'term_id', 'terms' => $type_id],
            ];
            if ($service_area_taxonomy_for_locations !== '') {
                $specific_tax_query[] = ['taxonomy' => $service_area_taxonomy_for_locations, 'operator' => 'NOT EXISTS'];
            }
            $found_posts = dibraco_get_related_post_ids($type_post_type, $specific_tax_query, 1, 'ids');
            $post_id = '';
            $post_url = '';
            if (!empty($found_posts)) {
                $post_id = $found_posts[0];
                $post_url = get_permalink($post_id);
            }
           $fallback_url= $fallback_urls_array[$type_id]['fallback_url'];
            $entries[$type_id] = [
                'related_post_title' => "{$type_term_name} In {$connector_term_name}",
                'related_post_id'    => $post_id,
                'related_post_url'   => $post_url,
                'fallback_url'       => $fallback_url,
            ];
        }
        update_term_meta($connector_id, $related_type_meta_key, $entries);
    }
}
function setup_connected_type_posts_no_fallback($connector_taxonomy, $type_post_type, $type_taxonomy, $related_type_meta_key) {
    $connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
    $type_terms = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true, 'fields' => 'id=>name']);
    foreach ($connector_terms as $connector_id => $connector_term_name) {
        $entries = [];
        foreach ($type_terms as $type_id => $type_term_name) {
            $type_id = (int)$type_id;
            $entries[$type_id] = [];
            $specific_tax_query = [
                'relation' => 'AND',
                ['taxonomy' => $connector_taxonomy, 'field' => 'term_id', 'terms' => $connector_id],
                ['taxonomy' => $type_taxonomy,      'field' => 'term_id', 'terms' => $type_id],
            ];
            $found_posts = dibraco_get_related_post_ids($type_post_type, $specific_tax_query, -1, 'ids');
            if (!empty($found_posts)) {
                foreach ($found_posts as $post_id) {
                    $entries[$type_id][$post_id] = [
                        'related_post_title' => "{$type_term_name} In {$connector_term_name}",
                        'related_post_id'    => $post_id,
                       'related_post_url'   => get_permalink($post_id)
                    ];
                }
            }
        }
        update_term_meta($connector_id, $related_type_meta_key, $entries);
    }
}

function dibraco_get_context_permalink_data($post_type, $front_part) {
    $post_type_object = get_post_type_object($post_type);
    $segments = 0;
    $is_hierarchical = "0";
    if ($post_type_object->hierarchical === true){
         $is_hierarchical = "1";
    }
    $archive_slug = ''; 
    $has_archive = $post_type_object->has_archive;
    if (is_string($has_archive)) {
        $archive_slug = $has_archive;
        $segments++;
    }
    $rewrite_data = (array) $post_type_object->rewrite;
    if ( empty($rewrite_data['with_front']) ) {
        $front_part = '';
    }
    $rewrite_slug = $rewrite_data['slug'] ?? '';
    if (!empty($front_part)) {
        $segments++;
    }
    if (!empty($rewrite_slug)) {
        $segments++;
    }
   return [
        'hierarchical' => $is_hierarchical,
        'front' => $front_part,
        'slug' => $rewrite_slug,
        'archive' => $archive_slug,
        'postname' => '%postname%',
        'segment_count' => $segments + 1,
    ];
}
function dibraco_get_current_term_id_for_post($post_id,  $taxonomy) {
   $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids', 'number' => 1]);
   $current_term_id = '';
   if (!empty($terms) && !is_wp_error($terms)) {
       $current_term_id = (int)$terms[0];
    }
    return $current_term_id;
}

function dibraco_enforce_one_connector_term_per_connector_post($post_id, $connector_term_id, $connector_taxonomy, $post_id_meta_key, $link_url_meta_key) {
    $old_term_id = dibraco_get_current_term_id_for_post($post_id, $connector_taxonomy);
    if ($old_term_id!=='') {
        $clear_id_result = update_term_meta($old_term_id, $post_id_meta_key, '');
        $clear_url_result = update_term_meta($old_term_id, $link_url_meta_key, '');
    }
    if ($connector_term_id!=='') {
        $connector_term_id = (int)$connector_term_id;
        $existing_post_id = get_term_meta($connector_term_id, $post_id_meta_key, true);
        if ($existing_post_id !=='') {
           wp_set_object_terms((int)$existing_post_id, [], $connector_taxonomy, false);
        }
        $update_id_result = update_term_meta($connector_term_id, $post_id_meta_key, $post_id);
        $update_url_result = update_term_meta($connector_term_id, $link_url_meta_key, get_permalink($post_id));
    }
    wp_set_object_terms($post_id, $connector_term_id, $connector_taxonomy, false);
}

function dibraco_verify_post_save_request($nonce_field_name, $nonce_action) {
    if (!isset($_POST[$nonce_field_name])) return false;
    if (!wp_verify_nonce($_POST[$nonce_field_name], $nonce_action)) return false;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
    if (!current_user_can('edit_posts')) return false;
    return true;
}
/*/
add_action('add_meta_boxes', 'dibraco_add_meta_boxes', 10, 2);
function dibraco_add_meta_boxes($post_type, $post) {
  $enabled_contexts = get_option('enabled_contexts');
    if (empty($enabled_contexts)) return;
    foreach ($enabled_contexts as $context => $context_data) {
        $context_type  = $context_data['context_type'];
        $context_name  = $context_data['context_name'];
        $post_type  = $context_data['post_type'];
        if ($context_type !=='unique'){
            $taxonomy = $context_data['taxonomy'];
        } 
        
        
add_meta_box(
    'dibraco_post_side_box',
    'Taxonomies and Images',
    'dibraco_render_side_metabox',
    $post_type,
    'side',
    'default'
    );
add_meta_box(
    'dibraco_post_content_meta_box', 
    'Post Fields', 
    'dibraco_render_content_metabox', 
    $post_type,
    'normal',
    'default'
    );

if ($context_name ==='jobs'){
add_meta_box(
    'job_meta_box', 
    'Jobs Custom Fields', 
    'display_job_meta_box', 
    $post_type, 
    'normal', 
    'high'
    );
}
if ($context_name ==='locations'){
}

}
}
*/


add_action('admin_init', function () {
    $custom_fields_for_pages = get_option('enable_custom_fields_for_pages', '0');
        if ($custom_fields_for_pages ==="1"){
            add_meta_box('side_image_fields_page', 'Page Images', 'da_render_images_for_pages', 'page', 'side', 'default');
            add_meta_box('section_fields_page', 'Post Fields', 'render_da_pages_metabox', 'page', 'normal', 'default');
            add_action('save_post_page', 'dibraco_save_section_fields_page');
            
        }
    $enabled_contexts = get_option('enabled_contexts');
    if (empty($enabled_contexts)) return;
    
    foreach ($enabled_contexts as $context => $context_data) {
        $context_type  = $context_data['context_type'];
        $context_name  = $context_data['context_name'];
        $post_type     = $context_data['post_type'];
        $screens = [$post_type];
       if ($context_type !== 'unique') {
            $taxonomy = $context_data['taxonomy'];
        }
        if($context_name ==='locations'){
             $screens = [$post_type, "edit-{$taxonomy}"];
        }
add_meta_box(
    'dibraco_post_side_box',
    'Taxonomies and Images',
    'dibraco_render_side_metabox',
    $post_type,
    'side',
    'default',
      ['__block_editor_compatible_meta_box' => true, 'class' => 'postbox']
    );
add_meta_box(
    'dibraco_post_content_meta_box', 
    'Post Fields', 
    'dibraco_render_content_metabox', 
    $post_type,
    'normal',
    'default',
    ['__block_editor_compatible_meta_box' => true, 'class' => 'postbox']
);
        $pairings = [];
        add_action("save_post_{$post_type}",'dibraco_save_meta_boxes', 20, 3);
        $schema_type = $context_data['schema'];
        $primary_meta = $context_data['primary_meta'];
        $dibraco_banner = $context_data['dibraco_banner'];
        $about = $context_data['about_section'];
        $main_sections = $context_data['main_sections']; 
        $contact_fields = $context_data['contact_section'];
        $portrait_images = $context_data['portrait_images'];
        $landscape_images = $context_data['repeater_images'];
        if ($context_type !== 'unique') {
            $taxonomy = $context_data['taxonomy'];
            $pairing = ['taxonomy' => $taxonomy, 'context_name' => $context_name];
            if ($context_type === 'type') {
                $post_per_term = $context_data['post_per_term'];
                if($post_per_term !=='1'){
                    add_action("created_{$taxonomy}", function($term_id) use($context) {
                    setup_brand_new_type_term_no_ppt($new_type_term_id, $context);}, 10, 1);
                }
                if($post_per_term === "1"){
                $pairing['main_post_for_term'] = true;
                }
            }
            $pairings[] = $pairing;
        }
        if ($context_type ==='type' || $context_name ==='employee' || $context_name ==='locations'){
            $has_certification = $context_data['has_certification']??'';
            if ($context_type ==='type') {
                $before_after = $context_data['before_after'];
                $post_per_term = $context_data['post_per_term'];
                $term_icon = $context_data['term_icon'];
            }
         }
         if ($context_type === 'type' || $context_type === 'unique'){
              $related_connectors = $context_data['related_connectors'];
              $related_connector_count = $context_data['related_connector_count'];
         }
         if ($context_type ==='connector'){
              $related_type_contexts = $context_data['related_type_contexts'];
              $related_unique_contexts  = $context_data['related_unique_contexts'];
         }
        if ($context_type === 'type' || $context_type === 'connector') {
             add_action("{$taxonomy}_edit_form_fields", 'render_dibraco_term_fields', 10, 1);
             add_action("edited_{$taxonomy}", 'save_dibraco_general_term_fields', 5, 1);
        }
        

      
            if($context_name === 'service_areas') {
                $related_type_contexts = $context_data['related_type_contexts'];
                add_action("created_{$taxonomy}", function($term_id) use($related_type_contexts, $taxonomy) {
                setup_related_type_terms_for_new_connector_term($term_id, $related_type_contexts, $context_name, $taxonomy);}, 11, 1);
                add_action( "{$taxonomy}_edit_form_fields", function($term) use ($taxonomy, $context_data) {
                display_service_area_term_fields($term, $taxonomy, $context_data);}, 10, 1); 
                add_action( "edited_{$taxonomy}", function($term_id) use ($context_data) {
                handle_save_service_area_term_related_types($term_id,$_POST,$context_data );}, 10, 1 );
            }
            if($context_name ==='locations' && $primary_meta==="1"){
                $related_type_contexts = $context_data['related_type_contexts'];
                add_action("created_{$taxonomy}", function($term_id) use($related_type_contexts, $taxonomy) {
                setup_related_type_terms_for_new_connector_term($term_id, $related_type_contexts, $context_name, $taxonomy);}, 11, 1);
                add_meta_box('location_details_meta_box', 'Location Details', 'render_location_meta_box', "edit-{$taxonomy}", 'normal', 'high');
                add_action("{$taxonomy}_edit_form_fields", function($term) use ($taxonomy) {
                do_meta_boxes("edit-{$taxonomy}", 'normal', $term);}, 110, 1);
                add_action("edited_{$taxonomy}", function($term_id) use ($taxonomy) {
                handle_save_location_term_meta($term_id, $taxonomy);}, 25, 1);
            }
            if($context_name ==='jobs'){
             add_meta_box('job_meta_box', 'Jobs Custom Fields', 'display_job_meta_box', $post_type, 'normal', 'high');
             add_action("save_post_{$post_type}", 'save_job_meta_box_data', 20);
             add_action("load-post.php", function() {
                add_action('post_submitbox_misc_actions', function($post) {
                    $valid_through_date_str = get_post_meta($post->ID, '_job_meta', true)['valid_through'] ?? '';
                    if (!empty($valid_through_date_str)) {
                        $expiration_timestamp = strtotime($valid_through_date_str);
                        $formatted_date = date_i18n(get_option('date_format'), $expiration_timestamp);
                        echo '<div class="misc-pub-section curtime misc-pub-expiration-date">';
                        echo '<strong><span id="timestamp"> Expires:</span></strong> ' . $formatted_date . '</span>';
                        echo '</div>';
                    }
                }, 25, 1);
            });
            }

        if ($context_type !== 'connector') {
            $related_connectors = $context_data['related_connectors'];
            if (!empty($related_connectors)) {
                foreach ($related_connectors as $related_connector_context_name => $related_connector_context_data) {
                    $related_taxonomy = $related_connector_context_data['taxonomy'];
                    $pairings[] = [
                        'taxonomy'     => $related_taxonomy,
                        'context_name' => $related_connector_context_name,
                    ];
                }
            }
        }

       
        if (!empty($pairings)) {
            add_remove_custom_column($post_type, $pairings, $context_type);
        }
    }
});

function setup_brand_new_type_term_no_ppt($new_type_term_id, $context){
$type_taxonomy = $context['taxonomy'];
$context_name = $context['context_name'];
$meta_key = "related_type_{$context_name}";
$related_connectors =$context['related_connectors'];
$new_type_term_id = (int)$new_type_term_id;
    if($related_connectors !==[]){
       foreach ($related_connectors as $related_connector => $related_data){
            $existing_meta = get_term_meta($connector_id, $meta_key, true);
            $connector_taxonomy = $related_connector['taxonomy'];
            $connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'ids']);
            foreach($connector_terms as $connector_id){
                $connector_id =(int)$connector_id;
                $existing_meta = get_term_meta($connector_id, $meta_key, true);
                $existing_meta[$new_type_term_id] = []; 
                update_term_meta($connector_id, $meta_key, $existing_meta);
            }
        
        }
    }
}



function unset_type_term_ppt_from_connectors($type_term_id, $related_connectors, $meta_key) {
    foreach ($related_connectors as $connector_context_name => $connector_data) {
        $connector_taxonomy = $connector_data['taxonomy'];
        $connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => true, 'fields' => 'ids']);
        foreach ($connector_terms as $connector_term_id) {
            $connector_term_id = (int) $connector_term_id;
            $existing_meta = get_term_meta($connector_term_id, $meta_key, true);
            unset($existing_meta[$type_term_id]);
            update_term_meta($connector_term_id, $meta_key, $existing_meta);
        }
    }
}
function dibraco_update_one_main_type_post_fallback($type_term_id, $post_permalink, $related_connectors, $meta_key, $related_connector_count) {
    foreach ($related_connectors as $related_connector_context => $related_connector_data) {
        $connector_taxonomy   = $related_connector_data['taxonomy'];
        $related_connector_name = $related_connector_data['context_name'];
        if ($related_connector_name === 'locations' && $related_connector_count === 2) {
            $term_ids = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'ids']);
            foreach ($term_ids as $connector_term_id) {
            $meta = get_term_meta($connector_term_id, $meta_key, true);
                $meta[$type_term_id]['fallback_url'] = $post_permalink;
                update_term_meta($connector_term_id, $meta_key, $meta);
                update_fallbacks_for_service_area_terms($connector_term_id, $meta_key, $type_term_id, $post_id);
             return;
    }
    $term_ids = get_terms([ 'taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'ids' ]);
        foreach ($term_ids as $connector_term_id) {
            $meta = get_term_meta($connector_term_id, $meta_key, true);
            $meta[$type_term_id]['fallback_url'] = $post_permalink;
            update_term_meta($connector_term_id, $meta_key, $meta);
        }
    }
}
}

function update_fallbacks_for_service_area_terms($new_loc_id, $meta_key, $type_term_id, $post_id){
    $fall_back_url = get_permalink($post_id);
    $act_ids = get_term_meta($new_loc_id, 'associated_act_terms', true);
    if (empty($act_ids)) {return;}
    foreach ($act_ids as $act_id) {
        $current_meta = get_term_meta($act_id, $meta_key, true);
        $current_meta[$type_term_id]['fallback_url'] = $fall_back_url;
        update_term_meta($act_id, $meta_key, $current_meta);    
    }
}


function dibraco_activate_new_main_type_term_post_fallback($type_term_id, $related_connectors, $post_permalink, $meta_key) {
    $type_term_name = get_term($type_term_id)->name;
    foreach ($related_connectors as $connector_context_name => $connector_data) {
        $connector_taxonomy = $connector_data['taxonomy'];
        $all_connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false]);
        foreach ($all_connector_terms as $connector_term) {
            $connector_term_id = $connector_term->term_id;
            $connector_term_name = $connector_term->name;
            $existing_meta = get_term_meta($connector_term_id, $meta_key, true);
            $existing_meta[$type_term_id] = [
                'related_post_title' => "$type_term_name in $connector_term_name",
                'related_post_id'    => '',
                'related_post_url'   => '',
                'fallback_url'       => $post_permalink
            ];
            update_term_meta($connector_term_id, $meta_key, $existing_meta);
        }
    }
}


function type_connector_clear_old_meta_not_touching_fallbacks($previous_connector_id, $meta_key, $type_term_id, $post_id, $post_per_term){
 $previous_connector_id;
    $old_meta = get_term_meta($previous_connector_id, $meta_key, true);
    if ($post_per_term === "1") {
        $old_meta[$type_term_id]['related_post_id'] = '';
        $old_meta[$type_term_id]['related_post_url'] = '';
    } else {
        unset($old_meta[$type_term_id][$post_id]);
    }
    update_term_meta($connector_term_id, $meta_key, $old_meta); 
}

function type_connector_update_term_meta($connector_term_id, $meta_key, $type_term_id, $post_id, $post_per_term) {
    $connector_term_name = get_term((int)$connector_term_id)->name;
    $type_term_name = get_term((int)$type_term_id)->name;
    $related_post_title = "$type_term_name in $connector_term_name";
    $current_meta = get_term_meta($connector_term_id, $meta_key, true);
       if ($post_per_term === "1") {
            $current_meta[$type_term_id] = [
                'related_post_id'    => $post_id,
                'related_post_url'   => get_permalink($post_id),
            ];
        } else {
            $current_meta[$type_term_id][$post_id] = [
            'related_post_title' => $related_post_title,
            'related_post_id'    => $post_id,
            'related_post_url'   => get_permalink($post_id),
            ];
        }
    update_term_meta($connector_term_id, $meta_key, $current_meta);
}

function update_related_connector_terms_from_type_post_update($post_id, $context, $previous_type_term_id, $new_type_term_id) {
    $type_context_name = $context['context_name'];
    $post_per_term = $context['post_per_term'];
    $type_taxonomy = $context['taxonomy'];
    $type_post_type = get_post_type($post_id);
    
    $related_connector_count =$context['related_connector_count'];
    $related_connectors = $context['related_connectors'];
    $type_taxonomy = $context['taxonomy'];
    $meta_key = "related_type_{$type_context_name}";
    $post_permalink = get_permalink($post_id);
    $type_has_changed = ($previous_type_term_id != $new_type_term_id);
    if ($related_connector_count === 1) {
    $existing_connector_meta = [];
    $new_connector_meta = [];
        foreach ($related_connectors as $related_connector_name => $connector_data) {
            $connector_taxonomy = $connector_data['taxonomy'];
            $current_connector_id = dibraco_get_current_term_id_for_post($post_id, $connector_taxonomy);
            if($current_connector_term_id !=='') {
                $existing_connector_meta = get_term_meta($current_connector_term_id, $meta_key, true);
            }
            $new_connector_term_id = $_POST["{$connector_taxonomy}_term"]; 
            get_term_meta($new_connector_term_id, $meta_key, true);
             if($new_connector_term_id !=='' ){
                $new_connector_term_id = (int)$new_connector_term_id;
                $new_connector_meta_key=get_term_meta($new_connector_term_id, $meta_key, true)[$current_type_term_id];
                $existing_post_id = $new_meta_index = $get_term_meta[$type_term_id]['related_post_id'];
               
            }if (in_array($post_id, $main_post_map, true)) {
          
          if(!$type_has_changed && !$connector_has_changed){
              return;
          }
           if ($type_has_changed && !$connector_has_changed){
              
           }
            $connector_has_changed = ($current_connector_term_id != $new_connector_term_id);
            if ($new_connector_term_id ==='' && $current_connector_term_id !=='' ){
                unset_post_from_data_datables($current_connector_term_id, $previous_type_term_id, $new_type_term_id, $post_id);
            }
         if (!$connector_has_changed && !$type_has_changed){
             return;
            }
        if ($connector_has_changed && ($current_connector_term_id !=='')) {
             $term_to_clear_from = $current_connector_term_id;
        } else if ($type_has_changed && !empty($current_connector_term_id)) {
            $term_to_clear_from = $current_connector_term_id;
        }
           if ($term_to_clear_from!==''){ 
             type_connector_clear_old_meta_not_touching_fallbacks($term_to_clear_from, $meta_key, $previous_type_term_id, $post_id, $post_per_term);
            }
            if ($new_type_term_id !=='') {
                
                    type_connector_update_term_meta($current_connector_term_id, $meta_key, $new_type_term_id, $post_id, $post_per_term);
                }
                $terms_to_set = [];
                wp_set_object_terms($post_id, $new_connector_term_id, $connector_taxonomy, false);
                }
        }
    }
    
    elseif ($related_connector_count === 2) {
        $areas_tax = $related_connectors['service_areas']['taxonomy'];
        $loc_tax = $related_connectors['locations']['taxonomy'];
        $current_area_id = dibraco_get_current_term_id_for_post($post_id, $areas_tax);
        $current_loc_id = dibraco_get_current_term_id_for_post($post_id, $loc_tax);
        $new_area_id = $_POST["{$areas_tax}_term"];
        if ($new_area_id!==''){
            $new_area_id = (int)$new_area_id;
            $get_term_meta($new_area_id, $areas_tax);
        }
        $new_loc_id = $_POST["{$loc_tax}_term"];
          if ($new_loc_id!==''){
            $new_loc_id = (int)$new_loc_id;
        } 
         $location_term_changed = ($new_loc_id != $current_loc_id);
        $area_term_changed = ($current_area_id != $new_area_id);
    

         if (!$area_term_changed && !$location_term_changed && !$type_has_changed){
            return;
        }
        
        if (!$area_term_changed && !$type_has_changed){
             go_handle_location_changed_on_type_post($new_loc_id, $current_location_term, $new_type_term_id, $previous_type_term_id, $post_id, $context);
        }
        if ($area_term_changed && !$type_has_changed){
             type_connector_clear_old_meta_not_touching_fallbacks($current_area_id, $meta_key, $previous_type_term_id, $post_id, $post_per_term);
             type_connector_update_term_meta($new_loc_id, $meta_key, $type_term_id, $post_id, $post_per_term);
             if($post_per_term ==="1" && $current_loc_id!==''){
                    $meta_key = "related_type_{$type_context_name}";
                    $area_parent_location_term = get_term_meta($new_area_id, 'area_parent_location_term', true);
                    $existing_meta = get_term_meta($new_area_id, $meta_key, 'area_parent_location_term', true);

             }
            
        }
        if ($current_loc_id !== '' && $previous_type_term_id !=='') {
         type_connector_clear_old_meta_not_touching_fallbacks($current_loc_id, $meta_key, $previous_type_term_id, $post_id, $post_per_term);
         
        }
        if ($changed_type_term && !$connector_loc_changed && !$connector_loc_changed){
            $new_type_term_id;
        }
        if ($area_term_changed && !$changed_type_term){
            type_connector_update_term_meta($connector_term_id, $meta_key, $type_term_id, $post_id, $post_per_term);
        }
        
        $area_terms_to_set = [];
        $loc_terms_to_set = [];
        if ($new_area_id !== '') {
             $current_term_row_meta = get_term_meta($new_area_id, $meta_key, true);
               
          type_connector_clear_old_meta_not_touching_fallbacks($new_area_id, $meta_key, $type_term_id, $post_id, $post_per_term);
        } else if ($new_loc_id !== '') {
             $current_term_row_meta = get_term_meta($new_loc_id, $meta_key, true);
            if ($post_per_term ==="1"){
        }
        }
        wp_set_object_terms($post_id, $new_area_id, $areas_tax, false);
        if ($new_loc_id !== '') {
            $new_loc_id = (int)$new_loc_id;
              wp_set_object_terms($post_id, $new_loc_id, $loc_tax, false);
      
        }
        else{
        wp_set_object_terms($post_id, [], $loc_tax, false);
        }
    }
}
function handle_post_type_changed_single_area_term($current_loc_id, $new_area_id, $new_type_term_id, $post_id){
  return;
    
}
function  go_handle_location_changed_on_type_post($new_loc_id, $current_location_term, $new_type_term_id, $previous_type_term_id, $post_id, $context){
   $type_context_name = $context['context_name'];
    $post_per_term = $context['post_per_term'];
    $type_taxonomy = $context['taxonomy'];
    $type_post_type = get_post_type($post_id);
    $meta_key = "related_type_{$type_context_name}";
    $post_permalink = get_permalink($post_id);
    $type_has_changed = ($previous_type_term_id != $new_type_term_id);

if ($type_has_changed){
    type_connector_clear_old_meta_not_touching_fallbacks($previous_type_term_id, $meta_key, $previous_type_term_id, $post_id, $post_per_term);
    type_connector_update_term_meta($new_loc_id, $meta_key, $type_term_id, $post_id, $post_per_term);
} elseif (!$type_has_changed) {
    type_connector_clear_old_meta_not_touching_fallbacks($previous_type_term_id, $meta_key, $previous_type_term_id, $post_id, $post_per_term);
    type_connector_update_term_meta($new_loc_id, $meta_key, $type_term_id, $post_id, $post_per_term);
}

type_connector_clear_old_meta_not_touching_fallbacks($current_loc_id, $meta_key, $previous_type_term_id, $post_id, $post_per_term);

update_fallbacks_for_service_area_terms($new_loc_id, $meta_key, $type_term_id, $post_id);
  

}


function setup_related_type_terms_for_new_connector_term($connector_term_id, $related_type_contexts, $context_name, $taxonomy) {
    $connector_term = get_term($connector_term_id);
    $status = get_option('locations_areas_status');
    $connector_term_name = $connector_term->name;
    foreach ($related_type_contexts as $related_type_name_context_name => $related_connector_data) {
    $entries = [];
        $related_connector_taxonomy = $related_connector_data['taxonomy'];
        $type_terms = get_terms(['taxonomy' => $related_connector_taxonomy, 'hide_empty' => true, 'fields' => 'id=>name']);
        $posts_per_term = $related_connector_data['post_per_term'];
        $related_connector_count = $related_connector_data['related_connnector_count'];
        $meta_key = "related_type_{$related_type_name_context_name}";
        if ($posts_per_term !== "1") {
            foreach ($type_terms as $type_id => $type_term_name) {
                $entries[$type_id] = []; 
            }
            update_term_meta($connector_term_id, $meta_key, $entries);
            continue;
        } elseif ($posts_per_term ==="1"){
            $fallback_source = 'options';
             if ($related_connector_count === 2){
                 $area_parent_location_term = get_term_meta($connector_term_id, 'area_parent_location_term', true);
                 if (!empty($area_parent_location_term)) {
                   $fallback_source = (int)$area_parent_location_term;
                }
             }
        }
        $fallback_map = dibraco_get_all_type_term_fallback_urls($fallback_source, $related_type_name_context_name);
        $main_posts = get_option("{$related_type_name_context_name}_main_posts");
        foreach ($main_posts as $type_term_id => $main_post_id) {
            $type_term_name = get_term($type_term_id)->name;
            $related_post_title = "{$type_term_name} in {$connector_term_name}";
            $fallback_url = $fallback_map[$type_term_id]['fallback_url'];
           $entries[$type_term_id] = ['related_post_title' => $related_post_title, 'related_post_id' => '', 'related_post_url' => '', 'fallback_url' => $fallback_url];
        }
        update_term_meta($connector_term_id, $meta_key, $entries);
    }
}


function save_related_connector_terms_to_unique($post_id, $context_data, $related_connectors) {
    $context_name = $context_data['context_name'];
    $meta_key     = "related_unique_{$context_name}";
    $post_title   = get_the_title($post_id);
    $post_url     = get_permalink($post_id);
    foreach ($related_connectors as $connector_key => $connector_data) {
        $taxonomy        = $connector_data['taxonomy'];
        $current_term_id = dibraco_get_current_term_id_for_post($post_id, $taxonomy);
        $new_term_id     = isset($_POST["{$taxonomy}_term"]) ? (int)$_POST["{$taxonomy}_term"] : '';
        if ($new_term_id === $current_term_id) {
            continue;
        }
        if ($current_term_id!=='') {
       $current_term_id = (int)$current_term_id;
        $old_meta = get_term_meta($current_term_id, $meta_key, true);
        unset($old_meta[$post_id]);
        update_term_meta($current_term_id, $meta_key, $old_meta);
        }
        if ($new_term_id !=='') {
            $new_meta = get_term_meta($new_term_id, $meta_key, true);
            if (!is_array($new_meta)) {
                $new_meta = [];
            }
            $new_term_id = (int)$new_term_id;
            $new_meta[$post_id] = [
                'related_post_id'    => $post_id,
                'related_post_title' => $post_title,
                'related_post_url'   => $post_url,
            ];

            update_term_meta($new_term_id, $meta_key, $new_meta);
        }
        wp_set_object_terms($post_id, $new_term_id, $taxonomy, false);
    }
}
/*
Related To Connector Contexts - Front End Data Tables Relationship is created by wordpress and lasts unless settings are changed and saved
This is the blueprint for the backend admin/database
Only Related Title Is Editable By The User and it staats with a default or 
Meta Data Array Key = "related_unique_{$unique_context_name}"
Unique Context Data Tables On Conncetors
$related_unique_context[$post_id['related_title', 'related_post_id', 'related_url']]
Headers - Related Title / Related Url
Type Context Data Tables On Connectors
Meta Data Array Key = "related_type_{$type_context_name}"
if $context['post_per_term']==="1"{
//Each Type Term Will Have 1 main post id - this represents the fallback Unless Related Connectors ==="2" - then the fallback for service areas is derivced hierarchically Main Post => Location, Location => Service Area
$related_trype_context[$term_id[['related_title', 'related_post_id', 'related_url', 'fallback_url']] 1 must exist per type term for each connector term **fallback will always exist
Row Index Id - 'type_term_id'
Headers - Type Term Name**nothing Under**, Title, Related URL, Fallback URL
Table Data - 'related_title' 'related_url 'fallback_url' **'related_post_id'**invisible
}
if $context['post_per_term']!=="1"{
$related_trype_context[$term_id[$post_id =>['related_title', 'related_post_id, 'related_url']]] 1 row must exist per type term for each connector term sub rows need not exist, but internal array will remain open 
$term_id[] <--default row
Row Index Id - type_term_id
Sub Row Index Id - 'related_post_id'
Headers - Type Term Name**Main Row Header
SubRow Headers - Title, Related URL
}
*/
function dibraco_admin_table_template($title_config, $headers, $styles, $rows, $colspan) {
    // 1. Title Configuration
    $title_tag   = $title_config['tag'] ?? 'h2';
    $title_style = $title_config['style'] ?? 'margin-bottom:0.5em;';
    $title_text  = $title_config['text'] ?? '';
    if (!in_array($title_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
        $title_tag = 'h2';
    }
    ?>
    <?php if (!empty($title_text)) : ?>
        <<?php echo $title_tag; ?> style="<?php echo esc_attr($title_style); ?>"><?php echo esc_html($title_text); ?></<?php echo $title_tag; ?>>
    <?php endif; ?>

    <table class="wp-list-table widefat striped" style="table-layout: fixed;">
        
        <thead>
            <tr>
                <?php foreach ($headers as $index => $header) : ?>
                    <th style="<?php echo isset($styles[$index]) ? esc_attr($styles[$index]) : ''; ?>">
                        <?php echo esc_html($header); ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>

        <tbody>
            <?php
            $num_columns = count($headers);
            foreach ($rows as $row_data) :
                $row_attributes = '';
                if (isset($row_data['data_attributes']) && is_array($row_data['data_attributes'])) {
                    foreach ($row_data['data_attributes'] as $attr_key => $attr_value) {
                        $row_attributes .= ' ' . esc_attr($attr_key) . '="' . esc_attr($attr_value) . '"';
                    }
                }
                $cells = $row_data['cells'] ?? $row_data;
                ?>
                <tr<?php echo $row_attributes; ?>>
                    <?php foreach ($cells as $col_index => $cell_data) :
                        $true_col_index = $num_columns - count($cells) +(int)$col_index;
                        $style_attr_string = isset($styles[$true_col_index]) ? esc_attr($styles[$true_col_index]) : '';
                        ?>
                        <?php
                        if (is_array($cell_data) && isset($cell_data['rowspan'])) {
                            $rowspan_attr = ' rowspan="' . esc_attr($cell_data['rowspan']) . '"';
                            $style_attr = !empty($cell_data['style']) ? ' style="' . esc_attr($cell_data['style']) . $style_attr_string . '"' : 'style="' . $style_attr_string . '"';
                            echo '<td' . $rowspan_attr . $style_attr . '>' . esc_html($cell_data['content']) . '</td>';

                        } elseif (is_array($cell_data) && !empty($cell_data['is_editable'])) {
                            $input_name  = $cell_data['name']  ?? '';
                            $input_value = $cell_data['value'] ?? '';
                            $style_attr  = !empty($cell_data['style']) ? ' style="' . esc_attr($cell_data['style']) . '"' : '';
                            $name_attr   = $input_name !== '' ? ' name="' . esc_attr($input_name) . '"' : '';
                            
                            echo '<td style="' . $style_attr_string . '">';
                            echo '<input type="text"' . $name_attr . ' value="' . esc_attr($input_value) . '"' . $style_attr . ' />';
                            
                            if (!empty($cell_data['hidden_fields']) && is_array($cell_data['hidden_fields'])) {
                                foreach ($cell_data['hidden_fields'] as $hidden_name => $hidden_value) {
                                    echo '<input type="hidden" name="' . esc_attr($hidden_name) . '" value="' . esc_attr($hidden_value) . '" />';
                                }
                            }
                            echo '</td>';
                        
                        } elseif (is_array($cell_data) && ($cell_data['type'] ?? '') === 'checkbox_list') {
                            $name = $cell_data['name'] ?? '';
                            $options = $cell_data['options'] ?? [];
                            
                            echo '<td style="' . $style_attr_string . '"><div class="dibraco-checkbox-list">';
                            foreach ($options as $option) {
                                $value = $option['value'] ?? '';
                                $label = $option['label'] ?? '';
                                $id = 'checkbox_' . esc_attr(str_replace(['[', ']'], '', $name)) . '_' . esc_attr($value);
                                $checked_attr = !empty($option['checked']) ? ' checked="checked"' : '';
                                
                                echo '<span class="checkbox-item" style="display:inline-block; margin-right: 15px;">';
                                echo '<input type="checkbox" id="' . $id . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . $checked_attr . '>';
                                echo ' <label for="' . $id . '">' . esc_html($label) . '</label>';
                                echo '</span>';
                            }
                            echo '</div></td>';

                        } else {
                            echo '<td style="' . $style_attr_string . '">';
                            echo is_string($cell_data) ? esc_html($cell_data) : '';
                            echo '</td>';
                        }
                        ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
        
        <tfoot>
            <tr>
                <td colspan="<?php echo esc_attr($colspan); ?>">
                    </td>
            </tr>
        </tfoot>

    </table>
    <br>
    <?php
}

function render_dibraco_admin_table($table) {
    $title_text = $table['title'] ?? '';
    $headers    = $table['headers'] ?? [];
    $styles     = $table['styles'] ?? [];
    $rows       = $table['rows'] ?? [];
    $colspan      = $table['colspan'] ?? count($headers); 

    $title_config = ['text' => $title_text];

    dibraco_admin_table_template($title_config, $headers, $styles, $rows, $colspan);
}
function render_context_tables($contexts, $context_type, $current_connector_term_id) {
    $prepare_function_name = "prepare_single_{$context_type}_table_data";

    foreach ($contexts as $context_name => $context_data) {
        $table_data = $prepare_function_name($context_name, $context_data, $current_connector_term_id);
        
        if ($table_data) {
            render_dibraco_admin_table($table_data);
        }
    }
}

function prepare_single_type_table_data($related_type_context_name, $type_context_data, $current_connector_term_id) {
    $related_type_meta_key = "related_type_{$related_type_context_name}";
    $rows_from_meta = get_term_meta($current_connector_term_id, $related_type_meta_key, true);
    $related_type_taxonomy = $type_context_data['taxonomy'];
    $post_per_term = $type_context_data['post_per_term'];

    $all_terms_in_taxonomy = get_terms(['taxonomy' => $related_type_taxonomy, 'hide_empty' => true, 'fields' => 'id=>name']);

    $table_rows = [];

    if ($post_per_term === "1") {
        foreach ($all_terms_in_taxonomy as $type_term_id => $type_term_name) {
           $type_term_id =(int)$type_term_id;
            $entry_data = $rows_from_meta[$type_term_id];
            $row_name = "{$related_type_meta_key}[{$type_term_id}]";
            
            $existing_data = [
               $entry_data['related_post_title'] => 'related_post_title',
                $entry_data['related_post_id'] => 'related_post_id',
                $entry_data['related_post_url'] => 'related_post_url',
                $entry_data['fallback_url'] => 'fallback_url'
            ];
            $table_rows[$type_term_id] = $existing_data;
        }
    } else {
        foreach ($all_terms_in_taxonomy as $type_term_id => $type_term_name) {
            $posts_for_this_term = $rows_from_meta[$type_term_id];

            foreach ($posts_for_this_term as $post_id => $post_data) {
                $row_name = "{$related_type_meta_key}[{$type_term_id}][{$post_id}]";
                $entry_data = [
                    'related_post_title' => $post_data['related_post_title'],
                    'related_post_id' => $post_data['related_post_id'],
                    'related_post_url' => $post_data['related_post_url'],
                ];
                $table_rows[$type_term_id][] = $entry_data;
            }
        }
    }
  $headers = [];
    $styles = [];
    if ($post_per_term === "1") {
        $headers = ['Type Term Name', 'Title', 'Related URL', 'Fallback URL'];
        $styles = ['width:15%', 'width:35%', 'width:25%', 'width:25%'];
    } else {
        $headers = ['Type Term Name', 'Title', 'Related URL'];
        $styles = ['width:20%', 'width:40%', 'width:40%'];
    }

    return [
        'title' => 'Related ' . ucwords(str_replace('_', ' ', $related_type_context_name)) . ' Posts',
        'headers' => $headers,
        'styles' => $styles,
        'rows' => $table_rows,
        'colspan'
    ];
}

function prepare_single_unique_table_data($unique_context_name, $unique_context_data, $current_connector_term_id) {
    $related_unique_meta_key = "related_unique_{$unique_context_name}";
    $unique_posts_data = get_term_meta($current_connector_term_id, $related_unique_meta_key, true);
    if (empty($unique_posts_data)) {return;}
    $table_rows = [];
    foreach ($unique_posts_data as $post_id => $post_data) {
        $row_name = "{$related_unique_meta_key}[{$post_id}]";
        $cell1 = '<td><input type="text" style="width:100%" name="' . esc_attr($row_name) . '[related_post_title]" value="' . esc_attr($post_data['related_post_title']) . '" />' .
                 '<input type="hidden" name="' . esc_attr($row_name) . '[related_post_url]" value="' . esc_attr($post_data['related_post_url']) . '" />' .
                 '<input type="hidden" name="' . esc_attr($row_name) . '[related_post_id]" value="' . esc_attr($post_id) . '" /></td>';
        $cell2 = '<td>' . esc_html($post_data['related_post_url']) . '</td>';
        $table_rows[] = [$cell1, $cell2];
    }
    return [
        'title'   => 'Related ' . ucwords(str_replace('_', ' ', $unique_context_name)) . ' Posts',
        'headers' => ['Title', 'URL'],
        'styles'  => ['width:40%', 'width:60%'],
        'rows'    => $table_rows,
         'colspan' => 6,
        ];
}

function pre_render_meta_box($post, $args) {
    $post_id = $post->ID;
    $taxonomy = $args['args']['pairing']['taxonomy'];
    $is_main_post_flag = '';
        if(isset($args['args']['pairing']['main_post_for_term'])){
            $is_main_post_flag = $args['args']['pairing']['context_name'];
        }
    $current_term_id = dibraco_get_current_term_id_for_post($post_id, $taxonomy); 
    render_radio_buttons_for_post_meta_box($taxonomy, $current_term_id, $post_id, $is_main_post_flag);
}
function render_radio_buttons_for_post_meta_box($taxonomy, $current_term_id, $post_id, $is_main_post_flag) {
    $human_readable_tax = ucwords(str_replace(['_', '-'], ' ', $taxonomy));
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    $options_array = [];
     if ($terms !== []){
        foreach ($terms as $term) {
            $options_array[$term->term_id] = $term->name;
        }
    }
    if ($post_id!=='') {$post_id = (int)$post_id;}
    if ($is_main_post_flag !==''){
        $term_main_map =  get_option("{$is_main_post_flag}_main_posts", []);
        $is_main = "0"; 
        if (isset($term_main_map[$current_term_id])) {$current_main_post_id = $term_main_map[$current_term_id];
            $is_main = ((int) $post_id === $current_main_post_id) ? "1" : "0";}
        echo FormHelper::generateCheckBox('main_post_for_term', 'Is This The Main Post For This Term?', $is_main, []);
    }
    echo FormHelper::generateRadioFieldsetWithIntegerValues("{$taxonomy}_term", "Select a {$human_readable_tax} Term", $current_term_id, $options_array, []);
}
function dibraco_manage_custom_column_titles($columns, $column_key, $column_title) {
    if (isset($columns[$column_key])) {
        $columns[$column_key] = $column_title;
    }
    return $columns;
}

function dibraco_sortable_column_callback($sortable_columns, $column_key, $taxonomy) {
    $sortable_columns[$column_key] = $taxonomy;
    return $sortable_columns;
}

function dibraco_hide_quick_edit_taxonomy($taxonomy_name, $target_taxonomy, &$show) {
    if ($taxonomy_name === $target_taxonomy) {
        $show = false;
    }
}

function add_remove_custom_column($post_type, $pairings, $context_type) {
    foreach ($pairings as $pairing) {
        $taxonomy = $pairing['taxonomy'];
        $context_name = $pairing['context_name'];
        $column_key = "taxonomy-{$taxonomy}";
        $column_title = ucwords(str_replace(['-', '_'], ' ', $context_name));
        $quick_edit_field_key = "{$taxonomy}_term";
        $is_main_post_flag = '';
        if (isset($pairing['main_post_for_term'])) {
            $is_main_post_flag = $context_name;
        }

        add_filter("manage_{$post_type}_posts_columns", function($columns) use ($column_key, $column_title) {
            return dibraco_manage_custom_column_titles($columns, $column_key, $column_title);
        }, 15);

        add_filter("manage_edit-{$post_type}_sortable_columns", function($sortable_columns) use ($column_key, $taxonomy) {
            return dibraco_sortable_column_callback($sortable_columns, $column_key, $taxonomy);
        }, 30, 1);

        add_filter('quick_edit_show_taxonomy', function($show, $taxonomy_name, $post_type_slug) use ($taxonomy) {
            dibraco_hide_quick_edit_taxonomy($taxonomy_name, $taxonomy, $show);
            return $show;
        }, 10, 3);

        add_action('quick_edit_custom_box', function($column_name, $post_type_slug) use ($column_key, $taxonomy, $post_type, $is_main_post_flag) {
            if ($column_name === $column_key && $post_type_slug === $post_type) {
                ?>
                <fieldset class="inline-edit-col-left inline-edit-<?php echo $taxonomy; ?>">
                    <div class="inline-edit-col column-<?php echo $column_name; ?>">
                        <span class="title inline-edit-group"></span>
                        <div class="inline-edit-<?php echo $taxonomy; ?>-input">
                            <?php
                            render_radio_buttons_for_post_meta_box($taxonomy, '', '', $is_main_post_flag);
                            ?>
                        </div>
                    </div>
                </fieldset>
                <?php
            }
        }, 15, 2);
        
add_action('bulk_edit_custom_box', function($column_name, $post_type_slug) use ($column_key, $taxonomy, $post_type, $column_title, $quick_edit_field_key) {
    if ($column_name === $column_key && $post_type_slug === $post_type) {
        wp_nonce_field('dibraco_bulk_nonce_action', 'dibraco_bulk_nonce_field', false);

// Bulk edit handler
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
        $options = [];
        foreach ($terms as $term) {
            $options[(int)$term->term_id] = $term->name;
        }
        $options['__no_change__'] = '— No Change —';
        $options[''] = 'None';
        $field_id = "{$taxonomy}_term_select";
        echo '<fieldset class="inline-edit-col-right">';
        echo '<div class="inline-edit-group ' . esc_attr($taxonomy) . '-input">';
        echo '<label for="' . esc_attr($field_id) . '">' . esc_html($column_title) . '</label>';
        echo '<select name="' . esc_attr($quick_edit_field_key) . '" id="' . esc_attr($field_id) . '">';
        foreach ($options as $value => $label) {
            $selected = ($value === '__no_change__') ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($value) . '" ' . $selected . '>' . esc_html($label) . '</option>';
        }

        echo '</select></div></fieldset>';
    }
}, 15, 2);

add_action("save_post_{$post_type}", function($post_id) use ($taxonomy, $quick_edit_field_key) {
    dibraco_save_taxonomy_term_bulk_or_quick_edit($post_id, $taxonomy, $quick_edit_field_key);
}, 10, 1);
add_action('pre_get_posts', function($query) use ($taxonomy, $post_type) {
    global $pagenow;
    if (is_admin() && $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === $post_type && $query->is_main_query()) {
        $tax_query = [];
        $filter_name = "{$taxonomy}_term";
        if (isset($_GET[$filter_name]) && !empty($_GET[$filter_name])) {
            $term_slug = sanitize_text_field($_GET[$filter_name]);
            $tax_query[] = ['taxonomy' => $taxonomy, 'field' => 'slug', 'terms' => $term_slug];
        }
        if (!empty($tax_query)) {
            $tax_query['relation'] = 'AND';
            $query->set('tax_query', $tax_query);
        }
    }
}, 10, 1);
        add_action('restrict_manage_posts', function($current_post_type) use ($post_type, $taxonomy, $quick_edit_field_key) {
            if ($current_post_type === $post_type) {
                $tax_obj = get_taxonomy($taxonomy);
                $terms = get_terms([
                    'taxonomy'   => $taxonomy,
                    'hide_empty' => false,
                ]);
                $selected_term = $_GET[$quick_edit_field_key] ?? '';
                echo "<select name='{$quick_edit_field_key}' id='{$taxonomy}_filter' class='postform'>";
                echo '<option value="">All ' . $tax_obj->labels->name . '</option>';
                foreach ($terms as $term) {
                    printf(
                        '<option value="%s"%s>%s</option>',
                         esc_attr($term->slug),
                        selected($selected_term, $term->slug, false),
                        $term->name
                    );
                }
                echo "</select>";
            }
        }, 10, 1);

        if ($is_main_post_flag === $context_name) {
            $main_post_map = get_option("{$context_name}_main_posts", []);
            if (!empty($main_post_map)) {
                add_filter('get_the_terms', function ($terms_array, $current_post_id, $taxonomy_name) use ($main_post_map, $taxonomy) {
                    if ($taxonomy_name === $taxonomy && in_array($current_post_id, $main_post_map)) {
                        foreach ($terms_array as $term_object) {
                            $main_post_id_for_this_term = $main_post_map[$term_object->term_id];
                            if ((int)$main_post_id_for_this_term === (int)$current_post_id) {
                                $term_object->name .= ' (Main)';
                            }
                        }
                    }
                    return $terms_array;
                }, 15, 3);
            }
        }
    }
}    
add_action('wp_ajax_bulk_save_taxonomy_terms_on_post', 'bulk_save_taxonomy_terms_on_post');


function bulk_save_taxonomy_terms_on_post() {
    check_ajax_referer('dibraco_bulk_nonce_action', 'dibraco_bulk_nonce_field');
     $post_ids = $_POST['post_ids'];
    $taxonomies_to_manage = $_POST['taxonomies']; 

    foreach ($taxonomies_to_manage as $taxonomy_slug) {
        $field_key = "{$taxonomy_slug}_term";
        $term_value = $_POST[$field_key] ?? '';
        
        if ($term_value === '__no_change__') {
            continue;
        }
        foreach ($post_ids as $post_id) {
            dibraco_save_taxonomy_term_bulk_or_quick_edit($post_id, $taxonomy_slug, $field_key);
        }
    }
    
    wp_send_json_success('Update process dispatched.');
    wp_die();
}


function dibraco_save_taxonomy_term_bulk_or_quick_edit($post_id, $taxonomy, $quick_edit_field_key) {
    if (!wp_doing_ajax() || !in_array($_POST['action'], ['inline-save', 'bulk_save_taxonomy_terms_on_post', 'bulk_edit'], true)) return;
if (!isset($_POST[$quick_edit_field_key])) {
    return;
}
$term_value = $_POST[$quick_edit_field_key];

    $term_value = $_POST[$quick_edit_field_key];

    if ($term_value !== '') $term_value = (int)$term_value;

    $post = get_post($post_id);
    $current_post_type = $post->post_type;

    $status = get_option('locations_areas_status');
    $enabled_contexts = get_option('enabled_connector_contexts');

    if ($status === 'both' || $status === 'multi_locations') {
        $locations_post_type = $enabled_contexts['locations']['post_type'];
        $locations_taxonomy = $enabled_contexts['locations']['taxonomy'];
        if ($current_post_type === $locations_post_type && $locations_taxonomy === $taxonomy) {
            dibraco_enforce_one_connector_term_per_connector_post($post_id, $term_value, $taxonomy, 'location_post_id', 'location_link_url');
            return;
        }
        if (dibraco_process_specific_type_connector_block($post_id, $current_post_type, $taxonomy, 'locations')) return;
        if (dibraco_process_specific_unique_connector_block($post_id, $current_post_type, $taxonomy, 'locations')) return;
    }

    if ($status === 'both' || $status === 'multi_areas') {
        $service_area_post_type = $enabled_contexts['service_areas']['post_type'];
        $service_area_taxonomy = $enabled_contexts['service_areas']['taxonomy'];
        if ($current_post_type === $service_area_post_type && $service_area_taxonomy === $taxonomy) {
            dibraco_enforce_one_connector_term_per_connector_post($post_id, $term_value, $taxonomy, 'service_area_post_id', 'service_area_link_url');
            return;
        }
        if (dibraco_process_specific_type_connector_block($post_id, $current_post_type, $taxonomy, 'service_areas')) return;
        if (dibraco_process_specific_unique_connector_block($post_id, $current_post_type, $taxonomy, 'service_areas')) return;
    }

    wp_set_post_terms($post_id, $term_value, $taxonomy, false);
}

function dibraco_process_specific_unique_connector_block($post_id, $current_post_type, $taxonomy, $connector_key){
     $enabled_unique_contexts = get_option('enabled_unique_contexts');
        foreach($enabled_unique_contexts as $unique_context => $context_data){
            $unique_post_type = $context_data['post_type'];
            if ($current_post_type !== $unique_post_type) {continue;}
                 $related_connectors = $context_data['related_connectors'];
                if (isset($related_connectors[$connector_key])) {
                 $related_connector_taxonomy = $related_connectors[$connector_key]['taxonomy'];
                   if ($related_connector_taxonomy === $taxonomy){
                       save_related_connector_terms_to_unique($post_id, $context_data, $related_connectors);
                       return true;
                   }
                }
            }  
            return false;
}
function dibraco_process_specific_type_connector_block($post_id, $current_post_type, $taxonomy, $connector_key){
   $enabled_type_contexts = get_option('enabled_type_contexts');
    foreach($enabled_type_contexts as $type_context_name => $type_context_data){
             $type_post_type = $type_context_data['post_type'];
             $type_taxonomy = $type_context_data['taxonomy'];
        if($current_post_type !== $type_post_type){continue;}
        if ($taxonomy === $type_taxonomy) {
            $previous_type_term_id = dibraco_get_current_term_id_for_post($post_id, $type_taxonomy);
            $type_term_id = $_POST["{$type_taxonomy}_term"]??'';
            $related_connectors = $type_context_data['related_connectors'];
            $related_connector_count = $type_context_data['related_connector_count'];
            if ($type_term_id !==''){
                $type_term_id = (int) $type_term_id;
            }
            if ($type_context_data['post_per_term'] ==="1"){
                $checkbox_of_main_term_value = $_POST['main_post_for_term']??'';
                figure_out_the_check_box_situation($type_term_id, $previous_type_term_id, $checkbox_of_main_term_value, $post_id, $type_context_name, $type_taxonomy, $related_connectors, $related_connector_count);
            }
            wp_set_object_terms($post_id, $type_term_id, $type_taxonomy);
            return true;
        }
         if (isset($related_connectors[$connector_key])) {
                $related_connector_taxonomy = $related_connectors[$connector_key]['taxonomy'];
                 if ($related_connector_taxonomy === $taxonomy){
                     save_related_connector_terms_to_type_post_type($post_id, $type_context_data, $related_connectors_count, $related_connectors, $type_term_id, $type_taxonomy);
                     return true;
                 }
             }
             }
             return false; 
        }