<?php

add_action('init', 'setup_schema_injection_hook');
function setup_schema_injection_hook() {
if (!is_admin()) {
$generate_schema = get_option('company_info')['generate_schema'] ?? '';
if ($generate_schema !=="1"){return;}
        add_action('wp_head', 'inject_dynamic_schema');
    }
}


function inject_dynamic_schema() {

    $current_post_id = get_the_ID();
    if (!$current_post_id) { return; }
    $page_url = get_permalink($current_post_id);
    $home_url = home_url();
    $status = get_option('locations_areas_status');
    if (!$status) { return; }
    $company_data = get_option('company_info');
    $company_schema_type = 'Organization';
    if ($status !== 'multi_locations' && $status !== 'both') {
        $company_schema_type = $company_data['schema'];
    }
     if ($company_data['schema'] ==='Corporation'){
        $company_schema_type = 'Corporation';
    }
    $about_page_url = '';
    $about_page_id = get_option('company_info')['about_page'];
    if (!empty($about_page_id)){
    $about_page_url = trailingslashit(get_permalink($about_page_id));
    }
    $contact_page_url ='';
    $contact_page_id = get_option('company_info')['contact_page'];
    if (!empty($contact_page_id)){
    $contact_page_url = trailingslashit(get_permalink($contact_page_id));
    }
    $main_company_id = home_url() . '#' . $company_schema_type;
    $website_node = _build_website_node($main_company_id);
    $breadcrumb_node = _build_breadcrumb_node($current_post_id);
    $page_url = trailingslashit($page_url);
    $home_url = trailingslashit($home_url);
    
    $main_entity_node = [];
    $webpage_node = [];
    $company_entity_not_on_front = []; 

    if ($page_url === $home_url){
        $main_entity_node = _build_main_company_entity($company_data, $status);
        $webpage_node = _build_webpage_node($current_post_id, $home_url, ['@id' => $main_entity_node['@id']]);
		$main_entity_node['mainEntityOfPage'] = ['@id' => $webpage_node['@id']];
    } else {
        $main_entity_node = generate_combined_schema_graph($status, $current_post_id);
        $company_entity_not_on_front = _build_organization_stub_node($status);
        if (!empty($main_entity_node)) {
            $webpage_node = _build_webpage_node($current_post_id, $page_url, ['@id' => $main_entity_node['@id']]);
            $main_entity_node['mainEntityOfPage'] = ['@id' => $webpage_node['@id']];
        }
    }
    $nodes = [
        $main_entity_node,
        $website_node,
        $company_entity_not_on_front,
        $webpage_node,
        $breadcrumb_node,
    ];
    $nodes_to_graph = [];
    foreach ($nodes as $node){
        $clean_node = _dibraco_filter_schema_recursive($node);
        if (!empty($clean_node)){
            $nodes_to_graph[]=$clean_node;
        }
    }

    if (empty($nodes_to_graph)) { return; }
    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => $nodes_to_graph
    ];


    echo "\n" . '<script type="application/ld+json" class="dibraco-schema-graph">' . json_encode($graph, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

function _build_webpage_node($post_id, $page_url, $main_entity_id_for_page) {
    $page_name = get_the_title($post_id);
    $webpage_node = [
        '@type'         => 'WebPage',
        '@id'           => $page_url . '#webpage', 
        'url'           => $page_url,
        'name'          => $page_name,
        'datePublished' => get_the_date('c', $post_id),
        'dateModified'  => get_the_modified_date('c', $post_id),
        'isPartOf'      => ['@id' => home_url() . '/#website'],
        'breadcrumb'    => ['@id' => $page_url . '/#breadcrumb'],
        'inLanguage'    => 'en-US',
        'mainEntity' => $main_entity_id_for_page
    ];
    
    return $webpage_node;
}

function _build_website_node($main_entity_id) {
    $home_url = home_url();
    $home_url = trailingslashit($home_url);
    $website_entity = [
        '@type'          => 'WebSite',
        '@id'            => $home_url . '/#website', 
        'url'            => $home_url,
        'name'           => get_bloginfo('name'),
        'description'    => get_bloginfo('description'),
        'publisher'      => [
        '@id' => $main_entity_id,
        ],
        'inLanguage'     => get_bloginfo('language'),
        'potentialAction' => [ 
            '@type'       => 'SearchAction',
            'target'      => [
                '@type'       => 'EntryPoint',
                'urlTemplate' => $home_url . '?s={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    return $website_entity;
}
function _build_organization_stub_node($status) {
    $home_url = home_url();
    $home_url = trailingslashit($home_url);
    $company_info = get_option('company_info');
    $phone_number = _format_phone_number_e164($company_info['phone_number']);
    $schema_type = $company_info['schema'];
    if ($status === 'multi_locations' || $status === 'both') {
        if ($schema_type!=='Corporation'){
        	$schema_type = 'Organization';
    	}
	}
    $minimal_entity = [
        '@type' => $schema_type,
        '@id'   => $home_url . '/#' . strtolower($schema_type),
        'name'  => $company_info['name'],
        'url'   => $home_url,
        'telephone' => $phone_number,
    ];
    $logo_data = [];
    $logo_attachment_id = $company_info['company_logo'];
    $logo_url = '';
    if ($logo_attachment_id !==''){
        $logo_attachment_id = (int)$logo_attachment_id;
        $logo_data = wp_get_attachment_metadata($logo_attachment_id);
    $logo_url = wp_get_attachment_image_url($logo_attachment_id, 'full');
    }
    
    $minimal_entity['logo'] = [
        '@type'  => 'ImageObject',
        '@id' => $logo_url . '/#logo',
        'url'    => $logo_url,
        'width'  => $logo_data['width'],
        'height' => $logo_data['height'],
    ];

    return $minimal_entity;
}
function _build_breadcrumb_node($post_id) {
    $page_url = get_permalink($post_id);
    $home_url = home_url();
    $page_url = trailingslashit($page_url);
    $home_url = trailingslashit($home_url);
    $breadcrumbs = ['@type' => 'BreadcrumbList', '@id' => $page_url . '#breadcrumb', 'itemListElement' => []];
    $ancestors = array_reverse(get_post_ancestors($post_id));
    $position = 1;
    if ($page_url === $home_url) {
        $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $position++, 'item' => ['@id' => $page_url, 'name' => get_the_title($post_id)]]; 
        return $breadcrumbs;
    }
    $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $position++, 'item' => ['@id' => $home_url, 'name' => 'Home']];
    foreach ($ancestors as $ancestor_id) {
        $ancestor_url = get_permalink($ancestor_id);
        $ancestor_url = trailingslashit($ancestor_url); 
        if ($ancestor_url !== $home_url) {
            $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $position++, 'item' => ['@id' => $ancestor_url, 'name' => get_the_title($ancestor_id)]];
        }
    }
    $breadcrumbs['itemListElement'][] = ['@type' => 'ListItem', 'position' => $position, 'item' => ['@id' => $page_url, 'name' => get_the_title($post_id)]];
    return $breadcrumbs;
}

function _dibraco_filter_schema_recursive($entity) {
    $filtered_entity = [];
    foreach ($entity as $key => $value) {
        if (is_array($value)) {
            $value = _dibraco_filter_schema_recursive($value);
        } elseif (is_string($value)) {
            if ($key === '@id' || in_array($key, ['mainEntity', 'publisher', 'isPartOf', 'breadcrumb'], true) || (str_ends_with($key, 'id') && str_contains($value, '#'))) {
                $original_value = $value;
                $value = strtolower($value); 
                $base_url = $value;
                $fragment_text = '';
                $has_original_fragment = false;
                if (strpos($original_value, '#') !== false) {
                    list($base_url, $fragment_text) = explode('#', $value, 2);
                    $has_original_fragment = true;
                }
                $base_url = rtrim($base_url, '/');

                if ($has_original_fragment) {
                    $value = $base_url . '/#' . $fragment_text;
                } else {
                    $value = trailingslashit($base_url);
                }
            }
        }
        if ($value !== null && $value !== '' && (!is_array($value) || !empty($value))) {
            $filtered_entity[$key] = $value;
        }
    }
    return $filtered_entity;
}

function generate_combined_schema_graph($status, $current_post_id) {
    $current_post_type = get_post_type($current_post_id);
    $enabled_contexts = get_option('enabled_contexts');
    if (!$enabled_contexts) { return []; }
    foreach ($enabled_contexts as $context_name => $context_data) {
        $context_post_type = $context_data['post_type'];
        if ($context_data['post_type'] !== $current_post_type) {
            continue;
        }
        if (($context_data['context_name']) === 'locations') {
            $term_id = dibraco_get_current_term_id_for_post($current_post_id, $context_data['taxonomy']);
         if ($term_id !==''){
            $term_id =(int)$term_id;
            $main_entity = _build_local_business_entity_from_data($current_post_id, $term_id, $context_data, $status);
          return $main_entity;
              }
              continue;
        }

        if (($context_data['context_name']) === 'service_areas') {
            $term_id = dibraco_get_current_term_id_for_post($current_post_id, $context_data['taxonomy']);
            if ($term_id !==''){
             $term_id =(int)$term_id;
            return _build_service_area_entity($current_post_id, $term_id, $context_data, $status);
            }
           continue;
        }
   
        if (($context_data['context_type']) === 'type') {
              if (($context_data['context_name']) === 'jobs') {
                   $main_entity = build_job_posting_schema($current_post_id, $status);
                   return $main_entity;
                   continue;
              }
            if (($context_data['schema']) === 'Service') {
                $term_id = dibraco_get_current_term_id_for_post($current_post_id, $context_data['taxonomy']);
                if ($term_id !==''){
                $term_id =(int)$term_id;
                $service_entity =  _build_service_entity($current_post_id, $term_id, $context_data, $status);
                return $service_entity;
                }
            }
           
            if (($context_data['schema'] ?? '') === 'Product') {
            }
        }

        if (($context_data['context_type']) === 'unique' && $context_name === 'employee') {
            $employee_entity = _build_employee_entity($current_post_id, $status);
            return $employee_entity;
        }
    }

    return [];
}

function _build_employee_entity($post_id, $status) {
    $page_url = get_permalink($post_id);
    $entity = [
        '@type' => 'Person',
        '@id'   => $page_url . '/#employee',
        'url'   => $page_url,
    ];
    $given_name   = get_post_meta($post_id, 'given_name', true);
    $family_name  = get_post_meta($post_id, 'family_name', true);
    $work_email   = get_post_meta($post_id, 'work_email', true);
    $work_phone   = get_post_meta($post_id, 'work_phone', true);
    $job_title    = get_post_meta($post_id, 'job_title', true);
    $employee_bio = get_post_meta($post_id, 'employee_bio', true);

    $entity['givenName']   = $given_name;
    $entity['familyName']  = $family_name;
    $entity['email']       = $work_email;
    $entity['telephone']   = $work_phone;
    $entity['jobTitle']    = $job_title;
    $entity['description'] = wp_strip_all_tags($employee_bio);
    $full_name_parts = array_filter([$given_name, $family_name]);
    if (!empty($full_name_parts)) {
        $entity['name'] = implode(' ', $full_name_parts);
    }

    $organization_stub = _build_organization_stub_node($status);
    $entity['worksFor'] = ['@id' => $organization_stub['@id']];

    if ($status === 'multi_locations' || $status === 'both') {
        $enabled_connector_contexts = get_option('enabled_connector_contexts');
        $locations_taxonomy = $enabled_connector_contexts['locations']['taxonomy'];

        $location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);

        if (!empty($location_term_id)) {
            $local_business_stub = _build_local_business_stub_from_data($location_term_id);
            $entity['jobLocation'] = ['@id' => $local_business_stub['@id']];
        }
    }

    $employee_image_id = get_post_meta($post_id, 'dibraco_portrait_1', true);
    if ($employee_image_id === '') {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        if ($thumbnail_id) {
            $employee_image_id = $thumbnail_id;
        }
    }

    $employee_image_url = '';

    if ($employee_image_id) {
        $url_from_id = wp_get_attachment_url($employee_image_id);
        if ($url_from_id) {
            $employee_image_url = $url_from_id;
        }
    }

    if ($employee_image_url) {
        $entity['image'] = $employee_image_url;
    }

    return $entity;
}

