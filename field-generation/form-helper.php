<?php
class FormHelper {
private static function prepareCondition($condition) {
    $controlling_field = $condition['field'];
    $current_value = $condition['current_value'] ?? '';
    $controlling_values = implode('|', (array)($condition['values']));
    $condition_attributes = "data-controlling-field='{$controlling_field}' data-controlling-values='{$controlling_values}'";
    $class_value = '';
    if (($current_value !=='')) {
     if (in_array($current_value, (array) ($condition['values']))) {
            $class_value = "visible";
         }
        if (!in_array($current_value, (array) ($condition['values']))) {
            $class_value = "hidden";
         }
    }
    return ['condition_attributes'=>$condition_attributes, 'condition_class'=>$class_value];
}

private static function prepareField($field_id, $condition, $base_class, $label ='') {
    $condition = is_array($condition) ? $condition : [];
    $name_to_class  = str_replace('_', '-', $field_id);
    $data_name  = "data-name='{$name_to_class}'";
    $class_list = "class='{$base_class} {$name_to_class}'";
    $label_for ='';
    $legend ='';
    $start_div_visual_split ="<div id='{$field_id}' {$class_list} {$data_name}>"; 
    $condition_attributes = '';
    if (!empty($condition)) {
        $prepared = self::prepareCondition($condition);
        $condition_attributes = $prepared['condition_attributes'];
        $condition_class = $prepared['condition_class'];
        if (!empty($condition_class)){
            $class_list = "class='{$base_class} {$name_to_class} {$condition_class}'";
        }
    }
    if($label !==''){
        $label_for = ucwords(str_replace('_', '-', $label));
        $legend_for =  ucwords(str_replace(['_', '-'], ' ', $label));
        $legend = "<legend>{$legend_for}</legend>";
        $label_for = "<label for='{$field_id}' {$condition_attributes}>$label_for</label>";
    }
    $display_style = null;
    if ($base_class ==='dibraco-toggle'){
        $display_style = "style='display: inline-flex; flex-direction: column; align-items: flex-start; max-width: 100%; min-width: 100px'";
    }
    $start_div="<div {$class_list} {$data_name} {$condition_attributes}>";
    $fieldset="<fieldset id='{$field_id}' {$class_list} {$display_style} {$data_name} {$condition_attributes}>";
    $start_id_div="<div id='{$field_id}' {$class_list} {$data_name} {$condition_attributes}>";
    $start_div_no_data_name="<div {$class_list} {$condition_attributes}>";
     return [
        'class_string' => $class_list,
        'data_name' => $data_name,
        'name_normalized' => $name_to_class,
        'condition_attributes' => $condition_attributes,
        'start_div' => $start_div,
        'start_id_div' => $start_id_div,
        'fieldset'=>$fieldset,
        'label_for' => $label_for,
        'legend' => $legend,
        'start_div_visual_split' =>$start_div_visual_split,
        'start_div_no_data_name' =>$start_div_no_data_name 
    ];
}


public static function generateToggleSwitch($field_id, $label, $value, $options_label = [], $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-toggle", $label));
    if (empty($options_label)) {
        $options_label = ["0" => 'ON', "1" => 'OFF'];
    }
    if ($value !== "1" && $value !== "0") {
        $value = "1"; 
}
    return "{$fieldset}
            {$legend}
            <div class='the-toggle' {$condition_attributes}>
                <input type='radio' class='toggle-input toggle-yes' id='{$field_id}_yes' name='{$field_id}' {$data_name} value='1'". checked($value, "1", false).">
                <input type='radio' class='toggle-input toggle-no' id='{$field_id}_no' name='{$field_id}' {$data_name} value='0'". checked($value, "0", false).">
                <label class='toggle-label toggle-yes' for='{$field_id}_yes'>{$options_label["1"]}</label>
                <label class='toggle-label toggle-no' for='{$field_id}_no'>{$options_label["0"]}</label>
            </div></fieldset>\n";
}

public static function generateCheckboxGroup($field_id, $label, $selected_values = [], $options = [], $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-checkbox-group", $label));
    $options_html = '';
    
    foreach ($options as $option_value => $option_label) {
        $checkbox_id = $option_value;
        $option_label = ucwords(str_replace(['_', '-'], ' ', $option_label));
        $checked = '';
        if (in_array($option_value, $selected_values)) {
            $checked = 'checked';
        }
        $options_html .= "
            <label for='{$checkbox_id}' name=style='margin-right:10px;display:inline-block;'>
                <input type='checkbox' id='{$checkbox_id}' name='{$field_id}[{$option_value}' value='{$option_value}' {$checked} {$data_name} {$option_label}></label>";
    }
    return "{$fieldset}{$legend}{$options_html}</div></fieldset>";
}


public static function generateCheckBox($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-checkbox", $label));
    $value = ($value == "1") ? "1" : "0";
    $checked_attr = ($value === "1") ? 'checked' : '';
    return "{$fieldset}{$legend}<input type='hidden' name='{$field_id}' value='0'><input type='checkbox' {$data_name} name='{$field_id}' value='1' {$checked_attr}></fieldset>";
}

public static function generateRadioFieldsetWithIntegerValues($field_id, $label, $value, $options_array = [], $condition = []) {
  $enabled_contexts = get_option('enabled_contexts');
    $base_class = 'dibraco-radio-fieldset';
    if (isset($enabled_contexts[$label])) {
        $base_class = "quickedit-fieldset";
    }
    extract(self::prepareField($field_id, $condition, $base_class, $label));
    $none_checked = null; 
    if ($value === '') {$none_checked = 'checked';}
    $none_option_id = "{$field_id}_none";
    $options_html = "<label for='{$none_option_id}'><input type='radio' id='{$none_option_id}' name='{$field_id}' {$data_name} value='' {$none_checked}>None</label>";
    foreach ($options_array as $option_value => $option_label) {
        $option_id = "{$field_id}_{$option_value}";
        $checked = null;
      if ($value !== '' && intval($value) === intval($option_value)) {
            $checked = 'checked';
            }
        $options_html .= "<label for='{$option_id}'><input type='radio' id='{$option_id}' name='{$field_id}' {$data_name} value='{$option_value}' {$checked}>$option_label</label>";
        }
    
    return "{$fieldset}
            {$legend}
            {$options_html}
            </fieldset>";
}
public static function generateRadioFieldset($field_id, $label, $value, $options_array = [], $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-radio"));
    $options_html = "";
    foreach ($options_array as $option_value => $option_label) {
        $checked = null;
        $option_id = "{$field_id}_{$option_value}";
        if($value === $option_value){
            $checked = 'checked';
        }
        $options_html .= "
            <label for='{$option_id}'><input type='radio' id='{$option_id}' name='{$field_id}' {$data_name} value='{$option_value}' {$checked}>{$option_label}</label>";
        }
    return "{$fieldset}
            {$legend}
            {$options_html}
            </fieldset>";
}

public static function generateHiddenField($field_id, $value, $condition = []) {
 $data_name  = str_replace('_', '-', $field_id);
return "<input type='hidden' id='{$field_id}' class='dibraco-hidden-field {$data_name}' name='{$field_id}' data-name='{$data_name}' value='{$value}'>";
}

private static function buildSelectOptions($value, $options) {
 if (isset($options['__no_change__'])) {
        $value = '__no_change__';   }
     $none_selected = null;
    if ($value ==='') {$none_selected = 'selected';}
    $options_html = "<option value='' {$none_selected}>Please select</option>\n";
    foreach ($options as $option_value => $option_label) {
         $selected = null;
         if ($value == $option_value) { 
        $selected = 'selected';}
       $option_label = ucwords(preg_replace('/[^\w]+/', ' ', $option_label));
     $options_html .= "<option value='{$option_value}' {$selected}>$option_label</option>\n";
    }
    return $options_html;
}
public static function generateSelect($field_id, $label, $value, $options, $condition = []) {
     $field_config['options'] = $options;
     return self::generateSimpleField($field_id, "select", $value, $condition, $field_config);
}
public static function generateWysiwyg($field_id, $label, $value, $rows, $condition = [], $field_config=[]) {
    return self::generateSimpleField($field_id, "wysiwyg", $value, $condition, $field_config);
}
public static function generateTextarea($field_id, $label, $value, $rows ='', $condition = [], $field_config=[]) {
    return self::generateSimpleField($field_id, "textarea", $value, $condition, $field_config);
}
public static function generateNoEditField($field_id, $label, $value, $condition = []) {
   return self::generateSimpleField($field_id, "no-edit", $value, $condition, $field_config=[]);
}
public static function generateTimeInput($field_id, $label, $value, $condition = []) {
   return self::generateSimpleField($field_id, "time", $value, $condition, $field_config=[]);
}
public static function generateNumberInput($field_id, $label, $value, $step = 1, $condition = [], $field_config=[]) {
return self::generateSimpleField($field_id, "number", $value, $condition, $field_config);
}
public static function generateDateInput($field_id, $label, $value, $condition = []) {
   return self::generateSimpleField($field_id, "date", $value, $condition, $field_config=[]);
}
public static function generateTextInput($field_id, $label, $value, $condition = []) {
   return self::generateSimpleField($field_id, "text", $value, $condition, $field_config=[]);
}
public static function generateButtonField($field_id, $label, $class, $condition = []) {
$button_text = ucwords(str_replace(['_', '-'], ' ',$label)); 
extract(self::prepareField($field_id, $condition, "dibraco-button"));
return "{$start_div}<button id='{$field_id}' name='{$field_id}' type='button' class='{$class}' {$data_name}>{$button_text}</button></div>";
}


private static function custom_editor_toolbar($name_to_class) {
    return "<div class='wysiwyg-toolbar' data-editor='{$name_to_class}'>
                <button type='button' class='editor-tab visual-tab active dashicons-before dashicons-edit' data-mode='visual'>Visual</button>
                <button type='button' class='editor-tab text-tab dashicons-before dashicons-editor-code' data-mode='text'>Text</button>
                <button type='button' class='insert-shortcode dashicons-before dashicons-phone' data-shortcode='[telephone-link]'>Phone</button>
                <button type='button' class='insert-shortcode dashicons-before dashicons-location' data-shortcode='[service-areas]'>Service Areas</button>
            </div>";
}
private static function generateSimpleField($field_id, $field_type, $value, $condition, $field_config=[]){
 $label = $field_config['label'] ?? self::generateLabel($field_id);
 $condition = is_array($condition) ? $condition : [];
 $base_class = "dibraco-{$field_type}";
 $name_to_class  = str_replace('_', '-', $field_id);
 $data_name  = "data-name='{$name_to_class}'";
 $class_list = "class='{$base_class} {$name_to_class}'";
 $string_type = "type='{$field_type}'";
 if($field_type ==='no-edit'){
     $string_type ="type='text' disabled style='background: #eeeeee; color:black; border: 1px solid #cccccc;'";
 }
 $string_name = "name='{$field_id}'";
 $string_value = "value='{$value}'";
 $string_id = "id='{$field_id}'";
 $condition_attributes = '';
 if (!empty($condition)) {
        $prepared = self::prepareCondition($condition);
        $condition_attributes = $prepared['condition_attributes'];
        $condition_class = $prepared['condition_class'];
        if (!empty($condition_class)){
            $class_list = "class='{$base_class} {$name_to_class} {$condition_class}'";
        }
         $label_for = "<label for='{$field_id}' {$condition_attributes}>$label</label>";
        $label_string="<label {$condition_attributes} class='{$condition_class}'>{$label}";
  }
 if(empty($condition)){
       $label_for = "<label for='{$field_id}'>$label</label>";
       $label_string="<label>$label";
 }
if ($field_type === 'select') {
    $options = $field_config['options'];
    $options_html = self::buildSelectOptions($value, $options);
    return "{$label_string}<select {$string_id} {$string_name} {$class_list} {$data_name}>$options_html</select></label>\n";
 }
 if ($field_type === 'textarea' || $field_type === 'wysiwyg'){
          $rows = $field_config['rows']; 
       $rows_string = "rows='{$rows}'";
    if($field_type === 'wysiwyg'){
        $toolbar = self::custom_editor_toolbar($name_to_class);
        $start_div = "<div {$class_list} {$data_name} {$condition_attributes}>";
        return "{$start_div}{$label_for}{$toolbar}<textarea {$string_name} {$string_id} {$rows_string} {$data_name}>$value</textarea></div>";
      } 
     return "$label_string<textarea {$string_name} {$class_list} {$string_id} {$rows_string} {$data_name}>$value</textarea></label>";
    }
 if($field_type ==='number'){
    $step = $field_config['step'];
    $string_step = "step=$step";
    return "{$label_string}<input {$string_type} {$string_id} {$string_name} {$string_value} {$string_step} {$data_name} {$class_list} {$condition_attributes}></label>";
 }
 
 return "{$label_string}<input {$string_type} {$string_id} {$string_name} {$string_value} {$class_list} {$condition_attributes}></label>";
    
}


public static function generateColorPicker($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-color-picker", $label));
    $color = '#FFFFFF';
    $alpha = 1.0;
    $value = trim((string) $value);
    $value = ltrim($value, '#');
    $value = '#' . substr($value, 0, 8);
    if (((strlen($value) === 7) || (strlen($value) === 9)) && (substr($value, 0, 1) === '#')) {
        if (strlen($value) === 9) {
            $color = substr($value, 0, 7); 
            $alpha = round(hexdec(substr($value, 7, 2)) / 255.0, 2);
        } else {
            $color = $value; 
        }
    }
    return "{$start_div}
    <label for='{$field_id}'>{$label}</label>
            <input type='text' class='dibraco-hex8-input' id='{$field_id}' name='{$field_id}' value='{$value}' {$data_name}>
 <button type='button' class='wp-color-result dibraco-fake-color-btn' {$data_name} style='display: flex; justify-content: flex-end; background-color:{$color}; padding:0; border: 1px solid black; border-radius: 3px; height: 28px; width: 100px;'>
                <span class='fake-picker-label' style='width: 75%; background-color: white; color: black; border-left:1px solid black; font-size:11px; display: inline-flex; align-items: center; justify-content: center;'>PICK COLOR</span>
            </button>
        <input type='hidden' class='wp-color-input' id='{$field_id}_hidden' value='{$value}' data-default-color='{$color}' {$data_name}>
        <div class='dibraco-slider iris-slider iris-strip' id='{$field_id}_slider' data-alpha-value='{$alpha}' {$data_name}></div>
    </div>";
}

public static function generateImageField($field_id, $label, $value, $condition = []) {
    $title = $label;
    extract(self::prepareField($field_id, $condition, "dibraco-image-field", $label));
    $image_id = $value;
  if ($value !== '') {
    if (!is_numeric($value)) {
        $image_id = attachment_url_to_postid($value);
    } elseif (is_numeric($value)){
        $image_id = $value;
        $value = wp_get_attachment_url($value);
    }
}
    return"{$start_div}$title
                    <img class='image-preview' src='{$value}' id='{$name_normalized}-preview' image-id='{$image_id}' style='max-width: 100%; object-fit: contain; aspect-ratio: auto; height: auto; display: block;'>
                        <input type='hidden' class='dibraco_image_id_input' id='{$name_normalized}' name='{$field_id}' value='{$value}' {$data_name}>
                        <div class='buttons'>
                            <button type='button' class='button media-button add_media' data-input='{$name_normalized}' data-name='{$name_normalized}-media-button' data-preview='{$name_normalized}-preview'>Choose Image</button>
                            <button type='button' class='button clear_image_button' data-name='{$name_normalized}-clear-image-button' data-input='{$name_normalized}' data-preview='{$name_normalized}-preview'>Clear Image</button>
                        </div>
                </div>";
}


private static $tracking_started = false;
private static $tracked_field_names = [];
private static $meta_array = [];

public static function generateField($field_name, $field_config) {
    $type = $field_config['type'];

    if ($type === "valueinjector") {
        if (empty($field_config['meta_array'])) {
                return;
        }
        self::$meta_array = $field_config['meta_array'];
         self::$tracking_started = true;
          self::$tracked_field_names = array_keys(self::$meta_array);
          return;
    }
    if (self::$tracking_started && $type !== "button" && $type !=='injectionend' && $type !=='valueinjector' && (!isset($field_config['skip_line']))) {
      foreach (self::$tracked_field_names as $tracked_field_name) {
          if ($field_name === $tracked_field_name) {
                    $stored_value = self::$meta_array[$tracked_field_name];
                    $field_config['value'] = $stored_value;
                }
            }
        }
    
    if ($type === "injectionend") {
        self::$tracking_started = false;
        self::$tracked_field_names = [];
        self::$meta_array = [];
    }
      if ($type === "injectionend" || $type === "valueinjector") {
        return;
    }
      if (empty($field_name) || empty($type) || empty($field_config)) {
            return "Error: Missing required parameters ('field_name', 'type', or 'field_config').";
        }
       
        if ($type !== 'button' && !array_key_exists('value', $field_config)) {
            $field_config['value'] = '';  
        }
        $values = $field_config['value'] ?? [];
        $rows_textarea = $field_config['rows'] ?? '';
        $field_config['rows'] = $rows_textarea;
        $value = $field_config['value'] ?? '';
           if($value ===''){
            $value =  $field_config['value'] = '';
           }
        $class = $field_config['class'] ?? '';
        $image_url = $field_config['data-url'] ?? '';
        $label = $field_config['label'] ?? ucwords(str_replace(['_', '-', '[', ']', '(', ')'], ' ', $field_name)); 
        $label = preg_replace('/\s+/', ' ', $label); 
        $label = trim($label); 
        $options_label = $field_config['options_label'] ?? [];
        if (!array_key_exists('condition', $field_config) || !is_array($field_config['condition'])) {
            $field_config['condition'] = [];
        }
        $condition = $field_config['condition'];

        $options = $field_config['options'] ?? [];
        if($type ==='number'){
            $step = $field_config['step'] ?? '1';
            $field_config['step'] = $step;
        }    
        if ($type === 'radio' && empty($options)) {
            return "Error: The {$type} field '{$field_name}' requires options but none were provided.";
        }
            unset($field_config['value']);
            unset($field_config['condition']);
            unset($field_config['type']);
        switch ($type) {
            case 'colorpicker':
                return self::generateColorPicker($field_name, $label, $value, $condition);
            case 'image':
                return self::generateImageField($field_name, $label, $value, $condition);
            case 'button':
                return self::generateButtonField($field_name, $label, $class, $condition);
            case 'toggle':
                return self::generateToggleSwitch($field_name, $label, $value, $options_label, $condition);
            case 'radio':
                return self::generateRadioFieldset($field_name, $label, $value, $options, $condition);
            case 'checkbox':
                return self::generateCheckBox($field_name, $label, $value, $condition);
            case 'checkbox_group':
                return self::generateCheckboxGroup($field_name, $label, $values, $options, $condition);
            case 'hidden': 
                return self::generateHiddenField($field_name, $value, $condition = []);
            default:
                 return self::generateSimpleField($field_name, $type, $value, $condition, $field_config);
        }
        return "Error: Unsupported field type '{$type}'.";
    }



private static function hiddenFields($field_name){
   return [
    'storage_field' => "<input type='hidden' name='{$field_name}' value='storage_container'>",
    'storage_end' => "<input type='hidden'  name='{$field_name}_end' value='storage_container_end'>"
    ];
}
public static function generateVisualSplit($field_name, $field_config) {
    $condition = $field_config['condition'] ?? [];
    $fields = $field_config['fields'];
    extract(self::prepareField($field_name, $condition, "dibraco-split"));
    $storage_array = $field_config['storage']??'0';
    $hidden = ['storage_field' => '', 'storage_end'   => ''];

    $subfields_html = '';
    $first_subfield_html = '';
    if($storage_array ==='1'){
        $hidden = self::hiddenFields($field_name);
    }
       $open_day =false;
       $is_first = true;
      
    foreach ($fields as $subfield_name => $subfield_config) {
        $subfield_config['condition'] = $subfield_config['condition'] ?? [];
        $type = $subfield_config['type'];
        $subfield_config['label'] = $subfield_config['label'] ?? ucwords(str_replace(['_', '-'], ' ', $subfield_name));

        if ($is_first) {
            $first_subfield_html = self::generateField($subfield_name, $subfield_config);
            $is_first = false;
        } else {
           if (str_starts_with($subfield_name, 'open_')) {
                if ($open_day) {
                     $subfields_html .= "</div>";
                 }
                    $subfields_html .= "<div class='day-col'>";
                  $open_day = true;
                 }
                   $subfields_html .= self::generateField($subfield_name, $subfield_config);
                }
    }
     if($open_day){
       $subfields_html .="</div>"; 
    }
    return "{$start_div_visual_split}
            {$hidden['storage_field']}
            {$first_subfield_html}
            <div class='split-group' {$condition_attributes}>
            {$subfields_html}</div>
            {$hidden['storage_end']}</div>";
}

private static $waiting_for_pair_start = '1';
private static $waiting_for_pair_end = '0';

