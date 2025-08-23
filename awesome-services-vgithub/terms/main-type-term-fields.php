<?php



function get_term_landscape_fields(){
      $fields = [];
    for ($i = 1; $i <= 5; $i++) {
        $field_name = "dibraco_landscape_{$i}";
        $fields[$field_name] = ['type' => 'image'];
    }
    return $fields;
}
function get_term_portrait_fields(){
   $fields = [];
    for ($i = 1; $i <= 5; $i++) {
        $field_name = "dibraco_portrait_{$i}";
        $fields[$field_name] = ['type' => 'image'];
    }
    return $fields;
}

function get_term_icon_field(){
    return ['term_icon' => ['type' => 'image']];
}
function get_before_after_repeater_fields(){
    return ['dibraco_ba' => ['type' => 'repeater', 'fields' => ['before' => ['type' => 'image', 'label' => 'Before Image'], 'after' => ['type' => 'image', 'label' => 'After Image']]]];
}
function render_dibraco_term_image_fields($term, $before_after, $enable_landscape_fields, $enable_portrait_fields, $term_icon) {
    echo '<table class="dibraco-admin-table">';
    if ($term_icon ==="1"){
        $term_icon_field = get_term_icon_field();
        $icon_field_name = key($term_icon_field); 
        $icon_config = $term_icon_field[$icon_field_name];
        $saved_icon_value = get_term_meta($term->term_id, $icon_field_name, true) ?? '';
        if (!empty($saved_icon_value)){
            $saved_icon_value = (int)$saved_icon_value;
        }
        $icon_config['value'] = $saved_icon_value;
        echo '<tr class="form-field term-group-wrap dibraco-term-icon-container">';
        echo '<td>';
        echo FormHelper::generateField($icon_field_name, $icon_config); 
        echo '</td></tr>';
    }

    if ($enable_landscape_fields === "1") {
        $saved_landscape_images = get_term_meta($term->term_id, 'dibraco_landscape_images', true) ?? [];
        echo '<tr class="form-field term-group-wrap dibraco-landscape-images-container">';
        echo '<td>';
        $landscape_fields_array = get_term_landscape_fields();
        foreach ($landscape_fields_array as $field_name => $field_config) {
            $field_config['value'] = $saved_landscape_images[$field_name] ?? '';
            echo FormHelper::generateField($field_name, $field_config);
        }
        echo '</td></tr>';
    }

    if ($before_after === "1") {
        $saved_before_after = get_term_meta($term->term_id, 'dibraco_ba', true) ?? [];
        echo FormHelper::generateField('nontracking', [
            'type' => 'valueinjector', 'meta_array' => $saved_before_after,
        ]);

        $before_after_field_config = get_before_after_repeater_fields();
        echo '<tr class="form-field term-group-wrap dibraco-before-after-container"><td>';
        foreach ($before_after_field_config as $field_name => $field_config) {
            echo FormHelper::generateRepeaterField($field_name, $field_config);
        }
        echo '</td></tr>';
        echo FormHelper::generateField('trackerend', ['type' => 'injectionend']);
    }
    
    if ($enable_portrait_fields === "1") {
        $saved_portrait_images = get_term_meta($term->term_id, 'dibraco_portrait_images', true) ?? [];
        echo '<tr class="form-field term-group-wrap dibraco-portrait-images-container">';
        echo '<td>';
        $portrait_fields_array = get_term_portrait_fields();
        foreach ($portrait_fields_array as $field_name => $field_config) {
            $field_config['value'] = $saved_portrait_images[$field_name] ?? '';
            echo FormHelper::generateField($field_name, $field_config);
        }
        echo '</td></tr>';
    }
    
    echo '</table>';
}


function save_dibraco_term_image_fields($term_id, $before_after, $enable_landscape_fields, $enable_portrait_fields, $term_icon) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    $submitted_data = $_POST;
    if ($enable_landscape_fields === "1") {
        $landscape_data = [];
        $keys = array_keys(get_term_landscape_fields()); 
        foreach ($keys as $key) {
            $landscape_data[$key] = $submitted_data[$key];
        }
        update_term_meta($term_id, 'dibraco_landscape_images', $landscape_data);
    }
    if ($term_icon ==="1") {
        $icon_value = $submitted_data['term_icon'];
        update_term_meta ($term_id, 'term_icon', $icon_value);
    }
    if ($before_after === "1") {
        $data_to_save = [];
        $repeater_config = get_before_after_repeater_fields();
        $base_name  = key($repeater_config);
        $sub_fields = array_keys($repeater_config[$base_name]['fields']);
        $row_count_key = "{$base_name}_row_count";
        $row_count = (int)$submitted_data[$row_count_key];
        update_term_meta($term_id, $row_count_key, $row_count);
        for ($i = 0; $i < $row_count; $i++) {
            foreach ($sub_fields as $sub_field_name) {
                $post_key = "{$base_name}[{$i}][{$sub_field_name}]";
                $data_to_save[$post_key] = $submitted_data[$post_key];
            }
        }
        update_term_meta($term_id, $base_name, $data_to_save);
    }

    if ($enable_portrait_fields === "1") {
        $portrait_data = [];
        $keys = array_keys(get_term_portrait_fields());
        foreach ($keys as $key) {
            $portrait_data[$key] = $submitted_data[$key];
        }
        update_term_meta($term_id, 'dibraco_portrait_images', $portrait_data);
    }
}



function render_term_certification_fields($term) {
    $has_certification = get_term_meta($term->term_id, 'has_certification', true);
    $cert_data = get_term_meta($term->term_id, 'certification_data', true);
    echo FormHelper::generateField('has_certification', ['type' => 'toggle', 'value' => $has_certification]);
    $fields = get_certification_fields();
    foreach ($fields['certification_section']['fields'] as $field_key => $field_config) {
        $field_config['value'] = $cert_data[$field_key] ?? '';
        $fields['certification_section']['fields'][$field_key] = $field_config;
    }
    echo FormHelper::generateVisualFieldGroup('certification_section', $fields['certification_section']);
}

function save_term_certification_fields($term_id) {
    $has_cert = $_POST['has_certification'] ?? '0';
    update_term_meta($term_id, 'has_certification', $has_cert);
    $fields = get_certification_fields();
    $data_to_save = [];
    foreach ($fields['certification_section']['fields'] as $field_key => $_) {
        if ($has_cert === '0') {
            $data_to_save[$field_key] = '';
        } else {
            $data_to_save[$field_key] = sanitize_text_field($_POST[$field_key]);
        }
    }
    update_term_meta($term_id, 'certification_data', $data_to_save);
}