function _build_main_company_entity($company_data, $status) {
    $home_url = home_url();
    $home_url = trailingslashit($home_url);
    $main_entity_type = $company_data['schema'];
    if ($status === 'multi_locations' || $status === 'both') {
        if ($main_entity_type!=='Corporation'){
        $main_entity_type = 'Organization';
    }}
    $logo_data = wp_get_attachment_metadata($company_data['company_logo']);
    $logo_url = wp_get_attachment_image_url($company_data['company_logo'], 'full');
    $logo = [
        '@type'  => 'ImageObject',
        '@id' => $logo_url . '/#logo',
        'url'    => $logo_url,
        'width'  => $logo_data['width'],
        'height' => $logo_data['height'],
    ];
    $main_entity_id = $home_url . '/#' . $main_entity_type;
    $phone_number = _format_phone_number_e164($company_data['phone_number'])??'';
	$image ='';
	if ($company_data['exterior_image'] !==''){
	$image = wp_get_attachment_image_url($company_data['exterior_image'], 'full');
	}
    $entity = [
        '@type'       => $main_entity_type,
        '@id'         => $main_entity_id,
        'url'         => $home_url,
        'name'        => $company_data['name'],
        'logo'        => $logo,
        'image'       => $image,
        'description' => strip_tags($company_data['company_description']),
        'email'       => $company_data['email_address'],
        'faxNumber'   => $company_data['fax_number'],
        'legalName'   => $company_data['legal_name'],
    ];

   if (!empty($company_data['founding_date'])) {
        $entity['foundingDate'] = date('Y-m-d', strtotime($company_data['founding_date']));
    }
   $enabled_type_contexts = get_option('enabled_type_contexts');
    if (!empty($enabled_type_contexts)) {
        foreach ($enabled_type_contexts as $context_data) {
            if (($context_data['has_certification'] ?? '') !== '1') {
                continue;
            }

            $taxonomy = $context_data['taxonomy'];
            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);

            if (is_wp_error($terms) || empty($terms)) {
                continue;
            }

            foreach ($terms as $term) {
                if (get_term_meta($term->term_id, 'has_certification', true) !== '1') {
                    continue;
                }

                $cert_data = get_term_meta($term->term_id, 'certification_data', true);

                if (empty($cert_data['certification_name']) || isset($seen_cert_names[$cert_data['certification_name']])) {
                    continue;
                }
                
                $term_cert_node = [
                    '@type' => 'Certification',
                    '@id'   => $home_url . '#cert-' . $term->taxonomy . '-' . $term->term_id, // Creates a unique @id
                    'name'  => $cert_data['certification_name'],
                    'url'   => $cert_data['certification_url'] ?? null,
                    'description' => $cert_data['certification_description'] ?? null,
                    'certificationIdentification' => $cert_data['certification_id'] ?? null,
                    'about' => $cert_data['certification_about'] ?? null,
                ];

                if (!empty($cert_data['certification_logo'])) {
                    $term_cert_node['logo'] = wp_get_attachment_url((int)$cert_data['certification_logo']);
                }
                if (!empty($cert_data['certification_valid_from'])) {
                    $term_cert_node['validFrom'] = date('Y-m-d', strtotime($cert_data['certification_valid_from']));
                }
                if (!empty($cert_data['certification_valid_in'])) {
                    $term_cert_node['validIn'] = ['@type' => 'AdministrativeArea', 'name' => $cert_data['certification_valid_in']];
                }
                if (!empty($cert_data['certification_expires'])) {
                    $term_cert_node['expires'] = date('Y-m-d', strtotime($cert_data['certification_expires']));
                }
                if (!empty($cert_data['certification_issuer_name'])) {
                    $term_cert_node['issuedBy'] = [
                        '@type' => 'Organization',
                        'name'  => $cert_data['certification_issuer_name'],
                        'url'   => $cert_data['certification_issuer_url'] ?? null,
                    ];
                }
                
                $all_certifications[] = $term_cert_node;
                $seen_cert_names[$term_cert_node['name']] = true;
            }
        }
    }

    if (!empty($all_certifications)) {
        $entity['hasCertification'] = $all_certifications;
    }

    $company_social_media_data = $company_data['social_media'] ?? []; 
    $social_media_urls = [];
    foreach ($company_social_media_data as $field_key => $link_value) {
        if (!empty($link_value)) { 
            $social_media_urls[] = $link_value;
        }
    }
    $entity['sameAs'] = $social_media_urls;
    $contact_page_id = $company_data['contact_page']??'';
    if ($contact_page_id && !empty($company_data['phone_number'])) {
        $contact_point = [
            '@type' => 'ContactPoint',
            'telephone' => _format_phone_number_e164($company_data['phone_number']),
            'contactType' => 'customer service'
        ];
        $contact_url = get_permalink($contact_page_id);
        if ($contact_url && !is_wp_error($contact_url)) {
            $contact_point['url'] = $contact_url;
        }
        $entity['contactPoint'] = [$contact_point];
    }
    $country = $company_data['addy_country'];
    if (empty($country)){
        $country = 'US';
    }
    if ($status ==='multi_areas' || $status ==='none') {
        $entity['telephone']  = $phone_number;
        $entity['hasMap'] = $company_data['gmb_map_link'];
        $entity['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress'   => trim($company_data['street_address'] . ' ' . $company_data['street_address_2']),
            'addressLocality' => $company_data['city'],
            'addressRegion'   => $company_data['state'],
            'postalCode'      => $company_data['zipcode'],
            'addressCountry'  => $country
        ];
        $entity['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $company_data['latitude'],
            'longitude' => $company_data['longitude']
        ];
        $entity['openingHoursSpecification'] = _build_opening_hours_spec($company_data['hours_of_operation']);
        $enabled_type_contexts = get_option('enabled_type_contexts');
        $all_catalogs = [];
        foreach ($enabled_type_contexts as $type_context_name => $type_context_data) {
            $type_context_schema = $type_context_data['schema'];
            $post_per_term = $type_context_data['post_per_term'];
            $type_post_type = $type_context_data['post_type'];
            $context_name_string = $type_context_data['context_name'];
            if ($type_context_schema === 'Service' && $post_per_term === "1" ) {
                 $option_name = "{$context_name_string}_main_posts";
                 $main_posts_map = get_option($option_name);
                 $offers_for_catalog = [];
                 if(!empty($main_posts_map)){
             foreach ($main_posts_map as $term_id => $post_id) {
                $term = get_term($term_id);
                $service_url = get_permalink($post_id);
                $offers_for_catalog[] = [
                    '@type' => 'Offer',
                    'itemOffered' => [
                        '@type'       => 'Service',
                        '@id'         => $service_url . '#service',
                        'name'        => get_the_title($post_id),
                        'url'         => $service_url,
                        'serviceType' => $term->name
                    ]
                ];
            }
            $name_base = str_replace(['_', '-'], ' ', $type_post_type);
            $catalog_name = ucwords($name_base) . 's';

            $all_catalogs[] = [
                '@type'           => 'OfferCatalog',
                'name'            => $catalog_name,
                'itemListElement' => $offers_for_catalog
            ];
            }
            }
        }
        if (!empty($all_catalogs)) {
            $entity['hasOfferCatalog'] = $all_catalogs;
        }
    }

    if ($status === 'multi_locations' || $status === 'both') {
        $location_taxonomy = get_option('enabled_connector_contexts')['locations']['taxonomy'];
        $location_terms = get_terms(['taxonomy' => $location_taxonomy, 'hide_empty' => true]);
        $location_stubs = [];
		$main_term = '';
		$ignore_main_term = get_option('enabled_connector_contexts')['locations']['ignore_main_term'] ?? '';
		if ($ignore_main_term === "1"){
		$main_term = get_option('enabled_connector_contexts')['locations']['main_term'] ?? '';
		if ($main_term !==''){
			$main_term = (int)$main_term;
		}
		}
        if ($location_terms && !is_wp_error($location_terms)) {
            foreach ($location_terms as $term) {
				$term_id = $term->term_id; 
				if ($term_id === $main_term) {continue;}
                $stub =_build_local_business_stub_from_data($term_id);
                if (!empty($stub)) {
                    $location_stubs[] = $stub;
                }
            }
        }
        $entity['location'] = $location_stubs;
    }
    
    if ($status === 'multi_areas') {
        $service_areas_taxonomy  = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];;
        $service_area_terms = get_terms(['taxonomy' => $service_areas_taxonomy, 'hide_empty' => false]);
        $service_areas = [];
        $coordinate_pairs = [];
        if (!empty($service_area_terms)) {
            foreach ($service_area_terms as $term) {
                $city = _build_city_objects_for_area_served($term->term_id);
                $service_areas[] = $city;
                if (isset($city['geo'])) {
                    $coordinate_pairs[] = [$city['geo']['latitude'], $city['geo']['longitude']];
                }
            }
        }
        if (count($coordinate_pairs) > 2) {
            $polygon_string = create_geoshape_polygon_string_from_service_areas($coordinate_pairs);
            $service_areas[] = [ "@type"   => "GeoShape", "polygon" => $polygon_string ];
        }
        $entity['areaServed'] = $service_areas;
    }

    return $entity;
}

