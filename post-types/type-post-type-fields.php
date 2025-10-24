<?php
function dibraco_prepare_term_images($image_type, $primary_term_id) {
   $term_image_meta = get_term_meta($primary_term_id, "dibraco_{$image_type}_images", true);
    if(empty($term_image_meta)){
        return [];
    }
    $all_image_values = array_values($term_image_meta);
    $prepared_term_image_values = array_filter($all_image_values);
    shuffle($prepared_term_image_values);
    return $prepared_term_image_values;
}
function dibraco_before_after_single_pair($post_id, $primary_term_id){
    $post_before_after_meta = get_post_meta($post_id, 'dibraco_before_after', true);
    if(!empty($post_meta)){
       $lock_field_value = $post_before_after_meta['dibraco_ba_lock'];
       $before_image = $post_before_after_meta['before_image'];
        }
    if(empty($post_meta) || (empty($before_image)) || $lock_field_value !=='1'){
   
           $term_before_after_meta = get_term_meta($primary_term_id, 'dibraco_before_after', true);
           $pair_count = $term_before_after_meta['dibraco_before_after_row_count'];
           $random_selection = rand(1, $pair_count);
                    $before_image = $random_selection['before_image'];
                    $after_image  = $random_selection['after_image'];
                    $post_before_after_meta['dibraco_ba_lock'] =$lock_field_value;
                    $post_before_after_meta['before_image']= $before_image;
                    $post_before_after_meta['after_image']= $after_image;
              update_post_meta($post_id, 'dibraco_before_after', $post_before_after_meta);
        }
    }

