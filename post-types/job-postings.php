<?php

function get_prepopulated_addresses() {
$address_keys = ["street_address", "street_address_2", "city", "state", "zipcode", "addy_country"];
    $status = get_option('locations_areas_status');
    $company_info = get_option('company_info',[]);
    $addresses = []; 
    if (!empty($company_info)){
        $show_address_on_org = $company_info['show_address_on_org'];
            $data = [];
    if ($show_address_on_org==='1'){
        $company_info = get_option('company_info',[]);
     foreach ($address_keys as $address_part) {
           $data[$key] = $company_info[$address_part];
     }
         $addresses['main_address'] = ['data' => $data];
    }
}
  if ($status === 'multi_locations' || $status === 'both') {
    $locations_context= get_option('enabled_connector_contexts')['locations'];
        $locations_taxonomy = $locations_context['taxonomy'];
        $terms = get_terms(['taxonomy' => $locations_taxonomy,'hide_empty' => true]);
    }
    foreach ($terms as $term) {
        $data = [];
        $has_data = false;
        foreach ($address_keys as $key) {
            $value =  get_term_meta($term->term_id, $key, true);
            $data[$key] = $value;
                if ($value !== '') {
                    $has_data = true;
                }
            }
            if ($has_data) {
                $addresses[$term->slug] = ['data' => $data];
            }
            }
         
    return $addresses;
}