function _build_local_business_entity_from_data($post_id, $location_term_id, $context_data, $status) {
    $term_meta = get_term_meta($location_term_id);
    $term_meta_data = [];
    foreach ($term_meta as $key => $value) {
        $term_meta_data[$key] = $value[0] ?? '';
    }

    $company_info = get_option('company_info');
    $company_main_logo_id = $company_info['company_logo'];
    $company_schema = $company_info['schema'];
    $location_page_url = get_permalink($post_id);
    $schema_type = $term_meta_data['schema'];
    $location_logo_id = $term_meta_data['location_logo'];
    $schema_type = $term_meta_data['schema'];
    $exterior_image_id = $term_meta_data['exterior_image'];
    $hours_data = get_term_meta($location_term_id, 'hours_of_operation', true) ?? [];
    $social_media_data =  get_term_meta($location_term_id, 'social_media', true) ?? [];
    $location_description = '';
    $location_description = get_post_meta($post_id, 'da_about_blurb', true) ?? '';
    if ($location_description === '') {
        $location_description = get_post_meta($post_id, 'da_banner_description', true);
    }
    if ($location_description !== '') {
        $location_description = strip_tags($location_description);
    }
    $street_address = trim($term_meta_data['street_address'] . ' ' . $term_meta_data['street_address_2']);
      if ($exterior_image_id !== '') {
            $exterior_image_id = (int)$exterior_image_id;
      }
    $exterior_image_url = wp_get_attachment_image_url($exterior_image_id);
    $parent_organization = ['@id' => home_url('/#organization') ];
	if ($company_schema === "Corporation"){
		$parent_organization= ['@id' => home_url('/#corporation') ];
	}
	$entity = [
        '@type'     => $schema_type,
        '@id'       => $location_page_url . '#' . strtolower($schema_type),
        'name'      => $term_meta_data['location_name'],
        'url'       => $location_page_url,
        'description' => $location_description,
        'image'     => $exterior_image_url,
        'telephone' => _format_phone_number_e164($term_meta_data['phone_number']),
        'email'     => $term_meta_data['email_address'],
        'faxNumber' => $term_meta_data['fax_number'],
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street_address,
            'addressLocality' => $term_meta_data['city'],
            'addressRegion'   => $term_meta_data['state'],
            'postalCode'      => $term_meta_data['zipcode'],
            'addressCountry'  => $term_meta_data['addy_country'],
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $term_meta_data['latitude'],
            'longitude' => $term_meta_data['longitude'],
        ],
         'parentOrganization' => $parent_organization,
         'hasMap' => $term_meta_data['gmb_map_link'],
         'openingHoursSpecification' => _build_opening_hours_spec($hours_data),
    ];
    if ($location_logo_id !== $company_main_logo_id) {
        $logo_url = wp_get_attachment_image_url($location_logo_id, 'full');
        $logo_data = wp_get_attachment_metadata($location_logo_id);

        if (!empty($logo_url)) {
            $entity['logo'] = [
                '@type'  => 'ImageObject',
                '@id'    => $logo_url . '/#logo',
                'url'    => $logo_url,
                'width'  => $logo_data['width'] ?? null,
                'height' => $logo_data['height'] ?? null,
            ];
        }
    }
    $social_media_urls = [];
    foreach ($social_media_data as $field_key => $value) { 
        if (!empty($value)) {
              $social_media_urls[] = $value; 
        }
    }
    $entity['sameAs'] = $social_media_urls;

    if ($status === 'both') {
        $associated_area_ids = get_term_meta($location_term_id, 'associated_act_terms', true);

        $service_areas = [];
        $coordinate_pairs = [];

        foreach ($associated_area_ids as $area_id) {
                $city = _build_city_objects_for_area_served($area_id);
                $service_areas[] = $city;
                if (isset($city['geo'])) {
                    $coordinate_pairs[] = [
                        (float)$city['geo']['latitude'],
                        (float)$city['geo']['longitude']
                    ];
                }
            }
        

        if (count($coordinate_pairs) > 2) {
            $polygon_string = create_geoshape_polygon_string_from_service_areas($coordinate_pairs);
            $service_areas[] = ['@type' => 'GeoShape', 'polygon' => $polygon_string];
        }
        $entity['areaServed'] = $service_areas;

    } 
    if (!empty($context_data['related_type_contexts'])) {
        foreach (($context_data['related_type_contexts']) as $type_context_name => $type_context_data) {
    if ($type_context_data['post_per_term'] === "1") {
                
                if ($type_context_data['schema'] === 'Service') {
                    $new_catalog = build_offer_catalog_from_context($type_context_name, $type_context_data, $location_term_id);
                    if ($new_catalog) {
                        if (!isset($entity['hasOfferCatalog'])) {
                            $entity['hasOfferCatalog'] = [];
                        }
                        $entity['hasOfferCatalog'][] = $new_catalog;
                    }
                } else {
                    //placeholderfornonservice
                }
    } elseif ($type_context_data['post_per_term'] !== "1") {
        //placeholderhere
    }
    }
    }
    if (!empty($context_data['related_unique_contexts'])){
    foreach (($context_data['related_unique_contexts']) as $context_name => $context_def) {
        if ($context_name === 'employee') {
            $employee_data = $term_meta['related_unique_employee'][0] ?? [];
            if (is_array($employee_data)) {
                $entity['numberOfEmployees'] = count($employee_data);
            }
        }
    }
    }
    return $entity;
}


