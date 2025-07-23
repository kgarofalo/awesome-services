<?php

function define_section_contexts() {
 $contexts = [
    'service_areas' => [
        'context_type' => 'connector', 
        'fields' => [
            'enabled' => ['name' => 'service_areas_enabled', 'value' => get_option('service_areas_enabled', "1")],
            'post_type' => ['name' => 'service_areas_post_type', 'value' => get_option('service_areas_post_type', '')],
            'taxonomy' => ['name' => 'service_areas_connector_tax', 'value' => get_option('service_areas_connector_tax', '')],
        ]
    ],
    'locations' => [
        'context_type' => 'connector', 
        'fields' => [
            'enabled' => ['name' => 'locations_enabled', 'value' => get_option('locations_enabled', "0")],
            'post_type' => ['name' => 'locations_post_type', 'value' => ''],
            'taxonomy' => ['name' => 'locations_connector_tax', 'value' => get_option('locations_connector_tax', '')],
            'main_term' => ['name' => 'main_term', 'value' => get_option('main_term', '')],
            ]
    ],
    'main_service' => [
        'context_type' => 'type',
        'fields' => [
            'enabled' => ['name' => 'main_service_enabled', 'value' => get_option('main_service_enabled', "1")],
            'schema' => ['name' => 'main_service_schema', 'value' => 'Service'],
            'post_type' => ['name' => 'main_service_post_type', 'value' => ''],
            'taxonomy' => ['name' => 'main_service_type_taxonomy', 'value' => get_option('main_service_type_taxonomy', '')]
        ]
    ],
    'second_service' => [
        'context_type' => 'type',
        'fields' => [
            'enabled' => ['name' => 'second_service_enabled', 'value' => "0"],
            'schema' => ['name' => 'second_service_schema', 'value' => 'Service'],
        ]
    ],
    'jobs' => [
        'context_type' => 'type',
        'fields' => [
            'enabled' => ['name' => 'jobs_enabled', 'value' => "0"],
            'schema' => ['name' => 'jobs_schema', 'value' => 'JobPosting'],
            'post_type' => ['name' => 'jobs_post_type', 'value' => get_option('jobs_post_type','')]
        ]
    ],
    'employee' => [
        'context_type' => 'unique',
        'fields' => [
          'enabled' => ['name' => 'employee_enabled', 'value' => "0"],
          'schema' => ['name' => 'employee_schema', 'value' => 'Person'],
        ]
    ]
];

    return $contexts;
}

function assemble_fields($context_name, $context_type, $field_keys = []) {
    $post_type_options = awesome_get_post_types();
    $schema_options = initialize_schema_options();
    $meta_data = [
        'enabled' => ['type' => 'toggle'],
        'post_type' => ['type' => 'select', 'options' => $post_type_options],
        'schema' => ['type' => 'select', 'options' => $schema_options],
        'remove' => ['name' => "{$context_name}_remove", 'type' => 'button', 'class' => 'remove-context-button button-secondary'],
        'primary_meta' => ['type' => 'toggle',  'label'=> 'Meta'],
        'dibraco_banner' =>['type' => 'toggle', 'label'=> 'Banner'],
        'main_sections' => ['type' => 'toggle', 'label'=> 'Main'],
        'contact_section' => ['type' => 'toggle', 'label' => 'Contact'],
        'portrait_images' =>['type' => 'toggle', 'label' => 'Portrait Images'],
        'repeater_images' => ['type' => 'toggle', 'label'=>'repeater'],
        'before_after' => ['type' => 'toggle', 'label'=>'Bef Aft'],
    ];
    $default_fields = [
        'enabled' => ['name' => "{$context_name}_enabled", 'value' => "1"],
        'schema' => ['name' => "{$context_name}_schema", 'value' => ''],
        'post_type' => ['name' => "{$context_name}_post_type", 'value' => ''],
        'primary_meta' => ['name' => "{$context_name}_primary_meta", 'value' => "0"],
        'dibraco_banner' =>['name' => "{$context_name}_dibraco_banner", 'value' => "0"],
        'main_sections' =>['name' => "{$context_name}_main_sections", 'value' => "0"],
        'contact_section' => ['name' => "{$context_name}_contact_section", 'value' => "1"],
        'portrait_images' => ['name' => "{$context_name}_portrait_images", 'value' => "0"],
        'repeater_images' => ['name' => "{$context_name}_repeater_images", 'value' => "0"],
        'before_after' => ['name' => "{$context_name}_before_after", 'value' => "0"]
        ];

      switch ($context_type) {
        case 'unique':
            if ($context_name === 'employee'){
            $default_fields = array_merge($default_fields, ['has_certification' => ['name' => "{$context_name}_has_certification", 'value' => "0"]]);
            $meta_data = array_merge($meta_data, ['has_certification' =>['type' => 'toggle', 'label' => 'Certification']]);
            }
            break;
        case 'type':
           $taxonomy_options = awesome_get_taxonomies_for_post_type(($field_keys['post_type']['value'] ?? null)) ?? [];
            $default_fields = array_merge($default_fields, [
                'taxonomy' => ['name' => "{$context_name}_type_taxonomy", 'value' => ''],
                'post_per_term' => ['name' => "{$context_name}_post_per_term", 'value' => "0"],
                'has_certification' => ['name' => "{$context_name}_has_certification", 'value' => "0"],
                'term_icon' => ['name' => "{$context_name}_term_icon", 'value' => "0"]
            ]);
            $meta_data = array_merge($meta_data, [
                'taxonomy' => ['type' => 'select', 'options' => $taxonomy_options],
                'post_per_term' => ['type' => 'toggle', 'label' => '1 Post Per Term'],
                'has_certification' =>['type' => 'toggle', 'label' => 'Certification'],
                'term_icon' => ['type' => 'toggle', 'label' => 'Term Icon']
            ]);
            break;

        case 'connector':
            $taxonomy_options = awesome_get_taxonomies_for_post_type(($field_keys['post_type']['value'] ?? null) ) ?? [];
            $default_fields = array_merge($default_fields, [
                'taxonomy' => ['name' => "{$context_name}_connector_tax", 'value' => ''],
                'about_section' => ['name' => "{$context_name}_about_section", 'value' => "1"],

            ]);
            $meta_data = array_merge($meta_data, [
                'taxonomy' => ['type' => 'select', 'options' => $taxonomy_options],
                 'about_section' => ['type' => 'toggle', 'label' => 'About Section'],
            ]);
            if ($context_name === 'locations') {
            $term_options = getTermOptions(($field_keys['taxonomy']['value'] ?? null)) ?? [];
            $default_fields['main_term'] = ['name' => 'main_term', 'value' => '', 'type' => 'select', 'options' => $term_options];
            $default_fields['ignore_main_term'] = ['name' => 'ignore_main_term', 'value' => "0", 'type' => 'toggle'];
            }
              break;
    }
  
$fields = [];
if (!empty($field_keys)) {
    foreach (array_keys($default_fields) as $key) {
        if (isset($field_keys[$key]) && ($field_keys[$key]['value'] !== '')) {
            $fields[$key] = array_merge($default_fields[$key], $meta_data[$key] ?? [], $field_keys[$key]);
        } else {
            $fields[$key] = array_merge($default_fields[$key], $meta_data[$key] ?? []);
        }
    }
    if (isset($field_keys['remove'])) {
        $fields['remove'] = $meta_data['remove'];
    }
} else {
    foreach (array_keys($default_fields) as $key) {
        $fields[$key] = array_merge($default_fields[$key], $meta_data[$key] ?? []);
    }
}
if ($context_type !== 'unique') {
    $insert_after_key = 'post_type';
    $position = array_search($insert_after_key, array_keys($fields));
    $items_to_relocate = [
        'taxonomy' => $fields['taxonomy'],
    ];
    unset($fields['taxonomy']);
    if ($context_name === 'locations') {
        $items_to_relocate['main_term'] = $fields['main_term'];
        $items_to_relocate['ignore_main_term'] = $fields['ignore_main_term'];
        unset($fields['main_term'], $fields['ignore_main_term']);
    }
    if ($position !== false) {
        $before = array_slice($fields, 0, $position + 1, true);
        $after  = array_slice($fields, $position + 1, null, true);
        $fields = $before + $items_to_relocate + $after;
    } else {
        $fields = $fields + $items_to_relocate;
    }
}
return $fields;
}

