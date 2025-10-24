<?php

function render_card_styles_selection_page() {
     $enabled = get_option('enabled_context_names');
    $selected_contexts = get_option('selected_contexts', []);
    ?>
    <div class="wrap">
    <h1>Card Styles Selection</h1>
    <form method="POST">
        <div style="margin: 20px 0;">
                <?php echo formHelper::generateCheckboxGroup( 'selected_contexts', 'Select Card Styles', $selected_contexts, $enabled, [] ); ?>
       </div>
        <p class="submit"><input type="submit" class="button-primary" value="Save Settings"></p>
    </form>
    </div>
    <?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selected_contexts = $_POST['selected_contexts'] ?? [];
        update_option('selected_contexts', $selected_contexts);
    }
}




function dibraco_get_card_settings($context_data, $context_name) {
    $select_options = dibraco_get_select_field_options($context_data, $context_name);
    $saved_colors = get_option('my_plugin_color_settings');

    return [
        'cards_section' => [
            'type' => 'visual_section', 
            'label' => "Cards Display Settings",
            'fields' => [
                'cards_layout' => [
                    'type' => 'visual_group', 
                    'label' => "Layout", 
                    'fields' => [
                        'cards_justify' => ['type' => 'select', 'value' => 'space_evenly', 'options' => $select_options['justify_content']],
                        'cards_alignment' => ['type' => 'select', 'value' => 'stretch', 'options' => $select_options['align_content']],
                        'display_style' => ['type' => 'select', 'value' => 'flex', 'options' => $select_options['display_style']],
                        'cards_randomize_order' => ['type' => 'toggle', 'value' => "0"]
                    ]
                ],
                'cards_row_settings' => [
                    'type' => 'visual_group', 
                    'label' => "Items Per Row", 
                    'fields' => [
                        'cards_large_breakpoint' => ['type' => 'text', 'value' => '1200px', 'label' => 'Lg. Break'],
                        'cards_medium_breakpoint' => ['type' => 'text', 'value' => '992px', 'label' => 'Med. Break'],
                        'cards_small_breakpoint' => ['type' => 'text', 'value' => '768px', 'label' => 'Sm. Break'],
                        'cards_large_card_row' => ['type' => 'number', 'value' => 3],
                        'cards_medium_card_row' => ['type' => 'number', 'value' => 2],
                        'cards_small_card_row' => ['type' => 'number', 'value' => 1],
                        'cards_extra_small_card_row' => ['type' => 'number', 'value' => 1]
                    ]
                ],
                'cards_toggle_settings' => [
                    'type' => 'visual_group', 
                    'label' => "Toggle Content & Order", 
                    'fields' => [
                        'cards_show_button' => ['type' => 'toggle', 'value' => "1", 'label' => 'Show Button'],
                        'cards_show_description' => ['type' => 'toggle', 'value' => "1", 'label' => 'Show Description'],
                        'cards_show_image' => ['type' => 'toggle', 'value' => "1", 'label' => 'Show Image'],
                        'cards_show_title' => ['type' => 'toggle', 'value' => "1", 'label' => 'Show Title'],
                        'cards_button_position' => ['type' => 'select', 'value' => 4, 'options' => $select_options['positions'], 'label' => 'Button Pos', 'condition' => ['field' => 'cards_show_button', 'values' => ["1"]]],
                        'cards_description_position' => ['type' => 'select', 'value' => 3, 'options' => $select_options['positions'], 'label' => 'Desc. Pos', 'condition' => ['field' => 'cards_show_description', 'values' => ["1"]]],
                        'cards_image_position' => ['type' => 'select', 'value' => 1, 'options' => $select_options['positions'], 'label' => 'Image Pos', 'condition' => ['field' => 'cards_show_image', 'values' => ["1"]]],
                        'cards_title_position' => ['type' => 'select', 'value' => 2, 'options' => $select_options['positions'], 'label' => 'Title Pos', 'condition' => ['field' => 'cards_show_title', 'values' => ["1"]]],
                        'cards_image_field' => ['type' => 'select', 'value' => 'featured_image', 'options' => $select_options['image_fields'], 'label' => 'Image Field', 'condition' => ['field' => 'cards_show_image', 'values' => ["1"]]]
                    ]
                ]
            ]
        ],
        'card_section' => [
            'type' => 'visual_section', 
            'label' => "Single Card Appearance",
            'fields' => [
                'card_alignment' => [
                    'type' => 'visual_group', 
                    'label' => "Alignment & Background", 
                    'fields' => [
                        'card_show_box_shadow' => ['type' => 'toggle', 'value' => "0"],
                        'card_background_color' => ['type' => 'colorpicker', 'value' => '#FFFFFFFF'],
                        'card_width' => ['type' => 'text', 'value' => 'auto'],
                        'card_align_items' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['align_content']],
                        'card_justify_content' => ['type' => 'select', 'value' => 'space_between', 'options' => $select_options['justify_content']]
                    ]
                ],
                'card_box_shadow' => [
                    'type' => 'visual_group', 
                    'label' => "Box Shadow Properties",
                    'condition' => ['field' => 'card_show_box_shadow', 'values' => ["1"]],
                    'fields' => [
                        'card_box_shadow_horizontal_offset' => ['type' => 'text', 'value' => '3px'],
                        'card_box_shadow_vertical_offset' => ['type' => 'text', 'value' => '3px'],
                        'card_box_shadow_spread_radius' => ['type' => 'text', 'value' => '3px'],
                        'card_box_shadow_blur_radius' => ['type' => 'text', 'value' => '3px'],
                        'card_box_shadow_color' => ['type' => 'colorpicker', 'value' => '#000000FF']
                    ]
                ],
                'card_spacing' => [
                    'type' => 'visual_group', 
                    'label' => 'Padding & Margin', 
                    'fields' => [
                        'card_margin_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-top'],
                        'card_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right'],
                        'card_margin_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-bottom'],
                        'card_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left'],
                        'card_padding_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-top'],
                        'card_padding_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-right'],
                        'card_padding_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-bottom'],
                        'card_padding_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-left']
                    ]
                ],
                'card_border' => [
                    'type' => 'visual_group', 
                    'label' => "Border", 
                    'fields' => [
                        'card_border_color' => ['type' => 'colorpicker', 'value' => '#000000FF', 'css_property' => 'border-color'],
                        'card_border_width' => ['type' => 'text', 'value' => '1px', 'css_property' => 'border-width'],
                        'card_border_style' => ['type' => 'select', 'value' => 'solid', 'options' => $select_options['border_styles'], 'css_property' => 'border-style'],
                        'card_border_radius' => ['type' => 'text', 'value' => '5px', 'css_property' => 'border-radius'],
                        'card_border_top' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_top'],
                        'card_border_bottom' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_bottom'],
                        'card_border_left' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_left'],
                         'card_border_right' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_right'],
                    ]
                ]
            ]
        ],
        'image_section' => [
            'type' => 'visual_section', 'label' => "Image Settings", 
            'condition' => ['field' => 'cards_show_image', 'values' => ["1"]],
            'fields' => [
                'image_content' => [
                    'type' => 'visual_group', 'label' => "Sizing & Display", 
                    'fields' => [
                        'image_alignment' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['align_content']],
                        'image_object_fit' => ['type' => 'select', 'value' => 'cover', 'options' => $select_options['image_fit_options']],
                        'image_aspect_ratio' => ['type' => 'select', 'value' => 'auto', 'options' => $select_options['image_aspect_ratios']],
                        'image_show_border' => ['type' => 'toggle', 'value' => "0"],
                        'image_show_drop_shadow' => ['type' => 'toggle', 'value' => "0"]
                    ]
                ],
                'image_margins' => [
                    'type' => 'visual_group', 'label' => "Margins", 
                    'fields' => [
                        'image_margin_top' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-top'],
                        'image_margin_right' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-right'],
                        'image_margin_bottom' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-bottom'],
                        'image_margin_left' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-left']
                    ]
                ],
                'image_border' => [
                    'type' => 'visual_group', 'label' => "Border", 'condition' => ['field' => 'image_show_border', 'values' => ["1"]], 
                    'fields' => [
                        'image_border_color' => ['type' => 'colorpicker', 'value' => '#000000FF', 'css_property' => 'border-color'],
                        'image_border_width' => ['type' => 'text', 'value' => '1px', 'css_property' => 'border-width'],
                        'image_border_style' => ['type' => 'select', 'value' => 'solid', 'options' => $select_options['border_styles'], 'css_property' => 'border-style'],
                        'image_border_radius' => ['type' => 'text', 'value' => '5px', 'css_property' => 'border-radius'],
                        'image_border_top' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_top'],
                        'image_border_bottom' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_bottom'],
                        'image_border_left' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_left'],
                        'image_border_right' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_right'],
                    ]
                ],
                'image_drop_shadow' => [
                    'type' => 'visual_group', 'label' => "Drop Shadow Properties", 
                    'condition' => ['field' => 'image_show_drop_shadow', 'values' => ["1"]],
                    'fields' => [
                        'image_drop_shadow_horizontal_offset' => ['type' => 'text', 'value' => '3px'],
                        'image_drop_shadow_vertical_offset' => ['type' => 'text', 'value' => '3px'],
                        'image_drop_shadow_blur_radius' => ['type' => 'text', 'value' => '3px'],
                        'image_drop_shadow_color' => ['type' => 'colorpicker', 'value' => '#000000FF']
                    ]
                ]
            ]
        ],
        'title_section' => [
            'type' => 'visual_section', 'label' => "Title Settings", 
            'condition' => ['field' => 'cards_show_title', 'values' => ["1"]],
            'fields' => [
                'title_text' => [
                    'type' => 'visual_group', 'label' => "Text Style", 
                    'fields' => [
                        'title_font_weight' => ['type' => 'select', 'value' => '600', 'options' => $select_options['text_weight'], 'css_property' => 'font-weight'],
                        'title_text_transform' => ['type' => 'select', 'value' => 'capitalize', 'options' => $select_options['text_transform'], 'css_property' => 'text-transform'],
                        'title_text_align' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['text_align'], 'css_property' => 'text-align'],
                        'title_color' => ['type' => 'colorpicker', 'value' => $saved_colors['primary_color'], 'css_property' => 'color'],
                        'title_font_size' => ['type' => 'text', 'value' => '20px', 'css_property' => 'font-size'],
                        'title_heading_type' => ['type' => 'select', 'value' => 'h3', 'options' => $select_options['heading']]
                    ]
                ],
                'title_margins' => [
                    'type' => 'visual_group', 'label' => "Margins", 
                    'fields' => [
                        'title_margin_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-top'],
                        'title_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right'],
                        'title_margin_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-bottom'],
                        'title_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left']
                    ]
                ]
            ]
        ],
        'description_section' => [
            'type' => 'visual_section', 'label' => "Description Settings", 
            'condition' => ['field' => 'cards_show_description', 'values' => ["1"]],
            'fields' => [
                'description_text' => [
                    'type' => 'visual_group', 'label' => "Text Style", 
                    'fields' => [
                        'description_font_weight' => ['type' => 'select', 'value' => '400', 'options' => $select_options['text_weight'], 'css_property' => 'font-weight'],
                        'description_text_transform' => ['type' => 'select', 'value' => 'none', 'options' => $select_options['text_transform'], 'css_property' => 'text-transform'],
                        'description_text_align' => ['type' => 'select', 'value' => 'left', 'options' => $select_options['text_align'], 'css_property' => 'text-align'],
                        'description_color' => ['type' => 'colorpicker', 'value' => '#000000FF', 'css_property' => 'color'],
                        'description_font_size' => ['type' => 'text', 'value' => '16px', 'css_property' => 'font-size']
                    ]
                ],
                'description_margins' => [
                    'type' => 'visual_group', 'label' => "Margins", 
                    'fields' => [
                        'description_margin_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-top'],
                        'description_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right'],
                        'description_margin_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-bottom'],
                        'description_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left']
                    ]
                ]
            ]
        ],
        'button_section' => [
            'type' => 'visual_section', 'label' => "Button Settings", 
            'condition' => ['field' => 'cards_show_button', 'values' => ["1"]],
            'fields' => [
                'button_colors' => [
                    'type' => 'visual_group', 'label' => "Colors", 
                    'fields' => [
                        'button_text_color' => ['type' => 'colorpicker', 'value' => '#000000FF'],
                        'button_text_hover_color' => ['type' => 'colorpicker', 'value' => '#000000FF'],
                        'button_background_color' => ['type' => 'colorpicker', 'value' => $saved_colors['primary_color']],
                        'button_background_hover_color' => ['type' => 'colorpicker', 'value' => '#0032f9FF']
                    ]
                ],
                'button_text' => [
                    'type' => 'visual_group', 'label' => "Text Style", 
                    'fields' => [
                        'button_text_font_weight' => ['type' => 'select', 'value' => '400', 'options' => $select_options['text_weight'], 'css_property' => 'font-weight'],
                        'button_text_transform' => ['type' => 'select', 'value' => 'uppercase', 'options' => $select_options['text_transform'], 'css_property' => 'text-transform'],
                        'button_text_align' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['text_align']],
                        'button_font_size' => ['type' => 'text', 'value' => '16px', 'css_property' => 'font-size'],
                        'use_height_width' => ['type' => 'toggle', 'value' => "0"]
                    ]
                ],
                'button_dimensions' => [
                    'type' => 'visual_group', 'label' => "Fixed Dimensions", 
                    'condition' => ['field' => 'use_height_width', 'values' => ["1"]], 
                    'fields' => [
                        'button_width' => ['type' => 'text', 'value' => 'auto'],
                        'button_height' => ['type' => 'text', 'value' => '45px'],
                        'button_align_horizontal' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['text_align']]
                    ]
                ],
                'button_paddings' => [
                    'type' => 'visual_group', 'label' => "Padding", 
                    'fields' => [
                        'button_padding_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-top', 'condition' => ['field' => 'use_height_width', 'values' => ["0"]]],
                        'button_padding_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-right'],
                        'button_padding_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-bottom', 'condition' => ['field' => 'use_height_width', 'values' => ["0"]]],
                        'button_padding_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-left']
                    ]
                ],
                'button_margins_top_bottom' => [
                    'type' => 'visual_group', 'label' => "Top/Bottom Margins",
                    'fields' => [
                        'button_margin_top' => ['type' => 'text', 'value' => '5px', 'css_property' => 'margin-top'],
                        'button_margin_bottom' => ['type' => 'text', 'value' => '5px', 'css_property' => 'margin-bottom']
                    ]
                ],
                'button_margins_left_right' => [
                    'type' => 'visual_group', 'label' => "Left/Right Margins",
                    'condition' => ['field' => 'use_height_width', 'values' => ["0"]],
                    'fields' => [
                        'button_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right'],
                        'button_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left']
                    ]
                ],
                'button_border' => [
                    'type' => 'visual_group', 'label' => "Border", 
                    'fields' => [
                        'button_border_color' => ['type' => 'colorpicker', 'value' => '#000000FF', 'css_property' => 'border-color'],
                        'button_border_color_hover' => ['type' => 'colorpicker', 'value' => '#0032f9FF'],
                        'button_border_width' => ['type' => 'text', 'value' => '1px', 'css_property' => 'border-width'],
                        'button_border_style' => ['type' => 'select', 'value' => 'solid', 'options' => $select_options['border_styles'], 'css_property' => 'border-style'],
                        'button_border_radius' => ['type' => 'text', 'value' => '5px', 'css_property' => 'border-radius'],
                        'button_border_top' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_top'],
                        'button_border_bottom' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_bottom'],
                        'button_border_left' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_left'],
                        'button_border_right' =>  ['type' => 'text', 'value' => '10px', 'css_property' => 'border_right'],
                    ]
                ]
            ]
        ]
    ];
}
function dibraco_get_select_field_options($context_data, $context_name) {

$options = [
        'text_transform' => ['none' => 'None', 'capitalize' => 'Capitalize', 'uppercase' => 'Uppercase', 'lowercase' => 'Lowercase'],
        'text_align' => ['left' => 'Left', 'center' => 'Center', 'right' => 'Right'],
        'text_weight' => ['normal' => 'Normal', 'bold' => 'Bold', 'bolder' => 'Bolder', '300' => '300', '400' => '400', '500' => '500', '600' => '600', '700' => '700', '800' => '800', '900' => '900'],
        'border_styles' => ['none' => 'None', 'solid' => 'Solid', 'dashed' => 'Dashed', 'dotted' => 'Dotted'],
        'justify_content' => ['flex-start' => 'Flex Start', 'center' => 'Center', 'flex-end' => 'Flex End', 'space-between' => 'Space Between', 'space-around' => 'Space Around', 'space-evenly' => 'Space Evenly'],
        'heading' => ['h1' => 'H1', 'h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'div' => 'Div', 'p' => 'Paragraph'],
        'align_content' => ['flex-start' => 'Flex Start', 'center' => 'Center', 'flex-end' => 'Flex End', 'space-between' => 'Space Between', 'space-around' => 'Space Around', 'stretch' => 'Stretch'],
        'image_aspect_ratios' => ['auto' => 'Auto', '16/9' => '16:9', '4/3' => '4:3', '1/1' => '1:1', '3/2' => '3:2', '3/1' => '3:1'], 
        'image_fit_options' => ['cover' => 'Cover', 'contain' => 'Contain'],
        'display_style' => ['flex' => 'Flex', 'grid' => 'Grid'],
        'positions' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'],
        'directions' => ['top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right'],
        'image_fields' => ['post_thumbnail' => 'Featured Image']
    ];

    if ($context_data['landscape_images'] === "1") {
        $options['image_fields']['dibraco_landscape_1'] = 'Landscape Image 1';
        $options['image_fields']['dibraco_landscape_2'] = 'Landscape Image 2';
    }
    if ($context_data['portrait_images'] === "1") {
        $options['image_fields']['dibraco_portrait_1'] = 'Portrait Image 1';
        $options['image_fields']['dibraco_portrait_2'] = 'Portrait Image 2';
    }
    if (($context_data['context_type'] ==='type' && $context_data['post_per_term']==="1") && ($context_data['term_icon'] === "1")){
        $options['image_fields']['term_icon'] = "Term Icon";
    }
    if ($context_name === 'locations') {
        $options['image_fields']['exterior_image'] = 'Exterior Image';
    }

return $options;
}

function dibraco_render_terms_checkbox_group($taxonomy, $terms, $selected_terms) {
    $selected_terms = array_map('intval', $selected_terms); 
    ?>
    <div class="terms-checkbox-group dibraco-section">
        <h4>Select Terms</h4>
        <?php foreach ($terms as $term) :
            $term_id = $term->term_id;
            $checked = in_array($term_id, $selected_terms, true) ? 'checked' : ''; 
            if (empty($selected_terms)) {
                $checked = 'checked';
            }
        ?>
            <label style="margin-right: 10px; display: inline-block;">
                <input type="checkbox" name="selected_terms[]" value="<?= $term_id; ?>" <?= $checked; ?>>
                <?= $term->name; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}

function prepare_variant_fields_for_render($saved_settings, $context_data, $context_name) {
    $blueprint = dibraco_get_card_settings($context_data, $context_name);
    
    foreach ($blueprint as $section_key => $section) {
        foreach ($section['fields'] as $group_key => $group) {
            foreach ($group['fields'] as $field_key => $field_config) {
                if (isset($saved_settings[$field_key])) {
                    $field_config['value'] = $saved_settings[$field_key];
                }
                $group['fields'][$field_key] = $field_config;
            }
            $section['fields'][$group_key] = $group;
        }
        $blueprint[$section_key] = $section;
    }
    
    return $blueprint;
}
function dibraco_add_new_variant_option($context_name, $context_type, $new_variant_index) {
    $all_variants = get_option("{$context_name}_card_styles", []);
    $template = $all_variants[0] ?? [];
    if ($context_type === 'type' || $context_type === 'connector') {
        $template['selected_terms'] = [];
    }
    $all_variants[$new_variant_index] = $template;
    update_option("{$context_name}_card_styles", $all_variants);
    return true;
}

function dibraco_save_card_styles_settings($context_name, $context_type, $post_data) {
    $active_index = (int)($post_data['dibraco_active_variant_tab_index'] ?? 0);
    
    $all_variants = get_option("{$context_name}_card_styles", []);
        $variant_data = [];
        $context_data = get_option('enabled_contexts')[$context_name] ?? [];
    $sections = dibraco_get_card_settings($context_data, $context_name);
    
    foreach ($sections as $section) {
        foreach ($section['fields'] as $group) {
            foreach ($group['fields'] as $field_key => $field_config) {
                if (isset($post_data[$field_key])) {
                    $variant_data[$field_key] = $post_data[$field_key];
                }
            }
        }
    }
    if ($context_type === 'type' || $context_type === 'connector') {
        $variant_data['selected_terms'] = isset($post_data['selected_terms']) 
            ? array_map('intval', $post_data['selected_terms']) 
            : [];
    }
    $all_variants[$active_index] = $variant_data;
    update_option("{$context_name}_card_styles", $all_variants);
    dibraco_generate_dynamic_card_css_for_variant($active_index, $variant_data, $context_name);
    return true;
}
function redirect_after_card_styles_action($query_args = []) {
    wp_safe_redirect(remove_query_arg('card_styles_nonce', add_query_arg($query_args, $_SERVER['REQUEST_URI'])));
    exit;
}
function dibraco_awesome_render_card_settings_page($saved_settings, $context_data, $context_name) {
    $hyphenated_context_name = str_replace('_', '-', $context_name);
    $current_active_tab_index = 0;
    
    if (!empty($saved_settings)) {
        $keys = array_keys($saved_settings);
        $current_active_tab_index = $keys[0];
    }
    
    if (isset($_GET['active_tab'])) {
        $current_active_tab_index = (int)$_GET['active_tab'];
    }
    
    if (isset($_POST['dibraco_active_variant_tab_index'])) {
        $current_active_tab_index = (int)$_POST['dibraco_active_variant_tab_index'];
    }
    
    $max_variants = 10;
    $context_type = $context_data['context_type'];
    
    $terms = [];
    if ($context_type === 'type' || $context_type === 'connector') {
        $taxonomy = $context_data['taxonomy'];
        $term_results = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
        if (!empty($term_results)) {
            $terms = $term_results;
        }
    }
    $nonce_action = 'dibraco_card_styles_actions_' . $context_name;
    
   if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_styles_nonce'])) {
    if(wp_verify_nonce($_POST['card_styles_nonce'], $nonce_action)) {
        $action_value = $_POST['submit_action'];
        
        if($action_value === 'save_card_styles') {
            dibraco_save_card_styles_settings($context_name, $context_type, $_POST);
            redirect_after_card_styles_action(['settings-updated' => '1', 'active_tab' => $current_active_tab_index]);
            
        } elseif($action_value === 'add_variant') {
            $all_variants = get_option("{$context_name}_card_styles", []);
            $next_index = 0;
            while(isset($all_variants[$next_index]) && $next_index < 10) {
                $next_index++;
            }
            if($next_index < 10) {
                dibraco_add_new_variant_option($context_name, $context_type, $next_index);
                redirect_after_card_styles_action(['settings-updated' => 'variant_added', 'active_tab' => $next_index]);
            }
            
        } elseif(is_numeric($action_value)) {
            $remove_index = (int)$action_value;
            $all_variants = get_option("{$context_name}_card_styles", []);
            unset($all_variants[$remove_index]);
            update_option("{$context_name}_card_styles", $all_variants);
            redirect_after_card_styles_action(['settings-updated' => 'variant_removed', 'active_tab' => 0]);
        }
    }
}
    $actual_variant_count = count($saved_settings);
    ksort($saved_settings);
    $settings = $saved_settings[$current_active_tab_index];
    ?>
<div id="dibraco-card-settings-<?= $hyphenated_context_name ?>" class="card-styles-settings">
    <ul class="variant-tabs">
        <?php foreach (array_keys($saved_settings) as $i): ?>
        <li class="variant-tab<?= ($i == $current_active_tab_index) ? ' active' : '' ?>" data-tab-index="<?= $i ?>">
            Variant <?= $i ?>
        </li>
        <?php endforeach; ?>
    </ul>
    <form id="card-styles-form-<?= $hyphenated_context_name ?>" method="post" action="">
        <?php wp_nonce_field($nonce_action, 'card_styles_nonce'); ?>
        <input type="hidden" id="dibraco_active_variant_tab_index" name="dibraco_active_variant_tab_index" value="<?= $current_active_tab_index ?>">
        
        <?php
        if ($context_type === 'type' || $context_type === 'connector') {
            $current_selected_terms = $settings['selected_terms'] ?? [];
            echo dibraco_render_terms_checkbox_group($taxonomy, $terms, $current_selected_terms);
        }
        
        $sections = prepare_variant_fields_for_render($settings, $context_data, $context_name);
        echo FormHelper::generateVisualSection("{$context_name}_card_styles", ['fields'=> $sections]);
        ?>
    
        <div class="my-buttons">
            <button type="submit" name="submit_action" value="save_card_styles" class="button button-primary">Save</button>
            <?php 
            if ($current_active_tab_index > 0) { 
                echo "<button type='submit' name='submit_action' value='{$current_active_tab_index}' class='button button-link-delete'>Remove</button>";
            }
            if ($actual_variant_count < 10) { 
                echo "<button type='submit' name='submit_action' value='add_variant' class='button button-secondary'>Add Variant</button>";
            } 
            ?>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.variant-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const index = tab.dataset.tabIndex;
            const params = new URLSearchParams(window.location.search);
            params.set('active_tab', index);
            window.location.search = params.toString();
        });
    });
    
    // Position select management for the active variant
    function getCorrespondingSelect(radioElement) {
        const radioName = radioElement.name;
        const type = radioName.split('_')[2];
        return document.querySelector(`select[name="cards_${type}_position"]`);
    }
    
    function getAllPositionSelects() {
        return document.querySelectorAll(`select[name$="_position"]`);
    }
    
    function setSelectValueAndUpdateNote(selectElement, newValue) {
        selectElement.value = newValue;
        selectElement.dataset.originalPosition = newValue;
    }
    
    function updateSelectOptions() {
        const activeRadios = document.querySelectorAll(`input[type="radio"][name^="cards_show_"][value="1"]:checked`);
        const currentActiveCount = activeRadios.length;
        const selects = getAllPositionSelects();
        
        selects.forEach(selectElement => {
            for (let i = 1; i <= 4; i++) {
                const option = selectElement.querySelector(`option[value="${i}"]`);
                if (option) option.hidden = (i > currentActiveCount);
            }
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            if (selectedOption && selectedOption.value !== "" && selectedOption.hidden) {
                selectElement.value = "";
            }
        });
    }
    
    function handleManualSelectChange(changedElement, originalPosition, newPosition) {
        if (originalPosition === newPosition) return;
        if (originalPosition === '' || newPosition === '') {
            setSelectValueAndUpdateNote(changedElement, newPosition);
            return;
        }
        
        const allSelects = getAllPositionSelects();
        let otherSelect = null;
        for (const sel of allSelects) {
            if (sel !== changedElement && sel.value === newPosition) {
                otherSelect = sel;
                break;
            }
        }
        
        setSelectValueAndUpdateNote(changedElement, newPosition);
        if (otherSelect) setSelectValueAndUpdateNote(otherSelect, originalPosition);
    }
    
    function handleToggleOn(radioElement) {
        const correspondingSelect = getCorrespondingSelect(radioElement);
        const activeRadios = document.querySelectorAll(`input[type="radio"][name^="cards_show_"][value="1"]:checked`);
        const activeCount = activeRadios.length;
        setSelectValueAndUpdateNote(correspondingSelect, activeCount.toString());
        updateSelectOptions();
    }
    
    function handleToggleOff(radioElement) {
        const correspondingSelect = getCorrespondingSelect(radioElement);
        const originalPosition = correspondingSelect.dataset.originalPosition || correspondingSelect.value;
        const oldNumeric = parseInt(originalPosition, 10);
        
        setSelectValueAndUpdateNote(correspondingSelect, '');
        
        if (!isNaN(oldNumeric) && oldNumeric > 0) {
            const allSelects = getAllPositionSelects();
            allSelects.forEach(sel => {
                if (sel !== correspondingSelect) {
                    const current = parseInt(sel.dataset.originalPosition || sel.value, 10);
                    if (!isNaN(current) && current > oldNumeric) {
                        setSelectValueAndUpdateNote(sel, (current - 1).toString());
                    }
                }
            });
        }
        
        updateSelectOptions();
    }
    
    // Initialize
    const allInitialSelects = getAllPositionSelects();
    allInitialSelects.forEach(sel => sel.dataset.originalPosition = sel.value);
    updateSelectOptions();
    
    // Event listeners
    allInitialSelects.forEach(select => {
        select.addEventListener('click', function() {
            this.dataset.originalPosition = this.value;
        });
        select.addEventListener('change', function() {
            handleManualSelectChange(this, this.dataset.originalPosition, this.value);
        });
    });
    
    document.querySelectorAll(`input[type="radio"][name^="cards_show_"]`).forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.checked) {
                this.value === '1' ? handleToggleOn(this) : handleToggleOff(this);
            }
        });
    });
});
</script>
<?php
}
function formatCSSBlock($selector, $styles) {
    $rules = '';
    foreach ($styles as $property => $value) {
        if (is_scalar($value) && $value !== '') {
            $property = strtolower(preg_replace('/_/', '-', $property));
            $rules .= "\t" . trim($property) . ': ' . trim((string)$value) . ";\n";
        }
    }
    if (empty($rules)) {
        return '';
    }
    
    return $selector . " {\n" . $rules . "}\n";
}