function da_get_job_fields() {
    $addresses = get_prepopulated_addresses();
    foreach ($addresses as $key => $address) {
        $prepopulated_address_map[$key] = $key;
    }
    
    $options = getJobPostingOptions();
    $current_date = current_time('Y-m-d');

$job_details_fields = [
	'type' => 'visual_section',
	'label' => 'Job Details',
	'fields' => [
		'job_title'       => ['type' => 'text', 'value' => ''],
		'description' => ['label' => 'Description', 'type' => 'wysiwyg', 'value' => ''],
		'employment_type' => ['label' => 'Employment Type', 'type' => 'select', 'options' => $options['employment_types'], 'value' => 'FULL_TIME'],
		'work_hours'      => ['label' => 'Work Hours', 'type' => 'text', 'value' => '9 to 5'],
		'immediate_start' => ['label' => 'Start Immediately?', 'type' => 'toggle', 'value' => "0"],
		'job_expires'   => ['label' => 'Hire By Date?', 'type' => 'toggle', 'value' => "0"],
		'job_start_date'  => ['label' => 'Start Date', 'type' => 'date', 'value' => $current_date,  'condition' => ['field' => 'immediate_start', 'values' => ["0"]]],
		'valid_through'   => ['type' => 'date', 'value' => $current_date, 'condition' => ['field' => 'job_expires', 'values' => ["1"]]],
		'direct_apply'    => ['label' => 'Apply Online', 'type' => 'toggle', 'value' => "1", 'options_label' => ["1" => 'No', "0" => 'Yes']],
        'job_date_posted' => ['label' => 'Date Posted', 'type' => 'date', 'value' => $current_date],
		'identifier'      => ['label' => 'Job Identifier', 'type' => 'text', 'value' => ''],
		'reports_to'      => ['label' => 'Reports To', 'type' => 'text', 'value' => ''],
	]
];
$compensation_fields = [
    'type' => 'visual_section',
    'label' => 'Compensation',
    'fields' => [
        'salary_range_toggle' => ['label' => 'Range or Exact', 'type' => 'toggle', 'value' => "1", 'options_label' => ["1" => 'Range', "0" => 'Exact']],
        'currency' => ['type' => 'text', 'value' => 'USD'],
        'salary_value' => ['label' => 'Exact Salary', 'type' => 'number', 'step' => "1", 'value' => '', 'condition' => ['field' => 'salary_range_toggle', 'values' => ["1"]]], 
        'salary' => ['label' => 'Salary Range', 'type' => 'group', 'condition' => ['field' => 'salary_range_toggle', 'values' => ["0"]],  
            'fields' => [
                'min' => ['label' => 'Minimum Salary', 'type' => 'number', 'step' => '1', 'value' => ''],
                'max' => ['label' => 'Maximum Salary', 'type' => 'number', 'step' => '1', 'value' => '']
            ]
        ],
        'payment_interval' => ['label' => 'Per', 'type' => 'select', 'options' => $options['salary_types'], 'value' => 'YEAR'],
    ]
];

$benefits = get_global_benefit_fields();
$prefixed_benefits = [];
foreach ($benefits as $name => $config) {
    $prefixed_benefits["{$name}"] = $config;
}



$benefits_fields = [
	'type' => 'visual_section',
	'label' => 'Benefits',
	
	'fields' => [
	    'job_benefits' => ['label' => 'Benefits Offered?', 'type' => 'toggle', 'value' => "0", 'option_labels' => ["1" => 'No', "0" => 'Yes']],
		'select_job_benefits' => ['type' => 'visual_group',  'condition' => ['field' => 'job_benefits', 'values' => ["1"]],
		'fields' => [
		    'benefit' => ['label' => 'Available Benefits', 'type' => 'group', 'fields' => $prefixed_benefits], 
            'add_new_benefit' => ['type' => 'text', 'ui_only' => true],
            'confirm_add_new_benefit' => ['type' => 'button', 'label' => 'Add Benefit', 'class' => '']
	    ]]]
	   ];

$education_requirements = [
    'type' => 'visual_section',
    'label' => 'Education & Qualifications',
    'fields' => [
        'education_setup' => ['type' => 'field_group', 'fields' => [
            'degree_required' => ['label' => 'Degree Required?', 'type' => 'toggle', 'options_label' => ["1" => 'No', "0" => 'Yes'], 'value' => "0"],
            'qualifications_required' => ['label' => 'Qualifications Required?', 'type' => 'toggle', 'options_label' => ["1" => 'No', "0" => 'Yes'], 'value' => "0"],
        ]],
        'education_fields' => ['type' => 'field_group', 'fields' => [
            'degree_requirements' => ['label' => 'Degree Requirement', 'type' => 'select', 'options' => $options['education_levels'], 'value' => 'bachelor_degree', 'condition' => ['field' => 'degree_required', 'values' => ["1"]]],
            'qualifications' => ['label' => 'Required Qualifications?', 'type' => 'wysiwyg', 'value'=> '', 'condition' => ['field' => 'qualifications_required', 'values' => ["1"]]],
        ]]
    ]
];

$experience_requirements = [
    'type' => 'visual_section',
    'label' => 'Experience Requirements',
    'fields' => [
        'experience_setup' => ['type' => 'field_group', 'fields' => [
            'experience_required' => ['label' => 'Experience Required?', 'type' => 'toggle', 'value' => "0", 'options_label' => ["1" => 'No', "0" => 'Yes']],
            'experience_in_place_of_education' => ['label' => 'Exp Instead of Edu?', 'options_label' => ["0" => 'Yes', "1" => 'No'], 'type' => 'toggle', 'value' => "0", 'condition' => ['field' => 'experience_required', 'values' => ["1"]]],
            'months_of_experience' => ['label' => 'Months of Experience', 'type' => 'number', 'value' => '', 'condition' => ['field' => 'experience_required', 'values' => ["1"]]],    
            'experience_description' => ['label' => 'Describe Experience Needed', 'type' => 'wysiwyg', 'value' => '', 'condition' => ['field' => 'experience_required', 'values' => ["1"]]],
        ]]
    ]
];
$job_location_fields = [
	'type' => 'visual_section',
	'label' => 'Job Location',
	'fields' => [
        'job_location_type' => ['label' => 'Job Location Type', 'type' => 'select', 'value' => 'Place', 'options' => $options['location_types']],     
        'addresses' => ['label' => 'Choose an Address', 'type' => 'select', 'options' => $prepopulated_address_map, 'value' => '', 'condition' => ['field' => 'job_location_type', 'values' => ['Place', 'Place_TELECOMMUTE']]],
        'street_address' => ['type' => 'text', 'condition' => ['field' => 'job_location_type', 'values' => ['Place', 'Place_TELECOMMUTE']]],
        'street_address_2' => ['type' => 'text', 'condition' => ['field' => 'job_location_type', 'values' => ['Place', 'Place_TELECOMMUTE']]],
        'address_city' => ['type' => 'text', 'condition' => ['field' => 'job_location_type', 'values' => ['Place', 'Place_TELECOMMUTE']]],
        'address_state' => ['type' => 'text', 'condition' => ['field' => 'job_location_type', 'values' => ['Place', 'Place_TELECOMMUTE']]],
        'zipcode' => ['type' => 'text', 'condition' => ['field' => 'job_location_type', 'values' => ['Place', 'Place_TELECOMMUTE']]],
        'addy_country' => ['type' => 'text', 'condition' => ['field' => 'job_location_type', 'values' => ['Place', 'Place_TELECOMMUTE']]],
		'restriction_type'  => ['label' => 'Restriction Type', 'type' => 'select', 'value' => '', 'options' => $options['geo_types'],  'condition' => ['field' => 'job_location_type', 'values' => ['TELECOMMUTE', 'Place_TELECOMMUTE']]],
		'country' => ['label' => 'Allowed Country', 'type' => 'repeater', 'condition' => ['field' => 'restriction_type', 'values' => ["country"]], 'fields' => ['country_name' => ['label' => 'Country Name', 'type' => 'text', 'value' => '']]],
		'state' => ['label' => 'Allowed States', 'type' => 'repeater', 'condition' => ['field' => 'restriction_type', 'values' => ["state"]], 'fields' => ['state_name' => ['label' => 'State Name', 'type' => 'text', 'value' => '']]],
        'city' => ['label' => 'Allowed Cities', 'type' => 'repeater', 'condition' => ['field' => 'restriction_type', 'values' => ["city"]], 'fields' => ['city_name' => ['label' => 'City, State', 'type' => 'text', 'value' => '']]]
        ]
];

$responsibilities_skills_fields = [
	'type' => 'visual_section',
	'label' => 'Responsibilities and Skills',
	'fields' => [
		'responsibility_description' => ['label' => 'Responsibility Description', 'type' => 'wysiwyg', 'value' => ''],
		'skills' => [
		    'type' => 'repeater', 
		    'fields' => [
		        'skill' => ['type' => 'text', 'value' => ''],
		]
		]]
];

	return ['job-details' => $job_details_fields, 'job-location' => $job_location_fields, 'education-requirements' => $education_requirements, 'experience' => $experience_requirements,'compensation' => $compensation_fields, 'benefits_section' => $benefits_fields, 'responsibilities-skills' => $responsibilities_skills_fields];
}

function getJobPostingOptions() {
    return [
        'employment_types' => ['FULL_TIME' => 'Full Time', 'PART_TIME' => 'Part Time', 'CONTRACTOR' => 'Contractor', 'TEMPORARY' => 'Temporary', 'INTERN' => 'Intern', 'VOLUNTEER' => 'Volunteer', 'PER_DIEM' => 'Per Diem', 'OTHER' => 'Other'],
        'salary_types' => ['HOUR' => 'Per Hour', 'DAY' => 'Per Day', 'WEEK' => 'Per Week', 'MONTH' => 'Per Month', 'YEAR' => 'Per Year'],
        'education_levels' => ['high_school' => 'High School', 'associate_degree' => 'Associate Degree', 'bachelor_degree' => 'Bachelor Degree', 'professional_certificate' => 'Professional Certificate', 'postgraduate_degree' => 'Postgraduate Degree'],
        'location_types' => ['Place' => 'Onsite', 'TELECOMMUTE' => 'Remote', 'Place_TELECOMMUTE' => 'Hybrid'],
        'geo_types' => ['country' => 'Country', 'state' => 'State', 'city' => 'City'],
    ];
}

