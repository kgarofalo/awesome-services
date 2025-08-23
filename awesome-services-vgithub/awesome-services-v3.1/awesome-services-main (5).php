<?php
/**
 * Plugin Name: Awesome Services Plugin
 * Description: A plugin to define complex relationships between multiple pairs of post types.
 * Version: 3.54
 * Author: ChatGPT 3.5
 */
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('AWESOME_SERVICES_PATH', plugin_dir_path(__FILE__));
define('AWESOME_SERVICES_URL', plugin_dir_url(__FILE__));

require_once AWESOME_SERVICES_PATH . 'includes/shortcode-functions.php'; 
require_once AWESOME_SERVICES_PATH . 'main-settings/plugin-relationship-settings.php';
require_once AWESOME_SERVICES_PATH . 'main-settings/company-info-options.php';
require_once AWESOME_SERVICES_PATH . 'main-settings/shortcode-list.php';
require_once AWESOME_SERVICES_PATH . 'includes/cardstylesnew.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/form-helper.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/da-hours-operation.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/da-social-media-fields.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/name-phone-address.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/form-processor.php';
require_once AWESOME_SERVICES_PATH . 'includes/card-front-end.php';
require_once AWESOME_SERVICES_PATH . 'post-types/multiple-location-term-setup.php';
require_once AWESOME_SERVICES_PATH . 'terms/job-benefits.php';
require_once AWESOME_SERVICES_PATH . 'post-types/job-postings.php';
require_once AWESOME_SERVICES_PATH . 'post-types/dibraco-relationships-main-posts.php';
require_once AWESOME_SERVICES_PATH . 'post-types/type-post-type-fields.php';
require_once AWESOME_SERVICES_PATH . 'includes/schema.php';
require_once AWESOME_SERVICES_PATH . 'terms/main-type-term-fields.php';
require_once AWESOME_SERVICES_PATH . 'terms/main-area-tax-terms.php';
require_once AWESOME_SERVICES_PATH . 'related/relational.php';
require_once AWESOME_SERVICES_PATH . 'includes/da_old-functions.php';
require_once AWESOME_SERVICES_PATH . 'includes/kmlgenerator.php';
/*
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://gitlab.com/dibraco/awesome-services/',
    __FILE__, 
    'awesome-services'
);
$myUpdateChecker->setBranch('main');
$myUpdateChecker->setAuthentication('glpat-yz8nzm_DT-Txy8-zL3Kt');
*/