 private static function openPair(){
    if(self::$waiting_for_pair_start==='1'){
    self::$waiting_for_pair_end = '1';
    self::$waiting_for_pair_start='0';
        return "<div class='dibraco-pair'>";
           }
           else return '';
        }
private static function closePair(){
    if(self::$waiting_for_pair_end ==='1'){
        self::$waiting_for_pair_start = '1';
        self::$waiting_for_pair_end ='0';
         return "</div>";
          }     
           else return '';
        }         
        
private static function generateLabel($field_name){
    return(ucwords(str_replace(['_', '-'], ' ', $field_name)));
}
  
public static function generateVisualSection($field_name, $field_config, $is_recursive = false) {
    $label = $field_config['label'] ?? null;
    $condition = $field_config['condition'] ?? [];
    $fields = $field_config['fields'] ?? [];
    $storage_array = $field_config['storage'] ?? '0';
    extract(self::prepareField($field_name, $condition, "dibraco-section"));
    $hidden = ['storage_field' => '', 'storage_end' => ''];
    if ($storage_array === '1') {
        $hidden = self::hiddenFields($field_name);
    }
     $subfields_html = '';
    foreach ($fields as $subfield_name => $subfield_config) {
        $type = $subfield_config['type'];
        if (($subfield_config['type'] !=='group') && ($subfield_config['type'] !=='field_group')){
        $subfield_config['label'] = $subfield_config['label'] ?? self::generateLabel($subfield_name);
        }
        if (isset($subfield_config['pair'])) {
            $subfields_html .= self::openPair();
        }
        $subfield_config['condition'] = $subfield_config['condition'] ?? [];

        switch ($type) {
            case 'visual_section':
                $subfields_html .= self::generateVisualSection($subfield_name, $subfield_config, true);
                break;

            case 'visual_split':
                $subfields_html .= self::generateVisualSplit($subfield_name, $subfield_config);
                break;
            case 'field_group':
                  $subfields_html .= self::generateVisualFieldGroup($subfield_name, $subfield_config);
                break;
            case 'visual_group':
                $subfields_html .= self::generateVisualFieldGroup($subfield_name, $subfield_config);
                break;
            case 'repeater':
                $subfields_html .= self::generateRepeaterField($subfield_name, $subfield_config);
                break;
            case 'group':
                $subfields_html .= self::generateGroup($subfield_name, $subfield_config);
                break;
            default:
                $subfields_html .= self::generateField($subfield_name, $subfield_config);
                if (isset($subfield_config['pair_end'])) {
                    $subfields_html .= self::closePair();
                }
                break;
        }

    }
    if ($is_recursive) {
        return "{$start_id_div}
            {$hidden['storage_field']}
            <h4>{$label}</h4>
            {$subfields_html}
            {$hidden['storage_end']}</div>";
    }

    return "{$start_id_div}
        {$hidden['storage_field']}
        <div class='dibraco-section-fields {$field_name}'>
        {$subfields_html}
        {$hidden['storage_end']}</div></div>";
}

public static function generateVisualFieldGroup($field_name, $field_config) {
  $label = $field_config['label'] ?? null; 
    $condition = $field_config['condition'] ?? [];
    $fields = $field_config['fields'];
    $storage_array = $field_config['storage']??'';
    extract(self::prepareField($field_name, $condition, "dibraco-group"));
    $hidden = [ 'storage_field' => '', 'storage_end'   => ''];
    if($storage_array ==='1'){
        $hidden = self::hiddenFields($field_name);
    }
    $subfields_html = '';
    if ($label !==null) {
        $label =  self::generateLabel($field_name);
        $label = '<h4>' . $label . '</h4>';
        $label = "<div class ='dibraco-group-header'>{$label}</div>";
    }
     foreach ($fields as $subfield_name => $subfield_config) {
       $subfield_config['condition'] = $subfield_config['condition'] ?? [];
               if(isset($subfield_config['pair'])){ 
                 $subfields_html .= self::openPair();
               }
        switch ($subfield_config['type']) {
            case 'field_group':
                $subfields_html .= self::generateVisualFieldGroup($subfield_name, $subfield_config);
                break;  
            case 'visual_group':
                $subfields_html .= self::generateVisualFieldGroup($subfield_name, $subfield_config);
                break;    
            case 'repeater':
                $subfields_html .= self::generateRepeaterField($subfield_name, $subfield_config);
                break;
            case 'group':
                $subfields_html .= self::generateGroup($subfield_name, $subfield_config);
                break;
            default:  
                 $subfields_html .= self::generateField($subfield_name, $subfield_config);
                if(isset($subfield_config['pair_end'])){ 
                 $subfields_html .= self::closePair();
               }
                break;
        }
    }
    return "<div id='{$field_name}' {$class_string} {$condition_attributes} {$data_name}>
            {$hidden['storage_field']}
            {$subfields_html}
            {$hidden['storage_end']}</div>";
}
public static function generateRepeaterField($field_name, $field_config, $parent_name = null) {
    $label       = $field_config['label'] ?? self::generateLabel($subfield_name);
    $condition   = $field_config['condition'] ?? [];
    $row_config  = $field_config['fields']; 
    $hidden = self::hiddenFields($field_name);
    if (!array_key_exists('row_count', $field_config)) {
       $row_count = 1;
    }
    if (self::$tracking_started){
         if (isset(self::$meta_array["{$field_name}_row_count"])){
         $row_count = (int)self::$meta_array["{$field_name}_row_count"]; 
         } else {
        $row_count = 1;
    }
    }

    if ($parent_name !== null) {$parent_name = "data-parent-name='{$parent_name}'";}
    extract(self::prepareField($field_name, $condition, "dibraco-repeater-wrapper"));
    
    $row_html = '';
    for ($index = 0; $index < $row_count; $index++) {
        $data_attr = "data-row-index={$index}";
        $row_html .= "<div class='repeater-row' {$data_name} {$parent_name} {$data_attr}>";
     foreach ($row_config as $subfield_name => $subfield_config) {
                    $group_condition = $subfield_config['condition']??[];
                switch ($subfield_config['type']) {
                case 'repeater':
                    $subfield_name_with_index = "{$field_name}[{$index}][{$subfield_name}]";
                    $row_html .= self::generateRepeaterField($subfield_name_with_index, $subfield_config, $field_name);
                    break;
         
                 case 'group':
                      if($group_condition){
                          $group_condition['field'] = "{$field_name}[{$index}][{$group_condition['field']}]";
                      }
                         $conditionals = self::prepareCondition($group_condition);
                        $conditionals = $conditionals['condition_attributes'];
                        $row_html .= "<div id='{$field_name}[{$index}][{$subfield_name}]' class='dibraco-group-fields' {$conditionals}>";
                    foreach ($subfield_config['fields'] as $nested_subfield_name => $nested_subfield_config){
                        $group_field_name = "{$subfield_name}_{$nested_subfield_name}";
                        $subfield_name_with_index ="{$field_name}[{$index}][$group_field_name]";
                         $sublabel = ucwords(str_replace(['_', '-', '[', ']', '(', ')'], ' ', $group_field_name)); 
                         $sublabel = preg_replace('/\s+/', ' ', $sublabel);
                        $sublabel = "$sublabel";
                        $nested_subfield_config['label'] = $sublabel;
                       if (self::$tracking_started){
                           if(self::$meta_array[$subfield_name_with_index]) {
                               $nested_subfield_config['value']=self::$meta_array[$subfield_name_with_index];
                           } elseif (self::$meta_array[$group_field_name]){
                            $nested_subfield_config['value']= self::$meta_array[$group_field_name] ?? ''; 
                           }
                       }
                       $nested_subfield_config['skip_line']='skip_line';
                         $row_html .= self::generateField($subfield_name_with_index, $nested_subfield_config);
                       }
                       $row_html .="</div>";
                    break;
              default:
                $sublabel = ucwords(str_replace(['_', '-', '[', ']', '(', ')'], ' ', $subfield_name)); 
                $sublabel = preg_replace('/\s+/', ' ', $sublabel);
                $sublabel = "$sublabel";
                $subfield_config['label'] = $sublabel;
                $subfield_name_with_index = "{$field_name}[{$index}][{$subfield_name}]";
                   if (self::$tracking_started) {
                         $subfield_config['skip_line']='skip_line';
                        $subfield_config['value'] = self::$meta_array[$field_name][$index][$subfield_name] ?? '';
                    }
                         
                $row_html .= self::generateField($subfield_name_with_index, $subfield_config);
                break;
            }
        }
         if ($index === 0){
    $row_html .= "<div class='remove-row'><button type='button' class='remove-row-button hidden' {$data_name} {$parent_name} {$data_attr}>Remove</button></div></div>";
         }  else {
            $row_html .= "<div class='remove-row'><button type='button' class='remove-row-button' {$data_name} {$parent_name} {$data_attr}>Remove</button></div></div>";
          }
    }
$label = trim(preg_replace('/\s+/', ' ', ucwords(str_replace(['_', '-', '[', ']', '(', ')'], ' ', $label))));
    return "<div id='{$field_name}' {$class_string} {$condition_attributes} {$data_name} {$parent_name}>
     <h4>{$label}</h4>
       <input type='hidden' name='{$field_name}_row_count' class='repeater-input' value='{$row_count}' {$data_name} {$parent_name}>
            <div class='repeater-rows' {$data_name} {$parent_name}>{$row_html}</div>
           <button type='button' class='add-row-button' {$parent_name} {$data_name}>Add Row</button></div>";
}

public static function generateGroup($field_name, $field_config) {
    $label     = $field_config['label'] ?? null; 
    $condition = $field_config['condition'] ?? [];
    $fields    = $field_config['fields'];

    extract(self::prepareField($field_name, $condition, "dibraco-group-fields"));
    if ($label !==null) {
        $label = ucwords(str_replace(['_', '-'], ' ', $label));
        $label = "<h4>{$label}</h4>";
    }

    $subfields_html = '';
    foreach ($fields as $subfield_name => $subfield_config) {
        $type = $subfield_config['type'];
        $subfield_config['label'] = $subfield_config['label'] ?? ucwords(str_replace(['_', '-'], ' ', $subfield_name));
        $subfield_config['condition'] = $subfield_config['condition'] ?? [];
        $prefixed_subfield_name = "{$field_name}_{$subfield_name}";
      switch ($type) {
        case 'field_group':
        case 'visual_group':
                $subfields_html .= self::generateVisualFieldGroup($subfield_name, $subfield_config);
                break;    
        default:
            $subfields_html .= self::generateField($prefixed_subfield_name, $subfield_config);
        }
    }
    return "{$start_id_div}{$label}
            {$subfields_html}</div>";
}

} 
