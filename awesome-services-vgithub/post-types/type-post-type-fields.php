<?php

function da_render_standard_image_box($post, $box) {
    $args       = $box['args'];
    $taxonomy   = $args['taxonomy'];
    $post_id = $post->ID;
    $term_id    = dibraco_get_current_term_id_for_post($post_id, $taxonomy);
    wp_nonce_field('dibraco_standard_image_meta_action', 'dibraco_standard_image_nonce');

    foreach ($args as $image_type => $is_enabled) {
        if ($is_enabled !== '1') { continue;}
        if ($image_type === 'landscape') {
            $wrapper_class = 'dibraco-landscape-images-box';
            $fields = get_landscape_image_fields();
        } elseif ($image_type ==='portrait') {
            $wrapper_class = 'dibraco-portrait-images-box';
            $fields = get_portrait_image_fields();
        } else {
               $wrapper_class = 'dibraco-term-icon-box';
                $icon_value = get_post_meta($post_id, 'term_icon', true) ?: '';
                if (!empty($term_id)) {
                    $term_icon_value = get_term_meta($term_id, 'term_icon', true);
                    if (!empty($term_icon_value)){
                        $term_icon_value = (int)$term_icon_value;
                    }
                    if (!empty($term_icon_value)) {
                        $icon_value = $term_icon_value;
                        }
                    }
            $icon_field_data = get_term_icon_field();
            $field_name = key($icon_field_data);
            $field_config = $icon_field_data[$field_name];
            $field_config['value'] = $icon_value;
            echo "<div class='dibraco-image-fields-container {$wrapper_class}'>";
            echo FormHelper::generateField($field_name, $field_config);
            echo '</div>';
            continue;
        }
        echo "<div class='dibraco-image-fields-container {$wrapper_class}'>";
        
        $lock_value_1 = get_post_meta($post_id, "dibraco_{$image_type}_1_lock", true) ?? '0';
        $lock_value_2 = get_post_meta($post_id, "dibraco_{$image_type}_2_lock", true) ?? '0';
        $fields_to_render = [];
        $empty_image_count = 0;

        foreach ($fields as $field_name => $field_config){
            $current_field_config = $field_config;
            if (str_contains($field_name, 'lock')){
                if (str_contains($field_name, '1')){
                    $current_field_config['value'] = $lock_value_1;
                     $fields_to_render[$field_name] = $current_field_config;
                     continue;
                } else {
                    $current_field_config['value'] = $lock_value_2; 
                    $fields_to_render[$field_name] = $current_field_config;
                    continue;
                }
            }
            if((str_contains($field_name, '1')) && $lock_value_1 !== "1"){$current_field_config['value'] = ''; $empty_image_count++; $fields_to_render[$field_name] = $current_field_config; continue; }
            if((str_contains($field_name, '2')) && $lock_value_2 !== "1"){$current_field_config['value'] = ''; $empty_image_count++; $fields_to_render[$field_name] = $current_field_config; continue; }
            $field_value = get_post_meta($post_id, $field_name, true);
            $current_field_config['value'] = $field_value; 
            if ($current_field_config['value'] ===''){
                $empty_image_count++;
            }
            $fields_to_render[$field_name] = $current_field_config;
        }
        $available_images = []; 
        if ($empty_image_count > 0 && $term_id !== '') {
            $meta = get_term_meta($term_id, "dibraco_{$image_type}_images", true);
            if (!empty($meta)) {
                for ($i = 1; $i <= 5; $i++) {
                    $key = "dibraco_{$image_type}_{$i}";
                    if (!empty($meta[$key])) {
                        $available_images[] = $meta[$key];
                    }
                }
            }
            $total_available_term_images = count($available_images);
            if ($total_available_term_images > 0){
                shuffle($available_images);
                $available_images = array_values($available_images); 
            }
        }
       foreach ($fields_to_render as $field_name => $field_config) {
           if ($empty_image_count > 0 && $term_id !== '') {
            if (($field_config['value']==='') && $total_available_term_images > 0) { 
                $field_config['value'] = $available_images[$total_available_term_images-1];
                $total_available_term_images --;
            }
            }
            echo FormHelper::generateField($field_name, $field_config);
        }
        echo '</div>';
    }
}

