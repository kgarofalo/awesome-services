<?php

/**
 * The definitive universal population function with added error logging for debugging.
 * It is the precise inverse of the dibraco_extract_field_names_helper function.
 */
function dibraco_condition_checker($field_template, $saved_data){

  foreach ($field_template as $section => $section_config) {
    foreach ($section_config['fields'] as $field_name => $field_config) {
        if (isset($field_config['condition'])) {
            $condition_field = $field_config['condition']['field'];
            $required_field_values = (array) $field_config['condition']['values'];
            $condition_field_value = $saved_data[$condition_field];
            if (!in_array($condition_field_value, $required_field_values, true)) {
                $field_type = $field_config['type'];
                if ($field_type === 'repeater') {
                    $saved_data[$field_name . '_row_count'] = 1;
                    continue;
                }
                if ($field_type === 'group') {
                    foreach ($field_config['fields'] as $subfield_name => $subfield_config) {
                        $meta_key = "{$field_name}_{$subfield_name}";
                        $saved_data[$meta_key] = '';
                    }

                    continue;
                }

                $saved_data[$field_name] = '';
            }
        }
    }
}
    return $saved_data;

}

function dibraco_extract_nested_arrays_test($fields) {
    
    $field_names = [];
    $standard_fields = [
        'text', 'textarea', 'date', 'time', 'colorpicker', 'image', 'number',
        'toggle', 'select', 'radio', 'checkbox', 'wysiwyg', 'hidden',
        'radioIntegers', 'no-edit'
    ];
    $repeating_containers =['repeater'];
    $checkbox_container = ['checkbox_group'];
    $visual_containers = ['visual_section', 'field_group', 'visual_group', 'visual_split'];
    $functional_containers = ['group'];
    $ui_only_types = ['button', 'ui_only'];
   foreach ($fields as $field_name => $field_config) {
       if (isset($field_config['ui_only'])){
           continue;
       }
        $field_type = $field_config['type']; 
        if (in_array($field_type, $standard_fields, true)) {
            $field_names[$field_name] = $field_name;
            continue;
        }
        if (in_array($field_type, $ui_only_types, true)) {
            continue;
        }
        if (in_array($field_type, $functional_containers, true)) {
    $subfields = $field_config['fields'];
    foreach ($subfields as $subfield_name => $subfield_config) {
        if (in_array($subfield_config['type'], $ui_only_types, true)) {
            continue;
            }
        if (in_array($subfield_config['type'], $standard_fields, true)) {
            $field_names["{$field_name}_{$subfield_name}"] = "{$field_name}_{$subfield_name}";
            continue;
            }
        }   
    }

if ($field_type === 'repeater') {
     $field_names["{$field_name}_row_count"] = "{$field_name}_row_count"; 
    $field_names[$field_name] = 'repeater';
    unset($fields[$field_name]);
    continue;
}
       
        
        if (in_array($field_type, $visual_containers, true)) {
            $subfields = $field_config['fields'];
            
            if (!empty($field_config['storage']) && $field_config['storage'] === "1") {
                $nested = dibraco_extract_nested_arrays_test($subfields);
                $field_names[$field_name] = $nested;
                continue;
            }
            if ($field_type === 'repeater') {
                    $field_names["{$field_name}_row_count"] = "{$field_name}_row_count"; 
                    $field_names[$field_name] = 'repeater';
                    unset($fields[$field_name]);
                    continue;
                }
            $nested = dibraco_extract_nested_arrays_test($subfields);
            foreach ($nested as $nested_name => $nested_value) {
                $field_names[$nested_name] = $nested_value;
            }
        }
    }
    
    return $field_names;
}



function dibraco_extract_nested_array_names($fields){
    $array_names = [];
    $visual_containers = ['visual_section', 'repeater', 'field_group', 'visual_group', 'visual_split']; 
   foreach ($fields as $field_name => $field_config) {  
     $storage_flag = $field_config['storage']??'';
     $type = $field_config['type']; 
     if ((!in_array($type, $visual_containers)) || ($storage_flag !=="1")) { 
         continue;
     }
     $array_names[] = $field_name;
}
     return $array_names;
}