function handle_add_new_benefit_ajax() {
    global $post; 
    $benefit_name = trim($_POST['benefit_name'] ?? '');
    if ($benefit_name === '') {
        wp_send_json_error(['message' => 'Benefit name cannot be empty.']);
    }
    $benefit_key = strtolower(str_replace(' ', '_', sanitize_text_field($benefit_name)));
    $global_benefits = get_option('global_benefits');
    if (isset($global_benefits[$benefit_key])) {
        wp_send_json_error(['message' => 'name exists']);
    }
    $global_benefits[$benefit_key] = $benefit_key;
    update_option('global_benefits', $global_benefits);
    $checkbox_html = FormHelper::generateCheckBox("benefit_{$benefit_key}", $benefit_key, '1', []);

    $job_meta = get_post_meta($post->ID, '_job_meta', true);
    $job_meta["benefit_{$benefit_key}"] = '1';
    update_post_meta($post->ID, '_job_meta', $job_meta);
    wp_send_json_success([ 'benefit_html' => $checkbox_html, 'added' => true]);
}
add_action('wp_ajax_add_new_benefit', 'handle_add_new_benefit_ajax');


function display_job_meta_box($post) {
    $post_id = $post->ID; 
    $nonce = wp_create_nonce('dibraco_save_job_meta'); 
    $job_meta = get_post_meta($post_id, '_job_meta', true);
 
    ?>
    <div class="job-meta-box">
        <input type="hidden" name="dibraco_job_meta_nonce" value="<?= esc_attr($nonce); ?>" />
        <?php
            $array_keys = dibraco_extract_field_names_helper(da_get_job_fields());
        $job_fields_template = da_get_job_fields(); 
        $storage_keys=dibraco_extract_nested_arrays_test($job_fields_template);


        foreach($storage_keys as $container_name => $storage_array_key){
    if (is_array($storage_array_key)){
        if (array_key_exists($container_name, $job_meta)){
         $job_meta = array_merge($job_meta,$job_meta[$container_name]); 
          $storage_keys = array_merge($storage_keys,$storage_keys[$container_name]);
          unset($job_meta[$container_name]);
          unset($storage_keys[$container_name]);
          
            }
        }
    }    
        $mapped_values = dibraco_filter_saved_data($job_meta, $storage_keys);
error_log(print_r($mapped_values,true));
        if (empty($job_meta)){
    echo FormHelper::generateVisualSection('job-details-form', ['fields' => $job_fields_template]);
        } else {
    FormHelper::generateField('who_cares', ['type' => 'valueinjector','meta_array' => $mapped_values]);
            echo FormHelper::generateVisualSection('job-details-form', ['fields' => $job_fields_template]);
    FormHelper::generateField('who_cares', ['type' => 'injectionend']);
        }

        ?>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var addressData = <?php echo json_encode(get_prepopulated_addresses($post_id)); ?>;
        document.getElementById('addresses').addEventListener('change', function () {
            var selectedAddress = this.value;
            if (selectedAddress && addressData[selectedAddress]) {
                var address = addressData[selectedAddress].data;
                document.getElementById('street_address').value = address['street_address'];
                document.getElementById('street_address_2').value = address['street_address_2'];
                document.getElementById('address_city').value = address['city'];
                document.getElementById('address_state').value = address['state'];
                document.getElementById('zipcode').value = address['zipcode'];
                document.getElementById('addy_country').value = address['addy_country'];

            }
        });
    });
jQuery(function($) {
    console.log('jQuery document ready function has started.');

    // DEBUG 2: Does jQuery find the button?
    var myButton = $('#confirm_add_new_benefit');
    console.log('Button found by jQuery:', myButton);
    console.log('Number of buttons found:', myButton.length)
    $('#confirm_add_new_benefit').on('click', function() {
        const benefitName = $('#add_new_benefit').val().trim();
        if (!benefitName) return;
        $.post(ajaxurl, {
            action: 'add_new_benefit',
            benefit_name: benefitName
        }, 
        function(response) {
            if (!response.success) {
                alert(response.data.message || 'Error adding benefit');
                return;
            }
           const benefitsContainer = $('#benefit');
            benefitsContainer.append(response.data.benefit_html);
            $('#add_new_benefit').val('');
            alert('Benefit added');
        });
    });
});
</script>
</div>
<?php
}

