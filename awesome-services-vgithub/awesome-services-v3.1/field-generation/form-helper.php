<?php
class FormHelper {
private static function prepareField($field_id, $condition, $base_class) {
    $condition = is_array($condition) ? $condition : [];
    $data_attr = str_replace('_', '-', $field_id); 
    $data_name = "data-name='{$data_attr}'";
    $whole_class = "class='{$base_class} {$data_attr}'";  
    $condition_attributes = ''; 

    if (empty($condition)) {
        return [
            'class_string' => $whole_class,
            'data_name' => $data_name,
            'condition_attributes' => '',
        ];
    }

    $controlling_field = $condition['field'] ?? '';
    $controlling_values = implode('|', (array) ($condition['values'] ?? []));

    $condition_attributes = " data-controlling-field='{$controlling_field}'";
    $condition_attributes .= " data-controlling-values='{$controlling_values}'";

    return [
        'class_string' => $whole_class,
        'data_name' => $data_name,
        'condition_attributes' => $condition_attributes,
    ];
}
    private static $tracking_started = false;
    private static $nested_tracking_started = false;
    private static $tracked_field_names = [];
    private static $meta_array = [];
    private static $nested_array=[];
    private static $nested_tracking_fields=[];
public static function generateButtonField($field_id, $label, $class, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-button"));
$button_text = ucwords(str_replace(['_', '-'], ' ',$label)); 
    return "<div {$class_string} {$condition_attributes}>
                <button id='{$field_id}' name='{$field_id}' type='button' class='{$class}' {$data_name}>
                    {$button_text}
                </button>
            </div>";
}



public static function generateWysiwyg($field_id, $label, $value, $condition = [], $field_config = []) {
        extract(self::prepareField($field_id, $condition, "dibraco-wysiwyg"));

        ob_start(); 
        wp_editor(
            $value,
            $field_id,
            [
                'textarea_name' => $field_id,
                'textarea_rows' => $field_config['rows'] ?? 8,
                'wpautop'       => $field_config['wpautop'] ?? false,
                'media_buttons' => $field_config['media_buttons'] ?? false,
                'teeny'         => $field_config['teeny'] ?? false,
            ]
        );
        $editor_html = ob_get_clean(); 
        return "<div {$class_string} {$condition_attributes}>
                    <label for='{$field_id}'>{$label}:</label>
                    {$editor_html}
                </div>";
    }

public static function generateToggleSwitch($field_id, $label, $value, $options_label = [], $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-toggle"));
    $label = ucwords(str_replace(['_', '-'], ' ', $label));
    if (empty($options_label)) {
        $options_label = ["0" => 'ON', "1" => 'OFF'];
    }
    if ($value !== "1" && $value !== "0") {
        $value = "1"; 
    }
    return "
        <div id='{$field_id}' {$class_string} {$condition_attributes}>
          <legend class='toggle-legend'>{$label}</legend>
            <div class='the-toggle'>
                <input type='radio' name='{$field_id}' value='1' " . checked($value, "1", false) . " class='toggle-input toggle-yes' id='{$field_id}_yes'>
                <input type='radio' name='{$field_id}' value='0' " . checked($value, "0", false) . " class='toggle-input toggle-no' id='{$field_id}_no'>
                <label class='toggle-label toggle-yes' for='{$field_id}_yes'>{$options_label["1"]}</label>
                <label class='toggle-label toggle-no' for='{$field_id}_no'>{$options_label["0"]}</label>
            </div>
        </div>";
}



public static function generateCheckBox($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-checkbox"));
    $value = ($value === "1") ? "1" : "0";
    $checked_attr = ($value === "1") ? 'checked' : '';
    return "<div {$class_string} {$condition_attributes}>
            <label for='{$field_id}'>{$label}</label>
                <input type='hidden' name='{$field_id}' value='0'>
                <input type='checkbox' id='{$field_id}' {$data_name} name='{$field_id}' value='1' {$checked_attr}>
        </div>";
}


public static function generateSelect($field_id, $label, $value, $options, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-select"));

    $options_html = '';
    $options_html .= '<option value="">Please select</option>';
    foreach ($options as $option_value => $option_label) {
        $option_label = ucwords(str_replace(['_', '-', '[', ']', '(', ')'], ' ', $option_label)); 
        $selected = ($value == $option_value) ? 'selected' : '';
        $options_html .= "<option value='{$option_value}' {$selected}>{$option_label}</option>";
    }

    return "
        <div {$class_string} {$condition_attributes}>
            <label for='{$field_id}'>{$label}</label>
            <select name='{$field_id}' id='{$field_id}' {$data_name}>
                {$options_html}
            </select>
        </div>";
}
public static function generateRadioFieldsetWithIntegerValues($field_id, $label, $value, $options_array = [], $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-radio-fieldset"));
    $options_html = "";
    $none_value = ''; 
    $none_id = "{$field_id}_none";
    $none_checked = ($value === '') ? 'checked' : '';
    $options_html .= "
        <label for='{$none_id}'>
            <input type='radio' id='{$none_id}' name='{$field_id}' value='{$none_value}' {$none_checked}>None</label>";

    foreach ($options_array as $option_value => $option_label) {
        $option_id = "{$field_id}_{$option_value}";
        $checked = intval($value) === intval($option_value) ? 'checked' : '';
        $data_name = "data-name='{$field_id}'";
        $options_html .= "
            <label for='{$option_id}'>
                <input type='radio' id='{$option_id}' name='{$field_id}' {$data_name} value='{$option_value}' {$checked}>$option_label</label>";
    }
          $fieldset = 'fieldset';
    if ($field_id === 'area_parent_location_term'){
        $fieldset = 'div';
    }
    return "<div {$class_string} {$condition_attributes}>
             <p>{$label}</p>
             <$fieldset id='{$field_id}'>
                {$options_html}
            </$fieldset>
        </div>";
}

public static function generateRadioFieldset($field_id, $label, $value, $options_array = [], $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-radio"));
    $options_html = "";
    foreach ($options_array as $option_value => $option_label) {
        $option_id = "{$field_id}_{$option_value}";
        $checked = ($value === (string)$option_value) ? 'checked' : '';
        $data_name = "data-name='{$field_id}'";
        $options_html .= "
            <label for='{$option_id}'>
                <input type='radio' id='{$option_id}' name='{$field_id}' {$data_name} value='{$option_value}' {$checked}>{$option_label}</label>";
    }

    return "<div {$class_string} {$condition_attributes}>
            <fieldset id='{$field_id}'>
                <p>{$label}</p>  
                {$options_html}
            </fieldset>
        </div>";
}



public static function generateField($field_name, $field_config) {
        $type = $field_config['type'];
        if ($type === 'starttracking' || $type ==="valueinjector") {
            self::$tracking_started = true;
            self::$meta_array = is_array($field_config['meta_array'] ?? null)
                   ? $field_config['meta_array']
                : [];
            self::$tracked_field_names = []; 
            if ($type === 'starttracking'){
             return "<input type='hidden' name='tracking_started' value='1'>";
            }
        }
        if (self::$tracking_started && $type !=="button") {
                
            if ($type === 'startnestedtracking'){
                self::$nested_tracking_started = true;
               self::$nested_array = is_array($field_config['nested_array'] ?? null)
                    ? $field_config['nested_array']
                    : [];
                self::$nested_tracking_fields = [];
                
                return "<input type='hidden' name='nested_tracking_started' value='1'>";
            }
               if (self::$nested_tracking_started){
            
                if ($type !== 'endnestedtracking' && $type !=='visual_section' && $type !=='field_group'){
                   self::$nested_tracking_fields[] = $field_name;
                

                 foreach (self::$nested_array as $stored_field_name => $stored_value) {
                        if ($field_name === $stored_field_name) {
                             if (is_array($stored_value)) {
                                $field_config['value'] = empty($stored_value) ? [] : $stored_value;
                            } else {
                               $field_config['value'] = $stored_value ?? '';
                            }
                        }
                     }
                  }
               }
                if ($type === 'endnestedtracking') {
                     self::$nested_tracking_started = false;
                     self::$nested_tracking_fields = [];
                     self::$nested_array = [];
                    return "<input type='hidden' name='nested_tracking_finished' value='1'>";  
               }
                
            if ($field_config['type'] !== "endtracking" && $field_config['type'] !== "injectionend") {
                self::$tracked_field_names[] = $field_name;
                
            
            foreach (self::$meta_array as $stored_field_name => $stored_value) {
                if ($field_name === $stored_field_name) {
                    if (is_array($stored_value)) {
                        $field_config['value'] = empty($stored_value) ? [] : $stored_value; 
                    } else {
                          $field_config['value'] = $stored_value ?? '';
                    }
                }
            }
            }
        }
        if ($type === 'endtracking' || $type ==="injectionend") {
            self::$tracking_started = false;
             self::$tracked_field_names = [];
            self::$meta_array = [];
            if ($type === 'endtracking'){
             return "<input type='hidden' name='tracking_finished' value='1'>";
            }
        }
        if ($type ==="injectionend" || $type ==="valueinjector"){
            return;
        }
        if (empty($field_name) || empty($type) || empty($field_config)) {
            return "Error: Missing required parameters ('field_name', 'type', or 'field_config').";
        }
       
        if ($type !== 'button' && !array_key_exists('value', $field_config)) {
            $field_config['value'] = '';  
        }
        $rows_textarea = $field_config['rows'] ?? '';
        $value = $field_config['value'] ?? '';
        $legend = $field_config['legend'] ?? '';
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
        $step = $field_config['step'] ?? '1';
        
        if ($type === 'radio' && empty($options)) {
            return "Error: The {$type} field '{$field_name}' requires options but none were provided.";
        }

        switch ($type) {
            case 'text':
                return self::generateTextInput($field_name, $label, $value, $condition);
            case 'textarea':
                return self::generateTextarea($field_name, $label, $value, $rows_textarea, $condition);
            case 'date':
                return self::generateDateInput($field_name, $label, $value, $condition);
            case 'time':
                return self::generateTimeInput($field_name, $label, $value, $condition);
            case 'colorpicker':
                return self::generateColorPicker($field_name, $label, $value, $condition);
            case 'image':
                return self::generateImageField($field_name, $label, $value, $condition);
            case 'button':
                return self::generateButtonField($field_name, $label, $class, $condition);
            case 'number':
                return self::generateNumberInput($field_name, $label, $value, $step, $condition);
            case 'toggle':
                return self::generateToggleSwitch($field_name, $label, $value, $options_label, $condition);
            case 'select':
                return self::generateSelect($field_name, $label, $value, $options, $condition);
            case 'radio':
                return self::generateRadioFieldset($field_name, $label, $value, $options, $condition);
            case 'checkbox':
                return self::generateCheckbox($field_name, $label, $value, $condition);
            case 'wysiwyg':
                return self::generateWysiwyg($field_name, $label, $value, $condition, $field_config);
        }

        return "Error: Unsupported field type '{$type}'.";
    }


public static function generateTextInput($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-text"));
    return "<div {$class_string} {$condition_attributes}>
            <label for='{$field_id}'>{$label}</label>
            <input type='text' id='{$field_id}' {$data_name} name='{$field_id}' value='{$value}'>
        </div>";
}


public static function generateTextarea($field_id, $label, $value, $rows_textarea ='', $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-textarea"));
$rows = $rows_textarea ?? '';
	if ($rows ===''){
		$rows = 6;
	}		
    return "<div {$class_string} {$condition_attributes}>
            <label for='{$field_id}'>{$label}:</label>
            <textarea id='{$field_id}' name='{$field_id}' rows='{$rows}' {$data_name}>{$value}</textarea>
        </div>";
}

public static function generateNumberInput($field_id, $label, $value, $step = 1, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-number"));

    return "<div {$class_string} {$condition_attributes}>
            <label for='{$field_id}'>{$label}:</label>
            <input type='number' id='{$field_id}' name='{$field_id}' value='{$value}' step='{$step}' {$data_name}>
        </div>";
}
public static function generateDateInput($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-date"));

    return "<div {$class_string} {$condition_attributes}>
            <label for='{$field_id}'>{$label}:</label>
            <input type='date' id='{$field_id}' {$data_name} name='{$field_id}' value='{$value}'>
        </div>";
}

public static function generateTimeInput($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-time"));
    return "<div {$class_string} {$condition_attributes}>
            <label for='{$field_id}'>{$label}:</label>
            <input type='time' id='{$field_id}' name='{$field_id}' value='{$value}' {$data_name}>
        </div>";
}


public static function generateColorPicker($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-color-picker"));
     $color = '#FFFFFF';
     $alpha = 1.0;
    if (((strlen($value) === 7) || (strlen($value) === 9)) && (substr($value, 0, 1) === '#')){
        if (strlen($value) === 9) {
            $color = substr($value, 0, 7); 
            $alpha = round(hexdec(substr($value, 7, 2)) / 255.0, 2);
        } else {
            $color = $value; 
        }
    }
    return "
    <div {$class_string} {$condition_attributes} {$data_name}>
        <label for='{$field_id}'>{$label}
            <input type='text' class='dibraco-hex8-input' id='{$field_id}' name='{$field_id}' value='{$value}' {$data_name}>
        </label>
 <button type='button' class='wp-color-result dibraco-fake-color-btn' {$data_name} style='display: flex; justify-content: flex-end; background-color:{$color}; padding:0; border: 1px solid black; border-radius: 3px; height: 28px; width: 100px;'>
                <span class='fake-picker-label' style='width: 75%; background-color: white; color: black; border-left:1px solid black; font-size:11px; display: inline-flex; align-items: center; justify-content: center;'>PICK COLOR</span>
            </button>
        <input type='hidden' class='wp-color-input' id='{$field_id}_hidden' value='{$value}' data-default-color='{$color}' {$data_name}>
        <div class='dibraco-slider iris-slider iris-strip' id='{$field_id}_slider' data-alpha-value='{$alpha}' {$data_name}></div>
    </div>";
}



public static function generateImageField($field_id, $label, $value, $condition = []) {
    extract(self::prepareField($field_id, $condition, "dibraco-image-field"));
    $title = $label;
    $image_url = $value ? wp_get_attachment_url($value) : '';

    return "
        <div {$class_string} {$condition_attributes}>
            <p>{$title}</p>
            <div class='image-preview-container' id='{$field_id}_preview_container'>
                <img class='image-preview' src='{$image_url}' id='{$field_id}_preview'>
            </div>
            <div class='buttons'>
                <button type='button' class='button media-button image-upload-button' data-target='{$field_id}_preview' data-input='{$field_id}_input'>Choose Image</button>
                <button type='button' class='button dibraco_clear_image_button' data-target='{$field_id}_preview' data-input='{$field_id}_clear'>Clear Image</button>
            </div>
            <input type='hidden' class='dibraco_image_id_input' id='{$field_id}_input' name='{$field_id}' value='{$value}' {$data_name}>
        </div>";
}




public static function generateVisualSection($field_name, $field_config, $is_recursive = false) {
    $label     = $field_config['label'] ?? ucwords(str_replace(['_', '-'], ' ', $field_name));
    $condition = $field_config['condition'] ?? [];
    $fields    = $field_config['fields'] ?? [];

    extract(self::prepareField($field_name, $condition, "dibraco-section"));

    $subfields_html = '';

    foreach ($fields as $subfield_name => $subfield_config) {
        if (($subfield_config['type'] !=='group') && ($subfield_config['type'] !=='field_group')){
        $subfield_config['label'] = $subfield_config['label'] ?? ucwords(str_replace(['_', '-'], ' ', $subfield_name));
        }
        
        $subfield_config['condition'] = $subfield_config['condition'] ?? [];

        switch ($subfield_config['type']) {
            case 'visual_section':
                $subfields_html .= self::generateVisualSection($subfield_name, $subfield_config, true);
                break;
            case 'field_group':
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
                break;
        }
    }

    if (!$is_recursive) {
        return "
        <div id='{$field_name}' {$class_string} {$condition_attributes} {$data_name}>
            <h3 style='width:100%'>{$label}</h3>
            <div class='section-fields'>
                {$subfields_html}
            </div>
        </div>";
    }

    return "
        <div id='{$field_name}' {$class_string} {$condition_attributes} {$data_name}>
            {$subfields_html}
        </div>";
}


public static function generateRepeaterField($field_name, $field_config, $parent_name = null) {
    $label       = $field_config['label'] ?? ucwords(str_replace(['_', '-'], ' ', $field_name));
    $condition   = $field_config['condition'] ?? [];
    $row_config  = $field_config['fields']; 
    if (!array_key_exists('row_count', $field_config)) {
       $row_count = 1;
    }
    if (!array_key_exists("{$field_name}_row_count", $field_config)) {
        $field_config["{$field_name}_row_count"] = 1;
    }
  
    if (self::$tracking_started && isset(self::$meta_array["{$field_name}_row_count"])) {
                if (self::$nested_tracking_started  && isset(self::$nested_array["{$field_name}_row_count"]))  {
                   $field_config["{$field_name}_row_count"] = (int)self::$nested_array["{$field_name}_row_count"]; 
                }
                 $field_config["{$field_name}_row_count"] = (int)self::$meta_array["{$field_name}_row_count"]; 
            }
    
    $row_count = $field_config["{$field_name}_row_count"];
    if ($row_count ===''){
        $row_count =1; 
    }
    if ($parent_name !== null) {$parent_name = "data-parent-name='{$parent_name}'";}
    extract(self::prepareField($field_name, $condition, "repeater-wrapper")); // provides $class_string, $data_name, $condition_attributes
    $row_html = '';
    for ($index = 0; $index < $row_count; $index++) {
        $data_attr = "data-row-index={$index}";
        $row_html .= "<div class='repeater-row' {$data_name} {$parent_name} {$data_attr}>";
     foreach ($row_config as $subfield_name => $subfield_config) {
          
                switch ($subfield_config['type']) {
                case 'repeater':
                    $subfield_name_with_index = "{$field_name}[{$index}][{$subfield_name}]";
                    $row_html .= self::generateRepeaterField($subfield_name_with_index, $subfield_config, $field_name);
                    break;
                case 'visual_section':
                    $subfield_name_with_index = "{$field_name}[{$index}]";
                    $row_html .= self::generateVisualSection($subfield_name_with_index, $subfield_config);
                    break;
                 case 'visual_group':
                 case 'field_group':
                     $subfield_name_with_index = "{$field_name}[{$index}]";
                    $subfields_html .= self::generateVisualFieldGroup($subfield_name, $subfield_config);
                break;    
                case 'group':
                    $subfield_name_with_index = "{$field_name}[{$index}][$subfield_name";
                    $row_html .= self::generateGroup($subfield_name_with_index, $subfield_config);
                    break;
                default:
                    $sublabel = ucwords(str_replace(['_', '-'], ' ', $subfield_name));;
                    $sublabel = "$sublabel $index";
                    $subfield_config['label'] = $sublabel;
                    $subfield_name_with_index = "{$field_name}[{$index}][{$subfield_name}]";
                    $row_html .= self::generateField($subfield_name_with_index, $subfield_config);
                    break;
            }
        }
         if ($index === 0){
        $row_html .= "<div class='remove-button'><button type='button' class='remove-row-button hidden' {$data_name} {$parent_name} {$data_attr}>Remove</button></div></div>";
         }  else {
            $row_html .= "<div class='remove-button'><button type='button' class='remove-row-button' {$data_name} {$parent_name} {$data_attr}>Remove</button></div></div>";
          }
    }
$label = ucwords(str_replace(['_', '-', '[', ']', '(', ')'], ' ', $label)); 
$label = preg_replace('/\s+/', ' ', $label);
$label = trim($label); 
    return "
    <div id='{$field_name}' {$class_string} {$condition_attributes} {$data_name} {$parent_name}>
     <h4>{$label}</h4>
       <input type='hidden' name='{$field_name}_row_count' class='repeater-input' value='{$row_count}' {$data_name} {$parent_name}>
            <div class='repeater-rows' {$data_name} {$parent_name}>{$row_html}</div>
            <div class='add-button'><button type='button' class='add-row-button' {$parent_name} {$data_name}>Add Row</button></div>
    </div>";
}


public static function generateVisualFieldGroup($field_name, $field_config) {
    $label     = $field_config['label'] ?? null; // DO NOT auto-generate label
    $condition = $field_config['condition'] ?? [];
    $fields    = $field_config['fields'];
    extract(self::prepareField($field_name, $condition, "dibraco-group"));
$subfields_html = '';

    if ($label !==null) {
        $label = ucwords(str_replace(['_', '-'], ' ', $label));
        $label = '<h4>' . $label . '</h4>';
        $label = "<div class ='dibraco-group-header'>{$label}</div>";
    }


     foreach ($fields as $subfield_name => $subfield_config) {
       $subfield_config['condition'] = $subfield_config['condition'] ?? [];

        switch ($subfield_config['type']) {
            case 'visual_section':
                $subfields_html .= self::generateVisualSection($subfield_name, $subfield_config, true);
                break;
            case 'field_group':
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
                break;
        }
    }

    return "
        <div id='{$field_name}' {$class_string} {$condition_attributes} {$data_name}>
            {$label}
            <div class='group-fields'>{$subfields_html}</div>
        </div>";

}
public static function generateGroup($field_name, $field_config) {
    $label     = $field_config['label'] ?? null; // DO NOT auto-generate label
    $condition = $field_config['condition'] ?? [];
    $fields    = $field_config['fields'];
if (preg_match('/\[\d+\]\[/', $field_name) && substr($field_name, -1) !== ']') {
            $normal_field_name = "{$field_name}]"; 
            extract(self::prepareField($normal_field_name, $condition, "dibraco-group"));
        } else {
            extract(self::prepareField($field_name, $condition, "dibraco-group"));
        }
    if ($label !==null) {
        $label = ucwords(str_replace(['_', '-'], ' ', $label));
        $label = '<h4>' . $label . '</h4>';
    }

    $subfields_html = '';
    foreach ($fields as $subfield_name => $subfield_config) {
        $subfield_config['label']     = $subfield_config['label']     ?? ucwords(str_replace(['_', '-'], ' ', $subfield_name));
        $subfield_config['condition'] = $subfield_config['condition'] ?? [];
        $prefixed_subfield_name = "{$field_name}_{$subfield_name}";
        if (preg_match('/\[\d+\]\[/', $field_name) && substr($field_name, -1) !== ']') {
            $prefixed_subfield_name = "{$field_name}_{$subfield_name}]";  // Add the missing closing bracket zent from repeater
        }
        $subfields_html .= self::generateField($prefixed_subfield_name, $subfield_config);
    }

$field_name = $normal_field_name ?? $field_name;
    return "
        <div id='{$field_name}' {$class_string} {$condition_attributes} {$data_name}>
            {$label}
            <div class='group-fields'>{$subfields_html}</div>
        </div>";
}


}