function processBorderStyles($border_fields) {
    $width = $border_fields['border_width'];
    $style = $border_fields['border_style'];
    $color = $border_fields['border_color'];
    if (empty($width) || $width === '0' || $width === '0px' || empty($style) || $style === 'none' || empty($color)) {
        return 'none';
    }
    return $width . ' ' . $style . ' ' . $color;
}

function dibraco_get_css_selector($base_selector, $context_name, $variant_index) {
    $css_context_name = str_replace('_', '-', $context_name);
    $base_selector_cleaned = ltrim($base_selector, '.');
    
    if ($base_selector_cleaned === 'cards-section') {
        return '.' . $css_context_name . '-' . $base_selector_cleaned . '-' . $variant_index;
    } 
    else {
        return '.' . $css_context_name . '-' . $base_selector_cleaned;
    }
}

function generateCardsContainerCSS($data, $context_name, $variant_index) {
    $css = '';
    $container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_item_unique_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $full_descendant_card_selector = $container_selector . ' ' . $card_item_unique_selector;

    // Extract values directly from flat array
    $display_style = $data['display_style'];
    $alignment = $data['cards_alignment'];
    $justify = $data['cards_justify'];
    $user_defined_width = $data['card_width'];
    $card_margin_left = $data['card_margin_left'];
    $card_margin_right = $data['card_margin_right'];

    $large_breakpoint  = (int) preg_replace('/\D/', '', $data['cards_large_breakpoint']);
    $medium_breakpoint = (int) preg_replace('/\D/', '', $data['cards_medium_breakpoint']);
    $small_breakpoint  = (int) preg_replace('/\D/', '', $data['cards_small_breakpoint']);
    
    $cards_per_row_settings = [
        'large'       => max(1, (int)$data['cards_large_card_row']),
        'medium'      => max(1, (int)$data['cards_medium_card_row']),
        'small'       => max(1, (int)$data['cards_small_card_row']),
        'extra_small' => max(1, (int)$data['cards_extra_small_card_row']),
    ];

    $regular_styles = [
        'box-sizing'      => 'border-box',
        'display'         => $display_style,
        'justify-content' => $justify,
    ];

    if ($display_style === 'grid') {
        $regular_styles['align-items'] = $alignment; 
    } else {
        $regular_styles['align-content'] = $alignment; 
        $regular_styles['flex-wrap'] = 'wrap';
    }
    
    $css .= formatCSSBlock($container_selector, $regular_styles);

    $responsive_tiers = [
        'large' => [
            'min_width' => $large_breakpoint,
            'max_width' => null, 
            'cards_per_row' => $cards_per_row_settings['large'],
        ],
        'medium' => [
            'min_width' => $medium_breakpoint,
            'max_width' => $large_breakpoint -1,
            'cards_per_row' => $cards_per_row_settings['medium'],
        ],
        'small' => [
            'min_width' => $small_breakpoint,
            'max_width' => $medium_breakpoint -1,
            'cards_per_row' => $cards_per_row_settings['small'],
        ],
        'extra_small' => [
            'min_width' => null, 
            'max_width' => $small_breakpoint -1,
            'cards_per_row' => $cards_per_row_settings['extra_small'],
        ],
    ];

    foreach ($responsive_tiers as $tier_name => $tier_data) {
        $min_width = $tier_data['min_width'];
        $max_width = $tier_data['max_width'];
        $current_cards_per_row = $tier_data['cards_per_row'];

        if (($min_width !== null && $min_width > 0) || ($max_width !== null && $max_width >= 0)) {
            $media_query_parts = [];
            if ($min_width !== null && $min_width > 0) {
                $media_query_parts[] = "(min-width: {$min_width}px)";
            }
            if ($max_width !== null && $max_width >= 0) {
                $media_query_parts[] = "(max-width: {$max_width}px)";
            }
            $media_query = "@media " . implode(" and ", $media_query_parts);
            
            if (!empty($media_query_parts)) {
                $css .= "{$media_query} {\n";
                
                if ($display_style === 'flex') {
                    $sizing_styles = [];
                    if ($user_defined_width !== 'auto' && $user_defined_width !== '') {
                        $sizing_styles['width'] = $user_defined_width;
                    } else {
                        $width_formula = "calc((100% / {$current_cards_per_row}) - ({$card_margin_left} + {$card_margin_right}))";
                        $sizing_styles['width'] = $width_formula;
                    }
                    $css .= formatCSSBlock("  " . $full_descendant_card_selector, $sizing_styles);

                } else { 
                    $grid_sizing_styles = [];
                    if ($user_defined_width !== 'auto' && $user_defined_width !== '') {
                        $grid_sizing_styles['grid-template-columns'] = "repeat(auto-fill, minmax({$user_defined_width}, 1fr))";
                    } else {
                        $grid_sizing_styles['grid-template-columns'] = "repeat({$current_cards_per_row}, 1fr)";
                    }
                    $css .= formatCSSBlock("  " . $container_selector, $grid_sizing_styles);
                }
                $css .= "}\n";
            }
        }
    }
    
    return $css;
}