function _build_service_area_entity($post_id, $term_id, $context_data, $status) {
    $page_url = get_permalink($post_id);
    $term_object = get_term($term_id);
    $main_provider_id = '';
    $provider_stub = [];
    if ($status === 'both') {
     $parent_loc_id = get_term_meta($term_id, 'area_parent_location_term', true);
     if (!empty($parent_loc_id)){
        $parent_loc_id = (int)$parent_loc_id;
     $provider_stub = _build_local_business_stub_from_data($parent_loc_id);
     }
     if(empty($provider_stub)){
     $provider_stub = _build_organization_stub_node($status);
     }
    } else { 
     $provider_stub = _build_organization_stub_node($status);
    }
    $area_description ='';
    $area_description = get_post_meta($post_id, 'da_about_blurb', true) ?? '';
    if ($area_description ===''){
        $area_description = get_post_meta($post_id, 'da_banner_description', true) ?? '';
    } 
    if ($area_description !==''){
         $area_description = strip_tags($area_description); 
    }
    $entity = [
        '@type'      => ['Service', 'City'],
        '@id'        => $page_url . '#service',
        'name'       => 'Services in ' . $term_object->name,
        'description' => $area_description,
        'url'        => $page_url,
        'areaServed' => _build_city_objects_for_area_served($term_id),
        'provider'   => ['@id' => $provider_stub['@id']],
    ];


    if (!empty($context_data['related_type_contexts'])) {
        foreach (($context_data['related_type_contexts']) as $type_context_name => $type_context_data) {
    if ($type_context_data['post_per_term'] === "1") {
                
                if ($type_context_data['schema'] === 'Service') {
                    $new_catalog = build_offer_catalog_from_context($type_context_name, $type_context_data, $term_id);
                    if ($new_catalog) {
                        if (!isset($entity['hasOfferCatalog'])) {
                            $entity['hasOfferCatalog'] = [];
                        }
                        $entity['hasOfferCatalog'][] = $new_catalog;
                    }
                } else {
                    //placeholderfornonservice
                }
    } elseif ($type_context_data['post_per_term'] !== "1") {
        //placeholderhere
    }
    }
    }
    if (!empty($context_data['related_unique_contexts'])){
        foreach (($context_data['related_unique_contexts']) as $context_name => $context_def) {
            if ($context_name === 'employee') {
                continue;
            }
        }
    }
    
    return $entity;
}