function myplugin_enqueue_admin_scripts($hook_suffix) {
error_log("myplugin_enqueue_admin_scripts fired on hook: " . $hook_suffix);

$current_page = $_GET['page'] ?? '';
$main_slug = 'dibraco-relationships';
$is_plugin_page = ($current_page === $main_slug || str_starts_with($current_page, $main_slug . '-'));
if ($current_page === $main_slug) {
        error_log(" - Enqueuing da-relationship-settings-script for plugin main page.");
        wp_enqueue_script('da-relationship-settings-script', AWESOME_SERVICES_URL . 'js/da-relationship-settings.js', [], false, true);
    }
        $screen = get_current_screen();
    if (isset($_GET['post']) && 'page' === get_post_type($_GET['post'])) {
        $enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', '');
        if ($enable_custom_fields_for_pages_value === "1") {
            error_log(" - Enqueuing styles for general pages.");
            wp_enqueue_style('common-fields-style', AWESOME_SERVICES_URL . 'css/da-common-fields.css');
            error_log(" - Enqueuing media-fields-images-terms-script.");
            wp_enqueue_script('media-fields-images-terms-script', AWESOME_SERVICES_URL . 'js/da-media-fields-images-terms.js', ['jquery'], null, true);
        }
    }
    if (strpos($hook_suffix, 'card-styles-') !== false ) {
        error_log(" - Enqueuing dibraco-card-styles-admin-css for card styles page.");
        wp_enqueue_style( 'dibraco-card-styles-admin-css', AWESOME_SERVICES_URL . 'css/da-card-styles-admin.css', array(), null, 'all');
    }


    $enabled_post_types = [];
    $enabled_taxonomies = [];
    $taxonomy_term_maps = [];
    $locations_post_type = '';
    $locations_connector_tax = '';
    $jobs_post_type = '';
    $should_enqueue_shared = false;
    $enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', '');
 $is_plugin_settings_page = (
        $hook_suffix === 'toplevel_page_dibraco-relationships' || 
        str_starts_with($hook_suffix, 'relationships_page_dibraco-relationships')
    );

 $enabled_contexts = get_option('enabled_contexts') ?? null;
    if (!$enabled_contexts) return;
    error_log(" - Beginning loop through enabled contexts.");
    foreach ($enabled_contexts as $context_name => $context_data) {
        error_log(" - - Processing context: " . $context_data['context_name']);
        $context_type = $context_data['context_type'];
        $name = $context_data['context_name'];
        $enabled_post_types[] = $context_data['post_type'];
        if ($context_type !== 'unique') {
            $enabled_taxonomies[] = $context_data['taxonomy'];
            if ($name === 'locations') {
                $locations_post_type = $context_data['post_type'];
                $locations_connector_tax = $context_data['taxonomy'];
            }
            $status = get_option('locations_areas_status');
            if ($status === 'both' && $name === 'service_areas') {
                $service_areas_taxonomy = $context_data['taxonomy'];
                if (('edit-tags.php' === $hook_suffix) && isset($_GET['taxonomy']) && $_GET['taxonomy'] === $service_areas_taxonomy) {
                    error_log(" - - - Enqueuing quickedit script for service areas.");
                    wp_enqueue_script('dibraco-term-quickedit', AWESOME_SERVICES_URL . 'js/term-quickedit.js', ['jquery', 'inline-edit-tax'], '1.0.1', true);
                    wp_localize_script('dibraco-term-quickedit', 'dibraco_qe_data', ['meta_key' => 'area_parent_location_term', 'sortable_name' => 'area_parent_location_name']);
                }
            }
        
        if ($name === 'jobs') {
                $jobs_post_type = $context_data['post_type'];
            }
            
            if ((in_array($hook_suffix, ['post.php', 'post-new.php'], true) && $screen->post_type === $locations_post_type) || (($hook_suffix === 'term.php') && isset($_GET['taxonomy']) && $_GET['taxonomy'] === $locations_connector_tax)) {
                error_log(" - - - Enqueuing specific scripts for locations.");
                wp_enqueue_style('locations-company-info-style', AWESOME_SERVICES_URL . 'css/locations_company_info.css');
                wp_enqueue_script('awesome-hours-of-operation-script', AWESOME_SERVICES_URL . 'js/da-hours-operation.js', ['jquery'], false, true);
            }
            
            if (in_array($hook_suffix, ['post.php', 'post-new.php'], true) && $screen->post_type === $jobs_post_type) {
                error_log(" - - - Enqueuing specific styles for jobs.");
                wp_enqueue_style('job-postings-style', AWESOME_SERVICES_URL . 'css/da-job-postings.css');
            }
        }
    }

   
   $is_enabled_post_type = in_array($screen->post_type ?? ($_GET['post_type'] ?? ''), $enabled_post_types, true);
    $is_post_edit_screen = ($hook_suffix === 'post.php' || $hook_suffix === 'post-new.php');
    $is_term_edit = strpos($hook_suffix, 'term.php') !== false;
    $is_all_posts_screen = isset($screen->base) && $screen->base === 'edit';
    $is_enabled_taxonomy = in_array($screen->taxonomy ?? ($_GET['taxonomy'] ?? ''), $enabled_taxonomies, true);

    $should_enqueue_shared = (
        $is_plugin_settings_page ||
        ($is_enabled_post_type && $is_post_edit_screen) ||
        $is_enabled_taxonomy ||
        $is_term_edit
    );

  if ($should_enqueue_shared) {
        error_log(" - Enqueuing a large block of shared scripts and styles.");
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('jquery-ui-theme');
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('postbox');
        wp_enqueue_script('wp-util');
        wp_enqueue_media();
        wp_enqueue_style('common-fields-style', AWESOME_SERVICES_URL . 'css/da-common-fields.css');
        wp_enqueue_script('da-color-picker2', AWESOME_SERVICES_URL . 'js/da-color-picker2.js', ['jquery', 'wp-color-picker', 'jquery-ui-slider'], false, true);
        wp_enqueue_script('da-repeater', AWESOME_SERVICES_URL . 'js/da-repeater.js', ['jquery'], false, true);
        wp_enqueue_script('media-fields-images-terms-script', AWESOME_SERVICES_URL . 'js/da-media-fields-images-terms.js', ['jquery'], null, true);
        wp_enqueue_script('da-conditional-fields', AWESOME_SERVICES_URL . 'js/da-conditional-fields.js', ['jquery'], false, true);
    }

     if ($hook_suffix === 'relationships_page_dibraco-relationships-company-info') {
        error_log(" - Enqueuing locations-company-info-style and awesome-hours-of-operation-script for company info page.");
        wp_enqueue_style('locations-company-info-style', AWESOME_SERVICES_URL . 'css/locations_company_info.css');
        wp_enqueue_script('awesome-hours-of-operation-script', AWESOME_SERVICES_URL . 'js/da-hours-operation.js', ['jquery'], false, true);
    }

      if ($is_all_posts_screen) {
        error_log(" - Enqueuing quick-edit-taxonomy-script for all posts screen.");
        wp_enqueue_script('quick-edit-taxonomy-script', AWESOME_SERVICES_URL . 'js/da-quickedits.js', ['jquery'], null, true);
        
        $taxonomies = [];
        foreach ($enabled_contexts as $context_name => $context_data) {
            if ($context_data['post_type'] === $screen->post_type) {
                if ($context_data['context_type'] !== 'unique') {
                    $taxonomies[] = $context_data['taxonomy'];
                }
                if ($context_data['context_type'] !== 'connector') {
                    $related_contexts = $context_data['related_connectors'] ?? [];
                    if (!empty($related_contexts)) {
                        foreach ($related_contexts as $related_context => $related_context_data) {
                            $taxonomies[] = $related_context_data['taxonomy'];
                        }
                    }
                }
            }
        }
       wp_localize_script('quick-edit-taxonomy-script', 'quickEditData', [
            'post_type' => $screen->post_type,
            'taxonomy' => $taxonomies,
            'context_name' => $context_data['context_name'] ?? '',
        ]);
    }
}
add_action('admin_enqueue_scripts', 'myplugin_enqueue_admin_scripts', 20);