function da_save_ba_meta($post_id) {
if (!dibraco_verify_post_save_request('da_ba_nonce', 'da_save_ba_meta_action')) {return; }
    $before = $_POST['dibraco_ba_before'];
    $after  = $_POST['dibraco_ba_after']; 
    $locked = $_POST['dibraco_ba_lock']; 
    update_post_meta($post_id, 'dibraco_ba_before', $before);
    update_post_meta($post_id, 'dibraco_ba_after',  $after);
    update_post_meta($post_id, 'dibraco_ba_lock',   $locked);
}
function da_save_standard_image_meta($post_id, $landscape_images, $portrait_images, $term_icon) {
    if (!dibraco_verify_post_save_request('dibraco_standard_image_nonce', 'dibraco_standard_image_meta_action')) {return;}
    if ($term_icon ==="1"){
       $field_name = 'term_icon';
       $value = (int)$_POST[$field_name];
        update_post_meta ($post_id, $field_name, $value);
    }
    if ($landscape_images === '1') {
        $landscape_field_definitions = get_landscape_image_fields();
        foreach ($landscape_field_definitions as $field_name => $field_config) {
                if (str_contains($field_name, 'lock')){
             $value = (string)$_POST[$field_name];
             }  else {
                 $value = (int)$_POST[$field_name];
             }
             update_post_meta($post_id, $field_name, $value);
        }
    }

    if ($portrait_images === '1') {
        $portrait_field_definitions = get_portrait_image_fields();
        foreach ($portrait_field_definitions as $field_name => $field_config) {
             if (str_contains($field_name, 'lock')){
             $value = (string)$_POST[$field_name];
             }  else {
                 $value = (int)$_POST[$field_name];
             }
             update_post_meta($post_id, $field_name, $value);
        }
    }
}

function da_render_ba_box($post, $box) {
    wp_nonce_field('da_save_ba_meta_action', 'da_ba_nonce');
    $taxonomy = $box['args']['taxonomy'];
    $term_id  = dibraco_get_current_term_id_for_post($post->ID, $taxonomy);
    if ($term_id ===''){return;}
    $all_term_image_data = (array) get_term_meta($term_id, 'dibraco_ba', true) ??[] ;
    $count = (int) ($all['dibraco_ba_row_count'] ?? 0);
    $pairs = [];
    for ( $i = 0; $i < $count; $i++ ) {
        $before = $all_term_image_data["dibraco_ba[{$i}][before]"] ?? '';
        $after = $all_term_image_data["dibraco_ba[{$i}][after]"]  ?? '';
        if ($before !== '' && $after !== '') {
            $pairs[] = ['before'=>$before,'after'=>$after];
        }
    }
    shuffle($pairs);
    $rand = $pairs[0] ?? ['before'=>'','after'=>''];
    $locked = get_post_meta( $post->ID, 'dibraco_ba_lock', true);
    if ($locked === '1'){
    $rand['before'] = get_post_meta( $post->ID, 'dibraco_ba_before', true );
    $rand['after']  = get_post_meta( $post->ID, 'dibraco_ba_after', true );
    }
    echo FormHelper::generateField( 'nontracking', ['type' => 'valueinjector', 'meta_array' => ['dibraco_ba_before' => $rand['before'], 'dibraco_ba_after' => $rand['after'],'dibraco_ba_lock' => $locked ]]);
    echo FormHelper::generateField('dibraco_ba_before', ['type' => 'image', 'label' => 'Before Image']); 
    echo FormHelper::generateField('dibraco_ba_after', ['type' => 'image', 'label' => 'After Image']);  
    echo FormHelper::generateField('dibraco_ba_lock', ['type' => 'checkbox']);
    echo FormHelper::generateField('trackerend', ['type'=>'injectionend']);
}

