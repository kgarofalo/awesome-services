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
require_once AWESOME_SERVICES_PATH . 'field-generation/name-phone-address.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/value-populator.php';
require_once AWESOME_SERVICES_PATH . 'main-settings/plugin-relationship-settings.php';
require_once AWESOME_SERVICES_PATH . 'main-settings/company-info-options.php';
require_once AWESOME_SERVICES_PATH . 'main-settings/shortcode-list.php';
require_once AWESOME_SERVICES_PATH . 'includes/cardstylesnew.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/form-helper.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/da-hours-operation.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/da-social-media-fields.php';
require_once AWESOME_SERVICES_PATH . 'field-generation/form-processor.php';
require_once AWESOME_SERVICES_PATH . 'includes/card-front-end.php';
require_once AWESOME_SERVICES_PATH . 'post-types/multiple-location-term-setup.php';
require_once AWESOME_SERVICES_PATH . 'terms/job-benefits.php';
require_once AWESOME_SERVICES_PATH . 'post-types/job-postings.php';
require_once AWESOME_SERVICES_PATH . 'includes/custom_field_creation.php';
require_once AWESOME_SERVICES_PATH . 'includes/acf_field_migration.php';
require_once AWESOME_SERVICES_PATH . 'post-types/dibraco-relationships-main-posts.php';
require_once AWESOME_SERVICES_PATH . 'post-types/type-post-type-fields.php';
require_once AWESOME_SERVICES_PATH . 'includes/schema.php';
require_once AWESOME_SERVICES_PATH . 'includes/export_entity.php';
require_once AWESOME_SERVICES_PATH . 'includes/export_all_plugin_options.php';
require_once AWESOME_SERVICES_PATH . 'terms/main-type-term-fields.php';
require_once AWESOME_SERVICES_PATH . 'terms/main-area-tax-terms.php';
require_once AWESOME_SERVICES_PATH . 'related/relational.php';
require_once AWESOME_SERVICES_PATH . 'includes/da_old-functions.php';
require_once AWESOME_SERVICES_PATH . 'includes/kmlgenerator.php';

function dibraco_enqueue_admin_scripts($hook_suffix) {

    $screen       = get_current_screen();
    $current_page = $_GET['page'] ?? '';
    $main_slug    = 'dibraco-relationships';
    $is_settings_root = ($current_page === $main_slug);
    $is_company_info  = ($hook_suffix === 'relationships_page_' . $main_slug . '-company-info');
    $is_settings_any  = ($hook_suffix === 'toplevel_page_' . $main_slug || str_starts_with($hook_suffix, 'relationships_page_' . $main_slug));
   
    if ($is_settings_root) {
        wp_enqueue_script('da-relationship-settings-script', AWESOME_SERVICES_URL . 'js/da-relationship-settings.js', [], false, true);
        wp_enqueue_style('dibraco-relationships-css', AWESOME_SERVICES_URL . 'css/da-relationships.css', [], null, 'all');
    }

    if ($is_company_info) { 
        wp_enqueue_style('locations-company-info-style', AWESOME_SERVICES_URL . 'css/locations_company_info.css');
        wp_enqueue_script('awesome-hours-of-operation-script', AWESOME_SERVICES_URL . 'js/da-hours-operation.js', ['jquery'], false, true);
    }

    if ($is_settings_root || $is_company_info || $is_settings_any) {
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_style('jquery-ui-theme');
        wp_enqueue_script('media-views');
        wp_enqueue_script('jquery-ui-slider');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script('postbox');
        wp_enqueue_script('media-editor');
        wp_enqueue_script('wp-util');
        wp_enqueue_media();
        wp_enqueue_editor();
        wp_enqueue_style('common-fields-style', AWESOME_SERVICES_URL . 'css/da-common-fields.css');
        wp_enqueue_script('da-color-picker2', AWESOME_SERVICES_URL . 'js/da-color-picker2.js', ['jquery', 'wp-color-picker', 'jquery-ui-slider'], false, true);
        wp_enqueue_script('da-repeater', AWESOME_SERVICES_URL . 'js/da-repeater.js', ['jquery'], false, true);
        wp_enqueue_script('media-fields-images-terms-script', AWESOME_SERVICES_URL . 'js/da-media-fields-images-terms.js', ['jquery', 'media-views', 'media-editor'], null, true);
        wp_enqueue_script('da-conditional-fields', AWESOME_SERVICES_URL . 'js/da-conditional-fields.js', ['jquery'], false, true);
    }
     
    if (strpos($hook_suffix, 'card-styles-') !== false) {
        wp_enqueue_style('dibraco-card-styles-admin-css', AWESOME_SERVICES_URL . 'css/da-card-styles-admin.css', [], null, 'all');
        wp_enqueue_script('da-color-picker2', AWESOME_SERVICES_URL . 'js/da-color-picker2.js', ['jquery', 'wp-color-picker', 'jquery-ui-slider'], false, true);
        wp_enqueue_style('common-fields-style', AWESOME_SERVICES_URL . 'css/da-common-fields.css');
        wp_enqueue_script('da-conditional-fields', AWESOME_SERVICES_URL . 'js/da-conditional-fields.js', ['jquery'], false, true);
        return;
    }
}