function awesome_register_main_settings_menu() {
   $slug = 'dibraco-relationships';
    add_menu_page(
        'Relationship Configuration',
        'Relationships',
        'manage_options',
        $slug,
        'render_relationships_settings_page',
        'dashicons-admin-generic',
        2
    );
    add_submenu_page(
        $slug,
        'Company Info',
        'Company Info',
        'manage_options',
        "$slug-company-info",
        'company_info_options_page'
    );
    add_submenu_page(
       $slug,
       'Social Media',
       'Social Media Properties',
       'manage_options',
       'social-media',
       'render_social_media_options_page'
       );
$enabled_context_names = get_option('enabled_context_names');  

$status = get_option('locations_areas_status');



  $type_contexts = (array)get_option('enabled_type_contexts');
  if (!empty($type_contexts)){
    add_submenu_page(
        $slug,
        'Main Posts Overview',
        'Main Posts',
        'manage_options',
        "$slug-main-posts",
        'render_dibraco_main_posts_screen'
        );
    }
    
         add_submenu_page(
        $slug,       
        'Custom Fields',         
        'Custom Fields',          
        'manage_options',         
        "$slug-custom-fields",      
        'da_render_custom_fields_page' 
    );
    add_submenu_page(
        $slug,
        'Export Terms',
        'Export Terms',
        'manage_options',
        'export-terms',
        'render_export_entities_page'
    );
    $status = get_option('locations_areas_status');
    if ($status === 'multi_locations' || $status === 'both') {
        add_submenu_page(
            $slug,                 
            'KML Map Generator',            
            'KML Generator',                 
            'manage_options',                 
            "$slug-kml-generator",                  
            'render_kml_generator_page'      
        );
    }
    
    if (!empty($enabled_context_names)) ;
    add_submenu_page(
        'dibraco-relationships',
        'Card Styles Selection',
        'Card Styles Selection',
        'manage_options',
        'card-styles-selection',
        'render_card_styles_selection_page'
    );
    $selected = get_option('selected_contexts');
    $enabled  = get_option('enabled_contexts');
    if (empty($selected) || empty($enabled)) return;
    foreach ($selected as $context_name) {
        $context_data = $enabled[$context_name]; 
        $label        = ucwords(str_replace(['_', '-'], ' ', $context_name));
        $menu_slug    = 'card-styles-' . str_replace('_', '-', $context_name);

        add_submenu_page(
            'relationships',
            "{$label} Card Styles",
            "{$label} Card Styles",
            'manage_options',
            $menu_slug,
            function () use ($context_data, $context_name) {
                dibraco_awesome_render_card_settings_page($context_data, $context_name);
            }
        );
    }

    add_submenu_page(
        $slug,
        'Migrate Legacy Options',
        'Migrate Options',
        'manage_options',
        'migrate-options',
        'render_migration_page'
    );

 
}
add_action('admin_menu', 'awesome_register_main_settings_menu', 10);