function dibraco_render_side_meta_box($post) {
    wp_nonce_field('dibraco_save_meta_action', 'dibraco_meta_nonce');
    $post_type = $post->post_type;
    $post_id = $post->ID;
    $enabled_contexts = get_option('enabled_contexts');
    foreach ($enabled_contexts as $enabled_context => $context_data) {
        if ($context_data['post_type'] === $post_type) {
            $context = $context_data;
            break;
        }
    }
    $context_name = $context['context_name'];
    $human_readable = ucwords(str_replace(['_', '-'], ' ', $context_name));
    $context_type = $context['context_type'];
    $landscape = $context['landscape_images'];
    $portrait = $context['portrait_images'];
    $image_fields =[];
    if($landscape ==="1"){
            if ($context_type === 'unique') {
                $image_fields += get_landscape_post_image_fields($context_type);
            }
        }
    if($portrait ==="1"){
        if ($context_type === 'unique') {
            $image_fields += get_portrait_post_image_fields($context_type);
            }
        }
   
    
      if($context_type==='type' || $context_type ==='connector'){
        $primary_taxonomy = $context['taxonomy'];
        $primary_term_id = dibraco_get_current_term_id_for_post($post_id, $primary_taxonomy);
        $primary_terms = get_terms(['taxonomy' => $primary_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
         if ($landscape ==='1'){
            $landscape_image_fields = get_landscape_post_image_fields($context_type);
            $term_image_field_values = [];
            if (!empty($primary_term_id)) {
                $term_image_field_values = dibraco_prepare_term_images('landscape', $primary_term_id);
            }
            for ($i = 1; $i <= 2; $i++) {
                $image_field = "dibraco_landscape_{$i}";
                $lock_field = "dibraco_landscape_{$i}_lock";
                $lock_value = get_post_meta($post_id, $lock_field, true);
            if ($lock_value !== "1" && !empty($term_image_field_values)) {
                $landscape_image_fields[$image_field]['value'] = array_shift($term_image_field_values);
                 $landscape_image_fields[$lock_field]['value']=$lock_value;
            } else {
                $landscape_image_fields[$image_field]['value'] = get_post_meta($post_id, $image_field, true);
                 $landscape_image_fields[$lock_field]['value']=$lock_value;
                }
            }
            $image_fields += $landscape_image_fields;
         }
            if($portrait ==="1"){
                $portrait_image_fields = get_portrait_post_image_fields($context_type);
                $term_image_field_values = [];
            if (!empty($primary_term_id)) {
                $term_image_field_values = dibraco_prepare_term_images('portrait', $primary_term_id);
            }
            for ($i = 1; $i <= 2; $i++) {
                $image_field = "dibraco_portrait_{$i}";
                $lock_field = "dibraco_landscape_{$i}_lock";
                $lock_value = get_post_meta($post_id, $lock_field, true);
            if ($lock_value !== "1" && !empty($term_image_field_values)) {
                $portrait_image_fields[$image_field]['value'] = array_shift($term_image_field_values);
                 $portrait_image_fields[$lock_field]['value']=$lock_value;
            } else {
                $portrait_image_fields[$image_field]['value'] = get_post_meta($post_id, $image_field, true);
                 $portrait_image_fields[$lock_field]['value']=$lock_value;
                }
            }
            $image_fields += $portrait_image_fields;
         }
                
        if ($context_type ==='type') {
            $post_per_term = $context['post_per_term'];
            if ($post_per_term ==='1'){
               $term_icon_enabled = $context['term_icon'];
               $before_after_enabled = $context['before_after'];
                }
            }  
        
            if ($context_type ==='connector'){
                    foreach ($image_fields as $field_name => $field_config){
                       echo FormHelper::generateField($field_name, $field_config);
                        }
                       echo FormHelper::generateRadioFieldsetWithIntegerValues("{$primary_taxonomy}_term", "Select $human_readable Term", $primary_term_id, $primary_terms, []);
                          return;
                       }
                 if ($post_per_term ==='1'){
                     if($before_after_enabled==="1"){
                       $image_fields += dibraco_before_after_single_pair($post_id, $primary_term_id);
                        } 
                     if($term_icon_enabled ==='1'){
                         $image_fields+= get_type_post_term_icon_field();
                          $term_icon_value = get_post_meta($post_id, 'term_icon', true);
                        if (empty($term_icon_value)){
                            $term_icon_value = get_term_meta($primary_term_id, 'term_icon', true);
                        }
                      $image_fields['term_icon']['value']= $term_icon_value;
                    }
                 }
                }
                $related_connectors = $context['related_connectors'];
                foreach ($image_fields as $field_name => $field_config){
                     echo FormHelper::generateField($field_name, $field_config);
                }
                 if(($context_type ==='type' && $post_per_term !=='1')){
                        echo FormHelper::generateRadioFieldsetWithIntegerValues("{$primary_taxonomy}_term", "Select {$human_readable} Term", $primary_term_id, $primary_terms, []);
                 }
                foreach ($related_connectors as $related_connector_context => $related_connector_data){
                $related_taxonomy = $related_connector_data['taxonomy'];
                    $human_readable = ucwords(str_replace(['_', '-'], ' ',$related_connector_data['connector_name']));
                    $related_term_id = dibraco_get_current_term_id_for_post($post_id, $related_taxonomy);
                    $term_options_array = get_terms(['taxonomy' => $related_taxonomy, 'hide_empty' => false, 'fields' => 'id=>name']);
                    echo FormHelper::generateRadioFieldsetWithIntegerValues("{$related_taxonomy}_term", "Select {$human_readable} Term", $related_term_id, $term_options_array, []);
                }   
              if($context_type !== 'type' || $post_per_term !=='1'){
                  return;
              }
   
        $main_post_map = get_option("{$context_name}_main_posts");
        $main_post_id = get_term_meta($primary_term_id, 'main_post_for_term', true);
        $main_post ="0";
        if (in_array($post_id, $main_post_map, true)){
                 $main_post = "1";
                    }
                    if($main_post ==="0"){
                        echo FormHelper::generateRadioFieldsetWithIntegerValues("{$primary_taxonomy}_term", "Select $human_readable Term", $primary_term_id, $primary_terms, []);
                         echo FormHelper::generateCheckBox('main_post_for_term', 'Is This The Main Post?', '', []); 
                         return;
                    }
              

        $possible_posts = get_posts(['post_type'=>$post_type,'posts_per_page'=>-1,'post_status'=>'publish','fields'=>'all','tax_query'=>[
                        ['taxonomy'=>$primary_taxonomy,'terms'=>$primary_term_id,'field'=>'term_id']]]); 
         if ((count($possible_posts) < 2 ) && (count($main_post_map) < 2)){
                echo "<p>To unset Main Post, Go to Main Posts Page</p>"; } else {
    $options = [];          
    foreach ($possible_posts as $possible_post) {
        $post_type_label = ucwords(str_replace(['_', '-'], ' ', $possible_post->post_type));
        $options[$possible_post->ID] = $post_type_label . ': ' . $possible_post->post_title;
    }
    echo FormHelper::generateRadioFieldsetWithIntegerValues("{$primary_taxonomy}_term", "Select $human_readable Term", $primary_term_id, $primary_terms, []);
    echo FormHelper::generateCheckBox('main_post_for_term', 'Is This The Main Post?', "1", []);
    echo FormHelper::generateSelect("new_main_post_select", 'Main Post', $post_id, $options, []);
     }
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const mainPostCheckbox = document.getElementById('main_post_for_term');
        if (!mainPostCheckbox || !mainPostCheckbox.checked) {
            return; 
        }

        let isChecked = mainPostCheckbox.checked;
        const postForm = document.getElementById('post');
        mainPostCheckbox.addEventListener('change', function () {
            isChecked = mainPostCheckbox.checked;
        });

        const currentPostId = document.getElementById('post_ID').value;
        const selectPostField = document.querySelector('select[name="new_main_post_select"]');
        let selectedPostId = selectPostField.value;
        selectPostField.addEventListener('change', function() {
            selectedPostId = selectPostField.value;
        });
        
        const initialTermId = <?php echo (int)$primary_term_id; ?>;
        const taxonomyName = '<?php echo (string)$primary_taxonomy; ?>';
        const termFieldset = document.getElementById(taxonomyName + '_term');
        let selectedTermId = initialTermId;

        termFieldset.addEventListener('change', function () {
            const selectedTermRadio = document.querySelector('input[name="' + taxonomyName + '_term"]:checked');
            if (selectedTermRadio.value === '') {
                selectedTermId = '';
            } else {
                selectedTermId = parseInt(selectedTermRadio.value, 10);
            }
        });

        const validateSubmission = (event) => {
            if (!isChecked && (selectedPostId === '' || selectedPostId === currentPostId)) {
                alert('Error: You have unchecked "Is This The Main Post?" but have not selected a different post as its replacement. Please select a new main post.');
                event.preventDefault();
                return;
            }
            if (selectedTermId !== initialTermId) {
                alert('Error: A post cannot be moved to a new term while it is the "Main Post". To move this post, you must first uncheck "Is This The Main Post?" and select a replacement.');
                event.preventDefault();
                return;
            }
        };

        postForm.addEventListener('submit', validateSubmission);
    });
    </script>
    <?php
}