function generateCardCSS($data, $toggles, $context_name, $variant_index) {
    $css = '';
    $card_base_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index); 
    $unique_container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_full_selector = $unique_container_selector . ' ' . $card_base_selector;

    // Extract border sides array
    $card_border_sides = $data['card_border_style_boxes'] ?? []; 

    $formatted_border_fields = [
        'border_width' => $data['card_border_width'],
        'border_style' => $data['card_border_style'],
        'border_color' => $data['card_border_color']
    ];

    $regular_styles = [
        'box-sizing'       => 'border-box',
        'display'          => 'flex',
        'flex-direction'   => 'column',
        'background-color' => $data['card_background_color'],
        'width'            => $data['card_width'],
        'align-items'      => $data['card_align_items'],
        'justify-content'  => $data['card_justify_content'],
        'border-radius'    => $data['card_border_radius'],
        'padding-top'      => $data['card_padding_top'],
        'padding-right'    => $data['card_padding_right'],
        'padding-bottom'   => $data['card_padding_bottom'],
        'padding-left'     => $data['card_padding_left'],
        'margin-top'       => $data['card_margin_top'],
        'margin-right'     => $data['card_margin_right'],
        'margin-bottom'    => $data['card_margin_bottom'],
        'margin-left'      => $data['card_margin_left'],
    ];

    // Add borders if specified
    if (!empty($card_border_sides)) {
        $border_string = processBorderStyles($formatted_border_fields);
        foreach ($card_border_sides as $direction) {
            $regular_styles["border-{$direction}"] = $border_string;
        }
    }

    // Add box shadow if enabled
    if (($toggles['card_show_box_shadow']) === "1") {
        $box_shadow = sprintf(
            '%s %s %s %s %s',
            $data['card_box_shadow_horizontal_offset'],
            $data['card_box_shadow_vertical_offset'],
            $data['card_box_shadow_blur_radius'],
            $data['card_box_shadow_spread_radius'],
            $data['card_box_shadow_color']
        );
        $regular_styles['box-shadow'] = $box_shadow;
    }

    $css .= formatCSSBlock($card_full_selector, $regular_styles);
    
    return $css;
}