function _build_service_entity($post_id, $type_term_id, $context_data, $status) {
    $page_url = get_permalink($post_id);
    $entity = [
        '@type' => 'Service',
        '@id'   => $page_url . '/#service',
        'url'   => $page_url,
    ];
    $area_name = '';

    if ($status === 'multi_areas') {
        $service_areas_taxonomy = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
        $service_area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_areas_taxonomy);
        if ($service_area_term_id !== '') {
            $entity['areaServed'] = _build_city_objects_for_area_served($service_area_term_id);
            $area_name = get_term($service_area_term_id)->name;
            $provider_stub = _build_organization_stub_node($status);
            $entity['provider'] = ['@id' => $provider_stub['@id']];
        }
    } elseif ($status === 'multi_locations') {
        $locations_taxonomy = get_option('enabled_connector_contexts')['locations']['taxonomy'];
        $location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
        if ($location_term_id !== '') {
            $provider_stub = _build_local_business_stub_from_data($location_term_id);
            $entity['provider'] = $provider_stub;
            $location_city_name = get_term($location_term_id)->name;
            $entity['areaServed'] = ['@type' => 'City', 'name' => $location_city_name];
            $area_name = $location_city_name;
        } else {
            $provider_stub = _build_organization_stub_node($status);
            $entity['provider'] = ['@id' => $provider_stub['@id']];
        }
    } elseif ($status === 'both') {
        $relevant_location_term_id = da_get_location_term_or_default($post_id);
        if ($relevant_location_term_id !== null) {
            $provider_stub = _build_local_business_stub_from_data($relevant_location_term_id);
            $entity['provider'] = $provider_stub;
            $service_areas_taxonomy = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
            $current_service_area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_areas_taxonomy);

            if (!empty($current_service_area_term_id)) {
                $entity['areaServed'] = _build_city_objects_for_area_served($current_service_area_term_id);
                $area_name = get_term($current_service_area_term_id)->name;
            } else {
                $location_city_name = get_term($relevant_location_term_id)->name;
                $entity['areaServed'] = ['@type' => 'City', 'name' => $location_city_name];
                $area_name = $location_city_name;
            }
        } else {
            $provider_stub = _build_organization_stub_node($status);
            $entity['provider'] = ['@id' => $provider_stub['@id']];
            $company_info = get_option('company_info');
            if (!empty($company_info['city'])) {
                $entity['areaServed'] = ['@type' => 'City', 'name' => $company_info['city']];
                $area_name = $company_info['city'];
            }
        }
    }
    
    $service_type_name = get_term($type_term_id)->name;
    $entity['name'] = !empty($area_name) ? "{$service_type_name} in {$area_name}" : $service_type_name;

    if ($context_data['dibraco_banner'] === '1') {
        $banner_description = get_post_meta($post_id, 'da_banner_description', true);
        if (!empty($banner_description)) {
            $banner_description = strip_tags($banner_description);
            $entity['description'] = $banner_description;
        }
    }
    $image_found = false;
    if ($context_data['repeater_images'] === '1') {
        $landscape_image = get_post_meta($post_id, 'dibraco_landscape_1', true);
        if (!empty($landscape_image)) {
            $entity['image'] = wp_get_attachment_url($landscape_image);
            $image_found = true;
        }
    }
    if (!$image_found && has_post_thumbnail($post_id)) {
        $entity['image'] = get_the_post_thumbnail_url($post_id, 'full');
    }
    $entity['serviceType'] = $service_type_name;
    
    if (($context_data['has_certification']) === '1') {
        $has_certification_term_meta = get_term_meta($type_term_id, 'has_certification', true);

        if ($has_certification_term_meta === "1") { 
            $certification_data = get_term_meta($type_term_id, 'certification_data', true);

            $certification_node = [
                '@type' => 'Certification',
                '@id'   => $page_url . '#certification',
                'name'  => $certification_data['certification_name'],
                'url'   => $certification_data['certification_url'],
                'description' => $certification_data['certification_description'],
                'certificationIdentification' => $certification_data['certification_id'],
                'about' => $certification_data['certification_about'],
            ];
            if (!empty($certification_data['certification_logo'])) {
                $cert_logo_id = (int)$certification_data['certification_logo'];
                $certification_node['logo'] = wp_get_attachment_url($cert_logo_id);
            }

            if (!empty($certification_data['certification_valid_from'])) {
                $certification_node['validFrom'] = date('Y-m-d', strtotime($certification_data['certification_valid_from']));
            }
if (!empty($certification_data['certification_valid_in'])) { // Including validIn as you specified
            $certification_node['validIn'] = [
                '@type' => 'AdministrativeArea',
                'name' => $certification_data['certification_valid_in']
            ];
        }
            if (!empty($certification_data['certification_expires'])) {
                $certification_node['expires'] = date('Y-m-d', strtotime($certification_data['certification_expires']));
            }

           
            if (!empty($certification_data['certification_issuer_name'])) {
                $certification_node['issuedBy'] = [
                    '@type' => 'Organization',
                    'name' => $certification_data['certification_issuer_name'],
                    'url' => $certification_data['certification_issuer_url']
                ];
            }

            $entity['hasCertification'] = $certification_node;
        } 
    }

    return $entity;
}