function save_job_meta_box_data($post_id) {
    if (!isset($_POST['dibraco_job_meta_nonce']) || !wp_verify_nonce($_POST['dibraco_job_meta_nonce'], 'dibraco_save_job_meta')) {
        return;
    }
    $prepopulated_addresses = get_prepopulated_addresses();
    $job_fields_template = da_get_job_fields();
    $all_valid_field_names = dibraco_extract_field_names_helper($job_fields_template);
   
    $meta_to_save = [];
   foreach ($all_valid_field_names as $field_name) {
       

         if (substr($field_name, -9) === 'row_count') {
                
                $meta_to_save[$field_name] = $_POST[$field_name];
                continue;
            }
            if (is_array($_POST[$field_name])) {
                $flattened_data = FormProcessor::flatten_array($_POST[$field_name], $field_name);
                foreach ($flattened_data as $flattened_name => $flattened_value) {
                    $meta_to_save[$flattened_name] = $flattened_value;
                }
            } else {
                $meta_to_save[$field_name] = $_POST[$field_name];
            }
    }


    $meta_to_save = dibraco_condition_checker($job_fields_template, $meta_to_save);

    $post_type = get_post_type($post_id);
    $valid_through_date_str = $meta_to_save['valid_through'];
    $expiration_hook = 'dibraco_expire_job_post_event';
    wp_clear_scheduled_hook($expiration_hook, [$post_id]);

    if (!empty($valid_through_date_str)) {
        $expiration_timestamp = strtotime($valid_through_date_str . ' +1 day');
        $current_timestamp = time();

        if ($expiration_timestamp > $current_timestamp) {
            wp_schedule_single_event($expiration_timestamp, $expiration_hook, [$post_id]);
            error_log("save_job_meta_box_data: Scheduled expiration event for post {$post_id} at timestamp {$expiration_timestamp}.");
        } else {
            if (get_post_status($post_id) === 'publish') {
                remove_action("save_post_{$post_type}", 'save_job_meta_box_data', 20);
                wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
                add_action("save_post_{$post_type}", 'save_job_meta_box_data', 20);
                error_log("save_job_meta_box_data: Expiration date is in the past. Set post {$post_id} to draft.");
            }
        }
    }

    // Save the raw meta data
    update_post_meta($post_id, '_job_meta', $meta_to_save);
}


/**
 * The WordPress Cron event callback.
 */
function dibraco_execute_job_expiration_action($post_id) {
    $post = get_post($post_id);
    if ($post && $post->post_type === 'jobs' && $post->post_status === 'publish') {
        wp_update_post([
            'ID'          => $post_id,
            'post_status' => 'draft',
        ]);
    }
}
add_action('dibraco_expire_job_post_event', 'dibraco_execute_job_expiration_action', 10, 1);


function build_job_posting_schema($post_id) {
   $status = DibracoSchemaEnv::$status;
    $job_data = get_post_meta($post_id, '_job_meta', true);
    $url = get_permalink($post_id);
    $posting_id = $url . '/#jobposting';
    $schema = [];
    $schema['@type'] = 'JobPosting';
    $schema['@id'] = $posting_id;
    $schema['url'] = $url;
    $schema['title'] = $job_data['job_title'];
    $schema['description'] = extract_description_fields($job_data);
    $org_stub = DibracoSchemaEnv::build_organization_stub_node($status);
    $schema['hiringOrganization'] = ['@id' => $org_stub['@id'], '@type' =>'Organization'];
	if ($job_data['job_location_type'] === 'TELECOMMUTE' || $job_data['job_location_type'] === 'Place_TELECOMMUTE'){
        $schema['jobLocationType'] = 'TELECOMMUTE';  
        $schema['applicantLocationRequirements'] = $applicant_location_requirements['schema']; 
    }
    if ($job_data['job_location_type'] === 'Place' || $job_data['job_location_type'] === 'Place_TELECOMMUTE'){
        $schema['jobLocation'] = generate_job_location($job_data)['schema'];   
    }
    $schema['employmentType'] = $job_data['employment_type'];
    $schema['directApply'] = $job_data['direct_apply'] === '1' ? true : false; 
    $schema['datePosted'] = $job_data['job_date_posted']; 
    $schema['jobImmediateStart'] = $job_data['immediate_start'] === '1' ? true : false;
    $schema['jobStartDate'] = $job_data['immediate_start'] === '1' ? null : $job_data['job_start_date'];
    $schema['validThrough'] = $job_data['valid_through'];
    $schema['workHours'] = $job_data['work_hours'];
    $schema['identifier'] = $job_data['identifier'];
    $schema['educationRequirements'] = 'no requirements';
    if ($job_data['degree_requirements'] === "1") {
        $credential = str_replace(['_', '-'], ' ', $job_data['degree_requirements']);
        $schema['educationRequirements'] = [
            "@type" => 'EducationalOccupationalCredential',
            "credentialCategory" => $credential
        ];
    }
    $schema['experienceRequirements'] = 'no requirements';
    if ($job_data['experience_required'] === "1") {
        $experience = (int)$job_data['months_of_experience'];
        $schema['experienceRequirements'] = [
            "@type" => 'OccupationalExperienceRequirements',
            "monthsOfExperience" => $experience
        ];
    }
    $schema['baseSalary'] = build_salary_data($job_data)['schema'];
    $schema['jobBenefits'] = generate_benefits_section($job_data)['schema'];
    if ($job_data['qualifications_required'] ==="1"){ 
    $schema['qualifications'] = $job_data['qualifications'];
    }
    $schema['skills'] = generate_skills_section($job_data)['schema'];

    return $schema;
}