function build_individual_context($context_name, $context_type, $field_keys=[]) {
    $fields = assemble_fields($context_name, $context_type, $field_keys);
    $context = [
        'context_name' => $context_name,
        'context_type'   => $context_type,
        'fields'         => $fields
    ];
    return $context;
    }
add_action('wp_ajax_build_individual_context', function() {
    $context_name = $_POST['new_context_name'];
    $context_type = $_POST['new_context_type'];
    $contexts = get_option('contexts');
    if (isset($contexts[$context_name])) {
        wp_send_json_error(['message' => 'Context already exists.']);
    }
    $field_keys = ['remove' => []];
    $new_fields = assemble_fields($context_name, $context_type, $field_keys);
    $subfields = [];
    $filtered_fields = [];
    foreach ($new_fields as $field_key => $field_data){
      $filtered_fields[$field_key]['name'] = $field_data['name'];
       if (isset($field_data['value'])){
           $filtered_fields[$field_key]['value'] = $field_data['value'];
        }
     if ($field_key === 'enabled') {
         $new_toggle_html = FormHelper::generateField($field_data['name'], $field_data);
          }   else {
        $actual_field_name = $field_data['name'];
        $subfields[$actual_field_name] = $field_data;
        }
    }
    $context = [
        'context_name' => $context_name,
        'context_type' => $context_type,
        'fields' => $filtered_fields
        ];
    
    $contexts[$context_name] = $context;
    update_option('contexts', $contexts);
    $new_section_html = FormHelper::generateVisualSection($context_name, ['label' => $context_name, 'condition' => ['field' => "{$context_name}_enabled", 'values' => ["1"]], 'fields' => $subfields], true);
    wp_send_json_success([
        'new_toggle_html'  => $new_toggle_html,
        'new_section_html' => $new_section_html
    ]);
});
   
function awesome_get_post_types() {
    $post_types = get_post_types(['public' => true, '_builtin' => false], 'objects');
    $post_type_options = [];
    foreach ($post_types as $post_type_slug => $post_type_obj) {
        $post_type_options[$post_type_slug] = $post_type_obj->label;
    }
    return  $post_type_options;
}
function awesome_get_taxonomies_for_post_type($post_type_slug) {
    $taxonomies = get_object_taxonomies($post_type_slug, 'objects');
    $taxonomy_options = [];
    foreach ($taxonomies as $taxonomy_slug => $taxonomy_obj) {
        $taxonomy_options[$taxonomy_slug] = $taxonomy_obj->label;
    }
        return $taxonomy_options;
}
add_action('wp_ajax_awesome_get_taxonomies_for_post_type', function() {
    wp_send_json_success(awesome_get_taxonomies_for_post_type($_POST['slug']));
});
function getTermOptions($taxonomy) {
    if((!$taxonomy) || (empty($taxonomy))){return;}
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    $options = [];
    foreach ($terms as $term) {
        $options[$term->term_id] = $term->name;
    }
    return $options;
}
add_action('wp_ajax_getTermObjects', function() {
    wp_send_json_success(getTermOptions($_POST['slug']));
});

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
    $contexts = get_option('contexts', []);
    if (empty($contexts)) {
        $contexts = define_section_contexts();
        update_option('contexts', $contexts);
    }
    $togglesubfields = [];
    $grouped_contexts = [
        'unique' => [],
        'type' => [],
        'connector' => []
    ];
    foreach ($contexts as $context_name => $context_data) {
        $context_type   = $context_data['context_type'];
        $field_keys     = $context_data['fields'];
        $context = build_individual_context($context_name, $context_type, $field_keys);
        $field_keys = $context['fields'];
        $context_name = $context['context_name'];
        $filtered_fields = [];
        foreach ($field_keys as $field_key => $field_data) {
            $filtered_fields[$field_key]['name'] = $field_data['name'];
            if (isset($field_data['value'])) {  
                $filtered_fields[$field_key]['value'] = $field_data['value'];
            }
            if ($field_key === 'enabled') {
                $togglesubfields[$field_data['name']] = $field_data;
            } else {
                $grouped_contexts[$context_type][$context_name]['fields'][$field_data['name']] = $field_data;
            }
        }
        $contexts[$context_name] = [
            'context_name'   => $context_name,
            'context_type'   => $context_type,
            'fields'         => $filtered_fields 
        ];
        $grouped_contexts[$context_type][$context_name]['type'] = 'visual_section';
        $grouped_contexts[$context_type][$context_name]['label'] = $context_name;
        $grouped_contexts[$context_type][$context_name]['condition'] = ['field' => "{$context_name}_enabled", 'values' => ["1"]];
    }
    update_option('contexts', $contexts);
        $enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', "0");
       
        $togglesubfields['enable_custom_fields_for_pages'] = [
            'type'  => 'toggle',
            'value' => $enable_custom_fields_for_pages_value,
            ];
    ?>
    <form method="post" action="<?= admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('save_relationships_settings', 'relationships_settings_nonce'); ?>
        <input type="hidden" name="action" value="handle_save_relationships_settings">
        <div id="wrap" style="display: flex;">
            <!-- Sidebar: Toggle switches and Add Context UI -->
            <div class="toggles-sidebar" style="width: 15%; padding-right: 5px;">
                <button type="button" id="add-context-btn" class="button field_group-secondary">Add Context</button>
                <div id="add-context-section" style="display: none;">
                    <?= FormHelper::generateTextInput('new_context_name', 'Context Name:', null); ?>
                    <?= FormHelper::generateRadioFieldset('new_context_type', 'Context Type:', 'type', ['unique' => 'Unique', 'type' => 'Type']); ?>
                    <button type="button" id="confirm-add-context" class="button button-secondary">Confirm</button>
                </div>
                <?= FormHelper::generateVisualSection('toggles_section', ['label'  => 'Context On/Off', 'fields' => $togglesubfields]); ?>
            </div>
            <div style="width: 80%;" class="toggle-smaller">
                <?php foreach ($grouped_contexts as $group_name => $section_groups){
                        echo FormHelper::generateVisualSection($group_name, ['label' =>  $group_name, 'fields' => $section_groups ]); 
                } ?>
            </div>
        </div>
        <button type="submit" class="button button-primary">Submit</button>
    </form>
    <?php
}

function handle_save_relationships_settings() {
    if (!wp_verify_nonce($_POST['relationships_settings_nonce'], 'save_relationships_settings')) {
        wp_die('Nonce verification failed or missing nonce.');
    }
    $contexts = get_option('contexts');
    $enabled_context_names = [];
    $enabled_contexts = [];
    $validation_errors = [];
   
    foreach ($contexts as $context_name => $context_data) {
        $enabled_value = $_POST[$context_data['fields']['enabled']['name']];
        $contexts[$context_name]['fields']['enabled']['value'] = $enabled_value;
        if ($enabled_value === "1") {
            $post_type_field_name = $context_data['fields']['post_type']['name'];
            if (empty($_POST[$post_type_field_name])) {
                $validation_errors[] = "Context '{$context_name}' must have a post type.";
                $enabled_value = "0";
            }
            if (isset($context_data['fields']['taxonomy'])) {
                $taxonomy_field_name = $context_data['fields']['taxonomy']['name'];
                if (empty($_POST[$taxonomy_field_name])) {
                    $validation_errors[] = "Context '{$context_name}' must have a taxonomy.";
                    $enabled_value = "0";
                }
            }
        }

        if ($enabled_value === "1") {
            $enabled_context_names[] = $context_name;
            $enabled_contexts[$context_name] = [
                'context_name' => $context_name,
                'context_type' => $context_data['context_type'],
            ];

            foreach ($context_data['fields'] as $field_key => $field_data) {
                if ($field_key === 'enabled' || $field_key === 'remove') continue;
                $field_value = $_POST[$field_data['name']];
                $contexts[$context_name]['fields'][$field_key]['value'] = $field_value;
                $enabled_contexts[$context_name][$field_key] = $field_value;
            }
        } else {
            if ($enabled_value !== "0") {
                $contexts[$context_name]['fields']['enabled']['value'] = "0";
            }
            foreach ($context_data['fields'] as $field_key => $field_data) {
                if ($field_key === 'remove') continue;
                if ($field_key !== 'enabled') {
                    $contexts[$context_name]['fields'][$field_key]['value'] = '';
                }
            }
        }
    }

    if (!empty($validation_errors)) {
        $_SESSION['validation_errors'] = implode("\\n", array_map('esc_js', $validation_errors));
        wp_redirect(add_query_arg(['page' => 'relationships', 'status' => 'error'], admin_url('admin.php')));
        exit;
    }

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
                'post_type'     => $other_post_type,
                'taxonomy'      => $other_data['taxonomy'],
                'post_per_term' => $other_data['post_per_term'],
             ];
        } else { 
                $enabled_contexts[$connector_context_name]['related_unique_contexts'][$other_name] = [
                'unique_name' => $other_name,
                'schema' =>  $schema,
                'post_type'   => $other_post_type,
            ];
        }
        $enabled_contexts[$other_name]['related_connectors'][$connector_context_name] = [
            'connector_name' => $connector_context_name,
            'schema' => $connector_schema,
            'taxonomy'       => $connector_taxonomy,
            'post_type'      => $connector_post_type,
         ];
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
                    dibraco_setup_type_post_list_with_fallback($connector_taxonomy, $type_post_type, $type_taxonomy, $related_connector_count, $related_type_meta_key, $service_area_taxonomy_for_locations);
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
    update_option('contexts', $contexts);
    wp_redirect(add_query_arg(['page' => 'relationships', 'status' => 'success'], admin_url('admin.php')));
    exit;
}
add_action('admin_post_handle_save_relationships_settings', 'handle_save_relationships_settings');