function build_rows_post_per_term($type_context_name, $context_data, $connector_term_id) {
    $meta_key  = "related_type_{$type_context_name}";
    $saved_data = get_term_meta($connector_term_id, $meta_key, true);
    $rows = [];
    foreach ($saved_data as $type_term_id => $term_data) {
            $term_name = get_term($type_term_id)->name;
            $url = $term_data['related_post_url'];
            $title = $term_data['related_post_title'];
        if ($url === '') {                          
            $url = $term_data['fallback_url'];
        }
        $rows[$type_term_id] = [
            'title' => $title,
            'url'   => $url,
            'servicetype' => $term_name
        ];
    }
    return $rows;
}

function get_display_rows_multi_post($context_name, $context_data, $term_id) {
    $meta_key = "related_type_{$context_name}";
    $full_data = get_term_meta($term_id, $meta_key, true);
    if (!is_array($full_data)) {return [];} 
    $rows = [];
    foreach ($full_data as $type_term_id => $posts) {
        if (!is_array($posts)) { continue;  } 
        foreach ($posts as $post_id => $entry) {
            $rows[] = [ 
                'title' => $entry['related_post_title'],
                'url'   => $entry['related_post_url'],
                ];
        }
    }
    return $rows;
}
function build_offer_catalog_from_context($type_context_name, $type_context_data, $location_term_id) {
    $rows = build_rows_post_per_term($type_context_name, $type_context_data, $location_term_id);
    if (empty($rows)) {
        return null;
    }
    $offers_for_catalog = [];
    foreach ($rows as $row_item) {
        $service_url = $row_item['url'];
        $service_title = $row_item['title'];
        $service_type_name = $row_item['servicetype'];
        $current_service_entity = [
            '@type' => 'Service',
            '@id'   => $service_url . '#Service',
            'name'  => $service_title,
            'url'   => $service_url,
            'serviceType' => $service_type_name
        ];
        $offers_for_catalog[] = [
            '@type' => 'Offer',
            'itemOffered' => $current_service_entity
        ];
    }
    $offer_catalog_entity = [
        '@type' => 'OfferCatalog',
        'name'  => $type_context_data['taxonomy'],
        'itemListElement' => $offers_for_catalog
    ];
    return $offer_catalog_entity;
}