function extract_description_fields($job_data) {
    $html = '';
    $description = trim($job_data['description']); 
    $clean_description = strip_tags($description); 
    if ($clean_description !== '') {
        $html .= '<p>Job Description: ' . $clean_description . '</p>';
    }
    $work_hours = $job_data['work_hours']; 
    $months_of_experience = $job_data['months_of_experience']; 
    $experience_description = $job_data['experience_description']; 
    $degree_requirements = $job_data['degree_requirements']; 
    $degree_required = $job_data['degree_required']; 
    $experience_required = $job_data['experience_required']; 
    $qualifications_required = $job_data['qualifications_required']; 
    $qualifications = $job_data['qualifications']; 
    $education_in_place_of_experience = $job_data['experience_in_place_of_education']; 
    $experience_bit = '';
    $experience_explained = '';
    if ($degree_required === '1') {
        $degree_requirement_display = ucwords(str_replace('_', ' ', $degree_requirements));
        $html .= '<p>A ' . $degree_requirement_display . ' is required.</p>';
    }
    if ($degree_required === '1' && $experience_required === '1' && $education_in_place_of_experience === '1') {
        if (!empty($experience_description)) {
            $education_in_place = "Although we will accept someone with relevant work experience in place of education, you must have at least $months_of_experience months of experience. Relevant experience includes: $experience_description.";
        } else {
            $education_in_place = "Although we will accept someone with relevant work experience in place of education, you must have at least $months_of_experience months of experience.";
        }
        $html .= '<p>' . $education_in_place . '</p>';
    } else {
        $education_in_place = "At this time, we are not considering applicants who do not meet our degree requirement.";
        $html .= '<p>' . $education_in_place . '</p>';

        if ($experience_required === '1') {
            $experience_bit = "Candidates must also have at least $months_of_experience months of relevant work experience.";
            $html .= '<p>' . $experience_bit . '</p>';
        }

        if (!empty($experience_description)) {
            $experience_explained = "We consider the following relevant work experience: $experience_description.";
            $html .= '<p>' . $experience_explained . '</p>';
        }
    }
    $skills = generate_skills_section($job_data)['human_readable'];
     if (!empty($skills)) {
        $html .= $skills;
    }
    $job_benefits = generate_benefits_section($job_data)['human_readable'];
    if (!empty($job_benefits)) {
        $html .= $job_benefits;
    }
    if (!empty($work_hours)) {
        $html .= '<p>Work hours for this position: ' . $work_hours . '.</p>';
    }
    return $html;
}

function generate_job_location($job_data) {
$street_address = $job_data['address_street_address'];
if (!empty($job_data['address_street_address_2'])) {
        $street_address .= ' ' . $job_data['address_street_address_2'];
    }
    $city = $job_data['address_city'];
    $state = $job_data['address_state'];
    $zipcode = $job_data['address_zipcode'];
    $country = !empty($job_data['address_addy_country']) ? $job_data['address_addy_country'] : 'US';
    $location_string = "$street_address<br>$city, $state $zipcode";
    $location_schema = [
        "@type" => "Place",
        "address" => [
            "@type" => "PostalAddress",
            "streetAddress" => $street_address,
            "addressLocality" => $city,
            "addressRegion" => $state,
            "postalCode" => $zipcode,
            "addressCountry" => $country
        ]
    ];
    return [ 'human_readable' => $location_string, 'schema' => $location_schema];
}

function get_applicant_location_requirements($job_data) {
    $restriction_type = $job_data['restriction_type']; 
    $applicant_location_requirements = [];
    $row_count_key = "{$restriction_type}_row_count";  
    $name_key = "{$restriction_type}_name";  
    $location_list_html = '';
    for ($i = 0; $i < intval($job_data[$row_count_key]); $i++) {
        $location_value = $job_data["{$restriction_type}[{$i}][{$name_key}]"];
        if (!empty($location_value)) {
            $applicant_location_requirements[] = [
                "@type" => ucfirst($restriction_type), 
                "name" => $location_value
            ];
          $location_list_html .= '<li>' . esc_html($location_value) . '</li>';
        }
    }
 if (!empty($applicant_location_requirements)) {
    return ['schema' => $applicant_location_requirements, 'human_readable' => '<ul>' . $location_list_html . '</ul>' ];
  }
 return ['schema' => null, 'human_readable' => '' ];
}

function generate_skills_section($job_data) {
    $skills = [];
    $skills_readable = '';
    for ($i = 0; $i < intval($job_data['skills_row_count']); $i++) {
        $skill = $job_data["skills[{$i}][skill]"];
        if ($skill) {
            $skills[] = $skill;
            $skills_readable .= "<li>" . ucwords($skill) . "</li>";
        }
    }
    if (!empty($skills)) {
        return [ 'schema' => $skills, 'human_readable' => '<ul>' . $skills_readable . '</ul>' ];
    }
    return ['schema' => [], 'human_readable' => '' ];
}
function generate_benefits_section($job_data) {
    $benefits = [];
    $benefits_readable = '';
    foreach ($job_data as $key => $value) {
    if (strpos($key, 'benefits_') === 0 && $value === "1") {
            $benefit_name = ucwords(str_replace(['_', '-'], ' ', substr($key, 9)));
            $benefits[] = $benefit_name;
            $benefits_readable .= "<li>" . esc_html($benefit_name) . "</li>"; 
        }
    }
    if (empty($benefits)) {
        return [ 'schema' => [], 'human_readable' => '' ];
    }
    return [ 'schema' => $benefits, 'human_readable' => '<ul>' . $benefits_readable . '</ul>' ];
}