function dibraco_render_normal_meta_box($post) {
    $post_type = $post->post_type;
    $post_id = $post->ID;
    $enabled_contexts = get_option('enabled_contexts');
    foreach ($enabled_contexts as $enabled_context => $context_data) {
        if ($context_data['post_type'] === $post_type) {
            $context = $context_data;
            break;
        }
    }
    $all_post_values = get_post_meta($post_id, '', false);
    $all_post_values = array_map('maybe_unserialize', array_map('current', $all_post_values));
  
    $context_name = $context['context_name'];
    $context_type = $context['context_type'];
    if ($context_type !== 'unique'){
        $taxonomy = $context['taxonomy'];
    }
    $contact_fields = $context['contact_section'];
    $dibraco_banner = $context['dibraco_banner'];
    $main_sections = $context['main_sections'];
    $context_name = $context['context_name'];
    $pairs_template = [];
    $fields_template = [];
    if ($dibraco_banner === "1") {
        $pairs_template += get_banner_fields();
    }
    if ($main_sections === "1") {
      $pairs_template += get_section_title_fields();
    }
    $user_defined_fields = get_dibraco_custom_fields_for_context($context_name);
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_name => $field_config) {
            if (isset($field_config['pair']) || isset($field_config['pair_end'])) {
                $pairs_template +=  [$field_name => $field_config];
            } else {
                $fields_template += [$field_name => $field_config];
            }
         }
    }
    if ($context_name ==='employee'){
        $has_certification = $context['has_certification'];
        $fields_template += get_employee_fields($has_certification);
    }
   if($context_type ==='connector'){
        $dibraco_about = $context['about_section'];
        $dibraco_commercial = $context['commercial_section'];
       if ($dibraco_about === "1") {
            $pairs_template += get_about_fields();
        }
      if ($dibraco_commercial ==="1"){
            $pairs_template += get_commercial_fields();
      }
      if($context_name ==='service_areas'){
        $service_area_term_id = dibraco_get_current_term_id_for_post($post_id, $taxonomy);
        if (!empty($service_area_term_id)){
            render_service_area_meta_box($post, $taxonomy); 
            } else {
             echo '<p> Please assign a term to this Service Area post <p><br>';
            }
        }
    if ($context_name ==='locations'){
        $location_term_id = dibraco_get_current_term_id_for_post($post_id, $taxonomy);
        if (!empty($location_term_id)){
           render_location_meta_box($post, $taxonomy); 
                      
        } else {
           echo '<p> Please assign a term to this locations post <p><br>';
            }
        }
    }
    if ($context_type ==='type'){
        $post_per_term = $context['post_per_term'];
        if ($post_per_term ==="1"){
            $pairs_template += get_repeater_field_list();
        }
    }
    if ($contact_fields === "1") {
        $pairs_template += get_contact_fields();
    } 
    $pair_keys = dibraco_extract_nested_arrays_test($pairs_template);
      foreach($pair_keys as $container_name => $storage_array_key){
        if (is_array($storage_array_key)){
            if (array_key_exists($container_name, $all_post_values)){
              $all_post_values = array_merge($all_post_values,$all_post_values[$container_name]); 
              $pair_keys = array_merge($pair_keys, $pair_keys[$container_name]);
              unset($all_post_values[$container_name]);
              unset($pair_keys[$container_name]);
            }
        }
    }    

    $mapped_pair_values = array_intersect_key($all_post_values, $pair_keys);
    FormHelper::generateField('who_cares', ['type' => 'valueinjector', 'meta_array' => $mapped_pair_values]);
    echo FormHelper::generateVisualSection('pairs_template', ['fields' => $pairs_template]);
    FormHelper::generateField('who_cares', ['type' => 'injectionend']);
    
    if (!empty($fields_template)) {
        $field_keys = dibraco_extract_nested_arrays_test($fields_template);
        foreach($field_keys as $container_name => $storage_array_key){
            if (is_array($storage_array_key)){
            if (array_key_exists($container_name, $all_post_values)){
              $all_post_values = array_merge($all_post_values, $all_post_values[$container_name]); 
              $field_keys = array_merge($field_keys, $field_keys[$container_name]);
              unset($all_post_values[$container_name]);
              unset($field_keys[$container_name]);
            }
        }
    }   
    $mapped_field_values = array_intersect_key($all_post_values, $field_keys);
    FormHelper::generateField('who_cares', ['type' => 'valueinjector', 'meta_array' => $mapped_field_values]);
    echo FormHelper::generateVisualSection('fields_template', ['fields' => $fields_template]);
    FormHelper::generateField('who_cares', ['type' => 'injectionend']);
}
}  



