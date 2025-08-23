<?php
function get_contact_info_fields($prefix='') {
  if ($prefix===''){
      $prefix = null;
  }
  return [
    "{$prefix}name" => ['type' => 'text'],
    "phone_number" => ['type' => 'text'], 
    "email_address" => ['type' => 'text'],
    "fax_number" => ['type' => 'text'],
    "place_id" => ['type' => 'text'],
    "gmb_map_link" => ['type' => 'text'],
    "second_phone" => ['type' => 'toggle', 'label' => 'Second Phone?', 'options_label' => ["0" => 'Yes', "1" => 'No'], 'value' => "0"],
    "additional_phone" => ['type' => 'text', 'condition' => ['field' => 'second_phone', 'values' => ["1"]]],
    "exterior_image" => ['type' => 'image'],
  ];
}


function get_address_fields() {
  return [
    "street_address"    => ['type' => 'text'],
    "street_address_2"  => ['type' => 'text'],
    "city"              => ['type' => 'text'],
    "state"             => ['type' => 'text'],
    "zipcode"           => ['type' => 'text'],
    "addy_country"      => ['type' => 'text', 'label' => 'Country'],
    "latitude"          => ['type' => 'text'],
    "longitude"         => ['type' => 'text'],
    "google_map_embed"  => ['type' => 'textarea']
  ];
}

  
function get_landscape_image_fields($context_type ='') {
     $fields = [];
     for ($i = 1; $i <= 2; $i++) {
        $fields["dibraco_landscape_{$i}"] = ['type'  => 'image', 'label' => "Landscape Image {$i}" ];
    if ($context_type !== 'unique') {
        $fields["dibraco_landscape_{$i}_lock"] = ['type'  => 'toggle', 'label' => 'Lock Image', 'options_label' => ["0" => 'Locked', "1" => 'Unlocked']];
        }
    }
    return $fields;
}

function get_portrait_image_fields($context_type = '') {
   $fields = [];
     for ($i = 1; $i <= 2; $i++) {
        $fields["dibraco_portrait_{$i}"] = ['type'  => 'image', 'label' => "Portrait Image {$i}" ];
    if ($context_type !== 'unique') {
        $fields["dibraco_portrait_{$i}_lock"] = ['type'  => 'toggle', 'label' => 'Lock Image'];
        }
    }
    return $fields;
}

function get_banner_fields(){
    return  [
    'da_main_h1'            => ['label' => 'Main H1', 'type' => 'text'],
    'da_banner_description' => ['label' => 'Banner Description', 'type' => 'wysiwyg', 'wpautop' => false, 'media_buttons' => true, 'teeny' => false, 'rows' => 5],
];
}
function get_contact_fields(){
return [
    'da_quote_title'        => ['label' => 'Quote Title', 'type' => 'text'],
    'da_contact_section'    => ['label' => 'Contact Section', 'type' => 'wysiwyg', 'wpautop' => false, 'media_buttons' => false, 'teeny' => false, 'rows' => 5],
];
}
function get_about_fields(){
  return[
    'da_about_title' =>['type' => 'text'],
    'da_about_blurb' => ['type' => 'wysiwyg', 'wpautop' => false, 'media_buttons' => true, 'teeny' => false, 'rows' => 7]
    ];
}
function get_service_area_term_fields (){
$fields =[];
        $fields['city'] = ['type' => 'text'];
        $fields['state']       = ['type' => 'text'];
        $fields['latitude']    = ['type' => 'text'];
        $fields['longitude']   = ['type' => 'text'];
return $fields;
}
function get_section_title_fields() {
    $fields = [];
    $fields['da_section_1_title'] = ['label' => 'Section 1 Title', 'type' => 'text'];
    $fields['da_section_1_p']     = ['label' => 'Section 1 Paragraph', 'type' => 'wysiwyg', 'wpautop' => false, 'media_buttons' => true, 'teeny' => false, 'rows' => 7];
    $fields['da_section_2_title'] = ['label' => 'Section 2 Title', 'type' => 'text'];
    $fields['da_section_3_title'] = ['label' => 'Section 3 Title', 'type' => 'text'];
    $fields['da_section_2_p']     = ['label' => 'Section 2 Paragraph', 'type' => 'wysiwyg', 'wpautop' => false, 'media_buttons' => true, 'teeny' => false, 'rows' => 7];
    $fields['da_section_3_p']     = ['label' => 'Section 3 Paragraph', 'type' => 'wysiwyg', 'wpautop' => false, 'media_buttons' => true, 'teeny' => false, 'rows' => 7];
    return $fields;
}
function get_repeater_field_list(){
$fields = [
        'da_list_title' => ['label' => 'List Title', 'type' => 'text'],
        'da_list_repeater' => ['type' => 'repeater', 'fields' => ['item' => ['type' => 'textarea', 'label'=> 'item', 'rows' => 2 ]]]
    ];
 return $fields;
}

