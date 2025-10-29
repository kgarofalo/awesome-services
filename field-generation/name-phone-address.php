<?php

function get_contact_info_fields($prefix=null) {
  return [
    "{$prefix}name" => ['type' => 'text'],
    "phone_number" => ['type' => 'text'], 
    "email_address" => ['type' => 'text'],
    "fax_number" => ['type' => 'text'],
    "place_id" => ['type' => 'text'],
    "gmb_map_link" => ['type' => 'text'],
    "second_phone" => ['type' => 'toggle', 'label' => 'Second Phone?', 'options_label' => ["0" => 'Yes', "1" => 'No'], 'value' => "1"],
    "additional_phone" => ['type' => 'text', 'condition' => ['field' => 'second_phone', 'values' => ["1"]]],
  ];
}

function get_location_or_company_image_fields($prefix=null){
    if ($prefix){
     return['location_logo'=> ['type' => 'image'],
      'exterior_image' => ['type'=>'image']];
    }
return['company_logo' =>['type' => 'image'],
        'map_pin' =>  ['type' => 'image'],
        'exterior_image' => ['type'=>'image'],
         ];
}
function get_location_only_fields($schema_options) {
  $fields = [
      'schema' => ['type'=>'select','options'=>$schema_options],
      'about_location' => ['type'=>'textarea','value'=>''],
      'price_range' => ['type'=>'text','value'=>'$$'],
      'payment_accepted' => ['type'=>'text','value'=>'Cash, Credit Card'],
      'map_pin' =>  ['type' => 'image'],
  ];
  if (in_array('employee', get_option('enabled_context_names'))) {
      $fields = $fields + ['location_manager' => ['type'=>'select','options'=>get_employee_posts_for_select_options()]];
  }
  return $fields;
}
function get_company_info_only_fields($schema_options) {
  $page_options = get_pages_for_contact_about();
    $fields=  [
         'legal_name' => ['label'=> 'Legal Name', 'type' => 'text'],
         'founding_date' => ['label'=> 'Company Founding Date', 'type' => 'date'],
         'schema' => ['type' => 'select', 'options' => $schema_options],
         'about_page' => ['type' => 'select', 'options' => $page_options],
         'contact_page' =>['type' => 'select', 'options' => $page_options],
         'non_profit_status'  => ['label' => 'Non Profit?', 'type'  => 'toggle', 'value' => "0"],
         'non_profit_type' => ['label'=> 'Non Profit Type', 'type' => 'select', 'options' => get_non_profit_type(), 'condition' => ['field' => 'non_profit_status', 'values' => ["1"]]],
         'company_description' => ['type'=>'textarea'],
         'show_address_on_org' => ['type' => 'toggle', 'value'=> '1', 'options_label' => ['1' => 'No', '0'=> 'Yes']],
         'generate_schema'  => ['label' => 'Generate Schema?', 'type'  => 'toggle', 'value' => "0"],
      ];
     $show_address_on_org = get_option('company_info')['show_address_on_org'] ??"1";
     if ($show_address_on_org ==='0'){
      $status = get_option('areas_locations_status',[]); 
      if ($status !=='multi_areas' && $status !== 'none'){
          $locations_taxonomy = get_option('enabled_connector_contexts')['locations']['taxonomy'];
          $location_terms = getTermOptions($locations_taxonomy);
          $fields = $fields += ['default_term' => ['type'=>'select','options'=>$location_terms, 'condition' => ['field' => 'show_address_on_org', 'values' => ["0"], 'current_value' => ''],]];
          return $fields;
        } elseif($status ==='multi_areas'){
            $service_area_taxonomy = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
            $service_area_terms = getTermOptions($service_area_taxonomy);
            $fields = $fields += ['default_term' => ['type'=>'select','options'=>$service_area_terms, 'condition' => ['field' => 'show_address_on_org', 'values' => ["0"], 'current_value' => ''],]];
            return $fields;
          } else {
              return $fields;
          }
     }
     return $fields;
  }

