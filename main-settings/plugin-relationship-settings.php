<?php
function taxonomy_term_id_one_post_per_term($term_ids, $post_ids) { 
    global $wpdb;
    $post_id_string = implode(',', array_map('intval', $post_ids));
    $term_id_string = implode(',', array_map('intval', $term_ids)); 
    $results = $wpdb->get_results("SELECT MIN(object_id) as object_id, term_taxonomy_id
        FROM {$wpdb->term_relationships}
        WHERE object_id IN ({$post_id_string})
        AND term_taxonomy_id IN ({$term_id_string})
        GROUP BY term_taxonomy_id");
    
    return array_column($results, 'object_id', 'term_taxonomy_id');
}
function post_type_posts_with_one_term_or_empty($post_ids, $term_ids) {
    global $wpdb;
    $term_ids_string = implode(',', array_map('intval', $term_ids));
    $post_ids_string = implode(',', array_map('intval', $post_ids));

    $results = $wpdb->get_results("
        SELECT tr.object_id AS post_id, tr.term_taxonomy_id AS term_id
        FROM {$wpdb->term_relationships} AS tr
        WHERE tr.term_taxonomy_id IN ({$term_ids_string})
          AND tr.object_id IN ({$post_ids_string}) ", ARRAY_A);
    $map = array_fill_keys($post_ids, ''); 
    foreach ($results as $row) {
        $map[(int)$row['post_id']] = (int)$row['term_id']; // int when term exists
    }
    return $map;
}
function post_ids_with_term_id_from_single_taxonomy($term_ids_to_match, $post_ids) {
    global $wpdb;
    $post_ids_string = implode(',', array_map('intval', $post_ids));
    $term_ids_string = implode(',', array_map('intval', $term_ids_to_match));
    $results = $wpdb->get_results("SELECT object_id, term_taxonomy_id
        FROM {$wpdb->term_relationships}
        WHERE object_id IN ({$post_ids_string})
        AND term_taxonomy_id IN ({$term_ids_string})", ARRAY_A);
    $grouped = array_fill_keys($term_ids_to_match, []);

    foreach ($results as $row) {
        $term_id = (int)$row['term_taxonomy_id'];
        $post_id = (int)$row['object_id'];
        $grouped[$term_id][] = $post_id;
    }
    return array_filter($grouped);
}

function get_type_taxonomy_term_ids_and_post_ids($type_taxonomy, $post_types) {
    global $wpdb;
    $post_types = (array)$post_types;
    $post_types_string = "'" . implode("','", $post_types) . "'";
    $results = $wpdb->get_results("
        SELECT p.ID AS post_id,
        tt.term_taxonomy_id AS term_id,
        p.post_type        
        FROM {$wpdb->term_taxonomy} AS tt
        INNER JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIN {$wpdb->posts} AS p ON tr.object_id = p.ID
        WHERE tt.taxonomy = '{$type_taxonomy}'
          AND p.post_type IN ({$post_types_string})
          AND p.post_status = 'publish'");
    $post_ids = [];
    $post_ids_by_type = [];
    $term_ids = [];
    foreach ($results as $row) {
        $post_ids[] = (int)$row->post_id;
        $term_ids[] = (int)$row->term_id;
        $post_ids_by_type[$row->post_type][] = (int)$row->post_id;
    }
    return [
        'term_ids' => array_values(array_unique($term_ids)),
        'post_ids' => array_values(array_unique($post_ids)),
        'post_ids_by_type' => array_map('array_unique', $post_ids_by_type),
    ];
}


function get_posts_with_term_from_list($term_id, $post_ids) {
    global $wpdb;
    $post_ids_string = implode(',', array_map('intval', $post_ids));
    $term_id_int = (int)$term_id;
    $query = "SELECT object_id 
              FROM {$wpdb->term_relationships} 
              WHERE term_taxonomy_id = {$term_id_int} 
              AND object_id IN ({$post_ids_string})";
    $results = $wpdb->get_col($query);
    return array_map('intval', $results);
}
function define_initial_individual_contexts() {
    //migration or initial setup you see if nothing is found these contexts all have their type their name and its a setup it has old variants but is really called once ever look its true
    $contexts = [
          'locations' => [
            'context_type' => 'connector', 
            'context_name' => 'locations',
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
            'context_name' => 'service_areas', 
            'fields' => [
                'enabled' => ['value' => get_option('service_areas', get_option('service_areas_enabled', "0"))],
                'post_type' => ['value' => get_option('service_areas_post_type', get_option('main_area_post_type', ''))],
                'taxonomy' => ['value' => get_option('service_areas_connector_tax', get_option('area_connector_tax', ''))],
                'schema' => ['value' => get_option('main_area_schema', 'Service')],
            ]
        ],
        'main_service' => [
            'context_type' => 'type',
            'context_name' => 'main_service',
            'fields' => [
                'enabled' => ['value' => get_option('main_service_enabled', get_option('main_service', "1"))],
                'schema' => ['value' => get_option('main_service_schema', 'Service')],
                'post_type' => ['value' => get_option('main_service_post_type', get_option('main_service_post_type', ''))],
                'taxonomy' => ['value' => get_option('main_service_type_taxonomy', get_option('main_type_tax', ''))]
            ]
        ],
        'second_service' => [
            'context_type' => 'type',
            'context_name' => 'second_service',
            'fields' => [
                'enabled' => ['value' => get_option('second_service_type', "0")],
                'schema' => ['value' => get_option('second_service_schema', 'Service')],
                'post_type' => ['value' => get_option('second_service_post_type', get_option('second_service_post_type', ''))],
                'taxonomy' => ['value' => get_option('second_service_type_taxonomy', get_option('second_type_tax', ''))]
            ]
        ],
        'jobs' => [
            'context_type' => 'type',
            'context_name' => 'jobs',
            'fields' => [
                'enabled' => ['value' => get_option('enable_jobs', "0")],
                'schema' => ['value' => get_option('job_posting_schema', 'JobPosting')],
                'post_type' => ['value' => get_option('jobs_post_type', get_option('job_post_type', ''))],
                'taxonomy' => ['value' => get_option('job_type_taxonomy', get_option('job_type_tax', ''))]
            ]
        ],
        'employee' => [
            'context_type' => 'unique',
            'context_name' => 'employee',
            'fields' => [
                'enabled' => ['value' => "0"],
                'schema' => ['value' => 'Person'],
                'post_type' => ['value' => '']
            ]
        ]
    ];

    return $contexts;
}
class AwesomeServicesRelationship {
    private static $page_taxonomy_slugs = [];
    private static $location_term_options = [];
    private static $location_taxonomy_name ='';
    private static $post_type_options = []; 
    private static $taxonomies_associated_with_page=[];
    public static function awesome_get_post_types() {
        global $wp_post_types;
        $post_type_options = [];

        foreach ($wp_post_types as $slug => $pt_obj) {
            if (!empty($pt_obj->public) && !empty($pt_obj->show_ui) && empty($pt_obj->_builtin) && $pt_obj->rewrite !== false) {
                $post_type_options[$slug] = $pt_obj->label;
            }
        }
        self::$post_type_options = $post_type_options; 
        return self::$post_type_options;
    }
public static function get_all_page_taxonomy_slugs() {
    global $wp_post_types;
    $list_from_post_type = $wp_post_types['page']->taxonomies;
    $list_from_taxonomies = [];
    global $wp_taxonomies;
    foreach ($wp_taxonomies as $taxonomy_slug => $taxonomy_obj) {
        if (in_array('page', $taxonomy_obj->object_type, true)) {
            $list_from_taxonomies[] = $taxonomy_slug;
        }
    }
    $merged_list = array_merge($list_from_post_type, $list_from_taxonomies);
    $taxonomies_associated_with_page = array_unique($merged_list);
    self::$taxonomies_associated_with_page = array_values($taxonomies_associated_with_page);
    return self::$taxonomies_associated_with_page;
}

public static function assemble_fields($context_name, $context_type, $field_keys = []) {
    error_log('public static function assemble fields relationships settings page here');
    $fields = [];

        $schema_options = initialize_schema_options();
        $post_type_options = self::$post_type_options;
        
//********All contexts get these first three fields ***/
        $fields['enabled'] = ['type' => 'toggle', 'label' => "{$context_name} Enabled", 'value' => $field_keys['enabled'] ?? "1"];
        $fields['schema'] = ['type' => 'select', 'options' => $schema_options, 'value' => $field_keys['schema'] ?? ""];
        $fields['post_type'] = ['type' => 'select', 'options' => $post_type_options, 'value' => $field_keys['post_type'] ?? ""];
//*****This feature super important, unique doesn't get it keep up high for order***//
        if ($context_type !== 'unique') {
            $taxonomy_options = awesome_get_taxonomies_for_post_type($fields['post_type']['value']);
            $fields['taxonomy'] = ['type' => 'select', 'options' => $taxonomy_options, 'value' => $field_keys['taxonomy'] ?? ''];
        }
//***This feature is meant to represent a location that also might be the same as the organization only found on location a connector type***//
        if ($context_name === 'locations') {
            $term_options = getTermOptions($fields['taxonomy']['value']);
            self::$location_taxonomy_name = $fields['taxonomy']['value'];
            self::$location_term_options = $term_options;
            $fields['main_term'] = ['type' => 'select', 'options' => $term_options, 'value' => $field_keys['main_term'] ?? ''];
            $fields['ignore_main_term'] = ['type' => 'toggle', 'value'=> $field_keys['ignore_main_term'] ?? "0"];
        }

//***The following features are for all contexts***///
        $fields['dibraco_banner'] = ['type' => 'toggle', 'label' => 'Banner', 'value' => $field_keys['dibraco_banner'] ?? "1"];
        $fields['main_sections'] = ['type' => 'toggle', 'label' => 'Main', 'value' => $field_keys['main_sections'] ?? "1"];
         if ($context_type ==="connector"){
            $fields['about_section'] = ['type' => 'toggle', 'label' => 'About Fields', 'value' => $field_keys['about_section'] ?? "1"];
            $fields['commercial_section'] = ['type' => 'toggle', 'label' => 'Commercial Fields', 'value' => $field_keys['commercial_section'] ?? "1"];
        }
        $fields['contact_section'] = ['type' => 'toggle', 'label' => 'Contact', 'value' => $field_keys['contact_section'] ?? "1"];
        $fields['portrait_images'] = ['type' => 'toggle', 'label' => 'Portrait Images', 'value' => $field_keys['portrait_images'] ?? "0"];
        $fields['landscape_images'] = ['type' => 'toggle', 'label' => 'Landscape Images', 'value' => $field_keys['landscape_images'] ?? $field_keys['repeaters']];
        if ($context_type === 'type') {
            $fields['post_per_term'] = ['type' => 'toggle', 'label' => '1 Post Per Term', 'value' => $field_keys['post_per_term'] ?? "0"];
            if ($field_keys['post_per_term'] ==="1"){
                $is_attached_to_page = (in_array($field_keys['taxonomy'], self::$taxonomies_associated_with_page));
                if(in_array($fields['post_type']['value'],  get_taxonomy(self::$location_taxonomy_name)->object_type)){
                    $fields['locations_main_term'] = ['type' => 'select', 'label' =>'Context Main Loc Term?', 'options' => self::$location_term_options, 'value' => $field_keys['locations_main_term'] ?? ''];
                }
                $fields['term_icon'] = ['type' => 'toggle', 'label' => 'Term Icon', 'value' => $field_keys['term_icon'] ?? "0"];
                $fields['before_after'] = ['type' => 'toggle', 'label' => 'Bef Aft', 'value' => $field_keys['before_after'] ?? "0"];
                if ($is_attached_to_page){
                    $fields['pages_represent'] = ['type' => 'toggle', 'label' => 'Pages as Main?', 'value' => $field_keys['pages_represent'] ?? "0"];
                    }
                }
            }
        if ($context_type === 'type' || $context_name === 'employee' || $context_name === 'locations') {
            $fields['has_certification'] = ['type' => 'toggle', 'label' => 'Certification', 'value' => $field_keys['has_certification'] ?? "0"];
        }
        if (isset($field_keys['remove'])) {
            $fields['remove'] = ['type' => 'button', 'class' => 'remove-context-button button-secondary'];
        }
        return $fields;
    }
}

function awesome_get_taxonomies_for_post_type($post_type) {
$taxonomy_options = [];
global $wp_taxonomies;
    foreach ($wp_taxonomies as $taxonomy_slug => $taxonomy_obj) {
        if (in_array($post_type, $taxonomy_obj->object_type)) {
            $taxonomy_options[$taxonomy_slug] = $taxonomy_obj->label;
        }
    }
    return $taxonomy_options;
}
add_action('wp_ajax_awesome_get_taxonomies_for_post_type', function() {
    wp_send_json_success(awesome_get_taxonomies_for_post_type($_POST['slug']));
    //I dontknow why we need this apparently we do
});
function getTermOptions($taxonomy) {
    $options = [];
    if ($taxonomy ===''){ return $options;}
    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    foreach ($terms as $term) {
        $options[$term->term_id] = $term->name;
    }
    return $options;
}
function handle_getTermObjects() {
    wp_send_json_success(getTermOptions($_POST['slug']));
    //I dont know why we need this when it only pertains to what is selected as the locations taxonomy in post this is selected in locations taxonomy
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

add_action('wp_ajax_build_individual_context', function() {
    $context_name = $_POST['new_context_name'];
    $context_type = $_POST['new_context_type'];
    $context_name = strtolower(str_replace(' ', '_', $context_name));
    $contexts = get_option('contexts');
    if (isset($contexts[$context_name])) {
        wp_send_json_error(['message' => 'Context already exists.']);
    }
    $field_keys = ['remove' => []];
    $new_fields = AwesomeServicesRelationship::assemble_fields($context_name, $context_type, $field_keys);
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

function render_relationships_settings_page() {
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce($_POST['relationships_settings_nonce'], 'save_relationships_settings')) {
        handle_save_relationships_settings(); 
    }
    if (isset($_GET['messages'])) {
        $message = urldecode($_GET['message']); 
        echo "<div class ='notice notice-error is-dismissible'><strong>{$message}</strong></div>";
    }
    if (empty(AwesomeServicesRelationship::$post_type_options)) {
        AwesomeServicesRelationship::awesome_get_post_types(); 
    }
    $contexts = get_option('contexts', []);
    if(!empty($contexts)){
     $contexts = ['locations' => $contexts['locations']] + $contexts;   
    }
    if (empty($contexts)) {
        $contexts = define_initial_individual_contexts();
        update_option('contexts', $contexts);
    }
    $enabledtogglesubfields = [];
    $contexts_config = array_fill_keys(['connector', 'type', 'unique'], ['type' => 'visual_section', 'fields' => []]);
    foreach ($contexts as $context_name => $context_data) {
        $context_type    = $context_data['context_type'];
        $field_keys = $context_data['fields'];
        $contexts_config[$context_type]['fields'][$context_name] = [
            'type' => 'group',
             'label' => ucwords(str_replace(['_', '-'], ' ', $context_name)),  
             'fields' =>[],
             'condition' => ['field' => "{$context_name}_enabled", 'values' => ["1"], 'current_value' => '']
            ];
          $field_keys = AwesomeServicesRelationship::assemble_fields($context_name, $context_type, $field_keys);
     $filtered_fields = [];
        
      foreach ($field_keys as $field_key => $field_data) {
            if ($field_key === 'remove') {
            $filtered_fields[$field_key] = $field_data;
        } else {
            $filtered_fields[$field_key] = $field_data['value'];
        }
           if ($field_key === 'enabled') {
              $enabledtogglesubfields["{$context_name}_{$field_key}"] = $field_data;
              $contexts_config[$context_type]['fields'][$context_name]['condition']['current_value'] = $field_data['value'];

            } else {
              $contexts_config[$context_type]['fields'][$context_name]['fields'][$field_key] = $field_data;
            }
        }
        $contexts[$context_name] = ['context_name' => $context_name, 'context_type' => $context_type, 'fields' => $filtered_fields];
    }
        update_option('contexts', $contexts);

$enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', "0");
$enabledtogglesubfields['enable_custom_fields_for_pages'] = ['type'  => 'toggle', 'value' => $enable_custom_fields_for_pages_value,];

    ?>    
        <div class="wrap">
        <h1>Dibraco Relationship Config Settings</h1>
        <div class="toggles-sidebar">
            <button type="button" id="add-context-btn" class="button field_group-secondary">Add Context</button>
                <div id="add-context-section" style="display: none;">
                    <?= FormHelper::generateTextInput('new_context_name', 'Context Name:', null); ?>
                    <?= FormHelper::generateRadioFieldset('new_context_type', 'Context Type:', 'type', ['unique' => 'Unique', 'type' => 'Type']); ?>
                    <button type="button" id="confirm-add-context" class="button button-secondary">Confirm</button></div>
                <form id='relationships-settings' method="post" action="<?= admin_url('admin.php?page=dibraco-relationships'); ?>">
                <?= wp_nonce_field('save_relationships_settings', 'relationships_settings_nonce'); ?>
                <?= FormHelper::generateVisualFieldGroup('toggles_section', ['label'  => 'Context On/Off', 'fields' => $enabledtogglesubfields]); ?>
                <?= FormHelper::generateVisualSection('contexts', ['fields' => $contexts_config]); ?></div>
       <button type="submit" class="button button-primary">Submit</button>
    </form></div>
    <?php
}

function handle_save_relationships_settings() {
    $contexts = get_option('contexts');
    $enabled_context_names = [];
    $enabled_contexts = [];
     foreach ($contexts as $context_name => $context_data) {
        $enabled_value = $_POST["{$context_name}_enabled"];
        $contexts[$context_name]['fields']['enabled'] = $enabled_value;
            if ($enabled_value === "1") {
                $post_type_field_name = "{$context_name}_post_type";
                $taxonomy_field_name = "{$context_name}_taxonomy";
                if ((empty($_POST[$post_type_field_name])) || ((isset($_POST[$taxonomy_field_name])) && (empty($_POST[$taxonomy_field_name])))) {
                    $message = "Context {$context_name} did not have a set post type or taxonomy";
                    wp_redirect(add_query_arg(['status' => 'error', 'message' => urlencode($message)], admin_url('admin.php?page=dibraco-relationships'))); 
                    exit;
                }
            }
        if ($enabled_value === "1") {
            $enabled_context_names[$context_name] = $context_name;
            $enabled_contexts[$context_name] = [
                'context_name' => $context_name,
                'context_type' => $context_data['context_type'],
            ];

            foreach ($context_data['fields'] as $field_key => $field_data) {
                $field_value = $_POST["{$context_name}_{$field_key}"];
                if ($field_key === 'remove') continue;
                if ($field_key === 'enabled') {
                $contexts[$context_name]['fields']['enabled'] = "1";
                 continue;
                }
                /*
                 if ($field_key ==='post_type'){
                   $enabled_contexts[$context_name]['permalink_data']= dibraco_get_context_permalink_data($field_value);
                }
                */
                $contexts[$context_name]['fields'][$field_key] = $field_value;
                $enabled_contexts[$context_name][$field_key] = $field_value;
            }
        } elseif($enabled_value ==='0') {
            foreach ($context_data['fields'] as $field_key => $field_data) {
                if ($field_key === 'remove') {
                    $contexts[$context_name]['fields']['remove'] = $field_data;
                    continue;
                }
                if ($field_key === 'enabled') {
                    $contexts[$context_name]['fields']['enabled'] = "0";
                } else {
                    $contexts[$context_name]['fields'][$field_key] = '';
                }
            }
        }
    }
    update_option('contexts', $contexts);
/*
This is where we handoff contexts to enabled contexts and subtypes after this we never touch contexts again, except in one settings page. Period.
*/
    $locations_enabled = false;
    $service_areas_enabled = false;
/*
This is where we find out what relatiosnhips exist between post types and taxonomies we already know that context specific post types and taxonmoies are related to each other
*/
    foreach ($enabled_contexts as $connector_context_name => $enabled_connector) {
        if ($enabled_connector['context_type'] !== 'connector') {
            continue;
            }
            $connector_post_type = $enabled_connector['post_type'];
            $connector_taxonomy = $enabled_connector['taxonomy'];
            $connector_schema = $enabled_connector['schema'];
            if ($connector_context_name === 'locations') {
                $locations_enabled = true;
                if (!empty($enabled_connector['main_term'])) {
                    $main_location_term_id = (int)$enabled_connector['main_term'];
                } else {
                    $main_location_term_id ='';
                }
            } elseif ($connector_context_name === 'service_areas') {
                $service_areas_enabled = true;
            }
            $enabled_contexts[$connector_context_name]['related_type_contexts'] = [];
            $enabled_contexts[$connector_context_name]['related_unique_contexts'] = [];
            //** this is the inner foreach loop where we determine the related type and unique contexts for each connector
            foreach ($enabled_contexts as $other_name => $other_data) {
                $other_type = $other_data['context_type'];
                if ($other_type === 'connector') {
                    continue;           
                }
                if (!isset($enabled_contexts[$other_name]['related_connectors'])) {
                    $enabled_contexts[$other_name]['related_connectors'] = [];   
                }
                $other_post_type = $other_data['post_type'];
                    $schema = $other_data['schema'];
                    $matches = in_array($connector_taxonomy, get_object_taxonomies($other_post_type, 'names'), true) || in_array($other_post_type, get_taxonomy($connector_taxonomy)->object_type, true);
                    if (!$matches) { continue;}
   		            if ($other_type === 'type') {
                         $is_attached_to_page = (in_array($other_data['taxonomy'], AwesomeServicesRelationship::get_all_page_taxonomy_slugs()));
                        $enabled_contexts[$connector_context_name]['related_type_contexts'][$other_name] = [
                        'type_name'=> $other_name,
                        'schema' =>  $schema,
                        'post_type' => $other_post_type,
                        'taxonomy' => $other_data['taxonomy'],
                        'post_per_term' => $other_data['post_per_term'],
                        ];
                        if($other_data['post_per_term'] ==="1" && $is_attached_to_page){
                            $enabled_contexts[$connector_context_name]['related_type_contexts'][$other_name]['pages_represent'] = $other_data['pages_represent'] ?? '0';
                        }
                 } elseif ($other_type === 'unique') {  
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
            if ($connector_context_name === 'locations') {
                 $enabled_contexts[$other_name]['related_connectors'][$connector_context_name]['main_term'] = $main_location_term_id;  
                 }
        } //This is the end of the inner foreach where we are determinging relationships for each of the connectors to type and uniques
    }  
 //** this is the end of the foreach loop above this all relationships are determined above this**//
    foreach ($enabled_contexts as $context_name => $context_data) {
        if ($context_data['context_type'] !== 'connector') {
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
$new_status_name = 'none';
if ($locations_enabled === true && $service_areas_enabled === true) {
    $status = 'both';
} elseif ($locations_enabled === true && $service_areas_enabled === false) {
    $status = 'multi_locations';
} elseif ($locations_enabled === false && $service_areas_enabled === true) {   
    $status = 'multi_areas';
}
update_option('locations_areas_status', $status);

if($locations_enabled){
    $locations_connector_tax = $enabled_contexts['locations']['taxonomy'];
    $locations_context_post_type = $enabled_contexts['locations']['post_type'];
    $all_locations_post_types_post_ids = get_posts(['post_type'=> $locations_context_post_type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
    foreach ($all_locations_post_types_post_ids as $location_post_id){
        $loc_term_id = dibraco_get_current_term_id_for_post($location_post_id, $locations_connector_tax);
        if (!empty($loc_term_id)){
            update_term_meta($loc_term_id, 'location_post_id', $location_post_id);
             update_term_meta($loc_term_id, 'location_link_url', get_permalink($location_post_id));
        }
    $all_location_term_ids = get_terms(['taxonomy' => $locations_connector_tax,'hide_empty' => false, 'fields' => 'ids']);
     foreach ($all_location_term_ids as $loc_term_id){
            $post_meta_on_term = get_term_meta($loc_term_id, 'location_link_url', true);     
            update_term_meta($loc_term_id, 'location_link_url', $post_meta_on_term);
                if(empty($post_meta_on_term)){
                 update_term_meta($loc_term_id, 'location_post_id', '');
                 }
            }
        }
    }
if($service_areas_enabled){
 $service_areas_connector_tax = $enabled_contexts['service_areas']['taxonomy'];
    $service_area_context_post_type = $enabled_contexts['service_areas']['post_type'];
    $service_area_post_types_post_ids = get_posts(['post_type'=> $service_area_context_post_type, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids']);
    foreach ($service_area_post_types_post_ids as $service_area_ctx_post_id){
        $area_term_id = dibraco_get_current_term_id_for_post($service_area_ctx_post_id, $service_areas_connector_tax);
        if (!empty($area_term_id)){
            update_term_meta($area_term_id, 'service_area_post_id', $service_area_ctx_post_id);
             update_term_meta($area_term_id, 'service_area_link_url', get_permalink($service_area_ctx_post_id));
        }
    $all_area_term_ids = get_terms(['taxonomy' => $service_areas_connector_tax,'hide_empty' => false, 'fields' => 'id=>name']);
    foreach ($all_area_term_ids_with_names as $area_term_id => $city_name) {
        $post_meta_on_term = get_term_meta($area_term_id, 'service_area_link_url', true);     
             update_term_meta($area_term_id, 'service_area_link_url', $post_meta_on_term);
          if (empty($post_meta_on_term)){
               update_term_meta($area_term_id, 'service_area_post_id', '');
            }
             update_term_meta($area_term_id, 'city', $city_name);
            }
        }
    }
//THis whole section here is for type contexts to set up a fallback post if and only if they are post per term ===1
foreach ($enabled_contexts as $enabled_type_context => $context_data) {
    if (($context_data['context_type'] !== 'type') || $context_data['post_per_term'] !== "1") {
        continue;
    }
    $related_connector_count = $context_data['related_connector_count'];
    $related_connectors = $context_data['related_connectors'];
    $type_context_name = $context_data['context_name'];
    $type_context_meta_key_for_connector_term = "related_type_{$type_context_name}";
    $main_post_map_option_name = "{$type_context_name}_main_posts"; 
    $type_taxonomy = $context_data['taxonomy'];
    $type_post_type = $context_data['post_type'];
    $pages_represent = $context_data['pages_represent'] ?? '0';
    if($pages_represent === '1'){
        $type_post_type = [$type_post_type, 'page'];
    }
    $result = get_type_taxonomy_term_ids_and_post_ids($type_taxonomy, $type_post_type);
    $type_term_ids = $result['term_ids'];
    $type_post_ids = $result['post_ids'];
    $post_ids_by_type = $result['post_ids_by_type'];
    $locations_main_term_id_for_this_context=$context_data['locations_main_term'];
    if ((!empty($related_connectors['service_areas']))){
        $connector_taxonomy = $related_connectors['service_areas']['taxonomy'];
        $connector_term_ids = get_terms(['taxonomy' => $connector_taxonomy, 'fields' => 'ids']);
        $service_area_term_ids_with_type_post_ids = post_ids_with_term_id_from_single_taxonomy($connector_term_ids, $type_post_ids);
        foreach($service_area_term_ids_with_type_post_ids as $connector_term_id => $type_post_type_ids){
            $connector_term_id=(int)$connector_term_id;
            $service_area_term_name =get_term($connector_term_id)->name;  
            $entries = []; 
           foreach($type_post_type_ids as $post_id){
                $type_term_id= dibraco_get_current_term_id_for_post($post_id, $type_taxonomy);
                $type_term_name = get_term($type_term_id)->name;
                $entries[$type_term_id] = [
                    'related_post_title' => "$type_term_name in $service_area_term_name",
                    'related_post_id' => (int)$post_id,
                    'related_post_url' => get_permalink($post_id),
                    'fallback_url'=>'',
                    ];
                    }
                update_term_meta($connector_term_id, $type_context_meta_key_for_connector_term, $entries); 
                }
            }
        if ((!empty($related_connectors['locations']))){
            $location_taxonomy = $related_connectors['locations']['taxonomy'];
            $connector_term_ids = get_terms(['taxonomy' => $location_taxonomy, 'fields' => 'ids']);
            if ($related_connector_count ===2){
                $location_type_post_ids = array_diff($type_post_ids, array_merge(...array_values($service_area_term_ids_with_type_post_ids)));
                $location_term_ids_with_type_post_ids = post_ids_with_term_id_from_single_taxonomy($connector_term_ids, $location_type_post_ids);
            } elseif ($related_connector_count ===1){
                $location_term_ids_with_type_post_ids = post_ids_with_term_id_from_single_taxonomy($connector_term_ids, $type_post_ids);
            }
            foreach($location_term_ids_with_type_post_ids as $connector_term_id => $type_post_type_ids){
                $connector_term_id=(int)$connector_term_id;
                $location_term_name =get_term($connector_term_id)->name;
               $entries = []; 
                foreach($type_post_type_ids as $post_id){
                $type_term_id= dibraco_get_current_term_id_for_post($post_id, $type_taxonomy);
                $type_term_name = get_term($type_term_id)->name;
                $entries[$type_term_id] = [
                    'related_post_title' => "$type_term_name in $location_term_name",
                    'related_post_id' => (int)$post_id,
                    'related_post_url' => get_permalink($post_id),
                    'fallback_url'=>'',
                    ];
                    }
                update_term_meta($connector_term_id, $type_context_meta_key_for_connector_term, $entries); 
                }
            }
       $main_post_map =[];
       $unmatched_type_term_ids = $type_term_ids;
       if ($locations_main_term_id_for_this_context !==''){
            $type_posts_with_main_location_id = get_posts_with_term_from_list($locations_main_term_id_for_this_context, $type_post_ids);
        foreach ($type_posts_with_main_location_id as $type_post_id){
          $type_term_id = dibraco_get_current_term_id_for_post($type_post_id, $type_taxonomy);
          $main_post_map[$type_term_id] = $type_post_id; 
            $unmatched_type_term_ids = array_diff($unmatched_type_term_ids, [$type_term_id]); 
          update_term_meta($type_term_id, 'main_post_for_term', $type_post_id);
        }
       }
    if ($pages_represent === "1") {
            $page_post_ids = $post_ids_by_type['page'];                         
                foreach($page_post_ids as $post_id){
                    $type_term_id = dibraco_get_current_term_id_for_post($post_id, $type_taxonomy);
                    $main_post_map[$type_term_id] = $post_id; 
                    update_term_meta($type_term_id, 'main_post_for_term', $post_id);
                }
                update_option($main_post_map_option_name, $main_post_map);
               continue;
    } elseif ($pages_represent ==='0'){
        if(!empty($related_connectors['locations'])){
            $location_taxonomy = $related_connectors['locations']['taxonomy'];
            $location_term_ids = get_terms(['taxonomy' => $location_taxonomy, 'fields' => 'ids']);
            if ($locations_main_term_id_for_this_context !==''){
                $location_term_ids = array_diff($location_term_ids, [$locations_main_term_id_for_this_context]);
            }
            $type_post_ids_with_location_id = taxonomy_term_id_one_post_per_term($location_term_ids, $type_post_ids);
            foreach ($type_post_ids_with_location_id as $connector_term_id => $type_post_id){
                $type_term_id = dibraco_get_current_term_id_for_post($type_post_id, $type_taxonomy);
                $main_post_map[$type_term_id] = $type_post_id;
                $unmatched_type_term_ids = array_diff($unmatched_type_term_ids, [$type_term_id]); 
                update_term_meta($type_term_id, 'main_post_for_term', $type_post_id);
                }
            }
        if (!empty($unmatched_type_term_ids)) {
            $type_term_id_with_one_type_post_id = taxonomy_term_id_one_post_per_term($unmatched_type_term_ids, $type_post_ids);
            foreach($type_term_id_with_one_type_post_id as $type_term_id => $main_post_id){
                $main_post_map[$type_term_id] = $main_post_id;
                update_term_meta($type_term_id, 'main_post_for_term', $main_post_id);
                     }
                }
            }
            update_option($main_post_map_option_name, $main_post_map);
        setup_fallbacks_for_conncetors_from_main_post_map($main_post_map, $type_context_meta_key_for_connector_term, $related_connector_count, $related_connectors);
    }

foreach ($enabled_contexts as $enabled_connector_contexts => $connector_context_data) {
    if ($connector_context_data['context_type'] !== 'connector') {
        continue;
    }
    $connector_post_type = $connector_context_data['post_type'];
    $connector_taxonomy = $connector_context_data['taxonomy'];
    $connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
    $related_unique_contexts = $connector_context_data['related_unique_contexts'];
    $allowed_unique_names=[];
    if (!empty($related_unique_contexts)) {
        foreach ($related_unique_contexts as $unique_context_name => $unique_context_data) {
            $allowed_unique_names[]=$unique_context_name;
            $unique_post_type = $unique_context_data['post_type'];
            setup_connected_unique_posts($connector_taxonomy, $connector_terms, $unique_post_type, $unique_context_name);
        }
    }
    $related_type_contexts = $connector_context_data['related_type_contexts'];
    $allowed_type_names =[];
    if (!empty($related_type_contexts)) {
        foreach ($related_type_contexts as $type_context => $type_context_data) {
            $type_context_name = $type_context_data['type_name'];
            $allowed_type_names[]=$type_context_name;
            $post_per_term = $type_context_data['post_per_term'];
            if ($post_per_term ==="1"){continue;}
                $type_post_type = $type_context_data['post_type'];
                $type_taxonomy = $type_context_data['taxonomy'];
                $related_type_meta_key = "related_type_{$type_context_name}";
                $related_connector_count = $type_context_data['related_connector_count'];
                 setup_connected_type_posts_no_fallback($connector_taxonomy, $connector_terms, $type_post_type, $type_taxonomy, $related_type_meta_key);
                }
            }
    foreach($connector_terms as $connector_term_id => $connector_name){
        $all_values = get_term_meta($connector_term_id, '', true);
        $all_values = array_map('maybe_unserialize', array_map('current', $all_values));
        $allowed_names = array_merge($allowed_unique_names, $allowed_type_names);
        if (!empty($allowed_names)){
        foreach ($all_values as $meta_key => $value){
           if (str_starts_with($meta_key, 'related_type_') || str_starts_with($meta_key, 'related_unique_')) {
                $suffix = str_replace(['related_type_', 'related_unique_'], '', $meta_key);
                if (!in_array($suffix, $allowed_names, true)) {
                    delete_term_meta($connector_term_id, $meta_key);
                    }
                }
            }
        }   
    }
}
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


function get_type_taxonomy_terms($type_taxonomy, $type_post_type) {
    $type_post_ids = get_posts(['post_type' => $type_post_type, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'publish', 'tax_query' => [
     ['taxonomy' => $type_taxonomy, 'operator' => 'EXISTS']]]); 
    $type_term_ids = wp_get_object_terms($type_post_ids, $type_taxonomy, ['fields' => 'ids']);
    return ['type_term_ids' => $type_term_ids, 'type_post_ids' => $type_post_ids];
}
function get_post_type_post_ids_with_main_term($type_post_type, $location_taxonomy, $main_location_term_id){
return  get_posts(['post_type' => $type_post_type, 'post_status' => 'publish', 'fields' => 'ids', 
    'posts_per_page' => -1, 'tax_query' => [
        ['taxonomy' => $location_taxonomy, 'field' => 'term_id', 'terms' => $main_location_term_id]
    ]]);
}
function validate_main_post_map($main_post_map, $type_term_ids, $type_taxonomy, $main_post_ids){ 
    $new_type_term_ids = [];
    foreach ($main_post_map as $type_term_id => $post_id) {
        $type_term_id = (int)$type_term_id;
        $post_id = (int)$post_id;
        if (!in_array($type_term_id, $type_term_ids, true)) {
            unset($main_post_map[$type_term_id]);
            $new_type_term_ids[] = $type_term_id;
            continue;
        }
        $post_term_id = dibraco_get_current_term_id_for_post($post_id, $type_taxonomy);
        if ($type_term_id !== $post_term_id || !in_array($post_id, $main_post_ids, true)){
            unset($main_post_map[$type_term_id]);
            delete_term_meta($type_term_id, 'main_post_for_term');
            $new_type_term_ids[] = $type_term_id;
            continue;
        } 
		unset($main_post_ids[$post_id]);
        update_term_meta($type_term_id, 'main_post_for_term', $post_id);
    }  
    return ['type_term_ids' => $new_type_term_ids, 'main_post_ids'=> $main_post_ids, 'main_post_map' => $main_post_map];
}


function dibraco_get_related_post_ids($post_type, $tax_query, $posts_per_page = -1, $fields = 'ids', $exclude = []) {
    $query_args = ['post_type' => $post_type,'posts_per_page' => $posts_per_page,'tax_query' => $tax_query,'post_status' => 'publish','fields' => $fields ];
    if (!empty($exclude)) {
        $query_args['exclude'] = $exclude;
    }
    return get_posts($query_args);
}

function setup_connected_unique_posts($connector_taxonomy, $connector_terms, $unique_post_type, $unique_context_name) {
    $related_unique_meta_key = "related_unique_{$unique_context_name}";
    foreach ($connector_terms as $connector_id => $connector_term_name) {
        $connector_id = (int)$connector_id;
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


function setup_fallbacks_for_conncetors_from_main_post_map($main_post_map, $type_meta_key, $related_connector_count, $related_connectors){
  
    foreach ($related_connectors as $related_connector => $connector_data){
        if($related_connector_count ===2 && $connector_data['connector_name'] ==='service_areas'){ continue;}
          $connector_taxonomy = $connector_data['taxonomy']; 
          $connector_term_ids = get_terms(['taxonomy' => $connector_taxonomy, 'fields' => 'ids']);
            foreach ($connector_term_ids as $connector_term_id){
                $existing_entry_data = get_term_meta($connector_term_id, $type_meta_key, true);
                foreach ($main_post_map as $type_term_id => $post_id) {
                    $post_permalink = get_permalink($post_id);
                    if(isset($existing_entry_data[$type_term_id])){
                        $existing_entry_data[$type_term_id]['fallback_url']=$post_permalink;
                    } elseif (!isset($existing_entry_data[$type_term_id])){
                        $type_term_name = get_term($type_term_id)->name;
                        $connector_term_name = get_term($connector_term_id)->name;
                        $existing_entry_data[$type_term_id] = [
                            'related_post_title' => "$type_term_name in $connector_term_name",
                            'related_post_id' => '',
                            'related_post_url' => '',
                            'fallback_url'=>$post_permalink
                            ];
                        }
                    }
                update_term_meta($connector_term_id, $type_meta_key, $existing_entry_data);
                if ($related_connector_count ===2){
                 $associated_service_area_term_ids = get_term_meta($connector_term_id, 'associated_act_terms', true);
                      if (!empty($associated_service_area_term_ids)) {
                       foreach ($associated_service_area_term_ids as $service_area_term_id) {
                         $service_area_term_id = (int)$service_area_term_id;
                         $service_area_existing_entry_data = get_term_meta($service_area_term_id, $type_meta_key, true);
                         foreach ($existing_entry_data as $type_term_id => $location_type_post_data) {
                            $fallback_url = $location_type_post_data['related_post_url'];
                            if(empty($fallback_url)){
                               $fallback_url = $location_type_post_data['fallback_url'];
                            }
                        if(isset($service_area_existing_entry_data[$type_term_id])){
                           $service_area_existing_entry_data[$type_term_id]['fallback_url']=$fallback_url;
                        } elseif (!isset($service_area_existing_entry_data[$type_term_id])) {
                            $type_term_name = get_term($type_term_id)->name;
                            $area_term_name = get_term($service_area_term_id)->name;
                            $service_area_existing_entry_data[$type_term_id] = [
                                'related_post_title' => "$type_term_name in $area_term_name",
                                'related_post_id' => '',
                                'related_post_url' => '',
                                'fallback_url'=>$fallback_url
                                ];
                            }
                         }
                       update_term_meta($service_area_term_id, $type_meta_key, $service_area_existing_entry_data);
                       }
                    }
                }
        }
    }
}

function setup_connected_type_posts_no_fallback($connector_taxonomy, $connector_terms, $type_post_type, $type_taxonomy, $related_type_meta_key) {
    $type_terms = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true, 'fields' => 'id=>name']);
    foreach ($connector_terms as $connector_id => $connector_term_name) {
        $connector_id = (int)$connector_id;
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

function dibraco_get_current_term_id_for_post($post_id,  $taxonomy) {
   $terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids', 'number' => 1]);
   $current_term_id = '';
   if (!empty($terms) && !is_wp_error($terms)) {
       $current_term_id = (int)$terms[0];
    }
    return $current_term_id;
}

function dibraco_enforce_one_connector_term_per_connector_post($post_id, $new_connector_term_id, $connector_taxonomy, $post_id_term_meta_key, $link_url_term_meta_key) {
    $old_connector_term_id = dibraco_get_current_term_id_for_post($post_id, $connector_taxonomy);
    $post_permalink = get_permalink($post_id);
    if ($old_connector_term_id !=='' && ($old_connector_term_id !== $new_connector_term_id)){
            update_term_meta($old_connector_term_id, $post_id_term_meta_key, '');
            update_term_meta($old_connector_term_id, $link_url_term_meta_key, '');
    }
    if ($new_connector_term_id !=='' && ($new_connector_term_id !== $old_connector_term_id)) {
        $existing_post_id = get_term_meta($new_connector_term_id, $post_id_term_meta_key, true);
        if ($existing_post_id !=='') {
           wp_set_post_terms((int)$existing_post_id, '', $connector_taxonomy, false);
        }
        $update_id_result = update_term_meta($new_connector_term_id, $post_id_term_meta_key, $post_id);
        $update_url_result = update_term_meta($new_connector_term_id, $link_url_term_meta_key, $post_permalink);
        wp_set_post_terms($post_id, $new_connector_term_id, $connector_taxonomy, false);
    }
}

function dibraco_verify_post_save_request($nonce_field_name, $nonce_action) {
    if (!isset($_POST[$nonce_field_name])) return false;
    if (!wp_verify_nonce($_POST[$nonce_field_name], $nonce_action)) return false;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
    if (!current_user_can('edit_posts')) return false;
    return true;
}

function setup_type_edit_screen($post_type, $taxonomies_for_edit_posts) {
    add_filter("manage_edit-{$post_type}_columns", function ($columns) use ($taxonomies_for_edit_posts) {
        foreach ($taxonomies_for_edit_posts as $context_name => $taxonomy) {
              $columns["taxonomy-{$taxonomy}"] = ucwords(str_replace(['-', '_'], ' ', $context_name));
              if ($context_name === 'jobs'){ $columns['job_expiration'] = 'Expires';};
        }
        return $columns;
    }, 99);
   add_filter("manage_{$post_type}_posts_custom_column", function($column_name, $post_id) {
    if ($column_name === 'job_expiration') {
        $job_meta = get_post_meta($post_id, '_job_meta', true);
        if ($job_meta['job_expires'] === '1' && !empty($job_meta['valid_through'])) {
            echo $job_meta['valid_through'];
        } else {
            echo '—';
        }
    }
}, 10, 2);
    add_filter("manage_edit-{$post_type}_sortable_columns", function ($sortable) use ($taxonomies_for_edit_posts) {
        foreach ($taxonomies_for_edit_posts as $context_name => $taxonomy) {
            $sortable["taxonomy-{$taxonomy}"] = "taxonomy-{$taxonomy}";
            if ($context_name === 'jobs'){ $sortable['job_expiration'] = 'job_expiration';}
        }
        return $sortable;
    }, 99);
    
    add_filter('quick_edit_show_taxonomy', function ($show, $tax) use ($taxonomies_for_edit_posts) {
        return in_array($tax, $taxonomies_for_edit_posts) ? false : $show;
    }, 99, 2);
    add_action('quick_edit_custom_box', function ($col) use ($taxonomies_for_edit_posts) {
        foreach ($taxonomies_for_edit_posts as $context_name => $taxonomy) {
            if ($context_name ==='jobs'){
            if($col === 'job_expiration'){
                echo FormHelper::generateField('valid_through', ['type' => 'date', 'label' => 'Expires']); break;
            }
            }
            if ($col === "taxonomy-{$taxonomy}") {
                $options = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
                echo FormHelper::generateRadioFieldsetWithIntegerValues("{$taxonomy}_term", $context_name, '', $options, []);
                break;
            }
        }
    }, 99, 2);
}
function setup_service_area_location_assignment($taxonomy) {
    $locations_taxonomy = get_option('enabled_contexts')['locations']['taxonomy'];
    $act_to_lct_assignments = get_option('act_to_lct_assignments', []);
    $location_terms = get_terms(['taxonomy' => $locations_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
    wp_enqueue_script('dibraco-quick-edit-term', AWESOME_SERVICES_URL . 'js/term-quickedit.js', ['jquery', 'inline-edit-tax'], null, true);
    add_filter("manage_edit-{$taxonomy}_columns", function ($columns) {
        $columns['area_parent_location_name'] = 'Parent Location';
        return $columns;
    }, 15);
    add_filter("manage_edit-{$taxonomy}_sortable_columns", function ($sortable) {
        $sortable['area_parent_location_name'] = 'area_parent_location_name';
        return $sortable;
    }, 15);
    add_filter("manage_{$taxonomy}_custom_column", function ($content, $column_name, $term_id) use ($act_to_lct_assignments) {
        if ($column_name === 'area_parent_location_name') {
            $location_term_id = $act_to_lct_assignments[$term_id] ?? '';
            $location_name = $location_term_id ? get_term($location_term_id)->name : 'none';
            return "<span>{$location_name}</span><div class='area_parent_location_term hidden' data-location-term-id='{$location_term_id}'></div>";
        }
    }, 20, 3);
    add_action('quick_edit_custom_box', function($column_name) use ($location_terms, $act_to_lct_assignments) {
        if ($column_name === 'area_parent_location_name') {
            echo FormHelper::generateRadioFieldsetWithIntegerValues("area_parent_location_term", 'Associated Location', '', $location_terms, []);
        }
    }, 99);
    add_action("edited_{$taxonomy}", 'save_location_connection_for_area_terms', 10);
}


add_action('current_screen', function ($current_screen) {
    $enabled_contexts = get_option('enabled_contexts');
    $current_screen_base = $current_screen->base;
    global $hook_suffix;
  error_log(print_r($current_screen, true));

        $allowed_screen_bases = ['post', 'edit', 'term', 'edit-tags'];
            $custom_fields_for_pages = get_option('enable_custom_fields_for_pages', '0');
                 if ($custom_fields_for_pages ==="1"){
                   add_meta_box('side_image_fields_page', 'Page Images', 'da_render_images_for_pages', 'page', 'side', 'default');
                   add_meta_box('section_fields_page', 'Post Fields', 'render_da_pages_metabox', 'page', 'normal', 'default');
                   add_action('save_post_page', 'dibraco_save_section_fields_page');
                }
        
        if (in_array($current_screen_base, $allowed_screen_bases)) {
            $all_post_types = array_column($enabled_contexts, 'post_type');
            $all_taxonomies = array_column($enabled_contexts, 'taxonomy');

           if ((in_array($current_screen->post_type, $all_post_types) || (in_array($current_screen->taxonomy, $all_taxonomies)))){
                   $post_type = $current_screen->post_type;
                   $taxonomy = $current_screen->taxonomy;
                wp_enqueue_style('common-fields-style', AWESOME_SERVICES_URL . 'css/da-common-fields.css');
                if ($hook_suffix !== 'edit-tags.php' && $hook_suffix !== 'edit.php'){
                     wp_enqueue_script('editor');
                    wp_enqueue_media();
                    wp_enqueue_script('wp-components');
                    wp_enqueue_script('wp-tinymce');
                    wp_enqueue_script('wplink');
                    wp_enqueue_script('wp-element');
                    wp_enqueue_script('wp-data');
                    wp_enqueue_script('wp-blocks');
                    wp_enqueue_script('jquery-ui-sortable');
                    wp_enqueue_script('media-views');
                    wp_enqueue_script('media-editor');
                    wp_enqueue_script('da-color-picker2', AWESOME_SERVICES_URL . 'js/da-color-picker2.js', ['jquery', 'wp-color-picker', 'jquery-ui-slider'], false, true);
                    wp_enqueue_script('da-repeater', AWESOME_SERVICES_URL . 'js/da-repeater.js', ['jquery'], false, true);
                     wp_enqueue_script('media-fields-images-terms-script', AWESOME_SERVICES_URL . 'js/da-media-fields-images-terms.js', ['jquery', 'editor', 'media-editor'], null, true);
                    wp_enqueue_script('da-conditional-fields', AWESOME_SERVICES_URL . 'js/da-conditional-fields.js', ['jquery'], false, true);
                }
                     if ($hook_suffix === 'post-new.php' || $hook_suffix === 'post.php'){
                        foreach ($all_taxonomies as $tax) {
                            if ($current_screen->is_block_editor()) {
                                wp_enqueue_script('lodash');
                                $panel_id = "taxonomy-panel-{$tax}";
                                $script = "wp.data.dispatch('core/editor').removeEditorPanel('{$panel_id}');";
                                wp_add_inline_script('wp-editor', $script);
                                 remove_meta_box("{$tax}div", $post_type, 'side');
                                 remove_meta_box("tagsdiv-{$tax}", $post_type, 'side');
                            } else {
                                remove_meta_box("{$tax}div", $post_type, 'side');
                                remove_meta_box("tagsdiv-{$tax}", $post_type, 'side');
                                }
                            }
                        add_meta_box("dibraco_render_side_meta_box", 'Taxonomies & images', 'dibraco_render_side_meta_box', $post_type, 'side', 'high', ['__block_editor_compatible_meta_box' => true]);
                        add_meta_box("dibraco_post_normal_meta_box", 'Post Fields', 'dibraco_render_normal_meta_box', $post_type, 'normal', 'default', ['__block_editor_compatible_meta_box' => true]  );
                        add_action("save_post_{$post_type}", 'dibraco_save_meta_box', 10, 3);
                    }
                }
             foreach ($enabled_contexts as $context => $context_data) {
                     $current_screen_post_type = $current_screen->post_type;
                     $current_screen_taxonomy = $current_screen->taxonomy;
                     $context_name  = $context_data['context_name'];
                     $context_type  = $context_data['context_type'];
                     $post_type = $context_data['post_type'];
                      $taxonomies_for_edit_posts = [];
                     if ($context_type !== 'unique') {
                       $taxonomy = $context_data['taxonomy'];
                       $taxonomies_for_edit_posts[$context_name] = $taxonomy;
                     }
                    if($context_type ==='unique' || $context_type ==='type'){
                        $related_connectors = $context_data['related_connectors'];
                        foreach ($related_connectors as $related_connector => $related_data) {
                            $related_taxonomy = $related_data['taxonomy'];
                            $related_connector_name = $related_data['connector_name'];
                            $taxonomies_for_edit_posts[$related_connector_name] = $related_taxonomy;
                           }
                    }
                    if ($hook_suffix === 'edit.php' && $current_screen_post_type === $post_type) {
                        wp_enqueue_script( 'dibraco-quick-edit', AWESOME_SERVICES_URL . 'js/da-quickedits.js', ['jquery', 'inline-edit-post'], null, true );
                        setup_type_edit_screen($post_type, $taxonomies_for_edit_posts);
                    }
                     if($context_name ==='jobs' && $current_screen_post_type === $post_type){
                        wp_enqueue_style('job-postings-style', AWESOME_SERVICES_URL . 'css/da-job-postings.css');
                        add_meta_box('job_meta_box', 'Jobs Custom Fields', 'display_job_meta_box', $post_type, 'normal', 'high');
                        add_action("save_post_{$post_type}", function($post_id) {
                        save_job_meta_box_data($post_id);
                         }, 10, 1);
                     }
           if ($context_type === 'connector'){
               if ($context_name ==='locations' && ($hook_suffix !== 'edit.php') && ($current_screen_post_type === $post_type || $current_screen_taxonomy === $taxonomy)){
                   wp_enqueue_style('locations-company-info-style', AWESOME_SERVICES_URL . 'css/locations_company_info.css');
                   wp_enqueue_script('awesome-hours-of-operation-script', AWESOME_SERVICES_URL . 'js/da-hours-operation.js', ['jquery'], false, true);
               }
               if (($hook_suffix === 'term.php') && $current_screen->taxonomy === $taxonomy){
                    error_log('context_name: ' . $context_name);
                    if($context_name ==='locations'){
                        add_action("{$taxonomy}_edit_form_fields", 'render_location_meta_box', 20, 2);
                        add_action("edited_{$taxonomy}", function($term_id) use ($taxonomy) {
                        handle_save_location_term_meta($term_id, $taxonomy);}, 25, 1);} 
                    if ($context_name ==='service_areas'){
                      add_action("{$taxonomy}_edit_form_fields", 'render_service_area_meta_box', 10, 2);
        
                        }
                }
               if (($hook_suffix ==='edit-tags.php') && $current_screen->taxonomy === $taxonomy){
                $related_type_contexts = $context_data['related_type_contexts'];
                         if ($context_name ==='service_areas')  {
                         add_action("saved_{$taxonomy}", function($term_id) use ($taxonomy) {
                        handle_save_service_area_term($term_id, $taxonomy);
                         }, 10, 1);} 
                add_action("created_{$taxonomy}", function($term_id) use($related_type_contexts, $taxonomy) {
                setup_related_type_terms_for_new_connector_term($term_id, $related_type_contexts, $context_name, $taxonomy);}, 11, 1);
                 if (get_option('locations_areas_status')==='both') { 
                     if($context_name ==='locations'){ 
                        add_action("{$taxonomy}_add_form_fields", function($taxonomy) {
                        render_area_connection_checkboxes_for_location_terms(null, $taxonomy);
                        }, 10, 1);}
                
                if ($context_name ==='service_areas')  {
                     setup_service_area_location_assignment($taxonomy);
                    add_action("{$taxonomy}_add_form_fields", function($taxonomy) {
                    render_locations_connection_radio_for_area_terms(null, $taxonomy);
                    }, 10, 1);
               
                        }
                    }    
               }
           }
           if ($context_type === 'type') {
                $post_per_term = $context_data['post_per_term'];
                if ($hook_suffix === 'term.php' && $_GET['taxonomy'] === $taxonomy) {
                      add_action("{$taxonomy}_edit_form_fields", function ($term) {
                        render_dibraco_type_term_fields($term->term_id);
                        });
                        add_action('edited_term', function ($term_id) {
                        save_dibraco_type_term_fields($term_id);
                        });
                    }
                if ($post_per_term !== '1' && $hook_suffix === 'edit-tags.php') {
                    add_action("created_{$taxonomy}", function ($term_id) use ($context_data) {
                        setup_brand_new_type_term_no_ppt($term_id, $context_data);
                    }, 10, 1);
                }
            }
        }

        error_log('Success! Running code on screen base: ' . $current_screen_base);
        }
    });




function setup_brand_new_type_term_no_ppt($new_type_term_id, $context){
$context_name = $context['context_name'];
$meta_key = "related_type_{$context_name}";
$related_connectors = $context['related_connectors'];
  foreach ($related_connectors as $related_connector_name => $related_data){
    $connector_taxonomy = $related_data['taxonomy'];
    $connector_terms = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'ids']);
        foreach($connector_terms as $connector_term_id){
            $existing_meta = get_term_meta($connector_term_id, $meta_key, true);
                $existing_meta[$new_type_term_id] = []; 
                update_term_meta($connector_term_id, $meta_key, $existing_meta);
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
  $type_term_id = (int)$type_term_id; 
  $post_id = get_the_id();
  if($related_connector_count == 1 ){
     foreach ($related_connectors as $related_connector_context => $related_connector_data) {
      $connector_taxonomy = $related_connector_data['taxonomy'];
      $connector_term_ids = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'ids']);
         foreach ($connector_term_ids as $connector_term_id) {
            $connector_term_id =(int)$connector_term_id;
            $existing_meta = get_term_meta($connector_term_id, $meta_key, true);
            $existing_meta[$type_term_id]['fallback_url'] = $post_permalink;
            update_term_meta($connector_term_id, $meta_key, $existing_meta);
         }
     } 
    return;     
  }
  elseif($related_connector_count == 2){
  foreach ($related_connectors as $related_connector_context => $related_connector_data) {
        $connector_taxonomy = $related_connector_data['taxonomy'];
        $related_connector_name = $related_connector_data['connector_name'];
        if ($related_connector_name === 'service_areas') {continue;}
        if ($related_connector_name === 'locations') {
            $connector_term_ids = get_terms(['taxonomy' => $connector_taxonomy, 'hide_empty' => false, 'fields' => 'ids']);
            foreach ($connector_term_ids as $connector_term_id) {
                $connector_term_id =(int)$connector_term_id;
                $existing_meta = get_term_meta($connector_term_id, $meta_key, true);
                $existing_meta[$type_term_id]['fallback_url'] = $post_permalink;
                if ($existing_meta[$type_term_id]['related_post_id'] === '') {
                    update_fallbacks_for_service_area_terms($connector_term_id, $meta_key, $type_term_id, $post_id);
                }
                update_term_meta($connector_term_id, $meta_key, $existing_meta);
                }
            }
        }
    return;
  }
}

function update_fallbacks_for_service_area_terms($new_loc_id, $meta_key, $type_term_id, $post_id){
    $fall_back_url = get_permalink($post_id);
    $act_ids = get_term_meta($new_loc_id, 'associated_act_terms', true);
    if (empty($act_ids)) {return;}
    foreach ($act_ids as $act_id) {
        $act_id = (int)$act_id;
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


function type_connector_clear_old_meta_not_touching_fallbacks($connector_term_id, $meta_key, $type_term_id, $post_id, $post_per_term){
 $connector_term_id;
    $old_meta = get_term_meta($connector_term_id, $meta_key, true);
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
        $current_meta[$type_term_id]['related_post_id'] = $post_id;
        $current_meta[$type_term_id]['related_post_url'] = get_permalink($post_id);
        } else {
            $current_meta[$type_term_id][$post_id] = [
            'related_post_title' => $related_post_title,
            'related_post_id'    => $post_id,
            'related_post_url'   => get_permalink($post_id),
            ];
        }
    update_term_meta($connector_term_id, $meta_key, $current_meta);
}

function save_related_connector_terms_to_type_post_type($post_id, $context_data, $related_connectors, $new_type_term_id, $current_type_term_id, $type_taxonomy) {
    $type_context_name = $context_data['context_name'];
    $post_per_term = $context_data['post_per_term'];
    $related_connectors_count = $context_data['related_connector_count'];
    $related_connectors = $context_data['related_connectors'];
    $meta_key = "related_type_{$type_context_name}";
    $post_permalink = get_permalink($post_id);
    $type_change = ($new_type_term_id !== $current_type_term_id);
   
if ($related_connectors_count === 2) {
        $areas_tax = $related_connectors['service_areas']['taxonomy'];
        $loc_tax = $related_connectors['locations']['taxonomy'];
        $current_area_id = dibraco_get_current_term_id_for_post($post_id, $areas_tax);
        $current_loc_id = dibraco_get_current_term_id_for_post($post_id, $loc_tax);
        $new_area_id = $_POST["{$areas_tax}_term"];
        $new_loc_id = $_POST["{$loc_tax}_term"];

        if ($current_area_id !== '' && $current_type_term_id !=='' ) {
         type_connector_clear_old_meta_not_touching_fallbacks($current_area_id, $meta_key, $current_type_term_id, $post_id, $post_per_term);
        }
        if ($current_loc_id !== '' && $current_type_term_id !== '') {
         type_connector_clear_old_meta_not_touching_fallbacks($current_loc_id, $meta_key, $current_type_term_id, $post_id, $post_per_term);
        }
        if ($new_type_term_id ==='') {
             if ($new_area_id !== '') {
                wp_set_object_terms($post_id, (int)$new_area_id, $areas_tax, false);
             }elseif ($new_area_id ==='') {
                 wp_set_object_terms($post_id, [], $areas_tax, false);
             }
             if ($new_loc_id !==''){
                wp_set_object_terms($post_id, (int)$new_loc_id, $loc_tax, false);
             } elseif ($new_loc_id ==='') {
                  wp_set_object_terms($post_id, [], $loc_tax, false);
             }
             return; 
        }
        if ($post_per_term ==='1'){
            if ($new_area_id !== '') {
                 type_connector_update_term_meta($new_area_id, $meta_key, $new_type_term_id, $post_id, $post_per_term);
                 wp_set_object_terms($post_id, (int)$new_area_id, $areas_tax, false);
                 if ($new_loc_id !==''){
                     wp_set_object_terms($post_id, (int)$new_loc_id, $loc_tax, false);
                 } elseif ($new_loc_id ===''){
                     wp_set_object_terms($post_id, [], $loc_tax, false);
                 }
            } elseif ($new_area_id ===''){
                wp_set_object_terms($post_id, [], $areas_tax, false);
                if ($new_loc_id !==''){
                    type_connector_update_term_meta($new_loc_id, $meta_key, $new_type_term_id, $post_id, $post_per_term);
                      wp_set_object_terms($post_id, (int)$new_loc_id, $loc_tax, false);
                } elseif ($new_loc_id ===''){
                    wp_set_object_terms($post_id, [], $loc_tax, false);
                }
            }
            return; 
            }
                
            if ($new_area_id !== '') {
                 type_connector_update_term_meta($new_area_id, $meta_key, $new_type_term_id, $post_id, $post_per_term);
                 wp_set_object_terms($post_id, (int)$new_area_id, $areas_tax, false);
            }
             if ($new_area_id ===''){
                wp_set_object_terms($post_id, [], $areas_tax, false);
             }
                 if ($new_loc_id !==''){
                     wp_set_object_terms($post_id, (int)$new_loc_id, $loc_tax, false);
                 }
                 if ($new_loc_id ===''){
                     wp_set_object_terms($post_id, [], $loc_tax, false);
                 }
                }
            }
 function save_related_connector_terms_to_unique($post_id, $context_data, $related_connectors) {
    $context_name = $context_data['context_name'];
    $meta_key = "related_unique_{$context_name}";
    $post_title  = get_the_title($post_id);
    $post_url = get_permalink($post_id);
    foreach ($related_connectors as $connector_key => $connector_data) {
        $related_connector_taxonomy = $related_connector_data['taxonomy'];
        $current_term_id = dibraco_get_current_term_id_for_post($post_id, $related_connector_taxonomy);
        $new_term_id = (int)$_POST["{$taxonomy}_term"];
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
            $new_term_id = (int)$new_term_id;
            $new_meta[$post_id] = [
                'related_post_id' => $post_id,
                'related_post_title' => $post_title,
                'related_post_url'   => $post_url,
            ];
            update_term_meta($new_term_id, $meta_key, $new_meta);
        }
        wp_set_object_terms($post_id, $new_term_id, $taxonomy, false);
    }
}       

function setup_related_type_terms_for_new_connector_term($connector_term_id, $related_type_contexts, $context_name, $taxonomy) {
    $connector_term = get_term($connector_term_id);
    $status = get_option('locations_areas_status');
    $connector_term_name = $connector_term->name;
    foreach ($related_type_contexts as $related_type_name_context_name => $related_type_data) {
        $entries = [];
        $post_per_term = $related_type_data['post_per_term'];
        $related_type_taxonomy = $related_type_data['taxonomy'];
        $related_type_post_type = $related_type_data['post_type'];
        $meta_key = "related_type_{$related_type_name_context_name}";
        if ($post_per_term !== "1") {
            $type_terms = get_terms(['taxonomy' => $related_type_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
            foreach ($type_terms as $type_id => $type_term_name) {
                $entries[$type_id] = []; 
            }
            update_term_meta($connector_term_id, $meta_key, $entries);
            continue;
        } 
        if ($post_per_term === "1") {
            $related_connector_count = $related_type_data['related_connector_count'];
            $area_parent_location_term ='';
            if ($related_connector_count === 2 && $context_name !=='locations'){
                 $area_parent_location_term = get_term_meta($connector_term_id, 'area_parent_location_term', true);
                 if (!empty($area_parent_location_term)) {
                    $area_parent_location_term = (int)$area_parent_location_term;
                    $parent_term_type_context_data = get_term_meta($area_parent_location_term, $meta_key, true);
                        foreach ($parent_term_type_context_data as $type_term_id => $post_data) {
                            $type_term_id = (int)$type_term_id;
                            $type_term_name = get_term($type_term_id)->name;
                            $related_post_title = "{$type_term_name} in {$connector_term_name}";
                            $fallback_url = $post_data['related_post_url'];
                            if (empty($fallback_url)){
                                $fallback_url = $post_data['fallback_url'];
                            }
                             $entries[$type_term_id] = ['related_post_title' => $related_post_title, 'related_post_id' => '', 'related_post_url' => '', 'fallback_url' => $fallback_url];
                        }
                        update_term_meta($connector_term_id, $meta_key, $entries);
                        continue;
                    }
                }
            $main_post_map = get_option("($related_type_name_context_name}_main_posts");
                  foreach ($main_post_map as $type_term_id => $fallback_post_id) {
                    $type_term_id =(int)$type_term_id;
                    $type_term_name = get_term($type_term_id)->name;
                    $related_post_title = "{$type_term_name} in {$connector_term_name}";
                    $fallback_post_id=(int)$fallback_post_id;
                    $fallback_url = get_permalink($fallback_post_id);
                     $entries[$type_term_id] = ['related_post_title' => $related_post_title, 'related_post_id' => '', 'related_post_url' => '', 'fallback_url' => $fallback_url];
                }
            update_term_meta($connector_term_id, $meta_key, $entries);
            continue;
        } 
    }
}




function dibraco_admin_table_template($title_config, $headers, $styles, $rows, $colspan) {
    $title_tag   = $title_config['tag'] ?? 'h2';
    $title_style = $title_config['style'] ?? 'margin-bottom:0.5em;';
    $title_text  = $title_config['text'] ?? '';
    if (!in_array($title_tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
        $title_tag = 'h2';
    }
    ?>
    <?php if (!empty($title_text)) : ?>
        <<?php echo $title_tag; ?> style="<?php echo $title_style; ?>"><?php echo $title_text; ?></<?php echo $title_tag; ?>>
    <?php endif; ?>
    <table class="wp-list-table widefat striped;">
        <thead>
            <tr>
                <?php foreach ($headers as $index => $header) : ?>
                    <th style="<?php echo $styles[$index] ?? ''; ?>">
                        <?php echo $header; ?>
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
                        $row_attributes .= ' ' . $attr_key . '="' . $attr_value . '"';
                    }
                }
                $cells = $row_data['cells'] ?? $row_data;
                ?>
                <tr<?php echo $row_attributes; ?>>
                    <?php foreach ($cells as $col_index => $cell_data) :
                        if ($cell_data === null) continue; 
                    $style_attr_string = $styles[$col_index] ?? '';
                            ?>
                            <?php
                            if (is_array($cell_data) && isset($cell_data['rowspan'])) {
                             $rowspan_attr = ' rowspan="' . $cell_data['rowspan'] . '"';
                            $style_attr = !empty($cell_data['style']) ? ' style="' . $cell_data['style'] . $style_attr_string . '"' : 'style="' . $style_attr_string . '"';
                            echo '<td' . $rowspan_attr . $style_attr . '>' . $cell_data['content'] . '</td>';

                        } elseif (is_array($cell_data) && !empty($cell_data['is_editable'])) {
                            $input_name  = $cell_data['name']  ?? '';
                            $input_value = $cell_data['value'] ?? '';
                            $style_attr  = !empty($cell_data['style']) ? ' style="' . $cell_data['style'] . '"' : '';
                            $name_attr   = $input_name !== '' ? ' name="' . $input_name . '"' : '';
                            echo '<td style="' . $style_attr_string . '">';
                            echo '<input type="text"' . $name_attr . ' value="' . $input_value . '"' . $style_attr . ' />';
                            if (!empty($cell_data['hidden_fields']) && is_array($cell_data['hidden_fields'])) {
                                foreach ($cell_data['hidden_fields'] as $hidden_name => $hidden_value) {
                                    echo '<input type="hidden" name="' . $hidden_name . '" value="' . $hidden_value . '" />';
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
                                $id = 'checkbox_' . str_replace(['[', ']'], '', $name) . '_' . $value;
                                $checked_attr = !empty($option['checked']) ? ' checked="checked"' : '';
                                echo '<input type="checkbox" id="' . $id . '" name="' . $name . '" value="' . $value . '"' . $checked_attr . '>';
                                echo ' <label for="' . $id . '">' . $label . '</label>';
                            }
                            echo '</div></td>';
                        } else {
                            echo '<td style="' . $style_attr_string . '">';
                            if (is_string($cell_data)) {
                            echo $cell_data;
                            }
                            echo '</td>';
                        }
                        ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="<?php echo $colspan ?>">
                    </td>
            </tr>
        </tfoot>

    </table>
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
    $post_per_term = $type_context_data['post_per_term'];

    if ($post_per_term === "1") {
        $headers = ['Title', 'Related URL',  'Fallback URL'];
        $styles  = ['width:40%','width:30%','width:20%'];
        $table_rows = [];
        foreach ($rows_from_meta as $type_term_id => $entry) {
            $row_name = "{$related_type_meta_key}[{$type_term_id}]";
            $table_rows[$type_term_id] = [
                'cells' => [
                    ['is_editable' => true, 
                    'name' => "{$row_name}[related_post_title]",
                    'value' => $entry['related_post_title'],
                'hidden_fields'  => ['related_post_id' => $entry['related_post_id']],
                ],
                    $entry['related_post_url'],
                    $entry['fallback_url'],
                ],
            ];
        }
    } else {
       $headers = ['Title', 'Related URL'];
       $styles  = ['width:50%','width:50%' ];
       $table_rows = [];

    foreach ($rows_from_meta as $type_term_id => $posts) {
         if (empty($posts)) continue;
        foreach ($posts as $post_id => $entry) {
            $row_name = "{$related_type_meta_key}[{$type_term_id}][$post_id]";
            $table_rows[] = [
                'cells' => [
                   ['is_editable' => true, 
                    'name' => "{$row_name}[related_post_title]", 
                    'value' => $entry['related_post_title'],
                    'hidden_fields' => ["{$row_name}[related_post_id]" => $entry['related_post_id']]
                   ],
                   $entry['related_post_url'],
               ],
           ];

            }
        }
    }
     if (empty($table_rows)) {
        return null;
    }
    return [
        'title'   => 'Related ' . ucwords(str_replace('_', ' ', $related_type_context_name)) . ' Posts',
        'headers' => $headers,
        'styles'  => $styles,
        'rows'    => $table_rows,
        'colspan' => count($headers),
    ];
}

function prepare_single_unique_table_data($unique_context_name, $unique_context_data, $current_connector_term_id) {
    $related_unique_meta_key = "related_unique_{$unique_context_name}";
    $unique_posts_data = get_term_meta($current_connector_term_id, $related_unique_meta_key, true);
    $table_rows = [];
    foreach ($unique_posts_data as $post_id => $post_data) {
        $row_name = "{$related_unique_meta_key}[{$post_id}]";
        $title_cell = [
            'is_editable' => true,
            'name' => "{$row_name}[related_post_title]",
            'value' => $post_data['related_post_title'],
            'hidden_fields' => [
                "{$row_name}[related_post_url]" => $post_data['related_post_url'],
                "{$row_name}[related_post_id]"  => $post_id,
            ]
        ];
        $url_cell = $post_data['related_post_url'];
        $table_rows[] = ['cells' => [$title_cell, $url_cell]];
    }
    if (empty($table_rows)) {
        return null;
    }

    return [
        'title'   => 'Related ' . ucwords(str_replace('_', ' ', $unique_context_name)) . ' Posts',
        'headers' => ['Title', 'URL'],
        'styles'  => ['width:40%', 'width:60%'],
        'rows'    => $table_rows,
        'colspan' => 2,
    ];
}


add_action('wp_ajax_inline_save', 'dibraco_handle_quick_edit_save', 10);
function dibraco_handle_quick_edit_save() {
    return;
}
function dibraco_save_taxonomy_term_bulk_or_quick_edit($post_id, $taxonomy, $quick_edit_field_key) {
  error_log('in here');return;
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
        
/*
function dibraco_get_context_permalink_data($post_type) {
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
        'front' => $post_type,
        'slug' => $rewrite_slug,
        'archive' => $archive_slug,
        'postname' => '%postname%',
        'segment_count' => $segments + 1,
    ];
}
*/