function generateImageCSS($data, $toggles, $context_name, $variant_index) {
    $css = '';
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $unique_container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_selector_with_container = $unique_container_selector . ' ' . $card_selector;
    
    $img_selector = "{$card_selector_with_container} img.card-image";

    $img_styles = [
        'aspect-ratio'  => $data['image_aspect_ratio'],

        'object-fit'   => $data['image_object_fit'],
        'border-radius' => $data['image_border_radius'],
        'margin-top'    => $data['image_margin_top'],
        'margin-bottom' => $data['image_margin_bottom'],
        'margin-left'   => $data['image_margin_left'],
        'margin-right'  => $data['image_margin_right'],
    ];

    // Add border if enabled
    if (($toggles['image_show_border']) === "1") {
        $image_border_sides = $data['image_border_style_boxes'] ?? [];
        
        if (!empty($image_border_sides)) {
            $formatted_border_fields = [
                'border_width' => $data['image_border_width'],
                'border_style' => $data['image_border_style'],
                'border_color' => $data['image_border_color']
            ];
            $border_string = processBorderStyles($formatted_border_fields);
            foreach ($image_border_sides as $direction) {
                $img_styles["border-{$direction}"] = $border_string;
            }
        }
    }

    // Add drop shadow if enabled
    if (($toggles['image_show_drop_shadow']) === "1") {
        $drop_shadow = sprintf(
            '%s %s %s %s',
            $data['image_drop_shadow_horizontal_offset'],
            $data['image_drop_shadow_vertical_offset'],
            $data['image_drop_shadow_blur_radius'],
            $data['image_drop_shadow_color']
        );
        $img_styles['filter'] = "drop-shadow({$drop_shadow})";
    }

    $css .= formatCSSBlock($img_selector, $img_styles);
    return $css;
}