add_action('admin_enqueue_scripts', 'dibraco_enqueue_admin_scripts', 20);


function awesome_register_main_settings_menu() {

    $slug = 'dibraco-relationships';

    add_menu_page('Relationship Configuration','Relationships','manage_options', $slug, 'render_relationships_settings_page', 'dashicons-admin-generic', 2);
    add_submenu_page($slug, 'Company Info', 'Company Info', 'manage_options', "$slug-company-info", 'company_info_options_page');
    add_submenu_page($slug, 'Social Media', 'Social Media Properties', 'manage_options', 'social-media', 'render_social_media_options_page');
    add_submenu_page($slug, 'Migrate Legacy Options', 'Migrate Options', 'manage_options', 'migrate-options', 'render_migration_page');
    add_submenu_page($slug, 'Export Some Options', 'Export Some Options', 'manage_options', 'export-all-plugin-options', 'render_export_all_plugin_options_page');
    $enabled_context_names = get_option('enabled_context_names');
    if (!$enabled_context_names) {
        return;
    }
    $enabled_contexts = get_option('enabled_contexts');
    add_submenu_page($slug, 'ACF Migration', 'ACF Migration', 'manage_options', "{$slug}-acf-migrator", 'dibraco_relationships_migrate_acf');
    add_submenu_page($slug, 'Custom Fields', 'Custom Fields', 'manage_options', "$slug-custom-fields", 'da_render_custom_fields_page');
    add_submenu_page($slug, 'Export Terms', 'Export Terms', 'manage_options', 'export-terms', 'render_export_entities_page');
   
    if (in_array('locations', $enabled_context_names)) {
        add_submenu_page($slug, 'KML Map Generator', 'KML Generator', 'manage_options', "$slug-kml-generator", 'render_kml_generator_page');
    }
     add_submenu_page($slug, 'Card Styles Selection', 'Card Styles Selection', 'manage_options', "{$slug}-card-styles-selection", 'render_card_styles_selection_page');
    if (in_array('jobs', $enabled_context_names)) {
            error_log('The condition was met: "jobs" context is enabled.');
            $jobs_post_type = $enabled_contexts['jobs']['post_type'];
        if (empty(get_option('global_job_benefits'))) {
                $default_benefit_field_names = ['health_benefits', 'paid_time_off', 'retirement_plan', 'flexible_schedule', '401_k', 'tuition_reimbursement'];
                update_option('global_job_benefits', $default_benefit_field_names);
            }
            add_submenu_page($slug, 'Manage Job Benefits', 'Job Benefits', 'manage_options', 'manage-global-benefits', 'render_global_benefits_page' );
            add_submenu_page("edit.php?post_type={$jobs_post_type}", 'Manage Job Benefits', 'Job Benefits', 'manage_options', 'manage-global-benefits', 'render_global_benefits_page');
        }

    foreach ($enabled_contexts as $context_data) {
        if (($context_data['context_type'] === 'type') && ($context_data['post_per_term'] === '1')) {
            add_submenu_page($slug, 'Main Posts Overview', 'Main Posts', 'manage_options', "$slug-main-posts", 'render_dibraco_main_posts_screen');
            break;
        } 
    }
  $selected_contexts = get_option('selected_contexts', []);
       if (!empty($selected_contexts)) {
        foreach ($selected_contexts as $selected_context_name) {
            $base_name = str_replace('_', '-', $selected_context_name);
            
            $context_data = $enabled_contexts[$selected_context_name];
            $menu_slug = "card-styles-{$base_name}";
            $saved_settings = get_option("{$selected_context_name}_card_styles");
            $label = ucwords(str_replace('-', ' ', $menu_slug));
            add_submenu_page($slug, $label, $label, 'manage_options', $menu_slug,
                function () use ($saved_settings, $context_data, $selected_context_name) {
                    dibraco_awesome_render_card_settings_page($saved_settings, $context_data, $selected_context_name);
                }
            );
        }
    }
}
add_action('admin_menu', 'awesome_register_main_settings_menu');

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
            static $is_calling = false; 
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
            static $is_calling = false; 

            if ($is_calling) {
                return $default; 
            }

            $is_calling = true; // Set the flag
            $old_value = get_option($old_option, $default);
            $is_calling = false; 
            return $old_value !== false ? $old_value : $default;
        });
    }
}
add_action('admin_init', 'initialize_global_option_filters');