function make_job_shortcodes($post_id) {
    $job_fields = da_get_job_fields();
    foreach ($job_fields as $section_config) {
        if (empty($section_config['fields'])) {
            continue;
        }
        foreach ($section_config['fields'] as $field_key => $field_config) {
            if (empty($field_config['type'])) {
                continue;
            }
            if (in_array($field_config['type'], ['text', 'textarea', 'number', 'date', 'select'], true)) {
                add_shortcode("job_{$field_key}", function () use ($field_key) {
                    $job_data = get_post_meta(get_the_ID(), '_job_meta', true);
                    return !empty($job_data[$field_key]) ? $job_data[$field_key] : '';
                });
            }
        }
    }
add_shortcode('job_skills', function () {
    $job_data = get_post_meta(get_the_ID(), '_job_meta', true);
    $skills = generate_skills_section($job_data)['human_readable'];
    return $skills;
});

add_shortcode('job_qualifications', function () {
    $job_data = get_post_meta(get_the_ID(), '_job_meta', true);
    $qualifications = $job_data['qualifications']; 
    if (empty($qualifications)) {
        return '<p>No qualifications required.</p>';
    }
    return '<p>' . esc_html($qualifications) . '</p>';  
});
add_shortcode('job_benefits', function () {
    $job_data = get_post_meta(get_the_ID(), '_job_meta', true);
      $job_benefits = generate_benefits_section($job_data)['human_readable'];
        return $job_benefits;
    });
    
add_shortcode('applicant_restrictions', function () {
    $job_data = get_post_meta(get_the_ID(), '_job_meta', true);
    if ($job_data['job_location_type'] === 'TELECOMMUTE' || $job_data['job_location_type'] === 'Place_TELECOMMUTE') {
        $application_restrictions = get_applicant_location_requirements($job_data);
        return $application_restrictions['human_readable'];
       }
 return '';
});

add_shortcode('job_address', function () {
$job_data = get_post_meta(get_the_ID(), '_job_meta', true);
    if ($job_data['job_location_type'] === 'Place' || $job_data['job_location_type'] === 'Place_TELECOMMUTE') {
    $location_data = generate_job_location($job_data);
    return $location_data['human_readable'];
    }
    return '';
  });
}
add_action('init', 'make_job_shortcodes');
add_shortcode('job_posting_summary', function () {
    $job_data = get_post_meta(get_the_ID(), '_job_meta', true);
    if (empty($job_data)) return '';
    $company_info = get_option('company_info');
    $logo_id = $company_info['company_logo'];
    $logo_url = !empty($logo_id) ? wp_get_attachment_url($logo_id) : '';

    $salary_string    = build_salary_data($job_data)['human_readable'];
    $education_html   = generate_education_section($job_data)['human_readable'];
    $experience_html  = generate_experience_section($job_data)['human_readable'];
    $skills_html      = generate_skills_section($job_data)['human_readable'];
    $benefits_html    = generate_benefits_section($job_data)['human_readable'];

    $job_title        = !empty($job_data['job_title']) ? $job_data['job_title'] : get_the_title();
    $job_description  = !empty($job_data['description']) ? $job_data['description'] : 'No detailed job description available.';
    $employment_type  = !empty($job_data['employment_type']) ? ucwords(strtolower(str_replace('_', ' ', $job_data['employment_type']))) : 'Full Time';
    $job_date_posted  = !empty($job_data['job_date_posted']) ? date('F j, Y', strtotime($job_data['job_date_posted'])) : 'Not available';
     $immediate_start  = ($job_data['immediate_start'] === "1") ? 'Yes' : 'No';

    $job_start_date   = ($job_data['immediate_start'] === "1")
                        ? date('F j, Y', strtotime(current_time('Y-m-d'))) // If immediate, set to today's date
                        : (!empty($job_data['job_start_date']) ? date('F j, Y', strtotime($job_data['job_start_date'])) : 'Not specified');

    $work_hours       = !empty($job_data['work_hours']) ? $job_data['work_hours'] : 'Not specified';
    $reports_to       = !empty($job_data['reports_to']) ? $job_data['reports_to'] : 'Not specified';
    $direct_apply     = ($job_data['direct_apply'] === "1") ? 'Yes' : ''; // "1" means Yes, "0" means nothing

    $valid_through = '';
    if ($job_data['job_expires'] === "1" && !empty($job_data['valid_through'])) {
        $valid_through = date('F j, Y', strtotime($job_data['valid_through']));
    }
    
    $experience_in_place_of_education_display = '';
    if ($job_data['degree_required'] === "1" && $job_data['experience_required'] === "1") {
        $experience_in_place_of_education_display = ($job_data['experience_in_place_of_education'] === "0") ? 'Yes' : 'No';
    }

    $location_display_output = '';
    $job_location_type = $job_data['job_location_type'];

    if ($job_location_type === 'Place') {
        $generated_location = generate_job_location($job_data)['human_readable'];
        $location_display_output = !empty($generated_location) ? '<p style="margin: 0; line-height: 1.5; font-size: 1em;">' . wp_kses_post($generated_location) . '</p>' : '<p style="color: #c0392b; font-weight: bold; margin: 0; font-size: 0.95em;">Error: Physical location data missing!</p>';
    } elseif ($job_location_type === 'TELECOMMUTE') {
        $generated_restriction = get_applicant_location_requirements($job_data)['human_readable'];
        $restriction_text = !empty($generated_restriction) ? wp_kses_post($generated_restriction) : '<ul style="margin: 0; padding-left: 20px; list-style: disc;"><li>Worldwide</li></ul>';
        $location_display_output = '
            <p style="margin: 0 0 10px 0; font-size: 1em;">Remote Position</p>
            <div style="border-left: 4px solid #b3d9ff; padding: 10px 15px; background-color: #e6f7ff; margin-top: 15px; border-radius: 4px;">
                <p style="margin: 0; font-weight: bold; font-size: 0.95em;">Applicants must be located in:</p>
                ' . $restriction_text . '
            </div>';
    } elseif ($job_location_type === 'Place_TELECOMMUTE') {
        $generated_location = generate_job_location($job_data)['human_readable'];
        $actual_location_text = !empty($generated_location) ? wp_kses_post($generated_location) : '<span style="color: #c0392b; font-weight: bold;">Error: Physical location data missing for this remote-specific location!</span>';
        $generated_restriction = get_applicant_location_requirements($job_data)['human_readable'];
        $restriction_text = !empty($generated_restriction) ? wp_kses_post($restriction_text) : '<ul style="margin: 0; padding-left: 20px; list-style: disc;"><li>Worldwide</li></ul>';

        $location_display_output = '
            <p style="margin: 0 0 10px 0; font-size: 1em;">Hybrid Position: Onsite in ' . $actual_location_text . ' with Remote Options</p>
            <div style="border-left: 4px solid #b3d9ff; padding: 10px 15px; background-color: #e6f7ff; margin-top: 15px; border-radius: 4px;">
                <p style="margin: 0; font-weight: bold; font-size: 0.95em;">Remote work is available, but applicants must be located in: </p>
                ' . $restriction_text . '
            </div>';
    } else {
        $location_display_output = '<p style="color: #c0392b; font-weight: bold; margin: 0; font-size: 0.95em;">Error: Invalid or unhandled job location type configured.</p>';
    }

    ob_start();
    ?>
    <div class="job-posting-summary-container" style="max-width: 1000px; margin: 40px auto; padding: 30px; border: 1px solid #e0e0e0; background-color: #ffffff; box-shadow: 0 8px 25px rgba(0,0,0,0.1); font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333;">

        <h1 style="font-size: 2.5em; color: #2c3e50; margin: 0 0 5px 0; padding-bottom: 0;">
            <?php echo esc_html($job_title); ?>
        </h1>
        <section style="display: flex; flex-wrap: wrap; align-items: flex-start; margin-bottom: 35px; padding-bottom: 25px; border-bottom: 1px solid #eee;">
            <div style="flex: 1 1 65%; min-width: 350px; padding-right: 25px;">
                <h2 style="font-size: 1.8em; color: #2c3e50; margin-top: 0; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 2px solid #a0a0a0;">Job Description</h2>
                <p style="margin: 0; font-size: 1em;"><?php echo wp_kses_post($job_description); ?></p>
            </div>
            <div style="flex: 0 0 150px; max-width: 150px; margin-left: auto; text-align: right; padding-top: 10px;">
                <?php if (!empty($logo_url)) : ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="Company Logo" loading="lazy" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.15);">
                <?php endif; ?>
            </div>
        </section>

        <div style="display: flex; flex-wrap: wrap; gap: 30px; margin-bottom: 35px;">
            <div style="flex: 1; min-width: 350px;">
                <section style="padding: 25px; border: 1px solid #eee; background-color: #fcfcfc; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h3 style="font-size: 1.4em; color: #2c3e50; margin-top: 0; margin-bottom: 18px; padding-bottom: 8px; border-bottom: 1px solid #ddd;">Job Details</h3>

                    <ul style="list-style: none; padding: 0; margin: 0 0 25px 0;">
                        <li style="margin-bottom: 10px;"><strong>Type:</strong> <?php echo esc_html($employment_type); ?></li>
                        <li style="margin-bottom: 10px;"><strong>Posted On:</strong> <?php echo esc_html($job_date_posted); ?></li>
                        <li style="margin-bottom: 10px;"><strong>Immediate Start:</strong> <?php echo esc_html($immediate_start); ?></li>
                        <?php
                        if ($immediate_start !== 'Yes') :
                        ?>
                            <li style="margin-bottom: 10px;"><strong>Start Date:</strong> <?php echo esc_html($job_start_date); ?></li>
                        <?php endif; ?>
                        <li style="margin-bottom: 10px;"><strong>Work Hours:</strong> <?php echo esc_html($work_hours); ?></li>
                        <li style="margin-bottom: 10px;"><strong>Reports To:</strong> <?php echo esc_html($reports_to); ?></li>
                        <li style="margin-bottom: 10px;"><strong>Direct Apply:</strong> <?php echo esc_html($direct_apply); ?></li>
                        <?php if (!empty($valid_through)) : ?>
                            <li style="margin-bottom: 10px;"><strong>Valid Through:</strong> <?php echo esc_html($valid_through); ?></li>
                        <?php endif; ?>
                             <?php if (!empty($job_data['identifier'])) : ?>
                            <li style="margin-bottom: 10px;"><strong>Job Identifier:</strong> <?php echo esc_html($job_data['identifier']); ?></li>
                        <?php endif; ?>
                    </ul>

                    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px dashed #eee;">
                        <h4 style="font-size: 1.2em; color: #333; margin-top: 0; margin-bottom: 15px;">Job Location</h4>
                        <?php echo $location_display_output; ?>
                    </div>

                   <div style="margin-top: 25px; padding-top: 25px; border-top: 1px dashed #eee;">
                        <h4 style="font-size: 1.2em; color: #333; margin-top: 0; margin-bottom: 15px;">Compensation</h4>
                        <?php if (!empty($salary_string)) : ?>
                            <div style="margin-bottom: 15px;">
                                <h5 style="font-size: 1em; color: #555; margin-top: 0; margin-bottom: 8px;">Salary</h5>
                                <p style="margin: 0; font-size: 0.95em; font-weight: bold;"><?php echo esc_html($salary_string); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($benefits_html)) : ?>
                            <div style="<?php echo !empty($salary_string) ? 'margin-top: 20px; padding-top: 20px; border-top: 1px dashed #eee;' : ''; ?>"> <h5 style="font-size: 1em; color: #555; margin-top: 0; margin-bottom: 8px;">Benefits</h5>
                                <?php echo wp_kses_post($benefits_html); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div style="flex: 1; min-width: 350px; display: flex; flex-direction: column; gap: 30px;">
                <section style="padding: 25px; border: 1px solid #eee; background-color: #fcfcfc; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                    <h3 style="font-size: 1.4em; color: #2c3e50; margin-top: 0; margin-bottom: 18px; padding-bottom: 8px; border-bottom: 1px solid #ddd;">Responsibilities</h3>
                    <?php
                    if (!empty($job_data['responsibility_description'])) {
                        $responsibility_desc = trim($job_data['responsibility_description']);
                        if (strpos($responsibility_desc, '<li>') === 0 || strpos($responsibility_desc, '<ul') === 0 || strpos($responsibility_desc, '<ol') === 0) {
                            echo wp_kses_post($responsibility_desc);
                        } else {
                            echo '<p style="margin: 0; font-size: 1em;">' . wp_kses_post($responsibility_desc) . '</p>';
                        }
                    } else {
                        echo '<p style="margin: 0; font-style: italic; color: #777; font-size: 0.95em;">Responsibilities not specified.</p>';
                    }
                    ?>
                </section>
            </div>
        </div>

        <section style="margin-top: 35px; padding: 25px; border: 1px solid #eee; background-color: #fcfcfc; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
            <h3 style="font-size: 1.4em; color: #2c3e50; margin-top: 0; margin-bottom: 18px; padding-bottom: 8px; border-bottom: 1px solid #ddd;">Education & Experience</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 250px;">
                    <h4 style="font-size: 1.1em; color: #333; margin-top: 0; margin-bottom: 8px;">Education</h4>
                    <?php
                    if (!empty($education_html)) {
                        echo wp_kses_post($education_html);
                    } else {
                        echo '<p style="margin: 0 0 10px 0; font-style: italic; color: #777; font-size: 0.95em;">Education requirements not specified.</p>';
                    }
                    ?>
                    <?php if (!empty($job_data['qualifications'])) : ?>
                        <h4 style="font-size: 1.1em; color: #333; margin-top: 15px; margin-bottom: 8px;">Qualifications</h4>
                        <p style="margin: 0; font-size: 1em;">
                            <?php echo wp_kses_post($job_data['qualifications']); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <div style="flex: 1; min-width: 250px;">
                    <?php
                    if ($experience_in_place_of_education_display === 'Yes') :
                        echo '<p style="margin: 0 0 10px 0; font-size: 1em; font-weight: bold;">Experience in place of Education: ' . esc_html($experience_in_place_of_education_display) . '</p>';
                    endif;
                    ?>
                    <?php
                    if (!empty($experience_html)) {
                        echo '<p style="margin: 0 0 10px 0; font-size: 1em;">' . wp_kses_post($experience_html) . '</p>'; // Now just the duration
                    } 
                    if ($job_data['experience_required'] === "1" && !empty($job_data['experience_description'])) :
                        echo '<p style="margin: 0 0 10px 0; font-size: 1em;"><strong>Details:</strong> ' . wp_kses_post($job_data['experience_description']) . '</p>';
                    endif;
                    ?>

                    <div style="margin-top: 25px; padding-top: 25px; border-top: 1px dashed #eee;">
                        <h4 style="font-size: 1.1em; color: #333; margin-top: 0; margin-bottom: 8px;">Required Skills</h4>
                        <?php if (!empty($skills_html)) : ?>
                            <?php echo wp_kses_post($skills_html); ?>
                        <?php else : ?>
                            <p style="margin: 0; font-style: italic; color: #777; font-size: 0.95em;">No specific skills listed.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>


    </div>
    <?php
    return ob_get_clean();
});
function build_salary_data($job_data) {
    $value = $job_data['salary_value'];
    if (empty($value) && empty($job_data['salary_min'])) {
        return ['schema' => null, 'human_readable' => ''];
    }
    $unitText = $job_data['payment_interval'];
    $currency = $job_data['currency'];
    if (empty($currency)){ $currency = 'USD'; }
    $salary_schema = [
        '@type'    => 'MonetaryAmount',
        'currency' => $currency,
        'value'    => [
            '@type' => 'QuantitativeValue',
        ],
    ];
    if(!empty($value)){
    $salary_string = "$" . number_format($value) . " Per " . ucwords(strtolower($unitText)); 
    $salary_schema['value']['value'] = (float)$value;
    $salary_schema['value']['unitText'] = $unitText;
    }
   else {
        $min = $job_data['salary_min'];
        $max = $job_data['salary_max'];
        $salary_schema['value']['minValue'] = (float)$min;
        $salary_schema['value']['maxValue'] = (float)$max;
        $salary_schema['value']['unitText'] = $unitText;
        $salary_string = "$" . number_format($min) . " - $" . number_format($max) . " Per " . ucwords(strtolower($unitText));
    }
    return ['schema' => $salary_schema, 'human_readable' => $salary_string ];
}