function generateTitleCSS($data, $context_name, $variant_index) {
    $css = '';
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $unique_container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_selector_with_container = $unique_container_selector . ' ' . $card_selector;
    
    $h3_selector = "{$card_selector_with_container} h3.card-title";

    $h3_styles = [
        'color'          => $data['title_color'],
        'font-size'      => $data['title_font_size'],
        'font-weight'    => $data['title_font_weight'],
        'text-transform' => $data['title_text_transform'],
        'text-align'     => $data['title_text_align'],
        'margin-top'     => $data['title_margin_top'],
        'margin-bottom'  => $data['title_margin_bottom'],
        'margin-left'    => $data['title_margin_left'],
        'margin-right'   => $data['title_margin_right'],
    ];

    $css .= formatCSSBlock($h3_selector, $h3_styles);
    return $css;
}

function generateDescriptionCSS($data, $context_name, $variant_index) {
    $css = '';
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $unique_container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_selector_with_container = $unique_container_selector . ' ' . $card_selector;
    
    $p_selector = "{$card_selector_with_container} p.card-description";

    $p_styles = [
        'color'          => $data['description_color'],
        'font-size'      => $data['description_font_size'],
        'font-weight'    => $data['description_font_weight'],
        'text-transform' => $data['description_text_transform'],
        'text-align'     => $data['description_text_align'],
        'margin-top'     => $data['description_margin_top'],
        'margin-bottom'  => $data['description_margin_bottom'],
        'margin-left'    => $data['description_margin_left'],
        'margin-right'   => $data['description_margin_right'],
    ];

    $css .= formatCSSBlock($p_selector, $p_styles);
    return $css;
}

