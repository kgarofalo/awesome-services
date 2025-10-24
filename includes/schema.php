<?php
if ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() ) {
add_action('init', 'setup_schema_injection_hook');
function setup_schema_injection_hook() {
$generate_schema = get_option('company_info')['generate_schema'] ?? '';
if ($generate_schema !=="1"){return;}
        DibracoSchemaEnv::init();
        add_action('wp_head', 'inject_dynamic_schema');
    }
}

class DibracoSchemaEnv {
    // --- Properties ---
    public static $status;
    public static $company_info;
    public static $company_schema_type;
    public static $show_address_on_org ="1";
    public static $locations_context = [];
    public static $service_areas_context = [];
    public static $act_to_lct_assignments = [];
    public static $act_to_lct_slug_assignments = [];
    public static $home_url;
    public static $company_entity_id;
    public static $website_id;
    public static $logo_node;
    public static $website_node;
    public static $about_page_url;
    public static $contact_page_url;
    public static $organization_stub_node;
    public static $marker_icon_url;
    public static function init() {
        self::$company_info = get_option('company_info');
        self::$company_schema_type = self::$company_info['schema'];
        self::$status = get_option('locations_areas_status');
        
        if (!self::$status) return;
        $map_pin_id = get_option('company_info')['map_pin'];
        self::$marker_icon_url = wp_get_attachment_url($map_pin_id);  
        $connector_contexts = get_option('enabled_connector_contexts');
        if (in_array(self::$status, ['multi_locations', 'both'], true)) {
            if (!in_array(self::$company_schema_type, ['Corporation', 'NGO', 'MedicalOrganization'], true)) {
                self::$company_schema_type = 'Organization';
            }
            self::$locations_context = $connector_contexts['locations'];
            self::$show_address_on_org = self::$company_info['show_address_on_org'];
            if (self::$status === 'both') {
                self::$service_areas_context = $connector_contexts['service_areas'];
                self::$act_to_lct_assignments = get_option('act_to_lct_assignments');
                self::$act_to_lct_slug_assignments = get_option('act_to_lct_slug_assignments');
            }
        }
        
        self::$home_url = trailingslashit(home_url());
        self::$company_entity_id = self::$home_url . '#' . strtolower(self::$company_schema_type);
        self::$website_id = self::$home_url . '#website';
         self::$about_page_url = '';
        $about_page_id = self::$company_info['about_page'];
        if (!empty($about_page_id)) {
            self::$about_page_url = trailingslashit(get_permalink((int)$about_page_id));
        }

        self::$contact_page_url = '';
        $contact_page_id = self::$company_info['contact_page'];
        if (!empty($contact_page_id)) {
            self::$contact_page_url = trailingslashit(get_permalink((int)$contact_page_id));
        }
        self::$logo_node = self::build_logo_node();
        self::$website_node = self::build_website_node();
        self::$organization_stub_node = self::build_organization_stub_node();
    }
public static function build_logo_node() {
        $logo_url = self::$company_info['company_logo'];
        if (empty($logo_id)) {return;}
        $logo_id = attachment_url_to_postid($logo_url);
        $logo_data = wp_get_attachment_metadata($logo_id);
        return [
            '@type'   => 'ImageObject',
            '@id'     => self::$home_url . '#logo',
            'url'     => $logo_url,
            'width'   => $logo_data['width'],
            'height'  => $logo_data['height'],
            'caption' => self::$company_info['name']
        ];
    }
 public static function build_organization_stub_node() {
        $entity = [
            '@type' => self::$company_schema_type,
            '@id'   => self::$company_entity_id,
            'name'  => self::$company_info['name'],
            'url'   => self::$home_url,
        ];

        if (self::$show_address_on_org === '1') {
            $entity['telephone'] = format_phone_number_e164(self::$company_info['phone_number']);
        } else {
            $entity['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => format_phone_number_e164(self::$company_info['phone_number']),
                'contactType' => 'customer service',
                'url' => self::$contact_page_url
            ];
        }

        if (!empty(self::$logo_node)) {
            $entity['logo'] = [
                '@id' => self::$logo_node['@id']
            ];
        }
        
        return $entity;
    }