function get_employee_fields(){
    $fields = [
    'employee-fields' => [ 'type' => 'visual_section', 'fields' => [
    'given_name' => ['type' => 'text', 'label' => 'First Name'],
    'family_name' => ['type' => 'text', 'label' => 'Last Name'],
    'work_email' => ['type' => 'text'],
    'work_phone' => ['type' => 'text'],
    'cell_number' => ['type' => 'text'],
    'job_title' => ['type' => 'text'],
    'employee_bio' => ['type' => 'textarea']
    ]
    ]];
    $use_cert_field = get_option('enabled_unique_contexts')['employee']['has_certification'] ?? '0';
    if ($use_cert_field ==="1"){
        $fields['employee-fields']['fields']['has_certification'] = ['type' => 'toggle', 'value' => "0"];
    }
    return $fields;
}
function get_certification_fields() {
  return [
      'certification_section' => ['type' => 'visual_group', 'condition'=> ['field' => 'has_certification', 'values' => ["1"]], 'fields' => [
		'certification_name' => ['type' => 'text', 'label' => 'Certification Name'],
        'certification_id' => ['type' => 'text', 'label' => 'Certification ID'],
        'certification_valid_from' => ['type' => 'date', 'label' => 'Valid From'],
        'certification_expires' => ['type' => 'date', 'label' => 'Expires'],
        'certification_valid_in' => ['type' => 'text', 'label' => 'Region or Area'],
		'certification_url' => ['type' => 'text', 'label' => 'Certification URL'],   
        'certification_issuer_name' => ['type' => 'text', 'label' => 'Issuer Name'],
        'certification_issuer_url' => ['type' => 'text', 'label' => 'Issuer Website'],
        'certification_about' => ['type' => 'textarea', 'label' => 'Issuer Description'],
		'certification_description'  => ['type' => 'textarea', 'label' => 'Certification Description'],         'certification_logo' => ['type' => 'image', 'label' => 'Certification Logo'],
      ]
    ]
  ];
}


final class DIBRACO_GMB_Integrator {

    private static $instance;

    public static function instance() {
        if (!isset(self::$instance) && !(self::$instance instanceof DIBRACO_GMB_Integrator)) {
            self::$instance = new DIBRACO_GMB_Integrator;
            self::$instance->hooks();
        }
        return self::$instance;
    }

    private function hooks() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_central_auth_callback']);
    }

    public function register_settings() {
        register_setting('dibraco_gmb_integrator_options', 'dibraco_settings');
    }

    public function settings_page_html() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('dibraco_settings', []);
        $auth_server_url = $settings['auth_server_url'] ?? '';
        ?>
        <div class="wrap">
            <h1>GMB Integrator Settings (OAuth Only)</h1>
            <div style="display:flex; flex-wrap: wrap; gap: 2rem;">
                <div style="flex: 1; min-width: 350px;">
                    <form action="options.php" method="post">
                        <?php settings_fields('dibraco_gmb_integrator_options'); ?>
                        <h2>Configure Your Central Auth Server</h2>
                        <p>Enter the URL of your central authentication server. This plugin will manage OAuth tokens received from this server.</p>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="dibraco_auth_server_url">Auth Server URL</label></th>
                                <td><input type="url" id="dibraco_auth_server_url" name="dibraco_settings[auth_server_url]" value="<?php echo esc_attr($auth_server_url); ?>" class="regular-text" placeholder="https://auth.your-agency.com" /></td>
                            </tr>
                        </table>
                        <?php submit_button('Save Auth Server URL'); ?>
                    </form>
                </div>
                <div style="flex:1; min-width: 350px;">
                    <h2>Connect Status</h2>
                    <?php if ($auth_server_url): ?>
                        <?php if ($this->get_valid_token()): ?>
                            <p style="color: green; font-weight: bold;">✓ Successfully connected to your Central Auth Server!</p>
                            <p>This plugin is ready to manage the OAuth tokens provided by your server.</p>
                        <?php else: ?>
                            <p>Auth Server URL is saved. Now, initiate the connection to Google through your central server.</p>
                            <a href="<?php echo $this->get_auth_url(); ?>" class="button button-primary">Connect via Central Auth Server</a>
                            <p class="description">If you encounter issues, ensure your central auth server is correctly configured and has the necessary Google API credentials and scopes.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Please enter and save your Auth Server URL in the form to the left to begin.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_auth_url() {
        $settings = get_option('dibraco_settings', []);
        $auth_server_url = $settings['auth_server_url'] ?? '';
        if (!$auth_server_url) return '#';

        $callback_url = admin_url('admin.php?page=dibraco-gmb-integrator');

        return add_query_arg('return_to', $callback_url, trailingslashit($auth_server_url) . 'auth.php');
    }

    public function handle_central_auth_callback() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'dibraco-gmb-integrator' || !isset($_GET['access_token'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_safe_redirect(admin_url('admin.php?page=dibraco-gmb-integrator&auth_error=permission'));
            exit;
        }

        $access_token = sanitize_text_field($_GET['access_token']);
        $refresh_token = sanitize_text_field($_GET['refresh_token']);
        $expires_in = (int) $_GET['expires_in'];

        update_option('dibraco_gmb_access_token', $access_token);
        update_option('dibraco_gmb_refresh_token', $refresh_token);
        update_option('dibraco_gmb_token_expires_at', time() + $expires_in);

        delete_option('dibraco_reviews');
        delete_option('dibraco_reviews_last_fetched');
        $settings = get_option('dibraco_settings', []);
        if (isset($settings['selected_locations'])) {
            unset($settings['selected_locations']);
            update_option('dibraco_settings', $settings);
        }

        wp_safe_redirect(admin_url('admin.php?page=dibraco-gmb-integrator'));
        exit;
    }

    private function get_valid_token() {
        $expires_at = get_option('dibraco_gmb_token_expires_at', 0);

        if (time() < $expires_at - 60) {
            return get_option('dibraco_gmb_access_token');
        }

        $settings = get_option('dibraco_settings', []);
        $auth_server_url = $settings['auth_server_url'] ?? '';
        $refresh_token = get_option('dibraco_gmb_refresh_token');

        if (!$auth_server_url || !$refresh_token) return false;

        $response = wp_remote_post(trailingslashit($auth_server_url) . 'refresh.php', [
            'body' => ['refresh_token' => $refresh_token],
            'timeout' => 15,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['access_token'])) {
            update_option('dibraco_gmb_access_token', $body['access_token']);
            update_option('dibraco_gmb_token_expires_at', time() + (int)$body['expires_in']);
            return $body['access_token'];
        }

        return false;
    }

}
