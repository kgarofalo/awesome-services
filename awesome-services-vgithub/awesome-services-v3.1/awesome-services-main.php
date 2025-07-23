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
require_once AWESOME_SERVICES_PATH . 'post-types/type-post-type-fields.php';
require_once AWESOME_SERVICES_PATH . 'includes/schema.php';
require_once AWESOME_SERVICES_PATH . 'terms/main-type-term-fields.php';
require_once AWESOME_SERVICES_PATH . 'terms/main-area-tax-terms.php';
require_once AWESOME_SERVICES_PATH . 'related/relational.php';
require_once AWESOME_SERVICES_PATH . 'includes/da_old-functions.php';
require_once AWESOME_SERVICES_PATH . 'includes/kmlgenerator.php';


function include_all_dynamic_files_from_uploads() {
    $uploads_dir = wp_upload_dir();
    $custom_dir = $uploads_dir['basedir'] . '/awesome-services';

    // Ensure the directory exists only if it doesn't exist
    if (!file_exists($custom_dir)) {
        if (!wp_mkdir_p($custom_dir)) {
            // If the directory creation fails, exit the function
            return;
        }
    }

}

add_action('plugins_loaded', 'include_all_dynamic_files_from_uploads', 2);
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
    'https://gitlab.com/dibraco/awesome-services/',
    __FILE__, 
    'awesome-services'
);
$myUpdateChecker->setBranch('main');
$myUpdateChecker->setAuthentication('glpat-yz8nzm_DT-Txy8-zL3Kt');