 public static function build_website_node() {
        $publisher = [ '@type' => self::$company_schema_type,
            '@id' => self::$company_entity_id];

        return [
            '@type' => 'WebSite',
            '@id' => self::$website_id,
            'url' => self::$home_url,
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'publisher' => $publisher,
            'inLanguage'  => get_bloginfo('language'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => self::$home_url . '?s={search_term_string}', ],
                'query-input' => 'required name=search_term_string', ],
        ];
    }
}

function inject_dynamic_schema() {
    $current_post_id = get_the_ID();
    if (!$current_post_id) { return; }
    $page_url = trailingslashit(get_permalink($current_post_id));
    if (!DibracoSchemaEnv::$status) { return; }
    $breadcrumb_node = build_breadcrumb_node($current_post_id);
    $main_entity_node = [];
    $webpage_node = [];
    $company_entity_not_on_front = []; 
    if ($page_url === DibracoSchemaEnv::$home_url) {
        $main_entity_node = build_main_company_entity();
        $webpage_node = build_webpage_node($current_post_id, $page_url, ['@id' => $main_entity_node['@id']]);
        $main_entity_node['mainEntityOfPage'] = ['@id' => $webpage_node['@id']];
    } else {
        $main_entity_node = generate_combined_schema_graph($current_post_id);
        if (!empty($main_entity_node)) {
            $company_entity_not_on_front = DibracoSchemaEnv::$organization_stub_node;
            $webpage_node = build_webpage_node($current_post_id, $page_url, ['@id' => $main_entity_node['@id']]);
            $main_entity_node['mainEntityOfPage'] = ['@id' => $webpage_node['@id']];
        }
    }

    $nodes = [
        $main_entity_node,
        DibracoSchemaEnv::$website_node,
        $company_entity_not_on_front,
        $webpage_node,
        $breadcrumb_node,
        DibracoSchemaEnv::$logo_node,
    ];

    $nodes_to_graph = [];
    foreach ($nodes as $node) {
        $clean_node = _dibraco_filter_schema_recursive($node);
        if (!empty($clean_node)) {
            $nodes_to_graph[] = $clean_node;
        }
    }

    if (empty($nodes_to_graph)) { return; }

    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => $nodes_to_graph
    ];
    echo "\n" . '<script type="application/ld+json" class="dibraco-schema-graph">' . json_encode($graph, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

function build_webpage_node($post_id, $page_url, $main_entity_id_for_page) {
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

function build_breadcrumb_node($post_id) {
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

function generate_combined_schema_graph($current_post_id) {
    $status = DibracoSchemaEnv::$status;
    $current_post_type = get_post_type($current_post_id);
    $enabled_contexts = get_option('enabled_contexts');
    if (!$enabled_contexts) { return []; }
    foreach ($enabled_contexts as $context_name => $context_data) {
        $context_post_type = $context_data['post_type'];
        if ($context_data['post_type'] !== $current_post_type) { continue; }
        if (($context_data['context_name']) === 'locations') {
            $term_id = dibraco_get_current_term_id_for_post($current_post_id, $context_data['taxonomy']);
         if ($term_id !==''){
            $term_id =(int)$term_id;
            $main_entity = build_local_business_entity_from_data($current_post_id, $term_id, $context_data, $status);
          return $main_entity;
              } continue; }
        if (($context_data['context_name']) === 'service_areas') {
            $term_id = dibraco_get_current_term_id_for_post($current_post_id, $context_data['taxonomy']);
            if ($term_id !==''){
             $term_id =(int)$term_id;
            return build_service_area_entity($current_post_id, $term_id, $context_data, $status);
            } continue; }
   
        if (($context_data['context_type']) === 'type') {
              if (($context_data['context_name']) === 'jobs') {
                   $main_entity = build_job_posting_schema($current_post_id, $status);
                   return $main_entity;
                   continue; }
            if (($context_data['schema']) === 'Service') {
                $term_id = dibraco_get_current_term_id_for_post($current_post_id, $context_data['taxonomy']);
                if ($term_id !==''){
                $term_id =(int)$term_id;
                $service_entity =  build_service_entity($current_post_id, $term_id, $context_data, $status);
                return $service_entity; }
            }
            if (($context_data['schema'] ?? '') === 'Product') {
            }
        }
        if (($context_data['context_type']) === 'unique' && $context_name === 'employee') {
            $employee_entity = build_employee_entity($current_post_id, $status);
            return $employee_entity;
        }
    }

    return [];
}

function build_employee_entity($post_id) {
$status = DibracoSchemaEnv::$status;

    $page_url = get_permalink($post_id);
    $entity = [
        '@type' => 'Person',
        '@id'   => $page_url . '/#employee',
        'url'   => $page_url,
    ];
   $employee_data = get_post_meta($post_id, 'employee-fields', true);

     $entity['givenName']   = $employee_data['given_name'];
    $entity['familyName']  = $employee_data['family_name'];
    $entity['email']       = $employee_data['work_email'];
    $entity['telephone']   = $employee_data['work_phone'];
    $entity['jobTitle']    = $employee_data['job_title'];
    $entity['description'] = wp_strip_all_tags($employee_data['employee_bio']);

    $organization_stub = DibracoSchemaEnv::build_organization_stub_node();
    $entity['worksFor'] = ['@id' => $organization_stub['@id']];

    if ($status === 'multi_locations' || $status === 'both') {
        $enabled_connector_contexts = get_option('enabled_connector_contexts');
        $locations_taxonomy = $enabled_connector_contexts['locations']['taxonomy'];

        $location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);

        if (!empty($location_term_id)) {
            $local_business_stub = build_local_business_stub_from_data($location_term_id);
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

function build_main_company_entity() {
    $company_info = DibracoSchemaEnv::$company_info;
    $status = DibracoSchemaEnv::$status;
    $show_address_on_org = DibracoSchemaEnv::$show_address_on_org;
    $image = '';
    if ($show_address_on_org === '1' && !empty($company_info['exterior_image'])) {
        $image = wp_get_attachment_image_url($company_info['exterior_image'], 'full');
    }
   $telephone = format_phone_number_e164($company_info['phone_number']);
   $entity = [
        '@type'       => DibracoSchemaEnv::$company_schema_type,
        '@id'         => DibracoSchemaEnv::$company_entity_id,
        'url'         => DibracoSchemaEnv::$home_url,
        'name'        => $company_info['name'],
        'image'       => $image,
        'description' => strip_tags($company_info['company_description']),
        'email'       => $company_info['email_address'],
        'faxNumber'   => $company_info['fax_number'],
        'legalName'   => $company_info['legal_name'],
        'foundingDate'=> $company_info['founding_date'],
    ];

    if (!empty(DibracoSchemaEnv::$logo_node)) {
        $entity['logo'] = ['@id' => DibracoSchemaEnv::$logo_node['@id']];
    }
    
    $company_social_media_data=$company_info['social_media'];
    $social_media_urls = [];
    foreach ($company_social_media_data as $field_key => $link_value) {
        if (!empty($link_value)) { 
            $social_media_urls[] = $link_value;
        }
    }
    $entity['sameAs'] = $social_media_urls;
     if ($show_address_on_org === "1") {
        $entity['telephone']  = $telephone;
        $entity['hasMap'] = $company_info['normal_map'];
         $entity['address'] = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => trim($company_info['street_address'] . ' ' . $company_info['street_address_2']),
            'addressLocality' => $company_info['city'],
            'addressRegion'   => $company_info['state'],
            'postalCode'      => $company_info['zipcode'],
            'addressCountry'  => $company_info['addy_country'] ?? 'US'
        ];
        $entity['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $company_info['latitude'],
            'longitude' => $company_info['longitude']
        ];
        $entity['openingHoursSpecification'] = build_opening_hours_spec($company_info['hours_of_operation']);
   }
   if ($show_address_on_org ==='0'){
       if (DibracoSchemaEnv::$contact_page_url && !empty($company_info['phone_number'])) {
            $entity['contactPoint'] = [[
                '@type'       => 'ContactPoint',
                'telephone'   => $telephone,
                'contactType' => 'customer service',
                'url'         => DibracoSchemaEnv::$contact_page_url
            ]];
        }
    }
   
     $all_certifications = [];
    
    $enabled_type_contexts = get_option('enabled_type_contexts');
    if (!empty($enabled_type_contexts)) {
        foreach ($enabled_type_contexts as $type_context_data) {
           if ($type_context_data['post_per_term'] === "1" && $type_context_data['has_certification'] !== '1'){ continue; }
            $type_taxonomy = $type_context_data['taxonomy'];
            $type_terms = get_terms(['taxonomy' => $type_taxonomy, 'hide_empty' => true]);
            foreach ($type_terms as $type_term) {
                if (get_term_meta($type_term->term_id, 'has_certification', true) !== '1') { continue; }
                $cert_data = get_term_meta($type_term->term_id, 'certification_data', true);
                
                if (!empty($cert_data)){
                    $term_cert_node = [
                        '@type' => 'Certification',
                        '@id' => DibracoSchemaEnv::$home_url . '#cert-' . $type_term->name,
                        'name'  => $cert_data['certification_name'],
                        'url'   => $cert_data['certification_url'],
                        'description' => $cert_data['certification_description'],
                        'certificationIdentification' => $cert_data['certification_id'],
                        'validFrom' => $cert_data['certification_valid_from'],
                        'expires' => $cert_data['certification_expires']
                    ];

                    if ($cert_data['certification_logo']) {
                        $term_cert_node['logo'] = wp_get_attachment_url((int)$cert_data['certification_logo']);
                    }
                    if ($cert_data['certification_valid_in']) {
                        $term_cert_node['validIn'] = [
                            '@type' => 'AdministrativeArea',
                            'name'  => $cert_data['certification_valid_in']
                        ];
                    }
                    if ($cert_data['certification_issuer_name']) {
                        $term_cert_node['issuedBy'] = [
                            '@type' => 'Organization',
                            'name'  => $cert_data['certification_issuer_name'],
                            'url'   => $cert_data['certification_issuer_url']
                        ];
                    }
                    
                    $all_certifications[] = $term_cert_node;
                }
            }
        }
    }
    if (!empty($all_certifications)) {
         $entity['hasCertification'] = $all_certifications;
    }

   if ($status ==='multi_areas' || $status ==='none') {
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
                        '@type' => 'Service',
                        '@id' => $service_url . '#service',
                        'name' => get_the_title($post_id),
                        'url' => $service_url,
                        'serviceType' => $term->name]];
            }
            $name_base = str_replace(['_', '-'], ' ', $type_post_type);
            $catalog_name = ucwords($name_base) . 's';
            $all_catalogs[] = [
                '@type' => 'OfferCatalog',
                'name' => $catalog_name,
                'itemListElement' => $offers_for_catalog ];
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
		$ignore_main_term = get_option('enabled_connector_contexts')['locations']['ignore_main_term'];
		if ($ignore_main_term === "1"){
		$main_term = get_option('enabled_connector_contexts')['locations']['main_term'];
		if ($main_term !==''){
			$main_term = (int)$main_term;
		}
        foreach ($location_terms as $term) {
			$term_id = $term->term_id; 
				if ($term_id === $main_term) {continue;}
                $stub =build_local_business_stub_from_data($term_id);
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
                $city = build_city_objects_for_area_served($term->term_id);
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
function build_local_business_entity_from_data($post_id, $location_term_id, $context_data) {
    $term_meta_data = get_term_meta($location_term_id, '', true);
    $term_meta_data = array_map('maybe_unserialize', array_map('current', $term_meta_data));
    $location_page_url = $term_meta_data['location_link_url'];
    if (empty($location_page_url)) {
     return;
    }
    $company_info = DibracoSchemaEnv::$company_info;
    $location_logo_id = $term_meta_data['location_logo'];
    $schema_type = $term_meta_data['schema'];
    $exterior_image_id = $term_meta_data['exterior_image'];
    $hours_data = $term_meta_data['hours_of_operation'];
    $social_media_data =  $term_meta_data['social_media'];
    $location_description = '';
    $location_description = get_post_meta($post_id, 'da_about_blurb', true);
    if ($location_description === '') {
        $location_description = get_post_meta($post_id, 'da_banner_description', true);
    }
    if ($location_description !== '') {
        $location_description = strip_tags($location_description);
    }
    $street_address = trim($term_meta_data['street_address'] . ' ' . $term_meta_data['street_address_2']);
      if ($exterior_image_id !== '') {
        $exterior_image_id = (int)$exterior_image_id;
        $exterior_image_url = wp_get_attachment_image_url($exterior_image_id);
      }
	$entity = [
        '@type'     => $schema_type,
        '@id'       => $location_page_url . '#' . strtolower($schema_type),
        'name'      => $term_meta_data['location_name'],
        'url'       => $location_page_url,
        'description' => $location_description,
        'image'     => $exterior_image_url,
        'telephone' => format_phone_number_e164($term_meta_data['phone_number']),
        'email'     => $term_meta_data['email_address'],
        'faxNumber' => $term_meta_data['fax_number'],
        'address'   => [ '@type' => 'PostalAddress', 'streetAddress'   => $street_address,
            'addressLocality' => $term_meta_data['city'],
            'addressRegion'   => $term_meta_data['state'],
            'postalCode' => $term_meta_data['zipcode'],
            'addressCountry'  => $term_meta_data['addy_country'],
        ],
        'geo' => [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $term_meta_data['latitude'],
            'longitude' => $term_meta_data['longitude'],
        ],
         'parentOrganization' => ['@id' => DibracoSchemaEnv::$company_entity_id],
         'hasMap' => $term_meta_data['normal_map'],
         'openingHoursSpecification' => build_opening_hours_spec($hours_data),
    ];
    $location_logo_id = $term_meta_data['location_logo'];
   if (!empty($location_logo_id) && $location_logo_id !== DibracoSchemaEnv::$company_info['company_logo']) {
        $logo_url  = wp_get_attachment_image_url((int)$location_logo_id, 'full');
        $logo_data = wp_get_attachment_metadata((int)$location_logo_id);
        $entity['logo'] = [
            '@type'  => 'ImageObject',
            '@id'    => $logo_url . '/#logo',
            'url'    => $logo_url,
            'width'  => $logo_data['width'],
            'height' => $logo_data['height'],
        ];
    } else {
        if (!empty(DibracoSchemaEnv::$logo_node)) {
            $entity['logo'] = ['@id' => DibracoSchemaEnv::$logo_node['@id']];
        }
    }
    $social_media_data = $term_meta_data['social_media'];
    $social_media_urls = [];
    
    foreach ($social_media_data as $field_key => $value) { 
        if (!empty($value)) {
              $social_media_urls[] = $value; 
        }
    }
    $entity['sameAs'] = $social_media_urls;

    if (DibracoSchemaEnv::$status === 'both')  {
        $associated_area_ids = $term_meta_data['associated_act_terms'];

        $service_areas = [];
        $coordinate_pairs = [];

        foreach ($associated_area_ids as $area_id) {
                $city = build_city_objects_for_area_served($area_id);
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

function build_service_area_entity($post_id, $term_id, $context_data) {
    $status = DibracoSchemaEnv::$status;
    $term_object = get_term($term_id);
    $page_url = get_term_meta($term_id, 'service_area_link_url', true);
    if ($page_url === '') {
        return;
    }

    $provider_stub = [];
    if ($status === 'both') {
        $parent_loc_id = get_term_meta($term_id, 'area_parent_location_term', true);
        if ($parent_loc_id !== '') {
            $parent_loc_id = (int)$parent_loc_id;
            $provider_stub = build_local_business_stub_from_data($parent_loc_id);
        }
    } else {
        $provider_stub = DibracoSchemaEnv::$organization_stub_node;
    }

    $provider_id = '';
    if (!empty($provider_stub['@id'])) {
        $provider_id = $provider_stub['@id'];
    } else {
        $provider_id = DibracoSchemaEnv::$organization_stub_node['@id'];
    }

    $area_description = get_post_meta($post_id, 'da_about_blurb', true);
    if ($area_description === '') {
        $area_description = get_post_meta($post_id, 'da_banner_description', true);
    }
    if ($area_description !== '') {
        $area_description = strip_tags($area_description);
    }

    $city_name = $term_object->name;

    $area_served_nodes = [
        ['@type' => 'City', 'name' => $city_name]
    ];

    $bounding_box_json = get_term_meta($term_id, 'bounding_box', true);
    if ($bounding_box_json !== '') {
        $bounding_box = json_decode($bounding_box_json, true);
        if (is_array($bounding_box)) {
            $box_string = "{$bounding_box[0]} {$bounding_box[2]} {$bounding_box[1]} {$bounding_box[3]}";
            $area_served_nodes[] = ['@type' => 'GeoShape', 'box' => $box_string];
        }
    }

    $entity = [
        '@type'        => ['Service', 'City'],
        '@id'          => $page_url . '#service',
        'name'         => 'Services in ' . $city_name,
        'description'  => $area_description,
        'url'          => $page_url,
        'areaServed'   => $area_served_nodes,
        'provider'     => ['@id' => $provider_id]
    ];

    if (!empty($context_data['related_type_contexts'])) {
        foreach ($context_data['related_type_contexts'] as $type_context_name => $type_context_data) {
            if ($type_context_data['post_per_term'] === '1' && $type_context_data['schema'] === 'Service') {
                $catalog = build_offer_catalog_from_context($type_context_name, $type_context_data, $term_id);
                if ($catalog) {
                    if (!isset($entity['hasOfferCatalog'])) {
                        $entity['hasOfferCatalog'] = [];
                    }
                    $entity['hasOfferCatalog'][] = $catalog;
                }
            }
        }
    }

    return $entity;
}

function build_service_entity($post_id, $type_term_id, $context_data) {
    $type_term_name = get_term($type_term_id)->name;
    $status = DibracoSchemaEnv::$status;
    $page_url = get_permalink($post_id);
    $entity = [
        '@type' => 'Service',
        '@id'   => $page_url . '/#service',
        'url'   => $page_url,
    ];
    $related_connector_count = $context_data['related_connector_count'];
    if ($related_connector_count === 2){
        $related_connectors = $context_data['related_connectors'];
        $service_areas_taxonomy= $related_connectors['service_areas']['taxonomy'];
        $locations_taxonomy= $related_connectors['locations']['taxonomy'];
        $service_area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_areas_taxonomy);
        if(!empty($service_area_term_id)){
            $location_term_id = get_term_meta($service_area_term_id, 'area_parent_location_term', true);
         }
      if(empty($service_area_term_id)){
            $location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
            }
         }
      if ($related_connector_count === 1){
          if ($status === 'multi_areas') {
             $service_areas_taxonomy = get_option('enabled_connector_contexts')['service_areas']['taxonomy'];
             $service_area_term_id = dibraco_get_current_term_id_for_post($post_id, $service_areas_taxonomy);
          }
        if ($status === 'multi_locations') {
             $locations_taxonomy = get_option('enabled_connector_contexts')['locations']['taxonomy'];
             $location_term_id = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
        }
      }
     if($service_area_term_id !==''){
            $city_name = get_term_meta($service_area_term_id, 'city', true);
            $area_served_nodes[] = ['@type' => 'City', 'name'  => $city_name,];
            $bounding_box_json = get_term_meta($service_area_term_id, 'bounding_box', true);
            if (!empty($bounding_box_json)) {
            $bounding_box = json_decode($bounding_box_json, true);
                $box_string = "{$bounding_box[0]} {$bounding_box[2]} {$bounding_box[1]} {$bounding_box[3]}";
                $area_served_nodes[] = ['@type' => 'GeoShape',  'box'   => $box_string];
            }
     }
     if($location_term_id !==''){
         if($service_area_term_id ===''){
             $city_name = get_term_meta($location_term_id, 'city', true);
             $area_served_nodes[] = ['@type' => 'City', 'name'  => $city_name,];
            $bounding_box_json = get_term_meta($location_term_id, 'bounding_box', true);
        if (!empty($bounding_box_json)) {
            $bounding_box = json_decode($bounding_box_json, true);
                $box_string = "{$bounding_box[0]} {$bounding_box[2]} {$bounding_box[1]} {$bounding_box[3]}";
                $area_served_nodes[] = ['@type' => 'GeoShape',  'box'   => $box_string];
            }
        }
        $provider_stub = build_local_business_stub_from_data($location_term_id);
        $entity['provider'] = ['@id' => $provider_stub['@id']];

     }
    if ($location_term_id ==='' && $service_area_term_id ===''){
          if (!empty($company_info['city'])) {
                $entity['areaServed'] = ['@type' => 'City', 'name' => $company_info['city']];
                   $city_name = $company_info['city'];
                }
              $provider_stub = DibracoSchemaEnv::$organization_stub_node;
                  $entity['provider'] = ['@id' => $provider_stub['@id']];
             }
    $service_type_name = get_term($type_term_id)->name;
///note to future self do something here if locations aren't enabled and if other things
$entity['name'] ="{$service_type_name} in {$city_name}";
    $entity['serviceType'] = $service_type_name;

    if ($context_data['dibraco_banner'] === '1') {
        $banner_description = get_post_meta($post_id, 'da_banner_description', true);
        if (!empty($banner_description)) {
            $banner_description = strip_tags($banner_description);
            $entity['description'] = $banner_description;
        }
    }
    $image_found = false;
    if ($context_data['landscape_images'] === '1') {
        $landscape_image = get_post_meta($post_id, 'dibraco_landscape_1', true);
        if (!empty($landscape_image)) {
            $entity['image'] = wp_get_attachment_url($landscape_image);
            $image_found = true;
        }
    }
    if (!$image_found && has_post_thumbnail($post_id)) {
        $entity['image'] = get_the_post_thumbnail_url($post_id, 'full');
    }

    if (($context_data['has_certification']) === '1') {
        $cert_data = get_term_meta($type_term_id, 'certification_data', true);
        if (!empty($cert_data)) { 
            $has_certification_term_meta = get_term_meta($type_term_id, 'has_certification', true);
                if (!empty($has_certification_term_meta ==="1")){
                    $term_cert_node = [
                        '@type' => 'Certification',
                        '@id' => DibracoSchemaEnv::$home_url . '#cert-' . $type_term_name,
                        'name'  => $cert_data['certification_name'],
                        'url'   => $cert_data['certification_url'],
                        'description' => $cert_data['certification_description'],
                        'certificationIdentification' => $cert_data['certification_id'],
                        'validFrom' => $cert_data['certification_valid_from'],
                        'expires' => $cert_data['certification_expires']
                    ];

                    if ($cert_data['certification_logo']) {
                        $term_cert_node['logo'] = wp_get_attachment_url((int)$cert_data['certification_logo']);
                    }
                    if ($cert_data['certification_valid_in']) {
                        $term_cert_node['validIn'] = [
                            '@type' => 'AdministrativeArea',
                            'name'  => $cert_data['certification_valid_in']
                        ];
                    }
                    if ($cert_data['certification_issuer_name']) {
                        $term_cert_node['issuedBy'] = [
                            '@type' => 'Organization',
                            'name'  => $cert_data['certification_issuer_name'],
                            'url'   => $cert_data['certification_issuer_url']
                        ];
                    }
                   $entity['hasCertification']=$term_cert_node;
                }
        }}
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

function build_local_business_stub_from_data($location_term_id) {
    $term_meta_data = get_term_meta($location_term_id, '', true);
    $term_meta_data = array_map('maybe_unserialize', array_map('current', $term_meta_data));
    $url = $term_meta_data['location_link_url'];
    if($url === '')
    $company_info= DibracoSchemaEnv::$company_info;
    $phone = $term_meta_data['phone_number'];
    $schema_type = $term_meta_data['schema'];
    $name = $term_meta_data['location_name'];
    if ($name ==='') {
        $name = get_option('company_info')['name'];
    }
    $hours_data = $term_meta_data['hours_of_operation'];
    $exterior_image_id =  $term_meta_data['exterior_image'];
    if ($exterior_image_id!==''){
        $exterior_image_id= (int)$exterior_image_id;
      $exterior_image_url = wp_get_attachment_image_url($exterior_image_id);
    }
	$parent_organization =  ['@id' => DibracoSchemaEnv::$company_entity_id];
	
    $street_address  = $term_meta_data['street_address'];
    $street_address_2 = $term_meta_data['street_address_2'];
    $city = $term_meta_data['city'];
    $state = $term_meta_data['state'];
    $zipcode = $term_meta_data['zipcode'];
    $country = $term_meta_data['addy_country'];
    $lat = $term_meta_data['latitude'];
    $long = $term_meta_data['longitude'];
    $map = $term_meta_data['normal_map'];
    $status = DibracoSchemaEnv::$status;
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
        'telephone' =>  format_phone_number_e164($phone),
        'openingHoursSpecification' => build_opening_hours_spec($hours_data),
        'parentOrganization' => $parent_organization
    ];
    
    return $stub;
}
function build_city_objects_for_area_served($service_area_term_id) {
    $service_area_name = get_term($service_area_term_id)->name;
    $city = get_term_meta($service_area_term_id, 'city', true);
    if ($city === '') {
        $city = $service_area_name;
    }
    $city_object = [
        '@type' => 'City',
        'name'  => $city,
    ];
    $latitude  = get_term_meta($service_area_term_id, 'latitude', true);
    $longitude = get_term_meta($service_area_term_id, 'longitude', true);

    if (!empty($latitude) && !empty($longitude)) {
        $city_object['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $latitude,
            'longitude' => $longitude
        ];
    }
    $url = get_term_meta($service_area_term_id, 'service_area_link_url', true);
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



function format_phone_number_e164($phone) {
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
function build_opening_hours_spec($hours_data) {
  $days_map =  get_dibraco_day_map();
  if ($hours_data['open_247'] === "1") {
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