function render_da_pages_metabox(){
wp_nonce_field('dibraco_save_da_section_fields', 'da_section_fields');
 $post_id = get_the_id();
        $banner_fields = get_banner_fields();
        $fields_to_render[] = [
            'da_main_h1' => $banner_fields['da_main_h1'],
            'da_banner_description' => $banner_fields['da_banner_description']
        ];
    
         $main_sec_fields = get_section_title_fields();
         
         $fields_to_render[] = [
            'da_section_1_title' => $main_sec_fields['da_section_1_title'],
            'da_section_1_p' => $main_sec_fields['da_section_1_p']
        ];
         
         $fields_to_render[] = [
            'da_section_2_title' => $main_sec_fields['da_section_2_title'],
            'da_section_2_p' => $main_sec_fields['da_section_2_p']
        ];
   
         $fields_to_render[] = [
            'da_section_3_title' => $main_sec_fields['da_section_3_title'],
            'da_section_3_p' => $main_sec_fields['da_section_3_p']
        ];
  
    
   $user_defined_fields = get_dibraco_custom_fields_for_context('page');
    if (!empty($user_defined_fields)) {
        $custom_field_groups = array_chunk($user_defined_fields, 2, true);
        foreach ($custom_field_groups as $group) {
            $fields_to_render[] = $group;
        }
    }
    
        $contact_fields_data = get_contact_fields();
        $fields_to_render[] = [
            'da_quote_title' => $contact_fields_data['da_quote_title'],
            'da_contact_section' => $contact_fields_data['da_contact_section']
        ];

    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
    foreach ($fields_to_render as $pair) {
        echo '<div style="width: 49%; margin-bottom: 20px;">'; 
        foreach ($pair as $field_id => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_id, true);
            echo FormHelper::generateField($field_id, $field_config);
        }
        echo '</div>';  
    }
    echo '</div>';
}
function dibraco_save_section_fields_page($post_id){
 if (!dibraco_verify_post_save_request('da_section_fields', 'dibraco_save_da_section_fields')) {return;}

  $user_defined_fields = get_dibraco_custom_fields_for_context('page');
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
       update_post_meta($post_id, $field_id, sanitize_textarea_field($_POST[$field_id]));
        }
    }
        update_post_meta($post_id, 'da_main_h1', sanitize_text_field($_POST['da_main_h1']));
        update_post_meta($post_id, 'da_banner_description', sanitize_textarea_field($_POST['da_banner_description']));
        update_post_meta($post_id, 'da_section_1_title', sanitize_text_field($_POST['da_section_1_title']));
        update_post_meta($post_id, 'da_section_1_p', sanitize_textarea_field($_POST['da_section_1_p']));
        update_post_meta($post_id, 'da_section_2_title', sanitize_text_field($_POST['da_section_2_title']));
        update_post_meta($post_id, 'da_section_2_p', sanitize_textarea_field($_POST['da_section_2_p']));
        update_post_meta($post_id, 'da_section_3_title', sanitize_text_field($_POST['da_section_3_title']));
        update_post_meta($post_id, 'da_section_3_p', sanitize_textarea_field($_POST['da_section_3_p']));
        update_post_meta($post_id, 'da_quote_title', sanitize_text_field($_POST['da_quote_title']));
        update_post_meta($post_id, 'da_contact_section', sanitize_textarea_field($_POST['da_contact_section']));

}
function render_da_combined_metabox($post, $args) {
    $context_data = $args['args']['context_data'];  
    $context_type = $context_data['context_type'];
    $post_id = $post->ID;
    if ($context_type !== 'unique'){
    $taxonomy = $context_data['taxonomy'];
    }
    $dibraco_about = $context_data['about_section'] ?? '0';
    $contact_fields = $context_data['contact_section'];
    $dibraco_banner = $context_data['dibraco_banner'];
    $main_sections = $context_data['main_sections'];
    $context_name = $context_data['context_name'];
    wp_nonce_field('dibraco_save_banner_contact_meta_action', 'da_banner_contact_nonce');
   
    if ($dibraco_banner === "1") {
        $banner_fields = get_banner_fields();
        $fields_to_render[] = [
            'da_main_h1' => $banner_fields['da_main_h1'],
            'da_banner_description' => $banner_fields['da_banner_description']
        ];
    }
    if ($dibraco_about ==="1"){
       $about_fields = get_about_fields();
       $fields_to_render[] =[
        'da_about_title' => $about_fields['da_about_title'],
        'da_about_blurb' => $about_fields['da_about_blurb']
        ];
    }
    if ($main_sections === "1") {
         $main_sec_fields = get_section_title_fields();
         
         $fields_to_render[] = [
            'da_section_1_title' => $main_sec_fields['da_section_1_title'],
            'da_section_1_p' => $main_sec_fields['da_section_1_p']
        ];
         
         $fields_to_render[] = [
            'da_section_2_title' => $main_sec_fields['da_section_2_title'],
            'da_section_2_p' => $main_sec_fields['da_section_2_p']
        ];
   
         $fields_to_render[] = [
            'da_section_3_title' => $main_sec_fields['da_section_3_title'],
            'da_section_3_p' => $main_sec_fields['da_section_3_p']
        ];
  
    }
   $user_defined_fields = get_dibraco_custom_fields_for_context($context_name);
    if (!empty($user_defined_fields)) {
        $custom_field_groups = array_chunk($user_defined_fields, 2, true);
        foreach ($custom_field_groups as $group) {
            $fields_to_render[] = $group;
        }
    }
    if ($contact_fields === "1") {
        $contact_fields_data = get_contact_fields();
        $fields_to_render[] = [
            'da_quote_title' => $contact_fields_data['da_quote_title'],
            'da_contact_section' => $contact_fields_data['da_contact_section']
        ];
    }

    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
   if( $context_name ==='service_areas'){
       $base_term_field_definitions =  get_service_area_term_fields();
       $term_id = dibraco_get_current_term_id_for_post($post_id, $taxonomy);
      foreach ($base_term_field_definitions as $field_id => $field_config){
        $field_config['value'] = get_term_meta($term_id, $field_id, true);
        echo '<div style="display: flex; width: 23%;">';
        echo FormHelper::generateField($field_id, $field_config);
        echo '</div>';
        }
   }
    foreach ($fields_to_render as $pair) {
        echo '<div style="width: 49%; margin-bottom: 20px;">'; 
        foreach ($pair as $field_id => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_id, true);
            echo FormHelper::generateField($field_id, $field_config);
        }
        echo '</div>'; 
    }
    if ($context_type ==='type'){
        $post_per_term = $context_data['post_per_term'];
        if ($post_per_term ==="1"){
            $repeater_meta = get_post_meta($post_id, '_list_repeater', true);
            $repeater_fields = get_repeater_field_list();
            
            echo '<div style="width: 49%; margin-bottom:10px;">';
            echo FormHelper::generateField('doesnt_matter', ['type'=> 'starttracking', 'meta_array' => $repeater_meta]);

            foreach ($repeater_fields as $field_key => $field_data) {
                if ($field_key === 'da_list_title') {
                    echo FormHelper::generateField($field_key, $field_data);
                } elseif ($field_key === 'da_list_repeater') {
					echo '<div style="width: 97%; display:block;">';
                    echo FormHelper::generateRepeaterField($field_key, $field_data);
					echo '</div>';
                }
            }

            echo '</div>';
            echo FormHelper::generateField('trackerend', ['type'=> 'endtracking']);
        }
    }
    echo '</div>';
}
function dibraco_save_banner_contact_section_fields($post_id, $context_data){
if (!dibraco_verify_post_save_request('da_banner_contact_nonce', 'dibraco_save_banner_contact_meta_action')) {return;}
    $context_type = $context_data['context_type'];
    $contact_fields = $context_data['contact_section'];
    $dibraco_banner = $context_data['dibraco_banner'];
    $main_sections = $context_data['main_sections'];
    $context_name = $context_data['context_name'];
    $dibraco_about = $context_data['about_section'] ?? '';
    $user_defined_fields = get_dibraco_custom_fields_for_context($context_name);
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
       update_post_meta($post_id, $field_id, sanitize_textarea_field($_POST[$field_id]));
        }
    }
    if ($dibraco_banner === "1") {
        $banner_fields = get_banner_fields();
        update_post_meta($post_id, 'da_main_h1', sanitize_text_field($_POST['da_main_h1']));
        update_post_meta($post_id, 'da_banner_description', sanitize_textarea_field($_POST['da_banner_description']));
    }
    
    if ($context_type ==='connector') {
        if ($dibraco_about ==="1"){
        $about_fields = get_about_fields();
        update_post_meta($post_id, 'da_about_title', $_POST['da_about_title']);
        update_post_meta($post_id, 'da_about_blurb', $_POST['da_about_blurb']);
        }
        $meta_prefix = rtrim($context_name, 's');
        $post_id_key = "{$meta_prefix}_post_id";
        $link_url_key = "{$meta_prefix}_link_url";
        $taxonomy = $context_data['taxonomy'];
        $new_term_id = $_POST["{$taxonomy}_term"] ?? '';
        dibraco_enforce_one_connector_term_per_connector_post($post_id, $new_term_id, $taxonomy, $post_id_key, $link_url_key);
    }
    if ($main_sections === "1") {
        $main_sec_fields = get_section_title_fields();
        update_post_meta($post_id, 'da_section_1_title', sanitize_text_field($_POST['da_section_1_title']));
        update_post_meta($post_id, 'da_section_1_p', sanitize_textarea_field($_POST['da_section_1_p']));
        
        update_post_meta($post_id, 'da_section_2_title', sanitize_text_field($_POST['da_section_2_title']));
        update_post_meta($post_id, 'da_section_2_p', sanitize_textarea_field($_POST['da_section_2_p']));
        
        update_post_meta($post_id, 'da_section_3_title', sanitize_text_field($_POST['da_section_3_title']));
        update_post_meta($post_id, 'da_section_3_p', sanitize_textarea_field($_POST['da_section_3_p']));
    }

    if ($contact_fields === "1") {
        $contact_fields_data = get_contact_fields();
        update_post_meta($post_id, 'da_quote_title', sanitize_text_field($_POST['da_quote_title']));
        update_post_meta($post_id, 'da_contact_section', sanitize_textarea_field($_POST['da_contact_section']));
    }
    if ($context_type ==='type'){
        $post_per_term = $context_data['post_per_term'];
        if($post_per_term==="1"){
            $repeater_data = [];
            $row_count = $_POST['da_list_repeater_row_count'];
            $repeater_data['da_list_repeater_row_count'] = $row_count;
            $repeater_data['da_list_title'] = $_POST['da_list_title'];  

            for ($index = 0; $index < $row_count; $index++){
            $repeater_data["da_list_repeater[{$index}][item]"] = $_POST["da_list_repeater"][$index]['item'];  
        }
        update_post_meta($post_id, '_list_repeater', $repeater_data);

    }
}
}
function da_render_unique_image_box($post_id, $box){
$args = $box['args'];
$post_id = get_the_ID();
    wp_nonce_field( 'dibraco_standard_image_meta_action', 'dibraco_standard_image_nonce' );

    foreach ($args as $image_type => $is_enabled) {
        if ($is_enabled !== '1') { continue;}

        if ($image_type === 'landscape') {
            $wrapper_class = 'dibraco-landscape-images-box';
            $fields_definitions_for_type = get_landscape_image_fields();
        } else {
            $wrapper_class = 'dibraco-portrait-images-box';
            $fields_definitions_for_type = get_portrait_image_fields();
        }

        echo "<div class='dibraco-image-fields-container {$wrapper_class}'>";
        foreach ($fields_definitions_for_type as $field_key => $field_config) { 
            if (str_ends_with($field_key, '_lock')) {
                continue;
            }
            $field_value = get_post_meta( $post_id, $field_key, true );
            $field_config['value'] = $field_value;
            echo FormHelper::generateField( $field_key, $field_config );
        }
        echo '</div>';
    }
}
function da_render_employee_fields() {
    $post_id = get_the_ID();
    $fields = get_employee_fields();
    $certification_enabled = get_option('enabled_unique_contexts')['employee']['has_certification'] ?? '0';
    wp_nonce_field('dibraco_employee_fields', 'employee_fields_nonce');
    $employee_data = get_post_meta($post_id, 'employee_data', true);
    foreach ($fields['employee-fields']['fields'] as $field_key => $field_config) {
        $fields['employee-fields']['fields'][$field_key]['value'] = $employee_data[$field_key];
    }
    echo FormHelper::generateVisualSection('employee-fields', $fields['employee-fields']);
    if ($certification_enabled === '1') {
    $cert_fields = get_certification_fields();
    $cert_data = get_post_meta($post_id, 'employee_certification_data', true);
    foreach ($cert_fields['certification_section']['fields'] as $field_key => $field_config) {
        $cert_fields['certification_section']['fields'][$field_key]['value'] = $cert_data[$field_key];
    }
    echo FormHelper::generateVisualGroup('certification_section', $cert_fields['certification_section']);
}
}

function save_da_employee_fields($post_id) {
if (!dibraco_verify_post_save_request('employee_fields_nonce', 'dibraco_employee_fields')) {return; }
    $fields = get_employee_fields()['employee-fields']['fields'];
    $certification_enabled = get_option('enabled_unique_contexts')['employee']['has_certification'] ?? '0';
    $data = [];
    foreach ($fields as $field_key => $field_config) {
        $data[$field_key] = $_POST[$field_key];
    }
    update_post_meta($post_id, 'employee_data', $data);
    if ($certification_enabled ==='1') {
    $cert_fields = get_certification_fields()['certification_section']['fields'];
    $cert_data = [];
    foreach ($cert_fields as $field_key => $field_config) {
        $cert_data[$field_key] = $_POST[$field_key];
    }
    update_post_meta($post_id, 'employee_certification_data', $cert_data);
}
}