function handle_employee_and_cerification_fields ($context, $post_id){
        $has_certification = $context['has_certification'];
        $employee_fields = get_employee_fields($has_certification);
        $storage_keys = dibraco_extract_nested_arrays_test($employee_fields);
        $data_to_save=[];
    foreach ($storage_keys as $container_name => $field_name) {
         if (!is_array($field_name)) {
            $field_name = $_POST[$field_name];
            update_post_meta($post_id, $container_name, $field_name);
         }
        if (is_array($field_name)) {
            foreach ($field_name as $field_name => $field_value) {
                $data_to_save[$container_name][$field_name] = $_POST[$field_name];
            }
            update_post_meta($post_id, $container_name, $data_to_save[$container_name]);
        }
    }
}


function handle_save_common_fields($dibraco_banner, $main_sections, $contact_fields, $post_id){
    $fields_to_save=[];
    if ($dibraco_banner === "1") {
        $fields_to_save += get_banner_fields();
    }
    if ($main_sections === "1") {
        $fields_to_save += get_section_title_fields();
    }
    if ($contact_fields === "1") {
        $fields_to_save += get_contact_fields();
    }
    dibraco_handle_updating_save_fields($fields_to_save, $post_id);
}
function handle_save_connector_post_fields($dibraco_about, $dibraco_commercial, $post_id){
    $fields_to_save=[];
    if ($dibraco_about === "1") {
        $fields_to_save += get_about_fields();
    }
    if ($dibraco_commercial === "1") {
        $fields_to_save += get_commercial_fields();
    }
    dibraco_handle_updating_save_fields($fields_to_save, $post_id);
}