function dibraco_setup_connector_term_posts( $taxonomy, $post_type, $post_id_meta_key, $post_url_meta_key ) {
    $all_terms = get_terms(['taxonomy' => $taxonomy,'hide_empty' => false, 'fields' => 'ids']);
    if (empty($all_terms)){return;}
    $all_posts = get_posts(['post_type'=> $post_type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
    if(empty($all_posts)) {
        foreach($all_terms as $term_id) {
            update_term_meta($term_id, $post_id_meta_key, '');
            update_term_meta($term_id, $post_url_meta_key, '');
        }
        return;
    }
    wp_delete_object_term_relationships($all_posts, $taxonomy);
    $relationships = wp_get_object_terms($all_posts, $taxonomy, [
        'fields' => 'all_with_object_id',
    ]);
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
function dibraco_get_related_post_ids($post_type, $tax_query, $posts_per_page = -1, $fields = 'ids') {
    $query_args = [
        'post_type'      => $post_type,
        'posts_per_page' => $posts_per_page,
        'tax_query'      => $tax_query,
        'post_status'    => 'publish',
        'fields'         => $fields,
    ];
    return get_posts($query_args);
}
function get_main_posts_for_options($context_data) {
    $context_name = $context_data['context_name'];
    $main_post_map = [];
    $type_taxonomy = $context_data['taxonomy'];
    $type_terms = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true]);
    if (empty($type_terms)) return;
    foreach ($type_terms as $type_term) {
        $term_id = $type_term->term_id;
        $main_post_id = get_term_meta($term_id, 'main_post_for_term', true);
        if (empty($main_post_id)) {
            $tax_query = [[ 'taxonomy' => $type_taxonomy, 'field' => 'term_id', 'terms' => $term_id ]];
            $main_posts = dibraco_get_related_post_ids($context_data['post_type'], $tax_query, 1);
            $main_post_id = $main_posts[0];
            update_term_meta($term_id, 'main_post_for_term', $main_post_id);
        }
        $main_post_map[$term_id] = (int) $main_post_id;
    }
    $option_name = "{$context_name}_main_posts";
    update_option($option_name, $main_post_map);
}


function dibraco_get_fallback_url($type_term_id, $connector_term_id, $related_type_meta_key, $connector_count, $type_taxonomy) {
    if ($connector_count === 2) {
        $area_parent_location_term = get_term_meta($connector_term_id, 'area_parent_location_term', true) ?? '';
        if ($area_parent_location_term !== '') {
            $location_meta = get_term_meta($area_parent_location_term, $related_type_meta_key, true);
            if (!empty($location_meta[$type_term_id]['related_post_url'])) {
                return $location_meta[$type_term_id]['related_post_url'];
            }
        }
    }
    return get_permalink(get_term_meta($type_term_id, 'main_post_for_term', true));
}


function dibraco_setup_type_post_list_with_fallback($connector_taxonomy, $type_post_type, $type_taxonomy, $connector_count, $related_type_meta_key, $service_area_taxonomy_for_locations = '') {
    $connector_terms = get_terms(['taxonomy'   => $connector_taxonomy, 'hide_empty' => false]);
    if (empty($connector_terms)) {
        return;
    }
    $type_terms = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true]);
    if (empty($type_terms)) {
        foreach ($connector_terms as $term) {
            update_term_meta($term->term_id, $related_type_meta_key, []);
        }
        return;
    }
    $tax_query = [
        'relation' => 'AND',
        ['taxonomy' => $connector_taxonomy, 'operator' => 'EXISTS'],
        ['taxonomy' => $type_taxonomy,      'operator' => 'EXISTS'],
    ];
    if ($service_area_taxonomy_for_locations !== '') {
        $tax_query[] = ['taxonomy' => $service_area_taxonomy_for_locations, 'operator' => 'NOT EXISTS'];
    }
    $all_posts = dibraco_get_related_post_ids($type_post_type, $tax_query, -1, 'ids');
    $map = [];
    if (!empty($all_posts)) {
        $all_rels = wp_get_object_terms($all_posts, [$connector_taxonomy, $type_taxonomy], ['fields' => 'all_with_object_id']);
        $post_relationships = [];
        foreach ($all_rels as $rel) {
            $post_relationships[$rel->object_id][$rel->taxonomy] = $rel->term_id;
        }
        foreach ($post_relationships as $post_id => $taxonomies) {
            if (isset($taxonomies[$connector_taxonomy], $taxonomies[$type_taxonomy])) {
                $connector_id = $taxonomies[$connector_taxonomy];
                $type_id = $taxonomies[$type_taxonomy];
                $map[$connector_id][$type_id] = $post_id;
            }
        }
    }
    foreach ($connector_terms as $connector_term) {
        $entries = [];
        $connector_id = $connector_term->term_id;
        $connector_term_name = $connector_term->name;
        foreach ($type_terms as $type_term) {
            $type_id = $type_term->term_id;
            $type_term_name = $type_term->name;
            $post_id = $map[$connector_id][$type_id] ?? '';
            $post_url = '';
            if (!empty($post_id)) {
                $post_url = get_permalink($post_id);
            }
            $entries[$type_id] = [
                'related_post_title' => "{$type_term_name} In {$connector_term_name}",
                'related_post_id'    => $post_id,
                'related_post_url'   => $post_url,
                'fallback_url'       => dibraco_get_fallback_url($type_id, $connector_id, $related_type_meta_key, $connector_count, $type_taxonomy),
            ];
        }
        update_term_meta($connector_term->term_id, $related_type_meta_key, $entries);
    }
}
function setup_connected_unique_posts($connector_taxonomy, $unique_post_type, $unique_context_name) {
    $related_unique_meta_key = "related_unique_{$unique_context_name}";
    $all_terms_to_reset_ids = get_terms([
        'taxonomy'   => $connector_taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ]);
    if (empty($all_terms_to_reset_ids)) {
        return;
    }
    $all_unique_post_objects = dibraco_get_related_post_ids($unique_post_type, [['taxonomy' => $connector_taxonomy, 'operator' => 'EXISTS']], -1, 'objects');
    if (empty($all_unique_post_objects)) {
        foreach ($all_terms_to_reset_ids as $term_id) {
            update_term_meta($term_id, $related_unique_meta_key, []);
        }
        return;
    }
    $all_unique_post_ids = wp_list_pluck($all_unique_post_objects, 'ID');
    $relationships = wp_get_object_terms($all_unique_post_ids, $connector_taxonomy, [
        'fields' => 'all_with_object_id',
    ]);
    $post_to_connector_term_map = [];
    foreach ($relationships as $rel) {
        $post_to_connector_term_map[$rel->object_id] = $rel->term_id;
    }
    $posts_for_this_connector_term = array_fill_keys($all_terms_to_reset_ids, []);
    foreach ($all_unique_post_objects as $post) {
        $post_id = $post->ID;
        if (!empty($post_to_connector_term_map[$post_id])) {
            $connector_term_id = $post_to_connector_term_map[$post_id];
            wp_set_object_terms($post_id, (int)$connector_term_id, $connector_taxonomy, false);
            $post_url = get_permalink($post_id);
            $posts_for_this_connector_term[$connector_term_id][$post_id] = [
                'related_post_id'    => $post_id,
                'related_post_title' => $post->post_title,
                'related_post_url'   => $post_url,
            ];
        }
    }
    foreach ($all_terms_to_reset_ids as $term_id) {
        $data_to_write = $posts_for_this_connector_term[$term_id];
        update_term_meta($term_id, $related_unique_meta_key, $data_to_write);
    }
}
function setup_connected_type_posts_no_fallback($connector_taxonomy, $type_post_type, $type_taxonomy, $related_type_meta_key) {
    $connector_term_names  = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
    if (empty($connector_term_names)){
         return;
    }
    $all_connector_terms_ids = [];
    $all_connector_terms_ids = array_keys($connector_term_names);
    $type_term_names = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true, 'fields' => 'id=>name']);
    if (empty($type_term_names)){
        return;
    }
    $all_relevant_post_ids = dibraco_get_related_post_ids($type_post_type, ['relation' => 'AND', ['taxonomy' => $connector_taxonomy, 'operator' => 'EXISTS'], ['taxonomy' => $type_taxonomy, 'operator' => 'EXISTS']], -1, 'ids');
    if (empty($all_relevant_post_ids)) {
           foreach ($all_connector_terms_ids as $term_id) { 
           update_term_meta($term_id, $related_type_meta_key, []); 
        } 
        return;
    }
    $connector_relationships = wp_get_object_terms($all_relevant_post_ids, $connector_taxonomy, ['fields' => 'all_with_object_id']);
    $type_relationships = wp_get_object_terms($all_relevant_post_ids, $type_taxonomy, ['fields' => 'all_with_object_id']);
    $post_to_connector_term_map = [];
    foreach ($connector_relationships as $connector_relationship) {
        if (!isset($post_to_connector_term_map[$connector_relationship->object_id])) {
            $post_to_connector_term_map[$connector_relationship->object_id] = $connector_relationship->term_id;
        }
    }
    $post_to_type_term_map = [];
    foreach ($type_relationships as $type_relationship) {
        if (!isset($post_to_type_term_map[$type_relationship->object_id])) {
            $post_to_type_term_map[$type_relationship->object_id] = $type_relationship->term_id; 
        }
    }
    $posts_grouped_by_connector = [];
    foreach ($all_connector_terms_ids as $connector_term_id) {
        $posts_grouped_by_connector[$connector_term_id] = [];
    }
     foreach ($all_relevant_post_ids as $post_id) {
        $connector_term_id = $post_to_connector_term_map[$post_id]; 
        $type_term_id = $post_to_type_term_map[$post_id]; 
        $type_term_name = $type_term_names[$type_term_id];
        $connector_term_name = $connector_term_names[$connector_term_id];
        $posts_grouped_by_connector[$connector_term_id][$type_term_id][$post_id] = [
            'related_post_title' => "{$type_term_name} In {$connector_term_name}",
            'related_post_id'    => $post_id,
            'related_post_url'   => get_permalink($post_id)
        ];
    }
    foreach ($all_connector_terms_ids as $connector_term_id) {
        $data_to_write = $posts_grouped_by_connector[$connector_term_id]; 
        update_term_meta($connector_term_id, $related_type_meta_key, $data_to_write);
    }
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
        $existing_post_id = get_term_meta($connector_term_id, $post_id_meta_key, true) ?? '';
        if ($existing_post_id !=='') {
           wp_set_object_terms((int)$existing_post_id, [], $connector_taxonomy, false);
        }
        $update_id_result = update_term_meta($connector_term_id, $post_id_meta_key, $post_id);
        $update_url_result = update_term_meta($connector_term_id, $link_url_meta_key, get_permalink($post_id));
    }
    wp_set_object_terms($post_id, $connector_term_id, $connector_taxonomy, false);
}