function generateButtonCSS($data, $toggles, $context_name, $variant_index) {
    $css = '';
    $border_fields_directions = $data['button_border_style_boxes'] ?? [];
    $use_fixed_dimensions = $data['use_height_width'];
    
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $unique_container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_selector_with_container = $unique_container_selector . ' ' . $card_selector; 
    
    $button_selector = "{$card_selector_with_container} a.card-button";
    $button_hover_selector = "{$button_selector}:hover";

    $button_base_styles = [
        'color'            => $data['button_text_color'],
        'display'          => 'flex',
        'background-color' => $data['button_background_color'],
        'border-radius'    => $data['button_border_radius'],
        'font-weight'      => $data['button_text_font_weight'],
        'font-size'        => $data['button_font_size'],
        'text-transform'   => $data['button_text_transform'],
        'text-align'       => $data['button_text_align'],
        'align-items'      => 'center',    
        'justify-content'  => $data['button_text_align'],    
        'align-content'    => 'center',
        'margin-top'       => $data['button_margin_top'],
        'margin-bottom'    => $data['button_margin_bottom'],
    ];

    // Add borders if specified
    $formatted_border_fields = [
        'border_width' => $data['button_border_width'],
        'border_style' => $data['button_border_style'],
        'border_color' => $data['button_border_color']
    ];
    
    if (!empty($border_fields_directions)){
       $border_string = processBorderStyles($formatted_border_fields);
        foreach ($border_fields_directions as $direction){
           $button_base_styles["border-{$direction}"] = $border_string;
        }
    }

    // Handle fixed vs dynamic dimensions
    if ($use_fixed_dimensions === "1") { 
        $button_base_styles['width']  = $data['button_width'];
        $button_base_styles['height'] = $data['button_height'];
        $alignment_choice = $data['button_align_horizontal'];
        $flex_map = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'];
        $button_base_styles['align-self'] = $flex_map[$alignment_choice];
        $button_base_styles['padding-left']   = $data['button_padding_left'];
        $button_base_styles['padding-right']  = $data['button_padding_right'];
    } else { 
        $button_base_styles['padding-top']    = $data['button_padding_top'];
        $button_base_styles['padding-bottom'] = $data['button_padding_bottom'];
        $button_base_styles['padding-left']   = $data['button_padding_left'];
        $button_base_styles['padding-right']  = $data['button_padding_right'];
        
        $alignment_choice = $data['button_text_align'];
        $flex_map = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'];
        $button_base_styles['align-self'] = $flex_map[$alignment_choice];
        $button_base_styles['margin-left'] = $data['button_margin_left'];
        $button_base_styles['margin-right'] = $data['button_margin_right'];
    }

    $button_hover_styles = [
        'color'              => $data['button_text_hover_color'],
        'background-color'   => $data['button_background_hover_color'],
    ];

    // Add hover borders if specified
    if (!empty($border_fields_directions)) {
        $formatted_border_hover_fields = [
            'border_width' => $data['button_border_width'],
            'border_style' => $data['button_border_style'],
            'border_color' => $data['button_border_color_hover']
        ];
        $hover_border_string = processBorderStyles($formatted_border_hover_fields);
        foreach ($border_fields_directions as $direction) {
            $button_hover_styles["border-{$direction}"] = $hover_border_string;
        }
    }
    
    $css .= formatCSSBlock($button_selector, $button_base_styles);
    $css .= formatCSSBlock($button_hover_selector, $button_hover_styles);
    return $css;
}