function get_address_fields() {
  return [
    "street_address" => ['type' => 'text'],
    "street_address_2"  => ['type' => 'text'],
    "city" => ['type' => 'text'],
    "state" => ['type' => 'text'],
    "zipcode" => ['type' => 'text'],
    "addy_country" => ['type' => 'text', 'label' => 'Country'],
    "latitude" => ['type' => 'text'],
    "longitude" => ['type' => 'text'],
    "bounding_box" => ['type' => 'text'], 
    "normal_map" => ['type' => 'text'],
    "street_map" => ['type' => 'text'],
  ];
}
function get_service_area_term_fields (){
return [
    'city' => ['type' => 'text'],
    'state' =>['type' => 'text'],
    'latitude' => ['type' => 'text'],
    'longitude' => ['type' => 'text'],
    'bounding_box' => ['type' => 'text'], 
    'menu_item_repeater' => [
            'type' => 'repeater', 
            'fields' => [ 
                'dish_name' => ['type' => 'text'],
                'description' => ['type' => 'textarea', 'rows' => 3],
                'image' => ['type' => 'image'],
                'price' => ['type' => 'number', 'step' => "0.01"],
                'has_sizes' => ['type' => 'toggle', 'label' => 'Multiple Sizes?', 'value' => '0'],
                'size' => [
                'type' => 'group',
                'condition' => ['field' => 'has_sizes', 'values' => ['1']],
                'fields' => [
                'small_price' => ['type' => 'number', 'step' => '0.01'],
                'small_portion' => ['type' => 'text'],
                'regular_price' => ['type' => 'number', 'step' => '0.01'],
                'regular_portion' => ['type' => 'text'],
                'large_price' => ['type' => 'number', 'step' => '0.01'],
                'large_portion' => ['type' => 'text']],
                    ],
                'diet_vegan' => ['type' => 'checkbox', 'label' => 'Vegan', 'value' => '0'],
                'diet_vegetarian' => ['type' => 'checkbox', 'label' => 'Vegetarian', 'value' => '0'],
                'diet_kosher' => ['type' => 'checkbox', 'label' => 'Kosher', 'value' => '0'],
                'diet_halal' => ['type' => 'checkbox', 'label' => 'Halal', 'value' => '0'],
                'calories' => ['type' => 'number', 'label' => 'Calories'],
                'protein' => ['type' => 'number', 'label' => "Protein (g)"],
                'fat' => ['type' => 'number', 'label' => "Fat (g)"]
            ]
            ]
   ];
}
function get_repeater_restaurant_menu(){
    return [
       'menu_item_repeater' => [
            'type' => 'repeater', 
            'fields' => [ 
                'dish_name' => ['type' => 'text'],
                'description' => ['type' => 'textarea', 'rows' => 3],
                'image' => ['type' => 'image'],
                'price' => ['type' => 'number', 'step' => "0.01"],
                'has_sizes' => ['type' => 'toggle', 'label' => 'Multiple Sizes?', 'value' => '0'],
                'size' => [
                'type' => 'group',
                'condition' => ['field' => 'has_sizes', 'values' => ['1']],
                'fields' => [
                'small_price' => ['type' => 'number', 'step' => '0.01'],
                'small_portion' => ['type' => 'text'],
                'regular_price' => ['type' => 'number', 'step' => '0.01'],
                'regular_portion' => ['type' => 'text'],
                'large_price' => ['type' => 'number', 'step' => '0.01'],
                'large_portion' => ['type' => 'text']],
                    ],
                'diet_vegan' => ['type' => 'checkbox', 'label' => 'Vegan', 'value' => '0'],
                'diet_vegetarian' => ['type' => 'checkbox', 'label' => 'Vegetarian', 'value' => '0'],
                'diet_kosher' => ['type' => 'checkbox', 'label' => 'Kosher', 'value' => '0'],
                'diet_halal' => ['type' => 'checkbox', 'label' => 'Halal', 'value' => '0'],
                'calories' => ['type' => 'number', 'label' => 'Calories'],
                'protein' => ['type' => 'number', 'label' => "Protein (g)"],
                'fat' => ['type' => 'number', 'label' => "Fat (g)"]
            ]
        ]
    ];
}
 function get_repeater_field_list(){
 return [
        'da_list_title' => ['type' => 'text', 'label' => 'List Title'], 
        'da_list_repeater' => ['type' => 'repeater', 'fields' => [ 
            'item' => ['type' => 'textarea', 'label'=> 'item', ],
        ]]]; 
}
function get_banner_fields(){
    return  [
    'da_main_h1'            => ['label' => 'Main H1', 'type' => 'text', 'pair' => '1'],
    'da_banner_description' => ['label' => 'Banner Description', 'type' => 'wysiwyg', 'pair_end' => '1'],
];
}
function get_contact_fields(){
return [
    'da_quote_title' => ['label' => 'Quote Title', 'type' => 'text', 'pair' => '1'],
    'da_contact_section' => ['label' => 'Contact Section', 'type' => 'wysiwyg', 'pair_end' => '1'],
];
}
function get_about_fields(){
  return[
    'da_about_title' =>['type' => 'text', 'label' => 'About Title', 'pair' => '1'],
    'da_about_blurb' => ['type' => 'wysiwyg', 'label' => 'About Blurb','pair_end'=>'1'],
    ];
}
function get_commercial_fields(){
     return[
     'da_commercial_title' =>['type' => 'text', 'label' => 'Commercial Title', 'pair' => '1'],
     'da_commercial_section' => ['type' => 'wysiwyg', 'label' => 'Commercial Section','pair_end'=>'1']
    ];
}