function dibraco_handle_updating_save_fields($fields, $post_id) {
    $fields = (array) $fields;

    foreach ($fields as $field_name => $field_data) {
            if ($field_data['type'] === "text") {
            update_post_meta($post_id, $field_name, sanitize_text_field($_POST[$field_name]));
            continue;
            }
        if ($field_data['type'] === "textarea") {
            update_post_meta($post_id, $field_name, sanitize_textarea_field($_POST[$field_name]));
            continue;
            }
        if ($field_data['type'] === "wysiwyg") {
            update_post_meta($post_id, $field_name, wp_kses_post($_POST[$field_name]));
            }
        }
    }
   
function dibraco_save_landscape_portrait_fields($post_id, $field_definitions){
        foreach ($field_definitions as $field_name => $field_config) {
        error_log('updated' . $field_name . 'value of ' . $_POST[$field_name]);
             update_post_meta($post_id, $field_name, $_POST[$field_name]);
        }
}    
      
       
function dibraco_save_meta_box($post_id, $post, $update){
if (!isset($_POST['dibraco_meta_nonce']) || !wp_verify_nonce($_POST['dibraco_meta_nonce'], 'dibraco_save_meta_action')) {
        return;
    }
    $post_type = $post->post_type;
    $post_id = $post->ID;
    $post_permalink = get_permalink($post_id);
    $enabled_contexts = get_option('enabled_contexts');
    foreach ($enabled_contexts as $enabled_context => $context_data) {
        if ($context_data['post_type'] === $post_type) {
            $context = $context_data;
            break;
        }
    }

    $context_name = $context['context_name'];
    $context_type = $context['context_type'];
    $contact_fields = $context['contact_section'];
    $dibraco_banner = $context['dibraco_banner'];
    $main_sections = $context['main_sections'];
    $landscape = $context['landscape_images'];
    $portrait = $context['portrait_images'];
 
    handle_save_common_fields($dibraco_banner, $main_sections, $contact_fields, $post_id);
    if ($landscape === '1') {
        $land_field_defs = get_landscape_post_image_fields($context_type);
        dibraco_save_landscape_portrait_fields($post_id, $land_field_defs);
    }
    if ($portrait === '1') {
        $port_field_definitions = get_portrait_post_image_fields($context_type);
        dibraco_save_landscape_portrait_fields($post_id, $port_field_definitions);
    }
    $user_defined_fields = get_dibraco_custom_fields_for_context($context_name);
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
            update_post_meta($post_id, $field_id, sanitize_textarea_field($_POST[$field_id]));
        }
    }
    if ($context_type ==='connector') {
        $dibraco_about = $context['about_section'];
        $dibraco_commercial = $context['commercial_section'];
        handle_save_connector_post_fields($dibraco_about, $dibraco_commercial, $post_id);
        $primary_taxonomy = $context['taxonomy'];
        $new_term_id = $_POST["{$primary_taxonomy}_term"];
        if ($new_term_id !==''){
            $new_term_id = (int)$new_term_id;
        }
        if ($context_name ==='service_areas'){
            save_dibraco_type_term_fields($new_term_id, $primary_taxonomy);
            dibraco_enforce_one_connector_term_per_connector_post($post_id, $new_term_id, $primary_taxonomy, "service_area_post_id", "service_area_link_url");
        }
        if ($context_name === 'locations') {
            handle_save_location_term_meta($new_term_id, $primary_taxonomy);
            dibraco_enforce_one_connector_term_per_connector_post($post_id, $new_term_id, $primary_taxonomy, "location_post_id", "location_link_url");
        }
    }
        
    if ($context_type === 'unique') {
        $related_connectors = $context['related_connectors'];
        if (!empty($related_connectors)) {
             save_related_connector_terms_to_unique($post_id, $context, $related_connectors);
            }
            if ($context_name ==='employee') {
                handle_employee_and_cerification_fields($context, $post_id); 
            }
        }
    if ($context_type === 'type') {
        $post_per_term = $context['post_per_term'];
         if ($post_per_term ==="1"){
            $has_term_icon = $context['term_icon'];
            if($has_term_icon ==='1'){
                $value_term_icon = (int)$_POST['term_icon'];
                update_post_meta($post_id, 'term_icon', $value_term_icon);
            }
            $has_before_after = $context['before_after'];
            if ($has_before_after ==="1"){
                $ba_meta_keys = ['dibraco_ba_before', 'dibraco_ba_after', 'dibraco_ba_lock'];
                foreach ($ba_meta_keys as $meta_key) {
                    update_post_meta($post_id, $related_connector_count, $_POST[$meta_key]);
                    }
                }
            $main_post_map = get_option("{$context_name}_main_posts");
            $is_main_post = in_array($post_id, $main_post_map, true);
            $pages_represent = $context['pages_represent'] ??'0';
            
        }
        $related_connector_count = $context['related_connector_count'];
        $primary_taxonomy = $context['taxonomy'];
        $current_type_term_id = dibraco_get_current_term_id_for_post($post_id, $primary_taxonomy);
        $meta_key_for_connector_terms  = "related_type_{$context_name}";
        $new_type_term_id = $_POST["{$primary_taxonomy}_term"];
        if ($new_type_term_id !==''){
            $new_type_term_id=(int)$new_type_term_id;
        }
        
        if ($related_connector_count !==0) {
            $related_connectors = $context['related_connectors'];
            if ($related_connector_count ===2){
                $locations_taxonomy = $related_connectors['locations']['taxonomy'];
                $service_areas_taxonomy = $related_connectors['service_areas']['taxonomy'];
               if (!empty($new_service_area_term)  && $new_service_area_term !== $current_service_area_term){
                    if($current_service_area_term !==''){
                            type_connector_update_term_meta($new_area_id, $meta_key_for_connector_terms, $new_type_term_id, $post_id, $post_per_term);
                            connector_term_clear_old_type_post_meta($current_area_id, $meta_key_for_connector_terms, $new_type_term_id, $post_id, $post_per_term);
                                if ($current_location_term !==''){
                                connector_term_clear_old_type_post_meta($current_location_term, $meta_key_for_connector_terms, $new_type_term_id, $post_id, $post_per_term);
                               update_fallbacks_for_service_area_terms($new_type_term_id, $post_permalink, $related_connectors, $meta_key_for_connector_terms, $related_connector_count);
                              
                         } elseif($current_service_area_term ===''){
                             if ($current_location_term !==''){
                             }
                         }
                    }
                            
                $new_service_area_term = $_POST["{$service_areas_taxonomy}_term"];
                $current_service_area_term = dibraco_get_current_term_id_for_post($post_id, $service_areas_taxonomy);
                 $new_locations_taxonomy_term =  $_POST["{$locations_taxonomy}_term"];
                 $current_location_term = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
               }
                if ($post_per_term ==='1'){
                    
                   if ($new_type_term_id ===''){
                       if (!empty($new_service_area_term)){
                        type_connector_clear_old_meta_not_touching_fallbacks($current_area_id, $meta_key_for_connector_terms, $new_type_term, $post_id, $psot_per_term);

                   }
                   }
                 if (!empty($new_service_area_term)){
                            $current_service_area_term = dibraco_get_current_term_id_for_post($post_id, $service_areas_taxonomy);
                            if (!empty($current_service_area_term)&& $current_service_area_term!==$new_service_area_term){
                                type_connector_clear_old_meta_not_touching_fallbacks($current_area_id, $meta_key_for_connector_terms, $new_type_term, $post_id, $psot_per_term);
                            }

                        $current_location_term = dibraco_get_current_term_id_for_post($post_id, $locations_taxonomy);
                        $new_locations_taxonomy_term =  $_POST["{$locations_taxonomy}_term"];
                        if(!empty($current_location_term)){
                            
                        }
                     }
            if (!empty($service_areas_taxonomy)){
                
            }
        if ($related_connector_count === 1) {
                $connector_taxonomy = $related_connectors['locations']['taxonomy'] ?? '';
            if (empty($connector_taxonomy)) {
                $connector_taxonomy = $related_connectors['service_areas']['taxonomy'];
                }
            }
            }
        
            }
        
        if ($post_per_term === "1" && $pages_represent === '0') {
            dibraco_handle_main_post_map_update($context, $post_id, $current_type_term_id);
        }
        if ($post_per_term === "1") {
           
    
            if ($new_type_term_id === '') {
                  wp_set_object_terms($post_id, [], $primary_taxonomy);
            }
            wp_set_object_terms($post_id, (int)$new_type_term_id, $primary_taxonomy);

            if (!empty($related_connectors)) {
                save_related_connector_terms_to_type_post_type($post_id, $context, $related_connectors, $new_type_term_id, $current_type_term_id, $primary_taxonomy);
            }
            }
        }
    }
}


