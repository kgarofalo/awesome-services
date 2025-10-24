<?php

function render_export_all_plugin_options_page() {
    ?>
    <div class="wrap">
        <h1>Export All Plugin Options</h1>
        <p>Click below to download all stored plugin options as a JSON file.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="export_all_plugin_options">
            <?php submit_button('Download All Options'); ?>
        </form>
    </div>
    <?php
}

function export_all_plugin_options() {
$export_data = [
'Docs' => 'these definitions sit in a “third space” — neither pure post-type registration nor pure taxonomy registration — they’re plugin-level abstractions that let you treat existing WP objects as structured contexts.',
'Fields: There are multiple fields with different defintiions, however they are classified into a few differeent groups - all fields'=>
    [
    'standard_fields' => "['text', 'textarea', 'date', 'time', 'colorpicker', 'image', 'number', 'toggle', 'select', 'radio', 'checkbox', 'wysiwyg', 'hidden', 'radioIntegers', 'checkbox_group']",
    'visual_containers' => "['visual_section', 'field_group', 'visual_group', 'visual_split'] does not impact a fields name, look for a storage flag here in the field config which indicates the name will be used for storage",
    'repeater' => "['repeater'] currently saves completely flat looking like a nested array always produces a new field called 'repeatername_row_count'",
    'functional_containers' => "['group'] not a container ever saves chained 'groupname_fieldname' ",
    'ui_only_types' => "['button', 'no-edit'] never saved though no-edit will have values they are programatic",
    ],
'company_info' => [
    'source' => "get_option('company_info')",
    'definition'=> 'stores company and organization structured data on the company as a whole this always exists as a gloabl option it reprenents the main company or organization. in cases with a main term
    company info may still define information about the organization as either a corporation(larger) or act as the organization half of the location main term below',
    'value'  => get_option('company_info', []),
    ],
'contexts' => [
    'source' => "get_option('contexts')",
    'definition' => 'settings only touched on save settings holds configurations only relevant on save settings in this are our predefined contexts, but'
        .'admin are allowed to define new "type" and "unique" contexts. ALL contexts have a post type, which is selected, a schema property, which is what they are'
        .'meant to represent in schema.org and an enabled toggle. type contexts and connector contexts also come with a primary taxonomy',
    'Enabled Toggle' => 'String 1 or 0 they are always stored enabled or disabled only user added contexts can be removed with a remove button',
    'Feature Flags' => 'Every context has a set of "fields" in the settings page that are toggles these toggles can be 1 or 0 and they define different fields each context'
        .'will have for this installation, the selection of the features is not hardcoded, they can be 1 or 0 , but the fields are hardcoded meaning each field has its own'
        .'definition sometimes as a set of fields, sometimes as sets of fields that combine some fields are found on terms, some on posts, and some are shared between between'
        .'posts and terms internally and externally this is not that complex.',
    'context_name' => 'Each context has a unique name, meaning that we define 6 but they do not all need to be used, and users can create their own all predefined contexts'
        .'are stored in a small option in wordpress options table. On the storage array the name is the array key, they are also saved with this inside the array, as well as a context type ',
    'context_type' => 'Contexts in our system come in 3 distinct kinds/flavors/types they are "connector" "type" and "unique"'
        .'Connector Contexts: Can not be added by a user and there are 2 service areas and locations.'
        .'They consist of a primary post type and a primary taxonomy each selected by an admin from existing post types and taxonomies'
        .'that means they will vary from website to website, if both are enabled locations are "parents" to service areas. If only service areas are enabled '
        .'then the location from company info owns the service areas. If only locations is enabled, the locations each represent a physical store, shop, office etc'
        .'Importantly if locations have a main term, which ties that term to the main company info location, when ignore main term is on it basically is a signal that locations are' 
        .'their own hubs and the company info location is the organization they are all a part of. if it is not ignored ',
    'value'  => get_option('contexts', []),

        ],
'enabled_connector_contexts' => [
    'source' => "get_option('enabled_connector_contexts')",
    'description' =>
        'saved and synced information specifically for connector contexts; ' .
        'a connector context is an abstraction that defines an entity both by a posttype post and a taxonomy term. ' .
        'There are 2 possible connectors: service_areas and locations. Where each post represents a term and vice versa.',
    'key definitions' =>
        'Locations connector context: will have a main term which is the other half of company info in some cases. ' .
        'It defines and represents a physical location that is often the same as the company info definition, ' .
        'only from a localBusiness perspective. In other cases when ignore_main_term is set to 1, ' .
        'it does not represent the company headquarters but rather a region. ' .
        'Service_areas: connector context that represents service areas that a service is provided to ' .
        'or served by a location OR in single location companies from company info.',
        'Notes' => 'the following data is not shown as it is a direct copy of what is stored in enabled contexts, but pre filtered and stored as an option',
],
'enabled_type_contexts' => [
    'source' => "get_option('enabled_type_contexts')",
    'definition' =>
        'saved and synced information specifically for type contexts. Each term represents a grouping of posts from this post type there is a primary post type and a primary taxonomy. ' .
        'While each post can exist within only one term terms can own many posts. the type posts relate to the connector terms as well where a connector term can have one type post per each type term if posts_per_term is 1 ' .
        'this loosely translates to 1 post from the type post type per each term to be stored on the connector term for display across the site post per term 0 means that there is no limit on the number of posts a connector ' .
        'can store but the connector can filter these terms by the 1 term they are associated with',
    'Key Concepts:' => [
        'Post-Per-Term=1' =>
            'only one post may stored on a connector taxonomy term per type term with a global fallback listed as the main post as seen below. when locations and service areas are both enabled this means that the global main fallback is stored on each location and each service area has a fallback of their area parent location term. if the area parent location term does not have a post itself, it will inherit the global fallback from the location term where it is also stored',
        'post-per-term not 1' =>
            'this concept means that while posts are still connected to a single type term, the way they are stored on a location or service area term is not limited to just 1 post however this does allow these type posts to still be listed and filtered from each location or service area term. because of their nature fallbacks are not needed on these type posts'
          ],
     'Notes' => 'the following data is not shown as it is a direct copy of what is stored in enabled contexts, but pre filtered and stored as an option',
    ],
        'enabled_unique_contexts' => [
            'source' => "get_option('enabled_unique_contexts')",
            'definition'=> 'saved and synced information specifically for unique contexts, defined by a post type this does not have a primary taxonomy only a primary post type the connector'
            .'can show as many as are assigned to this context',
            'Notes' => 'the following data is not shown as it is a direct copy of what is stored in enabled contexts, but pre filtered and stored as an option',
        ],
        'enabled_context_names' => [
            'source' => "get_option('enabled_context_names')",
            'definition'=> 'quick names lookup to see what is enabled for special rules for some contexts',
            'value'  => get_option('enabled_context_names', []),
        ],
        'enabled_contexts' => [
            'source' => "get_option('enabled_contexts')",
            'definition'=> 'all contexts in a single option synced up with the rest during settings save
            all feature flags taxonomies and post types will exist in slug term here',
            'value'  => get_option('enabled_contexts', []),
        ],
    ];

   $enabled_contexts = get_option('enabled_contexts');
    $main_post_maps = [];
    foreach ($enabled_contexts as $context_name => $context_data) {
        if ($context_data['context_type'] === 'type' && $context_data['post_per_term'] === "1") {
            $main_post_maps["{$context_name}_main_posts"] = [
                'source' => 'get_option({$context_name}_main_posts)',
                'value'  => get_option("{$context_name}_main_posts", []),
                'definition' => 'for type contexts that are post per term 1 this is a list of the top level of these posts, which can'
                .'may reside on page or as posts tied to the main location'
            ];
        }
    }
    if (!empty($main_post_maps)) {
        $export_data['main_post_maps'] = $main_post_maps;
    }
    
$relationships = [];
$enabled_names =get_option('enabled_context_names', []);
if (in_array('locations', $enabled_names, true)) {
    $locations_context = $export_data['enabled_contexts']['value']['locations'];
    $taxonomy = $locations_context['taxonomy'];
    $term_ids = get_terms(['taxonomy' => $taxonomy, 'fields' => 'ids']);
    foreach ($term_ids as $term_id) {
        $meta = get_term_meta($term_id, '', false);
        foreach ($meta as $field_name => $storage_array) {
            $meta[$field_name] = maybe_unserialize($storage_array[0]);
        }
        $relationships['locations']['entities'][$term_id] = $meta;
    }
}

if (in_array('service_areas', $enabled_names, true)) {
    $service_areas_context = $export_data['enabled_contexts']['value']['service_areas'];
    $taxonomy = $service_areas_context['taxonomy'];
    $term_ids = get_terms(['taxonomy' => $taxonomy, 'fields' => 'ids']);
    foreach ($term_ids as $term_id) {
        $meta = get_term_meta($term_id, '', false);
        foreach ($meta as $field_name => $storage_array) {
            $meta[$field_name] = maybe_unserialize($storage_array[0]);
        }
        $relationships['service_areas']['entities'][$term_id] = $meta;
    }
}

$export_data['relationships'] = $relationships;

    if (in_array('locations', $enabled_names, true) && in_array('service_areas', $enabled_names, true)) {
        $export_data['act_to_lct_assignments'] = [
            'rules' => 'First verify through get_option(enabled_context_names) or get_option(locations_areas_status) which must return both provided that this is ' 
            .'true the followiing will exist.',
            'sources' => 'get_option(act_to_lct_assignments) as well as on each term from what was assigned as service areas or locations ',
            'definition' => 'exists when both contexts, location and service_areas are enabled. This ties, through '
            .'terms only, location and service_area entities with service areas being the child to their parent locations.'
            .'Importantly, this can only exist when locations and service area contexts are both enabled.',
            'To Derive taxonomies linked' => 'once you have received get_option(enabled_contexts) or get_option(enabled)connector contexts.'
            .' they are avaialable at enabled_contexts[locations][taxonomy]  enabled_contexts[service_areas][taxonomy]'
            .'OR enabled_connector_contexts[locations][taxonomy] enabled_connector_contexts[service_areas][taxonomy]',
            'How To Read The Output?' => 'a service area term is on the left a location term is on the right',
            'value'  => get_option('act_to_lct_assignments', []),
            
        ];
        $export_data['act_to_lct_slug_assignments'] = [
            'source' => "get_option('act_to_lct_slug_assignments')",
            'value'  => get_option('act_to_lct_slug_assignments', []),
            'definition' => 'slug version of the act_to_lct assignments, whcih are term ids'
        ];
    }

    $filename = 'all-plugin-options-' . gmdate('Ymd-His') . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo wp_json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

add_action('admin_post_export_all_plugin_options', 'export_all_plugin_options');