function _build_local_business_stub_from_data($term_id) {
    $url = get_term_meta($term_id, 'location_link_url', true);
    if($url === ''){
        return [];
    }
    $phone = get_term_meta($term_id, 'phone_number', true);
    $schema_type = get_term_meta($term_id, 'schema', true);
    $name = get_term_meta($term_id, 'location_name', true);
    if ($name ==='') {
        $name = get_option('company_info')['name'];
    }
    $hours_data = get_term_meta($term_id, 'hours_of_operation', true);
        if (!is_array($hours_data)) {
        $hours_data = []; 
    }
    $exterior_image_id =  get_term_meta($term_id, 'exterior_image', true);
    if ($exterior_image_id!==''){
        $exterior_image_id= (int)$exterior_image_id;
      $exterior_image_url = wp_get_attachment_image_url($exterior_image_id);
    }
	$company_schema = get_option('company_info')['schema'];
	$parent_organization = ['@id' => home_url('/#organization')];
	if ($company_schema === 'Corporation'){
		$parent_organization = ['@id' => home_url('/#corporation')];
	}
	$street_address = get_term_meta($term_id, 'street_address', true);
	$city = get_term_meta($term_id, 'city', true);
	$state = get_term_meta($term_id, 'state', true);
	$zipcode = get_term_meta($term_id, 'zipcode', true);
    $country =  get_term_meta($term_id, 'addy_country', true);
    $lat = get_term_meta($term_id, 'latitude', true);
    $long = get_term_meta($term_id, 'longitude', true);
    $map =   get_term_meta($term_id, 'gmb_map_link', true);
    $status = get_option('locations_areas_status');
        $stub = [
        '@type' => $schema_type,
        '@id'   => $url . '#' . strtolower($schema_type),
        'name'  => $name,
        'url'   => $url,
        'image' => $exterior_image_url,
        'priceRange' => '$$',
        'address'   => [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street_address,
            'addressLocality' => $city,
            'addressRegion'   => $state,
            'postalCode'      => $zipcode,
            'addressCountry'  => $country
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $lat,
            'longitude' => $long,
        ],
        'hasMap' => $map,
        'telephone' =>  _format_phone_number_e164($phone),
        'openingHoursSpecification' => _build_opening_hours_spec($hours_data),
        'parentOrganization' => $parent_organization
    ];
    
    return $stub;
}
function _build_city_objects_for_area_served($term_id) {
    $service_area_name = get_term($term_id)->name;
    $city = get_term_meta($term_id, 'city', true);
    if ($city === '') {
        $city = $service_area_name;
    }

    $city_object = [
        '@type' => 'City',
        'name'  => $city,
    ];

    $latitude  = get_term_meta($term_id, 'latitude', true);
    $longitude = get_term_meta($term_id, 'longitude', true);

    if (!empty($latitude) && !empty($longitude)) {
        $city_object['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $latitude,
            'longitude' => $longitude
        ];
    }

    $url = get_term_meta($term_id, 'service_area_link_url', true);
    if (!empty($url)) {
        $city_object['url'] = $url;
        $city_object['@id'] = $url . '#city';
    }

    return $city_object;
}