function register_card_styles_selection_page() {
    $enabled = get_option('enabled_context_names');
    if (empty($enabled)) return;
    add_submenu_page(
        'dibraco-relationships',
        'Card Styles Selection',
        'Card Styles Selection',
        'manage_options',
        'card-styles-selection',
        'render_card_styles_selection_page'
    );
}
add_action('admin_menu', 'register_card_styles_selection_page', 105);



function dibraco_extract_field_names_helper($fields, $container_key = '', $container_type = '', $prefix = '') {
    $processed_keys = [];

    $standard_fields = ['text', 'textarea', 'date', 'time', 'colorpicker', 'image', 'number', 'toggle', 'select', 'radio', 'checkbox', 'wysiwyg'];
    $visual_containers = ['visual_section', 'field_group', 'visual_group'];
    $functional_containers = ['group', 'repeater'];

    foreach ($fields as $key => $config) {
        $type = $config['type'];

        if (in_array($type, $standard_fields)) {
            $current_name = $prefix . $key;
            if ($container_type === 'repeater') {
                $processed_keys[] = "{$container_key}[{$current_name}]";
            } elseif ($container_type === 'group') {
                $processed_keys[] = "{$container_key}_{$current_name}";
            } else {
                $processed_keys[] = $current_name;
            }
        } elseif (in_array($type, $functional_containers)) {
            if ($type === 'group') {
                if ($container_type === 'repeater') {
                    $new_prefix = $prefix . $key . '_';
                    $nested_keys = dibraco_extract_field_names_helper($config['fields'], $container_key, 'repeater', $new_prefix);
                    $processed_keys = array_merge($processed_keys, $nested_keys);
                } else {
                    $new_container_key = $container_key ? "{$container_key}_{$key}" : $key;
                    $nested_keys = dibraco_extract_field_names_helper($config['fields'], $new_container_key, 'group');
                    $processed_keys = array_merge($processed_keys, $nested_keys);
                }
            } elseif ($type === 'repeater') {
                $repeater_base_name = $container_key ? "{$container_key}_{$key}" : $key;
                $processed_keys[] = "{$repeater_base_name}_row_count";
                
                $repeater_container_key = "{$repeater_base_name}[0]";
                $nested_keys = dibraco_extract_field_names_helper($config['fields'], $repeater_container_key, 'repeater');
                $processed_keys = array_merge($processed_keys, $nested_keys);
            }
        } elseif (in_array($type, $visual_containers)) {
            $nested_keys = dibraco_extract_field_names_helper($config['fields'], $container_key, $container_type, $prefix);
            $processed_keys = array_merge($processed_keys, $nested_keys);
        }
    }
    return $processed_keys;
}
function dibraco_extract_field_names_recursive($fields) {
        $processed_keys = dibraco_extract_field_names_helper($fields);
        return $processed_keys;
}

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
        $export_data['enabled_contexts_config'] = $all_contexts;
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
    $term_meta_keys = ['service_area_post_id', 'city', 'state', 'latitude', 'longitude'];
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

    if ($context_config['before_after'] === '1') {
        $fields_to_get = array_merge($fields_to_get, array_keys(get_before_after_fields()));
        foreach ($fields_to_get as $key => $field_name) {
            if (strpos($key, 'lock') !== false) {
                unset($fields_to_get[$key]);
            }
        }
    }
	if ($context_name === 'employee') {
       $post_meta_data['employee_data'] = get_post_meta($post_id, 'employee_data', true);
	}
    foreach (array_unique($fields_to_get) as $field) {
        $post_meta_data[$field] = get_post_meta($post_id, $field, true);
    }