function myplugin_enqueue_admin_scripts($hook_suffix) {
        $screen = get_current_screen();
    if (isset($_GET['post']) && 'page' === get_post_type($_GET['post'])) {
        $enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', '');
        if ($enable_custom_fields_for_pages_value === "1") {
            wp_enqueue_style('common-fields-style', AWESOME_SERVICES_URL . 'css/da-common-fields.css');
        }
    }
    if ($hook_suffix === 'relationships_page_company-info') {
        wp_enqueue_style('locations-company-info-style', AWESOME_SERVICES_URL . 'css/locations_company_info.css');
        wp_enqueue_script('awesome-hours-of-operation-script', AWESOME_SERVICES_URL . 'js/da-hours-operation.js', ['jquery'], false, true);
    }
    $enabled_post_types = [];
    $enabled_taxonomies = [];
    $locations_post_type = '';
    $locations_connector_tax = '';
    $jobs_post_type = '';
    $should_enqueue_shared = false;
    $enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', '');
 $is_plugin_settings_page = (
        $hook_suffix === 'toplevel_page_relationships' || 
        str_starts_with($hook_suffix, 'relationships_page_')
    );

$enabled_contexts = get_option('enabled_contexts') ?? false;
if ($enabled_contexts){
    foreach ($enabled_contexts as $context_name => $context_data) {
        $context_type = $context_data['context_type'];
        $name = $context_data['context_name'];
        $enabled_post_types[] = $context_data['post_type']; 
       if ($context_type !== 'unique'){
           $enabled_taxonomies[] = $context_data['taxonomy'];
           if ($context_type === 'type') {
                 if($context_data['post_per_term'] === "1"){
                     $taxonomy_name = $context_data['taxonomy'];
                    $post_type_for_this_context = $context_data['post_type'];
                     $term_main_map = [];
                        $terms = get_terms([
                            'taxonomy' => $taxonomy_name,
                            'hide_empty' => false,
                            'object_type' => [$post_type_for_this_context]
                        ]);
                        if (!is_wp_error($terms) && !empty($terms)) {
                            foreach ($terms as $term) {
                                $main_post_id = get_term_meta($term->term_id, 'main_post_for_term', true);
                                $term_main_map[$term->term_id] = (int) $main_post_id;
                            }
                  $taxonomy_term_maps[$taxonomy_name] = $term_main_map;
                        }
                    }
                }
            }
        if ($name === 'locations') {
            $locations_post_type = $context_data['post_type'];
            $locations_connector_tax = $context_data['taxonomy'];
        }
        if ($name === 'service_areas') {
            $service_areas_taxonomy = $context_data['taxonomy'];
            if (('edit-tags.php' === $hook_suffix) && isset($_GET['taxonomy']) && $_GET['taxonomy'] === $service_areas_taxonomy) {
                wp_enqueue_script( 'dibraco-term-quickedit', AWESOME_SERVICES_URL . 'js/term-quickedit.js', ['jquery', 'inline-edit-tax'], '1.0.1', true );
                wp_localize_script( 'dibraco-term-quickedit', 'dibraco_qe_data', [ 'meta_key' => 'area_parent_location_term']);

        }
        }
        if ($name === 'jobs') {
            $jobs_post_type = $context_data['post_type'];
        }
        if (( in_array($hook_suffix, ['post.php','post-new.php'], true) && $screen->post_type === $locations_post_type )|| (($hook_suffix === 'term.php') &&  ($_GET['taxonomy'])===($locations_connector_tax))) {
           wp_enqueue_style('locations-company-info-style', AWESOME_SERVICES_URL . 'css/locations_company_info.css');
           wp_enqueue_script('awesome-hours-of-operation-script', AWESOME_SERVICES_URL . 'js/da-hours-operation.js', ['jquery'], false, true);
        }
        if ( in_array($hook_suffix, ['post.php','post-new.php'], true)  && $screen->post_type === $jobs_post_type){
        wp_enqueue_style('job-postings-style', AWESOME_SERVICES_URL . 'css/da-job-postings.css');
        }
    }}

   
$is_enabled_post_type = in_array( $screen->post_type ?? ($_GET['post_type'] ?? ''),$enabled_post_types,true);
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

    if ($is_enabled_post_type && $is_all_posts_screen) {
       wp_enqueue_script('quick-edit-taxonomy-script', AWESOME_SERVICES_URL . 'js/da-quickedits.js', ['jquery'], null, true);
       if (!empty($taxonomy_term_maps)) {
       wp_localize_script('quick-edit-taxonomy-script', 'quickEditData', [
          'post_type'    => $screen->post_type,
          'taxonomy'     => $screen->taxonomy ?? '',
          'taxonomy_term_maps' => $taxonomy_term_maps,
          'context_name' => '',
      ]);
       }
    }
    if ($hook_suffix === 'toplevel_page_relationships') {
        wp_enqueue_script('da-relationship-settings-script', AWESOME_SERVICES_URL . 'js/da-relationship-settings.js', [], false, true);
    }

}
add_action('admin_enqueue_scripts', 'myplugin_enqueue_admin_scripts', 20);

    
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




function awesome_register_main_settings_menu() {
    add_menu_page(
        'Relationship Configuration',
        'Relationships',
        'manage_options',
        'relationships',
        'render_relationships_settings_page',
        'dashicons-admin-generic',
        2
    );
     add_submenu_page(
        'relationships',       
        'Custom Fields',         
        'Custom Fields',          
        'manage_options',         
        'da-custom-fields',      
        'da_render_custom_fields_page' 
    );

    add_submenu_page(
        'relationships',
        'Company Info',
        'Company Info',
        'manage_options',
        'company-info',
        'company_info_options_page'
    );
        add_submenu_page(
            'relationships', 
            'GMB Integrator Settings',
            'GMB Integrator',
            'manage_options',
            'dibraco-gmb-integrator',
        [DIBRACO_GMB_Integrator::instance(), 'settings_page_html']
        );
    
    add_submenu_page(
        'relationships',
        'Main Posts Overview',
        'Main Posts',
        'manage_options',
        'dibraco-main-posts',
        'render_dibraco_main_posts_screen'
        );

    add_submenu_page(
       'relationships',
       'Social Media',
       'Social Media Properties',
       'manage_options',
       'social-media',
       'render_social_media_options_page'
       );
    add_submenu_page(
        'relationships',
        'Export Terms',
        'Export Terms',
        'manage_options',
        'export-terms',
        'render_export_entities_page'
    );


    add_submenu_page(
        'relationships',
        'Migrate Legacy Options',
        'Migrate Options',
        'manage_options',
        'migrate-options',
        'render_migration_page'
    );

 
}
add_action('admin_menu', 'awesome_register_main_settings_menu', 10);