function dibraco_generate_dynamic_card_css_for_variant($index, $data_to_save, $context_name) {
    $css = '';
    
    $toggles = [
        'image_show_drop_shadow' => $data_to_save['image_show_drop_shadow'] ?? "0",
        'image_show_border'      => $data_to_save['image_show_border'] ?? "0",
        'card_show_box_shadow'   => $data_to_save['card_show_box_shadow'] ?? "0",
        'cards_show_button'      => $data_to_save['cards_show_button'] ?? "0",
        'cards_show_description' => $data_to_save['cards_show_description'] ?? "0",
        'cards_show_image'       => $data_to_save['cards_show_image'] ?? "0",
        'cards_show_title'       => $data_to_save['cards_show_title'] ?? "0",
        'use_height_width'       => $data_to_save['use_height_width'] ?? "0"
    ];

    $css .= generateCardsContainerCSS($data_to_save, $context_name, $index);
    $css .= generateCardCSS($data_to_save, $toggles, $context_name, $index);

    if (($toggles['cards_show_image']) === "1") { 
        $css .= generateImageCSS($data_to_save, $toggles, $context_name, $index); 
    }
    if (($toggles['cards_show_title']) === "1") { 
        $css .= generateTitleCSS($data_to_save, $context_name, $index); 
    }
    if (($toggles['cards_show_description']) === "1") { 
        $css .= generateDescriptionCSS($data_to_save, $context_name, $index);
    }
    if (($toggles['cards_show_button']) === "1") { 
        $css .= generateButtonCSS($data_to_save, $toggles, $context_name, $index); 
    }

    writeCSSToFile($css, $context_name, $index);
}

function writeCSSToFile($css, $context_name, $index) {
    if (!function_exists('wp_upload_dir') || !function_exists('wp_mkdir_p')) {
        return false;
    }

    $upload_dir = wp_upload_dir();
    if (!empty($upload_dir['error'])) {
        return false;
    }
    $target_dir = trailingslashit($upload_dir['basedir']) . 'awesome-services/css';

    if (!file_exists($target_dir)) {
        if (!wp_mkdir_p($target_dir)) {
            return false;
        }
    } elseif (!is_dir($target_dir) || !is_writable($target_dir)) {
        error_log("Error: CSS directory is not writable or not a directory: " . $target_dir);
        return false;
    }
    
    $context_name = str_replace('_', '-', $context_name);
    $css_file = $target_dir . "/card-styles-{$context_name}-{$index}.css";
    file_put_contents($css_file, $css);
}