if ($context_name === 'jobs') {
    $raw = get_post_meta($post_id, '_job_meta', true);
    $processed = [];
    $job_fields = da_get_job_fields();
     $job_field_keys =  dibraco_extract_field_names_recursive($job_fields);
    foreach ($job_field_keys as $index => $key) {
        if (str_ends_with($key, '_row_count')) {
            $processed[$key] = $raw[$key];
        } elseif (preg_match('/^([a-zA-Z0-9_]+)\[(\d+)\]\[([a-zA-Z0-9_]+)\]$/', $key, $m)) {
            $processed[$m[1]][(int)$m[2]][$m[3]] = $raw[$key];
        } else {
            $processed[$key] = $raw[$key];
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

function da_render_custom_fields_page() {
    $enabled_contexts = get_option('enabled_contexts', []);
    if (get_option('enable_custom_fields_for_pages', '') === "1") {
        $enabled_contexts['page'] = ['post_type' => 'page'];
    }
    $all_custom_fields = get_option('dibraco_custom_fields', []);
    $field_options = ['text' => 'Text' , 'textarea' => 'Text Area', 'wysiwyg' => 'Wysiwig' ];
    if (isset($_POST['da_save_custom_fields_nonce']) && wp_verify_nonce($_POST['da_save_custom_fields_nonce'], 'da_save_custom_fields_action')) {
        $context_to_save = sanitize_key($_POST['context_selection']);
        $new_fields_for_context = [];
        $validation_error_message = '';
        if (!empty($_POST['fields'])) {
               $pair_start_count = count(array_column($_POST['fields'], 'pair'));
               $pair_end_count = count(array_column($_POST['fields'], 'pair_end'));
            if ($pair_start_count !== $pair_end_count) {
              $validation_error_message = 'The number of starting and ending pairs must match. The changes were not saved.';
             }
            }
        if (!empty($validation_error_message)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($validation_error_message) . '</p></div>';
        } else {
            if (!empty($_POST['fields'])) {
                    foreach ($_POST['fields'] as $original_field_name => $field_data) {
                    if ($original_field_name === '') { continue; }
                    $label = isset($_POST[$original_field_name]) ? trim(stripslashes($_POST[$original_field_name])) : '';
                    if ($label === '') {continue; }
                    $new_field_name = 'da_' . str_replace('-', '_', sanitize_title($label));
                    $type = $field_data['type'];
                    $config = ['type' => $type];
                    if (isset($field_data['pair'])) {
                        $config['pair'] = true;
                    }
                    if (isset($field_data['pair_end'])) {
                        $config['pair_end'] = true;
                    }
                    $new_fields_for_context[$new_field_name] = $config;
                }
            }
            $previous_fields = $all_custom_fields[$context_to_save] ?? [];
            $removed_keys = array_diff(array_keys($previous_fields), array_keys($new_fields_for_context));
            if (!empty($removed_keys)) {
                $post_type = $enabled_contexts[$context_to_save]['post_type'];
                $posts_to_update = get_posts(['post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']);
                if (!empty($posts_to_update)) {
                    foreach ($posts_to_update as $post_id) {
                        foreach ($removed_keys as $meta_key) {
                            delete_post_meta($post_id, $meta_key);
                        }
                    }
                }
            }
            $all_custom_fields[$context_to_save] = $new_fields_for_context;
            update_option('dibraco_custom_fields', $all_custom_fields);
            echo '<div class="notice notice-success is-dismissible"><p>Custom fields saved successfully!</p></div>';
        }
    }
    $current_context = $_GET['context'] ?? key($enabled_contexts);
    $current_fields = $all_custom_fields[$current_context] ?? [];
    ?>
    <div class="wrap">
        <h1>Custom Field Editor</h1>
        <p>Define additional fields for your contexts. These will be added to the main metabox for that context's post type.</p>
        <div style="margin-top: 20px;">
             <label for="context_selector" style="font-weight:bold; font-size:1.2em;">Editing fields for context:</label>
             <select id="context_selector" onchange="window.location.href='?page=dibraco-relationships-custom-fields&context=' + this.value;">
                 <?php foreach ($enabled_contexts as $context_name => $context_data) : ?>
                     <option value="<?php echo esc_attr($context_name); ?>" <?php selected($current_context, $context_name); ?>>
                         <?php echo esc_html(ucwords(str_replace(['-', '_'], ' ', $context_data['post_type']))); ?>
                     </option>
                 <?php endforeach; ?>
             </select>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="context_selection" value="<?php echo esc_attr($current_context); ?>">
            <?php wp_nonce_field('da_save_custom_fields_action', 'da_save_custom_fields_nonce'); ?>
                <table class="wp-list-table widefat striped" id="custom-fields-table" style="margin-top:20px;">
              <thead>
            <tr>
                <th style="width: 40%;">Field Label</th>
                <th style="width: 40%;">Field Type</th>
                <th style="width: 10%;">In Pair?</th>
                <th style="width: 10%;">Actions</th>
            </tr>
         </thead>
           <tbody id="fields-repeater-container">
            <?php
            if(empty($current_fields)){
                $current_fields = ['new_field' => ['type' => 'text']];
            }
            $first_field = true;
            foreach ($current_fields as $field_name => $config): ?>
            <?php
            $type = $config['type'];
            $is_pair_start = isset($config['pair']);
            $is_pair_end = isset($config['pair_end']);
            $display_label ='';
            If ($field_name !=="new_field"){
            $display_label = ucwords(trim(str_replace('_', ' ', substr($field_name, 3))));
            }
            ?>
        <tr class="field-row">
            <td>
            <input type="text" name="<?= $field_name; ?>" placeholder="New Field Name eg (Section 4 Title)" class="widefat field-name-input" value="<?= $display_label; ?>">
            </td>
            <td>
                <select name="fields[<?= $field_name ?>][type]" class="widefat">
                    <?php foreach ($field_options as $value => $label): ?>
                        <option value="<?= $value; ?>" <?php selected($type, $value); ?>><?= $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
          <td class="pair-toggle-cell">
              <?php if ($is_pair_end): ?>
                Paired<input type="hidden" name="fields[<?= $field_name ?>][pair_end]" value="1">
               </td><td></td>
           <?php else: ?>
                <input type="checkbox" name="fields[<?= $field_name ?>][pair]" value="1" <?= $is_pair_start ? 'checked' : '' ?>>
                   </td><td>
                       <?php if(!$first_field) : ?>
                   <a href="#" class="button button-danger remove-field-button">Remove</a>
                    <?php endif; ?></td>
               <?php endif; ?>
        </tr>
        <?php $first_field = false; endforeach; ?>
          </tbody>
        </table>
            <button type="button" class="button" id="add-field-button" style="margin-top:10px;">+ Add Field</button>
              <?php submit_button('Save Custom Fields'); ?>
        </form>
    </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('fields-repeater-container');
    const addButton = document.getElementById('add-field-button');
    if (!container || !addButton) return;

    // --- Helper Constants for Reused HTML ---
    const removeButtonHTML = '<a href="#" class="button button-danger remove-field-button">Remove</a>';
    const createPairCheckboxHTML = (fieldName) => `<input type="checkbox" name="fields[${fieldName}][pair]" value="1">`;

    const fieldOptions = {
        text: 'Text',
        textarea: 'Text Area',
        wysiwyg: 'Wysiwig'
    };
    let optionsHTML = '';
    for (const key in fieldOptions) {
        if (fieldOptions.hasOwnProperty(key)) {
            optionsHTML += `<option value="${key}">${fieldOptions[key]}</option>`;
        }
    }

    function fixInitialState() {
        const allRows = Array.from(container.querySelectorAll('.field-row'));
        allRows.forEach((currentRow) => {
            const isCurrentRowPairEnd = currentRow.querySelector('input[name*="[pair_end]"]');
            if (isCurrentRowPairEnd) return;
            const nextRow = currentRow.nextElementSibling;
            if (!nextRow) return;

            const nextRowStartsPair = nextRow.querySelector('.pair-toggle-cell input[name*="[pair]"]:checked');
            if (nextRowStartsPair) {
                const currentPairCell = currentRow.querySelector('.pair-toggle-cell');
                if (currentPairCell && currentPairCell.querySelector('input[name*="[pair]"]')) {
                    currentPairCell.innerHTML = '';
                }
            }
        });
    }

    function handlePairChecked(checkbox) {
        const currentRow = checkbox.closest('.field-row');
        const prevRow = currentRow.previousElementSibling;
        const nextRow = currentRow.nextElementSibling;
        if (nextRow) {
            const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
            const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
            const nextActionsCell = nextPairCell.nextElementSibling;
            nextPairCell.innerHTML = `Paired<input type="hidden" name="fields[${nextFieldName}][pair_end]" value="1">`;
            nextActionsCell.innerHTML = '';
        }
        if (prevRow) {
            const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
            const isPrevRowPairEnd = prevPairCell.querySelector('input[name*="[pair_end]"]');
            if (!isPrevRowPairEnd) {
                if (prevPairCell.querySelector('input[name*="[pair]"]')) {
                    prevPairCell.innerHTML = '';
                }
            }
        }
    }

    function handlePairUnchecked(checkbox) {
        const currentRow = checkbox.closest('.field-row');
        const prevRow = currentRow.previousElementSibling;
        const nextRow = currentRow.nextElementSibling;
        if (nextRow) {
            const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
            const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
            const nextActionsCell = nextPairCell.nextElementSibling;
            nextPairCell.innerHTML = createPairCheckboxHTML(nextFieldName);
            nextActionsCell.innerHTML = removeButtonHTML;
        }
        if (prevRow) {
            const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
            const isPrevRowPairEnd = prevPairCell.querySelector('input[name*="[pair_end]"]');
            if (!isPrevRowPairEnd) {
                const hasPairCheckbox = prevPairCell.querySelector('input[name*="[pair]"]');
                if (!hasPairCheckbox) {
                    const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                    prevPairCell.innerHTML = createPairCheckboxHTML(prevFieldName);
                }
            }
        }
    }

    container.addEventListener('change', function (e) {
        if (e.target.matches('.pair-toggle-cell input[name*="[pair]"]')) {
            const checkbox = e.target;
            if (checkbox.checked) {
                handlePairChecked(checkbox);
            } else {
                handlePairUnchecked(checkbox);
            }
        }
    });


    container.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-field-button')) {
            e.preventDefault();
            const rowToRemove = e.target.closest('.field-row');
            const prevRow = rowToRemove.previousElementSibling;
            const nextRow = rowToRemove.nextElementSibling;

            const pairCheckbox = rowToRemove.querySelector('input[name*="[pair]"]');
            const wasChecked = pairCheckbox && pairCheckbox.checked;
            const hadNoCheckbox = !pairCheckbox;

            rowToRemove.remove();
            if (wasChecked) {
                if (prevRow) {
                    const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
                    if (!prevPairCell.querySelector('input[name*="[pair_end]"]')) {
                        const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                        prevPairCell.innerHTML = createPairCheckboxHTML(prevFieldName);
                    }
                }
                if (nextRow) {
                    const nextFieldName = nextRow.querySelector('.field-name-input').getAttribute('name');
                    const nextPairCell = nextRow.querySelector('.pair-toggle-cell');
                    const nextActionsCell = nextPairCell.nextElementSibling;
                    nextPairCell.innerHTML = createPairCheckboxHTML(nextFieldName);
                    nextActionsCell.innerHTML = removeButtonHTML;
                }
            } else if (hadNoCheckbox) {
                if (prevRow) { // Added safety check for prevRow
                    const prevPairCell = prevRow.querySelector('.pair-toggle-cell');
                    if (!prevPairCell.querySelector('input[name*="[pair_end]"]')) {
                        const newNextRow = prevRow.nextElementSibling;
                        let newNextRowStartsPair = false;
                        if (newNextRow) {
                            const check = newNextRow.querySelector('input[name*="[pair]"]:checked');
                            if (check) newNextRowStartsPair = true;
                        }

                        if (!newNextRowStartsPair) {
                            const prevFieldName = prevRow.querySelector('.field-name-input').getAttribute('name');
                            prevPairCell.innerHTML = createPairCheckboxHTML(prevFieldName);
                        }
                    }
                }
            }
        }
    });

    addButton.addEventListener('click', function (e) {
        e.preventDefault();
        const fieldName = 'new_' + Math.random().toString(36).substring(2, 9);
        const lastRow = container.querySelector('.field-row:last-of-type');
        let lastRowStartsPair = false;
        if (lastRow) {
            const lastPairCheckbox = lastRow.querySelector('.pair-toggle-cell input[name*="[pair]"]:checked');
            if (lastPairCheckbox) {
                lastRowStartsPair = true;
            }
        }

        const newRow = document.createElement('tr');
        newRow.className = 'field-row';
        let pairCellHtml, actionsCellHtml;

        if (lastRowStartsPair) {
            pairCellHtml = `<td class="pair-toggle-cell">Paired<input type="hidden" name="fields[${fieldName}][pair_end]" value="1"></td>`;
            actionsCellHtml = '<td></td>';
        } else {
            pairCellHtml = `<td class="pair-toggle-cell">${createPairCheckboxHTML(fieldName)}</td>`;
            actionsCellHtml = `<td>${removeButtonHTML}</td>`;
        }
        newRow.innerHTML = `
            <td><input type="text" name="${fieldName}" placeholder="New Field Name eg (Section 4 Title)" class="widefat field-name-input" value=""></td>
            <td><select name="fields[${fieldName}][type]" class="widefat">${optionsHTML}</select></td>
            ${pairCellHtml}
            ${actionsCellHtml}
        `;
        container.appendChild(newRow);
        if (lastRowStartsPair && lastRow) {
            const lastRowActionsCell = lastRow.querySelector('.pair-toggle-cell').nextElementSibling;
            if (lastRowActionsCell) {
                lastRowActionsCell.innerHTML = '';
            }
        }
    });

    fixInitialState();
});
</script>
<?php
}
function get_dibraco_custom_fields_for_context($context_name) {
    $all_custom_fields = get_option('dibraco_custom_fields', []);
    if (isset($all_custom_fields[$context_name]) && is_array($all_custom_fields[$context_name])) {
    return $all_custom_fields[$context_name];
    }
    return [];
}