function dibraco_handle_main_post_map_update($context, $post_id, $current_type_term_id) {
    $context_name = $context['context_name'];
    $taxonomy = $context['taxonomy'];
    $related_connectors = $context['related_connectors'];
    $meta_key = "related_type_{$context_name}";
    $related_connector_count = $context['related_connector_count'];
    $post_permalink = get_permalink($post_id);
    $checkbox_value = $_POST['main_post_for_term'];
    $new_type_term_id = $_POST["{$taxonomy}_term"];
    $main_post_map = get_option("{$context_name}_main_posts");
    if (isset($_POST['new_main_post_select']) && $checkbox_value === '0') {
        $new_main_post_id = $_POST['new_main_post_select'];
        $main_post_map[$current_type_term_id] = (int)$new_main_post_id;
        update_option("{$context_name}_main_posts", $main_post_map);
        update_term_meta($current_type_term_id, 'main_post_for_term', $new_main_post_id);
        dibraco_update_one_main_type_post_fallback($current_type_term_id, get_permalink($new_main_post_id), $related_connectors, $meta_key, $related_connector_count);
    };
    if ($current_type_term_id === '' && $new_type_term_id !== '' && $checkbox_value === '1') {
        $main_post_map[(int)$new_type_term_id] = (int)$post_id;
        update_option("{$context_name}_main_posts", $main_post_map);
        update_term_meta($new_type_term_id, 'main_post_for_term', $post_id);
        dibraco_activate_new_main_type_term_post_fallback($new_type_term_id, $related_connectors, $post_permalink, $meta_key);
    }
    if (!isset($_POST['new_main_post_select']) && $checkbox_value === '1') {
        $main_post_map[(int)$current_type_term_id] = (int)$post_id;
        update_option("{$context_name}_main_posts", $main_post_map);
        update_term_meta($current_type_term_id, 'main_post_for_term', $post_id);
        dibraco_update_one_main_type_post_fallback($current_type_term_id, $post_permalink, $related_connectors, $meta_key, $related_connector_count);
    }
}
function da_render_images_for_pages(){
$post_id = get_the_ID();
$fields_definitions_for_landscape = get_landscape_post_image_fields();
$fields_definitions_for_portrait = get_portrait_post_image_fields();
echo "<div id='da_type_landscape_images' class='dibraco-landscape-images-container dibraco-landscape-images'>";
foreach ($fields_definitions_for_landscape as $field_key => $field_config) { 
if (str_ends_with($field_key, '_lock')) {
    continue;
    }
            $field_value = get_post_meta( $post_id, $field_key, true );
            $field_config['value'] = $field_value;
            echo FormHelper::generateField( $field_key, $field_config );
        }
        echo '</div>';
        echo "<div id='da_type_portrait_images' class='dibraco-portrait-images-container dibraco-portrait-images'>";
        foreach ($fields_definitions_for_portrait as $field_key => $field_config) { 
            if (str_ends_with($field_key, '_lock')) {
                continue;
            }
            $field_value = get_post_meta( $post_id, $field_key, true );
            $field_config['value'] = $field_value;
            echo FormHelper::generateField( $field_key, $field_config );
        }
        echo '</div>';
}
		 