function dibraco_extract_single_field_names($fields) { 
    $processed_keys = []; 
    $standard_fields = [ 
        'text', 'textarea', 'date', 'time', 'colorpicker', 'image', 'number', 
        'toggle', 'select', 'radio', 'checkbox', 'wysiwyg', 'hidden', 'radioIntegers', 'checkbox_group' 
    ]; 
    $visual_containers = ['visual_section', 'repeater', 'field_group', 'visual_group', 'visual_split']; 
    $functional_containers = ['group']; 
    $ui_only_types = ['button', 'no-edit']; 

    foreach ($fields as $field_name => $field_config) {
       $storage_flag = $field_config['storage']??'';
        if ( $storage_flag ==='1' || (isset($field_config['is_ui_only']) && $field_config['is_ui_only'] === true) || in_array($field_config['type'], $ui_only_types)) {
            continue;
        }

        $type = $field_config['type'];

        if (in_array($type, $standard_fields)) {
            $processed_keys[$fields] = $field_name;
        }
  

        if (in_array($type, $visual_containers)) { 
            $fields = $field_config['fields']; 
            $processed_keys = array_merge($processed_keys, dibraco_extract_field_names_helper($field_config['fields'])); 
        } 

        if ($type === 'group') { 
            $subfields = $field_config['fields']; 
            foreach ($subfields as $subfield_name => $subfield_config) { 
                $subfield_type = $subfield_config['type']; 
                if (in_array($subfield_type, $ui_only_types)) { 
                    continue; 
                } 
                $processed_keys[] = "{$field_name}_{$subfield_name}"; 
            } 
        } 
    /*
        if ($type === 'repeater') { 
            $processed_keys[] = "{$field_name}_row_count"; 
            $processed_keys[] =  $field_name;
            $subfields = $field_config['fields']; 
            foreach ($subfields as $subfield_name => $subfield_config) { 
                $subfield_type = $subfield_config['type']; 
                if (in_array($subfield_type, $ui_only_types)) { 
                    continue; 
                } 
               $processed_keys[] = "{$field_name}[0][{$subfield_name}]"; 
                if ($subfield_type === 'group') { 
                    $group_subfields = $subfield_config['fields']; 
                    foreach ($group_subfields as $group_field_name => $group_subfield_name) { 
                        $processed_keys[] = "{$field_name}[0][{$group_field_name}_{$group_subfield_name}]"; 
                    } 
                } */
        } 
    return $processed_keys; 
}
function dibraco_extract_field_names_helper($fields) { 
    $processed_keys = []; 
    $standard_fields = [ 
        'text', 'textarea', 'date', 'time', 'colorpicker', 'image', 'number', 
        'toggle', 'select', 'radio', 'checkbox', 'wysiwyg', 'hidden', 'radioIntegers', 'checkbox_group' 
    ]; 
    $visual_containers = ['visual_section', 'repeater', 'field_group', 'visual_group', 'visual_split']; 
    $functional_containers = ['group']; 
    $ui_only_types = ['button', 'no-edit']; 

    foreach ($fields as $field_name => $field_config) {
        if ((isset($field_config['is_ui_only']) && $field_config['is_ui_only'] === true) || in_array($field_config['type'], $ui_only_types)) {
            continue;
        }

        $type = $field_config['type'];

        if (in_array($type, $standard_fields)) {
            $processed_keys[] = $field_name;
        }
  

        if (in_array($type, $visual_containers)) { 
            $fields = $field_config['fields']; 
            $processed_keys = array_merge($processed_keys, dibraco_extract_field_names_helper($field_config['fields'])); 
        } 

        if ($type === 'group') { 
            $subfields = $field_config['fields']; 
            foreach ($subfields as $subfield_name => $subfield_config) { 
                $subfield_type = $subfield_config['type']; 
                if (in_array($subfield_type, $ui_only_types)) { 
                    continue; 
                } 
                $processed_keys[] = "{$field_name}_{$subfield_name}"; 
            } 
        } 

        if ($type === 'repeater') { 
            $processed_keys[] = "{$field_name}_row_count"; 
            $processed_keys[] =  $field_name;
            $subfields = $field_config['fields']; 
            foreach ($subfields as $subfield_name => $subfield_config) { 
                $subfield_type = $subfield_config['type']; 
                if (in_array($subfield_type, $ui_only_types)) { 
                    continue; 
                } 
               // $processed_keys[] = "{$field_name}[0][{$subfield_name}]"; 
                if ($subfield_type === 'group') { 
                    $group_subfields = $subfield_config['fields']; 
                    foreach ($group_subfields as $group_field_name => $group_subfield_name) { 
                        $processed_keys[] = "{$field_name}[0][{$group_field_name}_{$group_subfield_name}]"; 
                    } 
                } 
            } 
        } 
    } 
    return $processed_keys; 
}

function dibraco_extract_field_names_recursive($fields) {
    $processed_keys = dibraco_extract_field_names_helper($fields);
    return $processed_keys;
}