function get_section_title_fields() {
    return [
    'da_section_1_title' => ['label' => 'Section 1 Title', 'type' => 'text', 'pair' => '1'],
    'da_section_1_p'=> ['label' => 'Section 1 Paragraph', 'type' => 'wysiwyg', 'pair_end'=>'1'],
    'da_section_2_title'=> ['label' => 'Section 2 Title', 'type' => 'text', 'pair' => '1'],
    'da_section_2_p'=> ['label' => 'Section 2 Paragraph', 'type' => 'wysiwyg', 'pair_end'=>'1'],
    'da_section_3_title'=> ['label' => 'Section 3 Title', 'type' => 'text', 'pair' => '1'],
    'da_section_3_p'=> ['label' => 'Section 3 Paragraph', 'type' => 'wysiwyg', 'pair_end'=>'1'],
    ];
}


function get_employee_fields($certification_enabled ="0"){
    $fields = [
    'employee-fields' => [
        'type' => 'visual_section',
        'storage' => "1",
        'fields' => 
            [
            'given_name' => ['type' => 'text', 'label' => 'First Name'],
            'family_name' => ['type' => 'text', 'label' => 'Last Name'],
            'work_email' => ['type' => 'text'],
            'work_phone' => ['type' => 'text'],
            'cell_number' => ['type' => 'text'],
            'job_title' => ['type' => 'text'],
            'employee_bio' => ['type' => 'textarea']
            ]
        ]   
    ];
    if ($certification_enabled ==="1"){
        $certification_fields = get_certification_fields();
        $fields += $certification_fields;
    }
    return $fields;
}
function get_certification_fields() {
  return [
     'certification_data' => [
        'type' => 'visual_split',
        'label' => 'certification_data',
        'storage' => "1",
        'condition'=> ['field' => 'has_certification', 'values' => ["1"], 'current_value' => ''],
        'fields' => [
            'has_certification' => ['type' => 'toggle', 'value' => "0"],
		    'certification_name' => ['type' => 'text', 'label' => 'Certification Name'],
            'certification_id' => ['type' => 'text', 'label' => 'Certification ID'],
            'certification_valid_from' => ['type' => 'date', 'label' => 'Valid From'],
            'certification_expires' => ['type' => 'date', 'label' => 'Expires'],
            'certification_valid_in' => ['type' => 'text', 'label' => 'Region or Area'],
		    'certification_url' => ['type' => 'text', 'label' => 'Certification URL'],   
            'certification_issuer_name' => ['type' => 'text', 'label' => 'Issuer Name'],
            'certification_issuer_url' => ['type' => 'text', 'label' => 'Issuer Website'],
	    	'certification_description'  => ['type' => 'textarea', 'label' => 'Certification Description'],
	    	'certification_logo' => ['type' => 'image', 'label' => 'Certification Logo'],
            ]
        ]
    ];
}

 
function get_landscape_post_image_fields($context_type ='') {
     $fields = [];
  for ($i = 1; $i <= 2; $i++) {
    $fields["dibraco_landscape_{$i}"] = ['type'  => 'image', 'label' => "Landscape Image {$i}" ];
    if ($context_type !== 'unique') {
            $fields["dibraco_landscape_{$i}_lock"] = ['type'  => 'toggle', 'label' => 'Lock Image', 'options_label' => ["0" => 'Locked', "1" => 'Unlocked']];
        }
    }
    return $fields;
}