function create_geoshape_polygon_string_from_service_areas($coordinate_pairs) {
    $polygon_points = [];
    foreach ($coordinate_pairs as $pair) {
        $polygon_points[] = [(float)$pair[1], (float)$pair[0]];
    }
    $n = count($polygon_points);
    $centroid = ['x' => 0, 'y' => 0];
    foreach ($polygon_points as $point) {
        $centroid['x'] += $point[0];
        $centroid['y'] += $point[1];
    }
    $centroid['x'] /= $n;
    $centroid['y'] /= $n;
    usort($polygon_points, function($a, $b) use ($centroid) {
        $angleA = atan2($a[1] - $centroid['y'], $a[0] - $centroid['x']);
        $angleB = atan2($b[1] - $centroid['y'], $b[0] - $centroid['x']);
        return $angleA <=> $angleB;
    });
    if ($polygon_points[0] !== end($polygon_points)) {
        $polygon_points[] = $polygon_points[0];
    }
    $formatted_polygon_string = '';
    foreach ($polygon_points as $i => $point) {
        $formatted_polygon_string .= $point[1] . ' ' . $point[0];
        if ($i < count($polygon_points) - 1) {
            $formatted_polygon_string .= ' ';
        }
    }
    return $formatted_polygon_string;
}



function _format_phone_number_e164($phone) {
  $extension = '';
    $main_number = $phone;
    if (preg_match('/(ext|ext\.|x|#)\s*(\d+)/i', $phone, $matches)) {
        $extension = ' ext. ' . $matches[2];
        $main_number = str_replace($matches[0], '', $phone);
    }
    $numeric_phone = preg_replace('/[^0-9]/', '', $main_number);
    if (strlen($numeric_phone) == 11 && $numeric_phone[0] == '1') {
        $numeric_phone = substr($numeric_phone, 1);
    }
    if (strlen($numeric_phone) == 10) {
        return '+1' . $numeric_phone . $extension;
    }
    return '';
}
function _build_opening_hours_spec($hours_data) {
  $days_map =  get_dibraco_day_map();
  if (($hours_data['open_247']) === "1") {
        $all_days_schema = [];
        foreach ($days_map as $day_full => $_) {
            $all_days_schema[] = 'https://schema.org/' . ucfirst($day_full);
        }
        return [[ '@type'     => 'OpeningHoursSpecification', 'dayOfWeek' => $all_days_schema, 'opens' => '00:00', 'closes' => '23:59' ]];
    }
    $grouped_hours = [];
    foreach ($days_map as $day_full => $day_abbr) {
        if (($hours_data["open_{$day_full}"]) === "1") {
            $opens = date("H:i", strtotime($hours_data["{$day_abbr}_open_hour"]));
            $closes = date("H:i", strtotime($hours_data["{$day_abbr}_close_hour"]));
            if ($opens && $closes) {
                $time_key = "{$opens}-{$closes}";
                $grouped_hours[$time_key][] = 'https://schema.org/' . ucfirst($day_full);
            }
        }
    }
    $daily_specs = [];
    foreach ($grouped_hours as $time_key => $days_array) {
        list($opens, $closes) = explode('-', $time_key);
        $daily_specs[] = [ '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $days_array, 'opens' => $opens, 'closes' => $closes ];
    }
    return $daily_specs;
}