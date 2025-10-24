<?php
function render_dibraco_type_term_fields($term_id) {
$term = get_term($term_id);
$this_taxonomy = $term->taxonomy;
    $enabled_contexts = get_option('enabled_type_contexts');
    foreach ($enabled_contexts as $enabled_context) {
        if ($enabled_context['taxonomy'] === $this_taxonomy) {
            $context = $enabled_context;
            break; 
        }
    }
   
    error_log('I am here');
    $fields_to_render = [];
    $post_per_term = $context['post_per_term'];
    $all_values = get_term_meta($term_id);
    $all_values = array_map('maybe_unserialize', array_map('current', $all_values));
    error_log(print_r($all_values,true));

    $context_name = $context['context_name'];
    if ($post_per_term === "1") {
        if ($context['term_icon'] === "1") {
            $fields_to_render += get_type_post_term_icon_field();
        }
        if ($context['before_after'] === "1") {
            $fields_to_render += get_before_after_type_term_repeater_fields();
        }
    }
    if ($context['has_certification'] === "1") {
        $fields_to_render += get_certification_fields();
    }
    if ($context['landscape_images'] === "1") {
        $fields_to_render += get_term_landscape_fields();
    }
    if ($context['portrait_images'] === "1") {
        $fields_to_render += get_term_portrait_fields();
    }
    error_log('start fields to render');    
    error_log(print_r($fields_to_render,true));
    error_log('thatwas fields to render');
    $storage_keys= dibraco_extract_nested_arrays_test($fields_to_render);
   
    //(print_r($all_values, true));
    error_log(json_encode($storage_keys, true));
   foreach($storage_keys as $container_name => $storage_array_key){
    if (is_array($storage_array_key)){
        if (array_key_exists($container_name, $all_values)){
         $all_values = array_merge($all_values,$all_values[$container_name]); 
          $storage_keys = array_merge($storage_keys,$storage_keys[$container_name]);
          unset($all_values[$container_name]);
          unset($storage_keys[$container_name]);
            }
        }
    }

    $mapped_values = dibraco_filter_saved_data($all_values, $storage_keys);
    error_log(json_encode($mapped_values, true));
  
    FormHelper::generateField('who_cares', ['type' => 'valueinjector','meta_array' => $mapped_values]);
    echo FormHelper::generateVisualSection("{$context_name}_form", ['fields' => $fields_to_render]);
    FormHelper::generateField('who_cares', ['type' => 'injectionend']);
}

function save_dibraco_type_term_fields($term_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!wp_verify_nonce($_POST['type_context_nonce'], "save_type_term_fields_{$term_id}")) {
 return;
    }
    $term = get_term($term_id);
    $taxonomy = $term->taxonomy;
 $enabled_contexts = get_option('enabled_type_contexts');
  foreach ($enabled_contexts as $enabled_context) {
        if ($enabled_context['taxonomy'] === $taxonomy) {
            $context = $enabled_context;
            break; 
        }
    }
    $post_per_term = $context['post_per_term'];
    $fields_to_save=[];
   

    if ($post_per_term ==="1"){
        if ($context['term_icon'] === "1") {
            $fields_to_save += get_type_post_term_icon_field();      
            }
        if ($context['before_after'] === "1") {
            $fields_to_save += get_before_after_repeater_fields();
            }
       }
    if ( $context['has_certification']==="1") {
         $fields_to_save +=  get_certification_fields();
    }
    if ($context['landscape_images'] === "1") {
       $fields_to_save += get_term_landscape_fields();
    }
    if ($context['portrait_images'] === "1") {
        $fields_to_save += get_term_portrait_fields();
    }
    
    $template_fields = $fields_to_save; 
    error_log('a list of my fields to save');

    error_log(print_r($template_fields, true));
    $storage_keys = dibraco_extract_nested_arrays_test($template_fields);
    $data_to_save = [];
    $individual_fields =[];
  error_log('a list of my fields storage keys');
error_log(print_r($storage_keys, true));
  error_log('a list of whta comes through in post submit');

error_log(print_r($_POST, true));

  foreach ($storage_keys as $container_name => $field_name) {
         if (!is_array($field_name)) {
              $value = $_POST[$field_name];
               if(is_numeric($value)){
                      $value = (int)$value;
            }
             $individual_fields[$field_name]= $value;
        }   
        if (is_array($field_name)){
             foreach ($field_name as $field_name => $field_value){
                 $data_to_save[$container_name][$field_name] = $_POST[$field_name];
            }
            update_term_meta($term_id, $container_name, $data_to_save[$container_name] );
        }
        unset($data_to_save[$container_name]); 
    }

foreach ($individual_fields as $field_name => $value) {
        update_term_meta($term_id, $field_name, $value);
}
}