function generate_education_section($job_data) {
    if ($job_data['degree_required'] !== "1") {
        return ['human_readable' => 'No Requirements', 'schema' => 'no requirements'];
    }
    $schema_credential = str_replace('_', ' ', $job_data['degree_requirements']);
    return ['human_readable' => "<p><strong>Degree Requirements:</strong> " . ucwords($schema_credential) . "</p>",
            'schema'=> ["@type"=> 'EducationalOccupationalCredential',"credentialCategory" => $schema_credential]];
}

function generate_experience_section($job_data) {
    if ($job_data['experience_required'] !== "1") {
        return ['human_readable' => 'No Requirements', 'schema' => 'no requirements'];
    }
    $months_of_experience = (int)$job_data['months_of_experience'];
    $experience_readable = '';

    if ($months_of_experience > 0) {
        $years = floor($months_of_experience / 12);
        $remaining_months = $months_of_experience % 12;
        if ($years > 0) {
            $experience_readable .= $years . ' year' . ($years > 1 ? 's' : '');
        }
        if ($years > 0 && $remaining_months > 0) {
            $experience_readable .= ' and ';
        }
        if ($remaining_months > 0) {
            $experience_readable .= $remaining_months . ' month' . ($remaining_months > 1 ? 's' : '');
        }
    } else {
        $experience_readable = 'Not specified';
    }
    return [
        'human_readable' => "<p><strong>Experience:</strong> {$experience_readable}</p>",
        'schema' => ["@type" => 'OccupationalExperienceRequirements', "monthsOfExperience" => $months_of_experience]
    ];
}
