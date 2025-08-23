<?php


function get_term_landscape_fields(){
      $fields = [];
    for ($i = 1; $i <= 5; $i++) {
        $field_name = "dibraco_landscape_{$i}";
        $fields[$field_name] =  ['type' => 'image'];
      
    }
    return $fields;
}
function get_term_portrait_fields(){
   $fields = [];
    for ($i = 1; $i <= 5; $i++) {
        $field_name = "dibraco_portrait_{$i}";
         $fields[$field_name]= ['type' => 'image'];
    }
    return $fields;
}
function get_term_icon_field(){
    return ['term_icon' => ['type' => 'image']];
}
function get_before_after_repeater_fields(){
    return ['dibraco_ba' => ['type' => 'repeater', 'fields' => ['before' => ['type' => 'image', 'label' => 'Before Image'], 'after' => ['type' => 'image', 'label' => 'After Image']]]];
}
function render_dibraco_term_fields ($term) {
    $taxonomy = $term->taxonomy;
    $enabled_contexts = get_option('enabled_contexts');
    foreach ($enabled_contexts as $context){
        if ($context['context_type'] !== 'unique' && $context['taxonomy'] === $taxonomy) {
            $context_data = $context;
            break;
        }
    }
    $context_name = $context_data['context_name'];
    $context_type = $context_data['context_type'];
    $term_id = $term->term_id;
    echo '<table class="dibraco-admin-table">';
    if ($context_type === 'type') {
        if ($context_data['term_icon'] === "1") {
            $term_icon_field = get_term_icon_field();      
            $field_config    = $term_icon_field['term_icon'];
            $field_config['value'] = get_term_meta($term_id, 'term_icon', true);
            echo '<tr class="form-field term-group-wrap dibraco-term-icon-container"><td>';
            echo FormHelper::generateField('term_icon', $field_config);
            echo '</td></tr>';
        }
        if ($context_data['before_after'] === "1") {
            $saved_before_after = get_term_meta($term_id, 'dibraco_ba', true);
            FormHelper::generateField('nontracking', [
                'type'       => 'valueinjector',
                'meta_array' => $saved_before_after,
            ]);
            $before_after_field_config = get_before_after_repeater_fields();
            echo '<tr class="form-field term-group-wrap dibraco-before-after-container"><td>';
            foreach ($before_after_field_config as $field_name => $field_config) {
                echo FormHelper::generateRepeaterField($field_name, $field_config);
            }
            echo '</td></tr>';
            FormHelper::generateField('trackerend', ['type' => 'injectionend']);
        }
    }
    if ($context_name !== 'service_areas') {
        $has_cert = $context_data['has_certification'];
        if ($has_cert === '1') {
            $saved_data = (array)get_term_meta($term_id, 'certification_data', true);
            $has_certification = get_term_meta($term_id, 'has_certification', true)??'1';
            echo FormHelper::generateField('has_certification', ['type' => 'toggle', 'value' => $has_certification]);
            $field_definitions = get_certification_fields()['certification_data']['fields'];
            $merged_fields = [];
            foreach ($field_definitions as $field_key => $field_config) {

            $saved_value = $saved_data[$field_key]??'';
            $merged_fields[$field_key] = [
                'type'  => $field_config['type'],
                'value' => $saved_value,
            ];
        }
        $payload_for_form_helper = [
            'condition' => ['field' => 'has_certification', 'values' => ['1'], 'current_value' => $has_certification],
            'fields' => $merged_fields,
        ];
        echo FormHelper::generateVisualFieldGroup('certification_data', $payload_for_form_helper);
    }
}


   if ($context_data['repeater_images'] === "1") {
        $saved_landscape_images = get_term_meta($term_id, 'dibraco_landscape_images', true);
        echo '<tr class="form-field term-group-wrap dibraco-landscape-images-container"><td>';
        $landscape_fields = get_term_landscape_fields();
        foreach ($landscape_fields as $field_name => $field_config) {
                $field_config['type'] = 'image';
                $field_config['value'] = $saved_landscape_images[$field_name] ?? '';
            echo FormHelper::generateField($field_name, $field_config);
        }
        echo '</td></tr>';
    }
    if ($context_data['portrait_images'] === "1") {
        $saved_portrait_images = get_term_meta($term_id, 'dibraco_portrait_images', true);
        echo '<tr class="form-field term-group-wrap dibraco-portrait-images-container"><td>';
        $portrait_fields = get_term_portrait_fields();
        foreach ($portrait_fields as $field_name => $field_config) {
            $field_config['value'] = $saved_portrait_images[$field_name]??'';
            echo FormHelper::generateField($field_name, $field_config);
        }
        echo '</td></tr>';
    }
    echo '</table>';
}

function pack_field_group_with_data( $field_config, $saved_data ) {
    foreach ( $saved_data as $key => $value ) {
        if ( array_key_exists( $key, $field_config['fields'] ) ) {
            $field_config['value'] = $value;
        }
    }

    return $field_config;
}

function save_dibraco_general_term_fields($term_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    $term = get_term($term_id);
    $taxonomy = $term->taxonomy;
    $enabled_contexts = get_option('enabled_contexts');
    $context_data = [];
    foreach ($enabled_contexts as $context){
        if ($context['context_type'] === 'unique') { continue; }
        if ($context['taxonomy'] === $taxonomy) {
            $context_data = $context;
            break;
        }
    }
   $context_name = $context_data['context_name'];
   $context_type = $context_data['context_type'];
   if ($context_data['repeater_images'] === "1") {
        $landscape_data = [];
        $field_names = array_keys(get_term_landscape_fields()); 
        foreach ($field_names as $field_name) {
            $landscape_data[$field_name] = $_POST[$field_name];
        }
        update_term_meta($term_id, 'dibraco_landscape_images', $landscape_data);
    }
    if ($context_data['portrait_images'] === "1") {
        $portrait_data = [];
        $field_names = array_keys(get_term_portrait_fields());
        foreach ($field_names as $field_name) {
            $portrait_data[$field_name] = $_POST[$field_name];
        }
        update_term_meta($term_id, 'dibraco_portrait_images', $portrait_data);
    }
    if ($context_name !== 'service_areas' && (($context_data['has_certification'] ??'') === '1')) {
        update_term_meta ($term_id, 'has_certification', $_POST['has_certification']);
        if ($_POST['has_certification'] === "0") {
            delete_term_meta($term_id, 'certification_data');
        }
        if ($_POST['has_certification'] ==="1"){
            $cert_data=[];
            $fields = get_certification_fields()['certification_data']['fields'];
            foreach($fields as $field_name => $field_config){
            $cert_data[$field_name] = $_POST[$field_name];
            }
            update_term_meta($term_id, 'certification_data', $cert_data);
        }
    }
    if ($context_type ==='type'){
        if ($context_data['term_icon'] === "1") {
            $icon_value = $_POST['term_icon'];
            update_term_meta ($term_id, 'term_icon', $icon_value);
        }
    if ($context_data['before_after'] === "1") {
        $row_count = (int) $_POST['dibraco_ba_row_count'];
        $ba_meta   = [];
        $ba_meta['dibraco_ba_row_count'] = $row_count;
        for ($i = 0; $i < $row_count; $i++) {
        $ba_meta["dibraco_ba[{$i}][before]"] = $_POST["dibraco_ba[{$i}][before]"];
        $ba_meta["dibraco_ba[{$i}][after]"]  = $_POST["dibraco_ba[{$i}][after]"];
        }
        update_term_meta($term_id, 'dibraco_ba', $ba_meta);
        }
    }
}