function render_da_pages_metabox(){
$post_id = get_the_id();
$pairs_to_render = [];
$pairs_to_render += get_banner_fields();
$pairs_to_render += get_about_fields();
$pairs_to_render += get_commercial_fields();
$pairs_to_render += get_section_title_fields();
$fields_to_render[] = [];

    $user_defined_fields = get_dibraco_custom_fields_for_context('page');
    if (!empty($user_defined_fields)) {
        foreach ($user_defined_fields as $field_id => $field_config) {
            if (isset($field_config['pair']) || isset($field_config['pair_end'])) {
                $pairs_to_render +=  [$field_id => $field_config];
            } else {
                $fields_to_render[] = [$field_id => $field_config];
            }
         }
    }
    $pairs_to_render += get_contact_fields();
    $pairs_to_render = array_chunk($pairs_to_render, 2, true);
    echo '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
    foreach ($pairs_to_render as $pair) {
        echo '<div style="width: 49%; margin-bottom: 20px;">'; 
        foreach ($pair as $field_key => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_key, true);
            echo FormHelper::generateField($field_key, $field_config);
        }
        echo '</div>'; 
    }
    if (!empty($fields_to_render)) {
    echo '<div style="width: 49%; display: flex; flex-wrap: wrap; gap: 2px; margin-bottom: 20px;">';
    foreach ($fields_to_render as $single_field) {
        foreach ($single_field as $field_key => $field_config) {
            $field_config['value'] = get_post_meta($post_id, $field_key, true);
            $type = $field_config['type'];
            $width= '49%';
            if ($field_config['type']==='wysiwyg' ||$field_config['type']==='textarea'){
                $width='98%';
            }
            echo '<div style="width: ' . $width . ';">';
            echo FormHelper::generateField($field_key, $field_config);
            echo '</div>';
        }
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
$fields_to_save=[];
$fields_to_save += get_banner_fields();
$fields_to_save += get_about_fields();
$fields_to_save += get_commercial_fields();
$fields_to_save += get_section_title_fields();
$fields_to_save += get_contact_fields();
$fields_to_save += get_landscape_post_image_fields();
$fields_to_save += get_portrait_post_image_fields();
     foreach ($fields_to_save as $field_id => $field_data){
         if ($field_data['type']==='textarea' || $field_data['type']==='text' ){
            update_post_meta($post_id, $field_id, sanitize_textarea_field($_POST[$field_id]));
         continue;
         }
         if ($field_data['type']==='image'){
            update_post_meta($post_id, $field_id, (int)$_POST[$field_id]);
            continue;
         }
          if ($field_data['type']==='wysiwyg'){
            update_post_meta($post_id, $field_id, wp_kses_post($_POST[$field_id]));
            continue;
         }
     }
    
}