function add_doc_shortcode($tag, $callback, $usage = '', $description = '') {
    add_shortcode($tag, $callback);
    register_shortcode_doc($tag, $usage, $description);
}
function initialize_colors_admin() {
    $fallbacks = [
        'primary_color' => '#3498db',
        'color1'        => '#3498db',
        'color2'        => '#2ecc71',
        'color3'        => '#e74c3c',
        'color4'        => '#2c3e50',
        'color5'        => '#ecf0f1',
        'color6'        => '#f39c12',
        'color7'        => '#95a5a6',
        'color8'        => '#bdc3c7',
    ];

    $primary_color = null;
    $colors = [];
    $avada_options = get_option('fusion_options') ?? null;

    if (!$avada_options) {
        $primary_color = $fallbacks['primary_color'];
        $colors = $fallbacks;
    } else {
        $primary_color = $avada_options['primary_color'] ?? $fallbacks['primary_color'];

        $color_palette = $avada_options['color_palette'] ?? [];

        for ($i = 1; $i <= 8; $i++) {
            $key = 'color' . $i;
            $colors[$i] = $color_palette[$key]['color'] ?? $fallbacks[$key];
        }
    }

    $my_plugin_final_colors = [
        'primary_color' => $primary_color,
        'palette_colors' => $colors,
    ];

    update_option('my_plugin_color_settings', $my_plugin_final_colors);
}
add_action('admin_init', 'initialize_colors_admin');
function initialize_global_option_filters() {
    $option_mappings = [
        'multiple_locations' => 'locations_enabled',
        'enable_locations' => 'locations_enabled',
        'locations_connector_taxonomy' => 'locations_connector_tax',
        'location_schema' => 'locations_schema',
        'delete_location_meta_data' => 'delete_locations_meta_data',
        'enable_location_custom_fields' => 'enable_locations_custom_fields',
        'service_areas' => 'service_areas_enabled',
        'main_area_post_type' => 'service_areas_post_type',
        'area_connector_tax' => 'service_areas_connector_tax',
        'main_area_schema' => 'service_areas_schema',
        'delete_area_meta_data' => 'delete_service_areas_meta_data',
        'enable_area_custom_fields' => 'enable_service_areas_custom_fields',
        'main_service' => 'main_service_enabled',
        'main_type_tax' => 'main_service_type_taxonomy',
        'second_service' => 'second_service_enabled',
        'second_type_tax' => 'second_service_type_taxonomy',
        'enable_jobs' => 'jobs_enabled',
        'job_post_type' => 'jobs_post_type',
        'job_type_tax' => 'jobs_connector_type_taxonomy',
        'jobs_posting_schema' => 'jobs_schema',
    ];

    $reverse_option_mappings = array_flip($option_mappings);

    foreach ($option_mappings as $old_option => $new_option) {
        add_filter("pre_option_{$old_option}", function ($default) use ($new_option) {
            static $is_calling = false; // Static flag to prevent recursion

            if ($is_calling) {
                return $default; 
            }

            $is_calling = true; // Set the flag
            $new_value = get_option($new_option, $default);
            $is_calling = false; 
            return $new_value !== false ? $new_value : $default;
        });
    }

    foreach ($reverse_option_mappings as $new_option => $old_option) {
        add_filter("pre_option_{$new_option}", function ($default) use ($old_option) {
            static $is_calling = false; // Static flag to prevent recursion

            if ($is_calling) {
                return $default; // Break recursion and return default
            }

            $is_calling = true; // Set the flag
            $old_value = get_option($old_option, $default);
            $is_calling = false; // Reset the flag

            return $old_value !== false ? $old_value : $default;
        });
    }
}
add_action('admin_init', 'initialize_global_option_filters');