function dibraco_extract_field_names_recursive(): void {
    $fields_blueprint = get_repeater_field_list();

    $processed_keys = [];

    $recursive_extractor = 
        function(array $fields, string $container_key = '', string $container_type = '', string $prefix = '') 
        use (&$recursive_extractor, &$processed_keys): void 
    {
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
                        $recursive_extractor($config['fields'], $container_key, 'repeater', $new_prefix);
                    } else {
                        $new_container_key = $container_key ? "{$container_key}_{$key}" : $key;
                        $recursive_extractor($config['fields'], $new_container_key, 'group');
                    }
                } elseif ($type === 'repeater') {
                    $repeater_base_name = $container_key ? "{$container_key}_{$key}" : $key;
                    $processed_keys[] = "{$repeater_base_name}_row_count";
                    
                    $repeater_container_key = "{$repeater_base_name}[0]";
                    $recursive_extractor($config['fields'], $repeater_container_key, 'repeater');
                }

            } elseif (in_array($type, $visual_containers)) {
                $recursive_extractor($config['fields'], $container_key, $container_type, $prefix);
            }
        }
    };

    $recursive_extractor($fields_blueprint);

}

add_action('admin_init', 'dibraco_extract_field_names_recursive', 10);
function render_export_entities_page() {
    $all_contexts = get_option('enabled_context_names', []);
    ?>
    <div class="wrap">
        <h1><?php _e('Export Entities', 'awesome-services'); ?></h1>
        <p><?php _e('This tool exports the full data entity for any selected context. For connectors and types, it exports term data with associated post(s). For unique contexts, it exports post data directly.', 'awesome-services'); ?></p>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('export_entities_action', 'export_entities_nonce'); ?>
            <input type="hidden" name="action" value="export_entities_data">
            <hr>
            <h3><?php _e('Global Data', 'awesome-services'); ?></h3>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th>
                            <label>
                                <input type="checkbox" name="export_company_info" value="1">
                                <?php _e('Company Information', 'awesome-services'); ?> <em style="color:#777;">(from get_option(\'company_info\'))</em>
                            </label>
                        </th>
                    </tr>
                </tbody>
            </table>

            <hr>
            <h3><?php _e('Context Data', 'awesome-services'); ?></h3>
            <p><?php _e('Select which contexts to export:', 'awesome-services'); ?></p>
            <table class="form-table">
                <tbody>
                    <?php foreach ($all_contexts as $context_name) :
                        $label = esc_html(ucfirst(str_replace('_', ' ', $context_name)));
                    ?>
                        <tr>
                            <th>
                                <label>
   <input type="checkbox" name="contexts[]" value="<?php echo esc_attr($context_name); ?>">
                                        <?php echo $label; ?>                                </label>
                            </th>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
    $sections = initialize_dafields('location_');
    $term_meta_keys = [];

    foreach ($sections as $section_key => $section_data) {
        if ($section_key === 'hours_of_operation') {
            $term_meta_keys[] = 'hours_of_operation'; // just this
        } elseif ($section_key === 'social_media') {
            $term_meta_keys[] = 'social_media'; // just this
        } else {
            $term_meta_keys = array_merge($term_meta_keys, array_keys($section_data));
        }
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
foreach (array_unique($term_meta_keys) as $meta_key) {
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
        $raw_job_meta = get_post_meta($post_id, '_job_meta', true);

        if (!empty($raw_job_meta) && is_array($raw_job_meta)) {
            $processed_job_meta = $raw_job_meta;
            $job_field_structure = da_get_job_fields();

            unset($processed_job_meta['addresses']);

            foreach ($job_field_structure as $section) {
                foreach ($section['fields'] as $key => $config) {
                    if ($key === 'addresses') {
                        continue;
                    }

                    $type = $config['type'];

                    if ($type === 'group') {
                        $processed_job_meta[$key] = [];
                        foreach ($config['fields'] as $group_key => $group_config) {
                            $flat_key = "{$key}_{$group_key}";
                            if (isset($raw_job_meta[$flat_key])) {
                                $processed_job_meta[$key][$group_key] = $raw_job_meta[$flat_key];
                                unset($processed_job_meta[$flat_key]);
                            }
                        }
                    } elseif ($type === 'repeater') {
                        $row_count_key = "{$key}_row_count";
                        $row_count = (int)$raw_job_meta[$row_count_key];

                        $processed_job_meta[$key] = [];
                        
                        $repeater_sub_field_name = array_keys($config['fields'])[0];

                        for ($i = 0; $i < $row_count; $i++) {
                            $row_data = [];
                            $flat_key = "{$key}[{$i}][{$repeater_sub_field_name}]";

                            if ($i === 0) {
                                $row_data[$repeater_sub_field_name] = $raw_job_meta[$flat_key];
                            } else {
                                $row_data[$repeater_sub_field_name] = isset($raw_job_meta[$flat_key]) ? $raw_job_meta[$flat_key] : '';
                            }
                            
                            unset($processed_job_meta[$flat_key]);
                            
                            $processed_job_meta[$key][] = $row_data;
                        }
                    } else {
                        if (isset($raw_job_meta[$key])) {
                            $processed_job_meta[$key] = $raw_job_meta[$key];
                        }
                    }
                }
            }
            $post_meta_data['_job_meta'] = $processed_job_meta;
        } else {
            $post_meta_data['_job_meta'] = [];
        }
    }
    return [
        'post_id'    => $post->ID,
        'post_title' => $post->post_title,
        'post_url'   => get_permalink($post->ID),
        'post_meta'  => $post_meta_data,
    ];
}


function da_render_custom_fields_page() {
     if (isset($_POST['da_save_custom_fields_nonce']) && wp_verify_nonce($_POST['da_save_custom_fields_nonce'], 'da_save_custom_fields_action')) {
        $all_custom_fields = get_option('dibraco_custom_fields', []);
        $context_to_save = sanitize_text_field($_POST['context_selection']);
        $new_fields_for_context = [];
        if (!empty($_POST['fields'])) {
            foreach ($_POST['fields'] as $field_data) {
                if (empty($field_data['label'])) continue;

                $label = sanitize_text_field($field_data['label']);

                $allowed_types = ['text', 'textarea', 'wysiwyg'];
                $submitted_type = isset($field_data['type']) ? $field_data['type'] : 'text';
                $field_type = in_array($submitted_type, $allowed_types) ? $submitted_type : 'text';

                $slug = sanitize_title($label);
                $slug_with_underscores = str_replace('-', '_', $slug);
                $field_name = 'da_' . $slug_with_underscores; 

                $new_fields_for_context[$field_name] = [
                    'type'  => $field_type,
                ];
            }
        }
        $all_custom_fields[$context_to_save] = $new_fields_for_context;
        update_option('dibraco_custom_fields', $all_custom_fields);
        echo '<div class="notice notice-success is-dismissible"><p>Custom fields saved successfully!</p></div>';
    }
    $enabled_contexts = get_option('enabled_contexts', []);
    $enable_custom_fields_for_pages_value = get_option('enable_custom_fields_for_pages', '');
    if ($enable_custom_fields_for_pages_value === "1") {
        $enabled_contexts['page'] = 'page'; 
    }
    $all_custom_fields = get_option('dibraco_custom_fields', []);
    $current_context = $_GET['context'] ?? key($enabled_contexts);
    $current_fields = $all_custom_fields[$current_context] ?? [];
    ?>

    <div class="wrap">
        <h1>Custom Field Editor</h1>
        <p>Define additional fields for your contexts. These will be added to the main metabox for that context's post type.</p>
        <div style="margin-top: 20px;">
            <label for="context_selector" style="font-weight:bold; font-size:1.2em;">Editing fields for context:</label>
            <select id="context_selector" onchange="window.location.href='?page=da-custom-fields&context=' + this.value;">
                <?php
                foreach (array_keys($enabled_contexts) as $context_name) : ?>
                    <option value="<?php echo esc_attr($context_name); ?>" <?php selected($current_context, $context_name); ?>>
                        <?php echo esc_html(ucwords(str_replace('_', ' ', $context_name))); ?>
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
                        <th style="width: 45%;">Field Label</th>
                        <th style="width: 45%;">Field Type</th>
                        <th style="width: 10%;">Actions</th>
                    </tr>
                </thead>
                <tbody id="fields-repeater-container">
                    <?php if (!empty($current_fields)) : ?>
                        <?php $field_index = 0; ?>
                        <?php foreach ($current_fields as $field_name => $config) : ?>
                            <?php
                                $display_label = ucwords(str_replace('_', ' ', substr($field_name, 3)));
                                $type = (is_array($config) && isset($config['type'])) ? $config['type'] : 'text';
                            ?>
                            <tr class="field-row">
                                <td><input type="text" name="fields[<?php echo $field_index; ?>][label]" class="widefat" value="<?php echo esc_attr($display_label); ?>"></td>
                                <td>
                                    <select name="fields[<?php echo $field_index; ?>][type]" class="widefat">
                                        <option value="text" <?php selected($type, 'text'); ?>>Text Input</option>
                                        <option value="textarea" <?php selected($type, 'textarea'); ?>>Text Area</option>
                                        <option value="wysiwyg" <?php selected($type, 'wysiwyg'); ?>>WYSIWYG Editor</option>
                                    </select>
                                </td>
                                <td><a href="#" class="button button-danger remove-field-button">Remove</a></td>
                            </tr>
                            <?php $field_index++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="button" class="button" id="add-field-button" style="margin-top:10px;">+ Add Field</button>
            <?php submit_button('Save Custom Fields'); ?>
        </form>
    </div>
    <script type="text/template" id="field-row-template">
        <tr class="field-row">
            <td><input type="text" name="fields[][label]" class="widefat" placeholder="e.g., Section 4 Title" value=""></td>
            <td>
                <select name="fields[][type]" class="widefat">
                    <option value="text">Text Input</option>
                    <option value="textarea">Text Area</option>
                    <option value="wysiwyg">WYSIWYG Editor</option>
                </select>
            </td>
            <td><a href="#" class="button button-danger remove-field-button">Remove</a></td>
        </tr>
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('fields-repeater-container');
            const addButton = document.getElementById('add-field-button');
            const template = document.getElementById('field-row-template');

            if (!container || !addButton || !template) {
                return;
            }

            let fieldIndex = container.getElementsByClassName('field-row').length;
            addButton.addEventListener('click', function () {
                const newRowHtml = template.innerHTML.replace(/\[\]/g, '[' + fieldIndex + ']');
                container.insertAdjacentHTML('beforeend', newRowHtml);
                fieldIndex++;
            });

            // Event listener for removing fields using event delegation
            container.addEventListener('click', function (e) {
                if (e.target.classList.contains('remove-field-button')) {
                    e.preventDefault(); // Prevent default link behavior
                    e.target.closest('tr.field-row').remove(); // Remove the parent row
                }
            });
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