function get_portrait_post_image_fields($context_type = '') {
   $fields = [];
     for ($i = 1; $i <= 2; $i++) {
        $fields["dibraco_portrait_{$i}"] = ['type'  => 'image', 'label' => "Portrait Image {$i}" ];
        if ($context_type !== 'unique') {
            $fields["dibraco_portrait_{$i}_lock"] = ['type'  => 'toggle', 'label' => 'Lock Image', 'options_label' => ["0" => 'Locked', "1" => 'Unlocked']];
        }
    }
    return $fields;
}
function get_before_after_post_fields(){
    return [
        'dibraco_before_after' => [
            'type'=> 'field_group',
            'storage' => '1',
            'fields' => [
                'dibraco_ba_lock' =>  ['type' => 'toggle', 'label'=>'Lock Image?'],
                'dibraco_before_image' =>  ['type' => 'image',  'label' => 'Before Image'],
                'dibraco_after_image' =>  ['type' => 'image', 'label' => 'After Image'],
            ]
        ],
    ];
}
function get_term_landscape_fields(){
  return [
    'dibraco_landscape_images' => [
      'type' => 'field_group',
      'storage' => "1",
      'fields' => [
        'dibraco_landscape_1' => ['type' => 'image'],
        'dibraco_landscape_2' => ['type' => 'image'],
        'dibraco_landscape_3' => ['type' => 'image'],
        'dibraco_landscape_4' => ['type' => 'image'],
        'dibraco_landscape_5' => ['type' => 'image'],
      ]
    ]
  ];
}

function get_term_portrait_fields(){
  return [
    'dibraco_portrait_images' => [
      'type' => 'field_group',
      'storage' => "1",
      'fields' => [
        'dibraco_portrait_1' => ['type' => 'image'],
        'dibraco_portrait_2' => ['type' => 'image'],
        'dibraco_portrait_3' => ['type' => 'image'],
        'dibraco_portrait_4' => ['type' => 'image'],
        'dibraco_portrait_5' => ['type' => 'image'],
      ]
    ]
  ];
}

function get_type_post_term_icon_field(){
    return ['term_icon' => ['type' => 'image']];
}
function get_before_after_type_term_repeater_fields(){
    return ['dibraco_before_after' => 
        
        [
        'type' => 'repeater',
        'label'=> 'before_after',
        'fields' =>
                [   
                    'before_image' => ['type' => 'image', 'label' => 'Before Image'], 
                    'after_image' => ['type' => 'image', 'label' => 'After Image']
                ]
            ]
        ];
}


function flatten_meta_recursively($array, &$result) {
    foreach ($array as $key => $value) {
        $unserialized_value = maybe_unserialize($value);
        if (is_array($unserialized_value)) {
            flatten_meta_recursively($unserialized_value, $result);
        } else {
            $result[$key] = $unserialized_value;
        }
    }
}