function dibraco_verify_post_save_request($nonce_field_name, $nonce_action) {
    if (!isset($_POST[$nonce_field_name]) || !wp_verify_nonce($_POST[$nonce_field_name], $nonce_action)) return false;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
    if (!current_user_can('edit_posts')) return false;
    return true;
}
function render_dibraco_main_posts_screen() {
    $contexts = get_option('enabled_type_contexts');
    if (empty($contexts)) { echo '<div class="wrap"><h1>Main Posts by Term</h1><p>No enabled contexts found.</p></div>'; return; }
    echo '<div class="wrap"><h1>Main Posts by Term</h1>';
    foreach ($contexts as $context_key => $context) {
        if ($context['post_per_term'] !== '1') continue;
        $taxonomy = $context['taxonomy'];
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
        if (empty($terms)) continue;
        echo "<h2>Context: {$context_key}</h2><table class='widefat'><thead><tr><th>Term</th><th>Post Title</th><th>Post URL</th></tr></thead><tbody>";
        foreach ($terms as $term) {
            $post_id = get_term_meta($term->term_id, 'main_post_for_term', true);
            if ($post_id && get_post_status($post_id)) {
                $post_title = get_the_title($post_id);
                $post_url = get_permalink($post_id);
            } else {
                $post_title = '<em>Not Set</em>';
                $post_url = '<em>Not Available</em>';
            }
            echo "<tr><td>{$term->name}</td><td>{$post_title}</td><td><a href='{$post_url}' target='_blank'>{$post_url}</a></td></tr>";
        }
        echo '</tbody></table><br>';
    }
    echo '</div>';
}
add_action('admin_init', function () {
         $custom_fields_for_pages = get_option('enable_custom_fields_for_pages', '0');
        if ($custom_fields_for_pages ==="1"){
            add_meta_box('section_fields_page', 'Post Fields', 'render_da_pages_metabox', 'page', 'normal', 'default');
            add_action('save_post_page', 'dibraco_save_section_fields_page');
        }
  
    $enabled_contexts = get_option('enabled_contexts');
    if (empty($enabled_contexts)) return;
    foreach ($enabled_contexts as $context => $context_data) {
        $post_type     = $context_data['post_type'];
        $schema_type = $context_data['schema'];
        $context_type  = $context_data['context_type'];
        $context_name  = $context_data['context_name'];
        $contact_fields = $context_data['contact_section'];
        $dibraco_banner = $context_data['dibraco_banner'];
        $primary_meta = $context_data['primary_meta'];
        $portrait_images = $context_data['portrait_images'];
        $main_sections = $context_data['main_sections'];
        $landscape_images = $context_data['repeater_images'];
        $before_after = $context_data['before_after'];
        $term_icon = '';
        if ($context_type ==='type' || $context_name ==='employee'){
            $has_certification = $context_data['has_certification'];
            if ($context_type ==='type'){
                $term_icon = $context_data['term_icon'];
            }
        }
        $pairings = [];
        if ($context_type ==='unique'){
            add_meta_box("da_side_images", 'Side Images', 'da_render_unique_image_box', $post_type, 'side', 'default', ['landscape' => $landscape_images, 'portrait' => $portrait_images]);
            add_action("save_post_{$post_type}", function($post_id) use ($landscape_images, $portrait_images) {
            da_save_standard_image_meta($post_id, $landscape_images, $portrait_images);}, 20, 1);  
            if ($context_name ==='employee'){
                add_meta_box('employee_detail_fields', 'Employee Details', 'da_render_employee_fields', $post_type, 'normal', 'high');
                add_action("save_post_{$post_type}", 'save_da_employee_fields', 10, 1);
            }
        }
        if ($context_type !== 'unique') {
            $taxonomy = $context_data['taxonomy'];
            if($context_name === 'service_areas') {
            $related_type_contexts = $context_data['related_type_contexts'];
            add_action("created_{$taxonomy}", function($term_id) use($related_type_contexts, $taxonomy) {
            setup_related_type_terms_for_new_term($term_id, $related_type_contexts, $taxonomy);}, 10, 1);
            add_action( "{$taxonomy}_edit_form_fields", function($term) use ($taxonomy, $context_data) {
            display_service_area_term_fields($term, $taxonomy, $context_data);}, 10, 1); 
            add_action( "edited_{$taxonomy}", function($term_id) use ($context_data) {
            handle_save_service_area_term_related_types( $term_id, $_POST, $context_data );}, 10, 1 );
            }
            if($context_name ==='locations' && $primary_meta==="1"){
                $related_type_contexts = $context_data['related_type_contexts'];
                add_action("created_{$taxonomy}", function($term_id) use($related_type_contexts, $taxonomy) {
                setup_related_type_terms_for_new_term($term_id, $related_type_contexts, $taxonomy);}, 10, 1);
                $screens = [$post_type, "edit-{$taxonomy}"];
                add_meta_box('location_details_meta_box', 'Location Details', 'render_location_meta_box', $screens, 'normal', 'high', ['intial_schema' => $schema_type, 'locations_context' => $context_data]);
                add_action("save_post_{$post_type}", function($post_id) use ($taxonomy) {
                dibraco_save_location_meta($post_id, $taxonomy);}, 25, 1);
                add_action("{$taxonomy}_edit_form_fields", function($term) use ($taxonomy) {
                ?> <tr class="form-field term-group-wrap"> <td colspan="2" style="padding: 0;">
                <?php do_meta_boxes("edit-{$taxonomy}", 'normal', $term); ?>
                </td> </tr> <?php }, 110, 1);
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
            if ($before_after === "1"){
                 add_meta_box('da_ba_images', 'Before/After Images', 'da_render_ba_box', $post_type, 'side', 'default', ['taxonomy' => $taxonomy]);
                 add_action( "save_post_{$context_data['post_type']}", 'da_save_ba_meta' );
            }
            if ($landscape_images ==="1" || $portrait_images ==="1" || $before_after ==="1" || $term_icon ==="1"){
                add_action("{$taxonomy}_edit_form_fields", function ($term) use ($before_after, $landscape_images, $portrait_images, $term_icon) {
                render_dibraco_term_image_fields($term, $before_after, $landscape_images, $portrait_images, $term_icon);
                }, 10, 1);
                add_action("edited_{$taxonomy}", function ($term_id) use ($before_after, $landscape_images, $portrait_images, $term_icon) {
                  save_dibraco_term_image_fields($term_id, $before_after, $landscape_images, $portrait_images, $term_icon);}, 5, 1);
                }
              if ($context_type === 'type' && $has_certification === "1") {
                add_action("{$taxonomy}_edit_form_fields", 'render_term_certification_fields', 10, 1);
                add_action("edited_{$taxonomy}", 'save_term_certification_fields', 10, 1);
                }
            if ($landscape_images ==="1" || $portrait_images ==="1" || $term_icon ==="1"){
            add_meta_box("da_side_images", 'Side Images', 'da_render_standard_image_box', $post_type, 'side', 'default', ['landscape' => $landscape_images, 'portrait' => $portrait_images, 'term_icon'=> $term_icon, 'taxonomy' => $taxonomy]);
            add_action("save_post_{$post_type}", function($post_id) use ($landscape_images, $portrait_images, $term_icon) {
            da_save_standard_image_meta($post_id, $landscape_images, $portrait_images, $term_icon); }, 20, 1);
            }
            $pairing = [
                'taxonomy'     => $taxonomy,
                'context_name' => $context_name,
            ];
            if ($context_type === 'type') {
                if($context_data['post_per_term'] === "1"){
                $pairing['main_post_for_term'] = true;
            }
            }
            $pairings[] = $pairing;
        }
        if ($context_type !== 'connector') {
            $related_connectors = $context_data['related_connectors'] ?? [];
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
if (($contact_fields === "1" || $dibraco_banner === "1" || $main_sections ==="1")) {
        add_meta_box('banner_contact_section_fields', 'Post Fields', 'render_da_combined_metabox', $post_type, 'normal', 'default',   ['context_data' => $context_data]);
        add_action("save_post_{$post_type}", function($post_id) use ($context_data) {
         dibraco_save_banner_contact_section_fields($post_id, $context_data); }, 20, 1);
    }
if (!empty($pairings)) {
            add_remove_custom_metabox($post_type, $pairings);
            add_remove_custom_column($post_type, $pairings);
    }
   if ($context_type === 'unique' || $context_type === 'type') {
        switch ($context_type) {
            case 'unique':
                add_action("save_post_{$post_type}", function ($post_id) use ($context_data, $related_connectors) {
                    if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {return;}
                    if (!empty($related_connectors)) {
                        save_related_connector_terms_to_unique($post_id, $context_data, $related_connectors);
                    }
                }, 10, 2);
                break;
            case 'type':
            add_action("save_post_{$post_type}", function ($post_id) use ($taxonomy, $related_connectors, $context_data) {
               if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {return;}
                $related_connectors = $context_data['related_connectors'];
                $checkbox_of_main_term_value = "0";
                $previous_type_term_id =  dibraco_get_current_term_id_for_post($post_id, $taxonomy);
                $new_type_term_id = $_POST["{$taxonomy}_term"] ?? '';
                if ($new_type_term_id!==''){
                    $new_type_term_id = (int)$new_type_term_id;
                }
                if ($context_data['post_per_term'] === "1" && (!empty($related_connectors))){
                 $context_name = $context_data['context_name'];
                 $related_connector_count = $context_data['related_connector_count'];
                 $checkbox_of_main_term_value = $_POST['main_post_for_term'];
                 figure_out_the_check_box_situation($new_type_term_id, $previous_type_term_id, $checkbox_of_main_term_value, $post_id, $context_name, $taxonomy, $related_connectors, $related_connector_count);
                }
                wp_set_object_terms($post_id, $new_type_term_id, $taxonomy);
                    if (!empty($related_connectors)) {
                    save_related_connector_terms_to_type_post_type($post_id, $context_data, $related_connectors, $new_type_term_id, $taxonomy);
                    }
                }, 10, 2);
                break;
        }
    }}
});
function figure_out_the_check_box_situation($new_term_id, $old_term_id, $checkbox_value, $post_id, $context_name, $taxonomy, $related_connectors, $related_connector_count) {

    error_log("DIBRADEBUG: === figure_out_the_check_box_situation START ===");
    error_log("DIBRADEBUG: Params: new_term_id={$new_term_id}, old_term_id={$old_term_id}, checkbox_value={$checkbox_value}, post_id={$post_id}, context_name={$context_name}");

    $main_post_map = get_option("{$context_name}_main_posts");
    // CRITICAL GUARD: Ensure $main_post_map is an array, especially if the option is brand new
    if (!is_array($main_post_map)) {
        $main_post_map = [];
        error_log("DIBRADEBUG: main_post_map was NOT an array (likely option not set yet). Initialized to empty array.");
    }
    error_log("DIBRADEBUG: Initial main_post_map from get_option: " . print_r($main_post_map, true));

    $new_tax_query = [[ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $new_term_id ]];
    $old_tax_query = [[ 'taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => $old_term_id ]];

    if ((int)$old_term_id && $old_term_id !== $new_term_id) {
        error_log("DIBRADEBUG: Old term ID logic triggered. old_term_id: {$old_term_id}");
        $old_main_id = get_term_meta($old_term_id, 'main_post_for_term', true) ?? '';
        error_log("DIBRADEBUG: old_main_id for term {$old_term_id}: {$old_main_id}");

        if ((int)$old_main_id === $post_id) {
            error_log("DIBRADEBUG: Current post {$post_id} was main for old term {$old_term_id}. Finding replacement.");
            $replacement = get_posts([
                'post_type' => 'any',
                'posts_per_page' => 1,
                'tax_query' => $old_tax_query,
                'post_status' => 'publish',
                'fields' => 'ids',
                'exclude' => [$post_id]
            ]);
            $replacement_id = $replacement[0] ?? $post_id; // Original logic: Fallback to $post_id if no other replacement found.
            error_log("DIBRADEBUG: Old term replacement get_posts result: " . print_r($replacement, true) . ", replacement_id chosen: {$replacement_id}");

            update_post_meta($post_id, 'main_post_for_term', '0');
            error_log("DIBRADEBUG: Post {$post_id} meta 'main_post_for_term' set to '0'.");

            // Ensure $replacement_id is a valid post ID before trying to update its meta.
            // Original code: update_post_meta($replacement_id, 'main_post_for_term', '1');
            if ($replacement_id && get_post_status($replacement_id)) { // Added check for valid replacement ID
                 update_post_meta($replacement_id, 'main_post_for_term', '1');
                 error_log("DIBRADEBUG: Replacement post {$replacement_id} meta 'main_post_for_term' set to '1'.");
            } else {
                 error_log("DIBRADEBUG: No valid replacement post found to set as main for old term {$old_term_id}.");
            }


            update_term_meta($old_term_id, 'main_post_for_term', $replacement_id);
            error_log("DIBRADEBUG: Term meta for old term {$old_term_id} updated to: {$replacement_id}");

            $main_post_map[$old_term_id] = (int)$replacement_id;
            error_log("DIBRADEBUG: main_post_map updated locally for old_term_id {$old_term_id}: " . print_r($main_post_map, true));
        }
    }

    if ((int)$new_term_id) {
        error_log("DIBRADEBUG: New term ID logic triggered. new_term_id: {$new_term_id}");
        $current_main = get_term_meta($new_term_id, 'main_post_for_term', true) ?? '';
        error_log("DIBRADEBUG: current_main for new_term_id {$new_term_id}: {$current_main}");
        $new_value = '';

        if ($checkbox_value === "1") {
            error_log("DIBRADEBUG: Checkbox value is '1'. Setting post {$post_id} as main for term {$new_term_id}.");
            $new_value = $post_id;

            // CRITICAL CHECK: Only attempt to un-main if current_main is a valid post ID AND is not the current post
            // Original: update_post_meta($current_main, 'main_post_for_term', '0');
            if ((int)$current_main && (int)$current_main !== $post_id && get_post_status((int)$current_main)) {
                 update_post_meta((int)$current_main, 'main_post_for_term', '0');
                 error_log("DIBRADEBUG: Previous main post {$current_main} for new_term_id {$new_term_id} un-mained.");
            } else {
                 error_log("DIBRADEBUG: No previous main post to un-main for new_term_id {$new_term_id} or current post is the same.");
            }

            update_post_meta($post_id, 'main_post_for_term', '1');
            error_log("DIBRADEBUG: Post {$post_id} meta 'main_post_for_term' set to '1'.");

            $main_post_map[$new_term_id] = (int)$post_id;
            error_log("DIBRADEBUG: main_post_map updated locally for new_term_id {$new_term_id}: " . print_r($main_post_map, true));

        } else {
            error_log("DIBRADEBUG: Checkbox value is NOT '1'.");
            if ((int)$current_main === $post_id) {
                error_log("DIBRADEBUG: Current post {$post_id} was main for new_term_id {$new_term_id} but checkbox is unchecked. Finding new main.");
                $replacement = get_posts([
                    'post_type' => 'any',
                    'posts_per_page' => 1,
                    'tax_query' => $new_tax_query,
                    'post_status' => 'publish',
                    'fields' => 'ids',
                    'exclude' => [$post_id]
                ]);
                $replacement_id = $replacement[0] ?? $post_id; // Original logic: Fallback to $post_id if no other replacement found.
                error_log("DIBRADEBUG: New term replacement get_posts result: " . print_r($replacement, true) . ", replacement_id chosen: {$replacement_id}");

                update_post_meta($post_id, 'main_post_for_term', '0');
                error_log("DIBRADEBUG: Post {$post_id} meta 'main_post_for_term' set to '0'.");

                // Ensure $replacement_id is a valid post ID before trying to update its meta.
                // Original code: update_post_meta($replacement_id, 'main_post_for_term', '1');
                if ($replacement_id && get_post_status($replacement_id)) { // Added check for valid replacement ID
                    update_post_meta($replacement_id, 'main_post_for_term', '1');
                    error_log("DIBRADEBUG: Replacement post {$replacement_id} meta 'main_post_for_term' set to '1'.");
                } else {
                    error_log("DIBRADEBUG: No valid replacement post found to set as main for new term {$new_term_id}.");
                }

                $new_value = $replacement_id;
                $main_post_map[$new_term_id] = (int)$replacement_id;
                error_log("DIBRADEBUG: main_post_map updated locally for new_term_id {$new_term_id} with replacement: " . print_r($main_post_map, true));
            } else {
                error_log("DIBRADEBUG: Post {$post_id} was not main, and checkbox is unchecked. Main post for term {$new_term_id} remains {$current_main}.");
                $new_value = $current_main;
                $main_post_map[$new_term_id] = (int)$current_main;
            }
        }
        update_term_meta($new_term_id, 'main_post_for_term', $new_value);
        error_log("DIBRADEBUG: Term meta 'main_post_for_term' for new_term_id {$new_term_id} updated to: {$new_value}");
    }

    error_log("DIBRADEBUG: Attempting to update global option `{$context_name}_main_posts` with: " . print_r($main_post_map, true));
    update_option("{$context_name}_main_posts", $main_post_map);
    error_log("DIBRADEBUG: Global option `{$context_name}_main_posts` AFTER update: " . print_r(get_option("{$context_name}_main_posts"), true));

    // --- Related Connectors Loop (unchanged) ---
    foreach ($related_connectors as $related_connector => $connector_data) {
        $meta_key = "related_type_{$context_name}";
        $connector_taxonomy = $connector_data['taxonomy'];
        $terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false]);
        foreach ($terms as $term) {
            $connector_meta = get_term_meta($term->term_id, $meta_key, true);
            if (!isset($connector_meta[$new_term_id])) {continue;}
            $entry = $connector_meta[$new_term_id];
            if (!empty($entry['related_post_id'])) { continue; }
            $fallback_url = '';
            if ($related_connector_count === 2 && $related_connector === 'service_areas') {
                $location_id = get_term_meta($term->term_id, 'area_parent_location_term', true);
                $location_meta = get_term_meta($location_id, $meta_key, true);
                $location_entry = $location_meta[$new_term_id] ?? [];

                if (!empty($location_entry['related_post_url'])) {
                    $fallback_url = $location_entry['related_post_url'];
                } else {
                    // This relies on $main_post_map[$new_term_id] being correctly set
                    $main_post_id = $main_post_map[$new_term_id];
                    $fallback_url = get_permalink($main_post_id);
                }
            } else {
                // This relies on $main_post_map[$new_term_id] being correctly set
                $main_post_id = $main_post_map[$new_term_id];
                $fallback_url = get_permalink($main_post_id);
            }
            $connector_meta[$new_term_id]['fallback_url'] = $fallback_url;
            update_term_meta($term->term_id, $meta_key, $connector_meta);
        }
    }
    error_log("DIBRADEBUG: === figure_out_the_check_box_situation END ===");
}


function type_connector_clear_old_meta($connector_term_id, $meta_key, $type_term_id, $post_id, $post_per_term) {
    $old_meta = get_term_meta($connector_term_id, $meta_key, true);
    if ($post_per_term === "1") {
        if (isset($old_meta[$type_term_id])) {
            $old_meta[$type_term_id]['related_post_id'] = '';
            $old_meta[$type_term_id]['related_post_url'] = ''; 
        }
        update_term_meta($connector_term_id, $meta_key, $old_meta);
    } else {
        if (isset($old_meta[$type_term_id][$post_id])) {
            unset($old_meta[$type_term_id][$post_id]);
            if (empty($old_meta[$type_term_id])) {
                unset($old_meta[$type_term_id]);
            }
        }
        if (empty($old_meta[$type_term_id])) {
            delete_term_meta($connector_term_id, $meta_key); 
        } else {
            update_term_meta($connector_term_id, $meta_key, $old_meta); 
        }
    }
}
function update_fallbacks_for_service_area_terms($new_loc_id, $meta_key, $type_term_id, $post_id, $areas_tax){
    $fall_back_url = get_permalink($post_id);
    $act_ids = get_term_meta($new_loc_id, 'associated_act_terms', true);
    if (empty($act_ids)) {return;}
    foreach ($act_ids as $act_id) {
        $current_meta = get_term_meta($act_id, $meta_key, true);
        if (!isset($current_meta[$type_term_id])) {
            $type_term_name     = get_term($type_term_id)->name;
            $connector_term_name = get_term($new_loc_id)->name;
            $current_meta[$type_term_id] = [
                'related_post_title' => "$type_term_name in $connector_term_name",
                'related_post_id'    => '',
                'related_post_url'   => '',
            ];
        }
        $current_meta[$type_term_id]['fallback_url'] = $fall_back_url;
        update_term_meta($act_id, $meta_key, $current_meta);    
    }
}
function type_connector_update_term_meta($connector_term_id, $meta_key, $type_term_id, $post_id, $post_per_term) {
    $connector_term_name = get_term($connector_term_id)->name;
    $type_term_name = get_term($type_term_id)->name;
    $related_post_title = "$type_term_name in $connector_term_name";
    $current_meta = get_term_meta($connector_term_id, $meta_key, true);
       if ($post_per_term === "1") {
        if (!isset($current_meta[$type_term_id])) {
            $current_meta[$type_term_id] = [
                'related_post_title' => "$type_term_name in $connector_term_name",
                'related_post_id'    => $post_id,
                'related_post_url'   => get_permalink($post_id),
                'fallback_url'       => '', 
            ];
            update_term_meta($connector_term_id, $meta_key, $current_meta);
            return;
        }
        $current_meta[$type_term_id]['related_post_id'] = $post_id;
        $current_meta[$type_term_id]['related_post_url'] = get_permalink($post_id);
    } else {
        if (!isset($current_meta[$type_term_id])) {
             $current_meta[$type_term_id] = [];
            }
            $current_meta[$type_term_id][$post_id] = [
            'related_post_title' => $related_post_title,
            'related_post_id'    => $post_id,
            'related_post_url'   => get_permalink($post_id),
            ];
    }
    update_term_meta($connector_term_id, $meta_key, $current_meta);
}
function save_related_connector_terms_to_type_post_type($post_id, $context_data, $related_connectors, $type_term_id, $taxonomy) {
    $type_context_name = $context_data['context_name'];
    $related_connectors_count = $context_data['related_connector_count'];
    $post_per_term = $context_data['post_per_term'];
    $meta_key = "related_type_{$type_context_name}";
    if ($related_connectors_count === 1) {
        foreach ($related_connectors as $connector_data) {
            $connector_taxonomy = $connector_data['taxonomy'];
            $current_term_id = dibraco_get_current_term_id_for_post($post_id, $connector_taxonomy);
            $new_term_id = $_POST["{$connector_taxonomy}_term"] ?? '';
            if ($current_term_id !== '' && $current_term_id != $new_term_id) {
                type_connector_clear_old_meta($current_term_id, $meta_key, $type_term_id, $post_id, $post_per_term);
            }
            $terms_to_set = [];
            if ($new_term_id !== '') {
                $new_term_id = (int)$new_term_id;
                type_connector_update_term_meta($new_term_id, $meta_key, $type_term_id, $post_id, $post_per_term);
            }
            wp_set_object_terms($post_id, $new_term_id, $connector_taxonomy, false);
        }
    } 
    else if ($related_connectors_count === 2) {
        $areas_tax = $related_connectors['service_areas']['taxonomy'];
        $loc_tax = $related_connectors['locations']['taxonomy'];
        $current_area_id = dibraco_get_current_term_id_for_post($post_id, $areas_tax);
        $current_loc_id = dibraco_get_current_term_id_for_post($post_id, $loc_tax);
        $new_area_id = $_POST["{$areas_tax}_term"];
        $new_loc_id = $_POST["{$loc_tax}_term"];
        if ($current_area_id !== '' ) {
         type_connector_clear_old_meta($current_area_id, $meta_key, $type_term_id, $post_id, $post_per_term);
        }
        if ($current_loc_id !== '') {
         type_connector_clear_old_meta($current_loc_id, $meta_key, $type_term_id, $post_id, $post_per_term);
        }
        $area_terms_to_set = [];
        $loc_terms_to_set = [];
        if ($new_area_id !== '') {
            $new_area_id = (int)$new_area_id;
            type_connector_update_term_meta($new_area_id, $meta_key, $type_term_id, $post_id, $post_per_term);
        } else if ($new_loc_id !== '') {
             $new_loc_id = (int)$new_loc_id;
            type_connector_update_term_meta($new_loc_id, $meta_key, $type_term_id, $post_id, $post_per_term);
            if ($post_per_term ==="1")
            update_fallbacks_for_service_area_terms($new_loc_id, $meta_key, $type_term_id, $post_id, $areas_tax);
        }
        wp_set_object_terms($post_id, $new_area_id, $areas_tax, false);
        if ($new_loc_id !== '') {
            $new_loc_id = (int)$new_loc_id;
        }
        wp_set_object_terms($post_id, $new_loc_id, $loc_tax, false);
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
        $current_term_id = int($current_term_id);
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
function setup_related_type_terms_for_new_term($connector_term_id, $related_type_contexts, $taxonomy) {
    $connector_term = get_term($connector_term_id, $taxonomy);
    if (!$connector_term) return;

    $connector_term_name = $connector_term->name;

    foreach ($related_type_contexts as $related_type_name => $related_connector_data) {
        if ($related_connector_data['post_per_term'] !== "1") continue;

        $main_posts = get_option("{$related_type_name}_main_posts");
        if (!$main_posts) continue;

        $meta_key = "related_type_{$related_type_name}";
        $existing_meta = get_term_meta($connector_term_id, $meta_key, true) ?: [];

        foreach ($main_posts as $type_term_id => $main_post_id) {
            $type_term = get_term($type_term_id);
            $related_post_title = "{$type_term->name} in {$connector_term_name}";
            $fallback_url = get_permalink($main_post_id);

            $existing_meta[$type_term_id] = [
                'related_post_title' => $related_post_title,
                'related_post_id'    => '',
                'related_post_url'   => '',
                'fallback_url'       => $fallback_url,
            ];
        }
        update_term_meta($connector_term_id, $meta_key, $existing_meta);
    }
}



function render_related_type_context_tables($related_type_contexts, $current_connector_term_id) {
    foreach ($related_type_contexts as $related_type_context_name => $type_context_data) {
        $related_type_meta_key = "related_type_{$related_type_context_name}";
        $rows = get_term_meta($current_connector_term_id, $related_type_meta_key, true);
        if (empty($rows)) {continue;}
        $post_per_term = $type_context_data['post_per_term'];
        $context_label = ucwords(str_replace('_', ' ', $related_type_context_name));
        $colspan = ($post_per_term === "1") ? 4 : 3;
        $width = ($post_per_term === "1") ? 'width:30%' : 'width:45%';
        ?>
        <table class="wp-list-table widefat striped">
            <thead> <tr><th colspan="<?= $colspan ?>" style="text-align:left; background:#f1f1f1; font-weight:bold;">Related <?= $context_label ?> Posts</th></tr>
                <tr><th style="width:10%">Term</th><th style="<?= $width ?>">Title</th><th style="<?= $width ?>">URL</th><?php if ($post_per_term === "1"): ?><th style="width:30%">Fallback</th><?php endif; ?></tr>
            </thead> <tbody>
            <?php foreach ($rows as $type_term_id => $entry_data){
               $type_term_name = get_term($type_term_id)->name; $posts_to_render = ($post_per_term === "1") ? [$entry_data] : $entry_data;
                $row_name = "{$related_type_meta_key}[{$type_term_id}]";
                foreach ($posts_to_render as $post_key => $post_details) {
                    $related_post_id = $post_details['related_post_id'];
                    $display_title = $post_details['related_post_title'];
                    $related_post_url = $post_details['related_post_url'];
                    if ($post_per_term !== "1") {$row_name = "{$related_type_meta_key}[{$type_term_id}][{$related_post_id}]";}
                    ?><tr> <td><?= $type_term_name ?></td> <td>
                            <input type="text" style="width:100%" name="<?= $row_name ?>[related_post_title]" value="<?= $display_title ?>" />
                        </td> <td> <?= $related_post_url ?> <input type="hidden" name="<?= $row_name ?>[related_post_id]" value="<?= $related_post_id ?>" />
                            <input type="hidden" name="<?= $row_name ?>[related_post_url]" value="<?= $related_post_url ?>" /> </td>
                        <?php if ($post_per_term === "1"): ?> <td> <input type="hidden" name="<?= $row_name ?>[fallback_url]" value="<?= $post_details['fallback_url'] ?>" />
                            <?= $post_details['fallback_url'] ?> </td> <?php endif; ?> </tr>
                    <?php
                }
            }
            ?> </tbody> </table>
        <?php
    }
}

function render_related_unique_context_tables($related_unique_contexts, $current_connector_term_id) {
    foreach ($related_unique_contexts as $unique_context => $unique_context_data) {
        $unique_context_name = $unique_context_data['unique_name'];
        $related_unique_meta_key = "related_unique_{$unique_context_name}";
        $related_unique_posts_data = get_term_meta($current_connector_term_id, $related_unique_meta_key, true);
        if (empty($related_unique_posts_data)) {continue;}
        $context_label = ucwords(str_replace('_', ' ', $unique_context_name));
        ?>
        <table class="wp-list-table widefat striped">
            <thead><tr><th colspan="2" style="text-align:left; background:#f1f1f1; font-weight:bold;">Related <?= $context_label ?> Posts</th></tr>
                <tr><th style="width: 40%;">Title</th><th style="width: 60%;">URL</th></tr></thead>
            <tbody>
    <?php 
    foreach ($related_unique_posts_data as $post_id => $post_data){
        $display_title = $post_data['related_post_title'];
        $related_post_url = $post_data['related_post_url'];
        $row_name = "{$related_unique_meta_key}[{$post_id}]";
        ?>
        <tr> <td> <input type="text" style="width:100%" name="<?= $row_name ?>[related_post_title]" value="<?= $display_title ?>" />
                            <input type="hidden" name="<?= $row_name ?>[related_post_url]" value="<?= $related_post_url ?>" />
                            <input type="hidden" name="<?= $row_name ?>[related_post_id]" value="<?= $post_id ?>" />
                        </td> <td> <?= $related_post_url ?> </td> </tr>
        <?php } ?> </tbody>  </table> <?php }  
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
function add_remove_custom_metabox($post_type, $pairings) {
  foreach ( $pairings as $pairing ) {
    add_action( "add_meta_boxes_{$post_type}", function() use ($post_type, $pairing) {
        $taxonomy = $pairing['taxonomy'];
        $context_name = $pairing['context_name']; 
        $is_main_post_flag = isset($pairing['main_post_for_term']) ? $context_name : '';
        $context_name_readable = ucwords(str_replace(['-', '_'], ' ', $context_name));
        add_meta_box( "dibraco_{$taxonomy}_custom_box", "{$context_name_readable} Selector", 'pre_render_meta_box', $post_type, 'side', 'default', [ 'pairing' => $pairing, '__block_editor_compatible_meta_box' => true]);
        foreach ( [ 'normal','side','advanced' ] as $context ) {
          remove_meta_box( "{$taxonomy}div", $post_type, $context );
         }
    }, 99 );
    }
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
function add_remove_custom_column($post_type, $pairings) {
   foreach ($pairings as $pairing) {
        $taxonomy = $pairing['taxonomy'];
        $context_name = $pairing['context_name'];
        $column_key = "taxonomy-{$taxonomy}";
        $column_title = ucwords(str_replace(['-', '_'], ' ', $context_name));
        $quick_edit_field_key = "{$taxonomy}_term";
        $is_main_post_flag = '';
        if (isset($pairing['main_post_for_term'])){
            $is_main_post_flag = $context_name; 
        }
        
add_filter("manage_{$post_type}_posts_columns", function($columns) use ($column_key, $column_title) {
    if (isset($columns[$column_key])) {
        unset($columns[$column_key]);
    }
    $new_columns = [];
    foreach ($columns as $key => $title) {
        $new_columns[$key] = $title;
        if ($key === 'title') {
            $new_columns[$column_key] = $column_title;
        }
    }
    return $new_columns; 
}, 15, 1);

if ($is_main_post_flag === $context_name) {
    $main_post_map = get_option("{$context_name}_main_posts", []);
    if (empty($main_post_map)) {return;}
    add_filter('get_the_terms', function ($terms_array, $current_post_id, $taxonomy_name) use ($main_post_map, $taxonomy) {
        if ($taxonomy_name !== $taxonomy){return $terms_array;}
        if (!in_array($current_post_id, $main_post_map)) {return $terms_array;}
        foreach ($terms_array as $term_object) {
            $main_post_id_for_this_term = $main_post_map[$term_object->term_id];
            if ((int)$main_post_id_for_this_term === (int)$current_post_id) {
                $term_object->name .= ' (Main)';
            }
        }
      return $terms_array;
            }, 15, 3);
        }
    add_filter("manage_edit_{$post_type}_sortable_columns", function($sortable_columns) use ($column_key) {
            $sortable_columns[$column_key] = $column_key;
            return $sortable_columns;
        }, 20, 1);
       add_filter('quick_edit_show_taxonomy', function($show, $taxonomy_name, $post_type) use ($taxonomy) {
         if ($taxonomy_name === $taxonomy){return false;}
            return $show;
       }, 10, 3);
    add_action('quick_edit_custom_box', function($column_name, $post_type_slug) use ($column_key, $taxonomy, $post_type, $is_main_post_flag) {
         if ($column_name !== $column_key || $post_type_slug !== $post_type) {return;}
    ?>
    <fieldset class="inline-edit-col-left inline-edit-<?php echo $taxonomy; ?>">
        <div class="inline-edit-col column-<?php echo $column_name; ?>">
          <span class="title inline-edit-group"></span>
            <div class="inline-edit-<?php echo $taxonomy; ?>-input">
               <?php
                 render_radio_buttons_for_post_meta_box($taxonomy, '', '',  $is_main_post_flag);
               ?>
            </div>
        </div>
    </fieldset>
    <?php

}, 15, 2); 
add_action("save_post_{$post_type}", function($post_id, $post, $update) use ($taxonomy, $quick_edit_field_key) {
     if (!wp_doing_ajax() || ! isset($_POST['action']) || $_POST['action'] !== 'inline-save' ) {return;}     
     if (!isset($_POST[$quick_edit_field_key])) {return;}
    $term_value = $_POST[$quick_edit_field_key]; 
    if ($term_value !==''){ $term_value = (int)$term_value; }
    $current_post_type = $post->post_type;
    $locations_post_type = '';
    $locations_taxonomy = '';
    $service_area_post_type = '';
    $service_area_taxonomy = ''; 
    $status = get_option('locations_areas_status');
    $enabled_contexts = get_option('enabled_connector_contexts');
    if ($status === 'both' || $status === 'multi_locations') {
         $locations_post_type = $enabled_contexts['locations']['post_type'];
         $locations_taxonomy = $enabled_contexts['locations']['taxonomy'];
           if (($current_post_type === $locations_post_type) && ($locations_taxonomy === $taxonomy)){
               dibraco_enforce_one_connector_term_per_connector_post($post_id, $term_value, $taxonomy, 'location_post_id', 'location_link_url');
               return; }
             if (dibraco_process_specific_type_connector_block($post_id, $current_post_type, $taxonomy, 'locations')){ return; }
         if (dibraco_process_specific_unique_connector_block($post_id, $current_post_type, $taxonomy, 'locations')){ return; }
        }
    if ($status === 'both' || $status === 'multi_areas') {
            $service_area_post_type = $enabled_contexts['service_areas']['post_type'];
            $service_area_taxonomy = $enabled_contexts['service_areas']['taxonomy'];
            if (($current_post_type === $service_area_post_type) && ($service_area_taxonomy === $taxonomy)){
               dibraco_enforce_one_connector_term_per_connector_post($post_id, $term_value, $taxonomy, 'service_area_post_id', 'service_area_link_url'); return; }
           if (dibraco_process_specific_type_connector_block($post_id, $current_post_type, $taxonomy, 'service_areas')){ return; }
        if (dibraco_process_specific_unique_connector_block($post_id, $current_post_type, $taxonomy, 'service_areas')){ return; }
        }
            wp_set_post_terms($post_id, $term_value, $taxonomy, false); 
         }, 10, 3);
    }
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
             $previous_type_term_id = dibraco_get_current_term_id_for_post($post_id, $type_taxonomy);
             $type_term_id = $_POST["{$type_taxonomy}_term"]??'';
              $related_connectors = $type_context_data['related_connectors'];
              $related_connector_count =  $type_context_data['related_connector_count'];
            if ($type_term_id !==''){
                $type_term_id = (int) $type_term_id;
            }
            if ($type_context_data['post_per_term'] ==="1"){
              $checkbox_of_main_term_value = $_POST['main_post_for_term']??'';     
             figure_out_the_check_box_situation($type_term_id, $previous_type_term_id, $checkbox_of_main_term_value, $post_id, $type_context_name, $type_taxonomy, $related_connectors, $related_connector_count);
             }
              wp_set_object_terms($post_id, $type_term_id, $type_taxonomy);
             if (isset($related_connectors[$connector_key])) {
                $related_connector_taxonomy = $related_connectors[$connector_key]['taxonomy'];
                 if ($related_connector_taxonomy === $taxonomy){
                     save_related_connector_terms_to_type_post_type($post_id, $type_context_data, $related_connectors, $type_term_id, $type_taxonomy);
                     return true;
                 }
             }
             }
             return false; 
        }