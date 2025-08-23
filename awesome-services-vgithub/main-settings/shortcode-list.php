<?php
/**
 * Renders a documentation page listing all registered shortcodes from the plugin.
 */

// Add the new documentation page to the admin menu
function dibraco_register_shortcode_docs_page() {
    add_submenu_page(
        'relationships',               
        'Shortcode Reference',        
        'Shortcode Reference',       
        'manage_options',          
        'dibraco-shortcode-docs',      
        'dibraco_render_shortcode_docs_page' 
    );
}
add_action('admin_menu', 'dibraco_register_shortcode_docs_page', 150);


// Callback function to render the page content
function dibraco_render_shortcode_docs_page() {
    global $shortcode_tags;

    $plugin_shortcodes = dibraco_get_plugin_shortcodes();

    ?>
    <div class="wrap">
        <h1>Plugin Shortcode Reference</h1>
        <p>This page lists all the shortcodes registered by this plugin. Note that some shortcodes require specific attributes to function correctly.</p>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th style="width: 25%;">Shortcode</th>
                    <th style="width: 35%;">Attributes</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($plugin_shortcodes)): ?>
                    <tr>
                        <td colspan="3">No plugin-specific shortcodes were found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($plugin_shortcodes as $tag => $details): ?>
                        <tr>
                            <td><code>[<?php echo esc_html($tag); ?>]</code></td>
                            <td><pre><?php echo esc_html($details['attributes']); ?></pre></td>
                            <td><?php echo esc_html($details['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function dibraco_get_plugin_shortcodes() {
    $shortcodes = [
        'current_year' => [
            'attributes' => 'None',
            'description' => 'Displays the current 4-digit year.'
        ],
        'company_info_*' => [
            'attributes' => 'None',
            'description' => 'Dynamically created for each field in company info (e.g., [company_info_name], [company_info_phone_number]).'
        ],
        'arf' => [
            'attributes' => "field, tax, subfield, type, pt, options",
            'description' => 'A legacy shortcode for fetching related ACF fields. Likely deprecated.'
        ],
        'service_areas' => [
            'attributes' => 'mode="comma" or "list"',
            'description' => 'Displays a list of service areas or locations based on the current plugin status.'
        ],
        '*_link' => [
            'attributes' => 'loc="location-slug"',
            'description' => 'Dynamically created for each social media field (e.g., [facebook_link], [twitter_link]). Shows the URL for a specific location if `loc` is provided, otherwise defaults to the main company URL.'
        ],
        'da_map_embed' => [
            'attributes' => 'loc="location-slug"',
            'description' => 'Displays the Google Map embed for a specific location or the main company.'
        ],
        'da_reviews' => [
            'attributes' => 'loc="location-slug"',
            'description' => 'Displays the reviews shortcode for a specific location or the main company.'
        ],
        'da_opening_hours' => [
            'attributes' => 'loc="location-slug", output="ranges" or "list"',
            'description' => 'Displays the opening hours for a specific location or the main company.'
        ],
        'da_logo_url' => [
            'attributes' => 'loc="location-slug"',
            'description' => 'Returns the URL of the logo for a specific location or the main company.'
        ],
        'da_address' => [
            'attributes' => 'loc="location-slug", country="yes"',
            'description' => 'Displays a formatted address. Shows country if `country` attribute is "yes".'
        ],
        'telephonelink' => [
            'attributes' => 'loc="location-slug"',
            'description' => 'Displays a clickable telephone link.'
        ],
         'telephoneonly' => [
            'attributes' => 'loc="location-slug"',
            'description' => 'Displays only the formatted telephone number text (not a link).'
        ],
        'telephonelinkonly' => [
            'attributes' => 'loc="location-slug"',
            'description' => 'Returns only the `tel:` URL for a phone number.'
        ],
        'da_email' => [
            'attributes' => 'type="link" or "text", loc="location-slug"',
            'description' => 'Displays an email address as either a clickable link or plain text.'
        ],
        'dibraco_location_map' => [
            'attributes' => 'view="normal", "street", or "satellite", loc="location-slug"',
            'description' => 'Displays an interactive Google Map based on location data.'
        ],
        'display_cards' => [
            'attributes' => 'context="context_name", variant="0"',
            'description' => 'The main shortcode to display dynamically styled cards for any enabled context (e.g., "main_service").'
        ],
        'da_related_posts_list' => [
            'attributes' => 'context="context_name", bullets="yes" or "no"',
            'description' => 'Displays a simple `<ul>` list of posts from a specified context.'
        ],
        'da_related_list_comma' => [
            'attributes' => 'context="context_name", separator=","',
            'description' => 'Displays a comma-separated list of linked posts from a context.'
        ],
        'da_related_photo_hover' => [
            'attributes' => 'context="context_name"',
            'description' => 'Displays a grid of linked featured images from a context with a hover effect.'
        ],

        'filtered_locations_list' => [
            'attributes' => 'config_id="id" OR service="slug"',
            'description' => 'Displays a grid of location cards, filtered either by a saved KML map configuration or by taxonomy terms.'
        ],
        'my_locations_list' => [
            'attributes' => 'orderby="name", order="ASC", hide_empty="false"',
            'description' => 'Displays a grid of all location cards, including the main company info.'
        ],
        'job_*' => [
            'attributes' => 'None',
            'description' => 'Dynamically created for each job field (e.g., [job_title], [job_work_hours]).'
        ],
        'job_posting_summary' => [
            'attributes' => 'None',
            'description' => 'Displays a full, detailed summary of a job posting.'
        ]
    ];

    return $shortcodes;
}