<?php

function render_card_styles_selection_page() {
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_styles_selection_nonce']) && wp_verify_nonce($_POST['card_styles_selection_nonce'], 'save_card_styles_selection')) {
    $newly_selected_contexts = $_POST['selected_contexts'] ?? [];
    $previously_selected_contexts = get_option('selected_contexts', []);
    $contexts_to_remove = array_diff($previously_selected_contexts, $newly_selected_contexts);
    if (!empty($contexts_to_remove)) {
        $max_variants = 10;
        foreach ($contexts_to_remove as $context_name_to_remove) {
            for ($variant_index = 0; $variant_index < $max_variants; $variant_index++) {
                $option_name = "{$context_name_to_remove}_card_styles_{$variant_index}";
                delete_option($option_name);
                $upload_dir = wp_upload_dir();
                $css_file_path = $upload_dir['basedir'] . "/awesome-services/css/card-{$context_name_to_remove}-{$variant_index}.css";
                if (file_exists($css_file_path)) {
                    unlink($css_file_path);
                }
            }
        }
    }

    update_option('selected_contexts', $newly_selected_contexts);
    wp_safe_redirect(menu_page_url('card-styles-selection', false));
    exit;
}
    $enabled = get_option('enabled_context_names');
    $selected = get_option('selected_contexts', []);
    ?>
    <div class="wrap">
    <h1>Card Styles Selection</h1>
    <form method="POST">
        <?php wp_nonce_field('save_card_styles_selection', 'card_styles_selection_nonce'); ?>
        <table class="widefat">
            <thead>
                <tr><th>Context Name</th><th>Enable Cards</th></tr>
            </thead>
            <tbody>
                <?php foreach ($enabled as $name): ?>
                    <tr>
                        <td><?php echo esc_html(ucwords(str_replace(['_', '-'], ' ', $name))); ?></td>
                        <td>
                            <input type="checkbox" name="selected_contexts[]"
                                   value="<?php echo esc_attr($name); ?>"
                                   <?php checked(in_array($name, $selected, true)); ?> />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="submit"><input type="submit" class="button-primary" value="Save Settings"></p>
    </form>
    </div>
    <?php
}

function register_card_styles_selection_page() {
    $enabled = get_option('enabled_context_names');
    if (empty($enabled)) return;
    add_submenu_page(
        'relationships',
        'Card Styles Selection',
        'Card Styles Selection',
        'manage_options',
        'card-styles-selection',
        'render_card_styles_selection_page'
    );
}
add_action('admin_menu', 'register_card_styles_selection_page', 105);
function load_card_styles_scripts($hook) {
    if (strpos($hook, 'card-styles-') !== false ) {  
    wp_enqueue_style( 'dibraco-card-styles-admin-css', AWESOME_SERVICES_URL . 'css/da-card-styles-admin.css', array(), null, 'all'); 
    }
}
add_action('admin_enqueue_scripts', 'load_card_styles_scripts');

function register_card_styles_settings_page() {
    $selected = get_option('selected_contexts');
    $enabled  = get_option('enabled_contexts');
    if (empty($selected) || empty($enabled)) return;
    foreach ($selected as $context_name) {
        $context_data = $enabled[$context_name]; 
        $label        = ucwords(str_replace(['_', '-'], ' ', $context_name));
        $menu_slug    = 'card-styles-' . str_replace('_', '-', $context_name);

        add_submenu_page(
            'relationships',
            "{$label} Card Styles",
            "{$label} Card Styles",
            'manage_options',
            $menu_slug,
            function () use ($context_data, $context_name) {
                dibraco_awesome_render_card_settings_page($context_data, $context_name);
            }
        );
    }
}
add_action('admin_menu', 'register_card_styles_settings_page', 110);




function dibraco_get_card_settings($context_data =[], $context_name ='') {
    $select_options = dibraco_get_select_field_options($context_data, $context_name);
    $saved_colors = get_option('my_plugin_color_settings');
        $cards_section = [
            'type' => 'visual_section',
            'label' => "Cards Display Settings", 
            'fields' => [
                'cards_layout' => [
                    'type' => 'field_group', 
                    'label' => "Layout",     
                    'fields' => [
                        'cards_justify' => ['type' => 'select', 'value' => 'space_evenly', 'options' => $select_options['justify_content'], 'css_property' => 'justify-content'],
                        'cards_alignment' => ['type' => 'select', 'value' => 'stretch', 'options' => $select_options['align_content'], 'css_property' => 'align-content'],
                        'display_style' => ['type' => 'select', 'value' => 'flex', 'options' => $select_options['display_style'], 'css_property' => 'display'],
                        'cards_randomize_order' => ['type' => 'toggle', 'value' => "0"]
                    ]
                ],
                'cards_row_settings' => [
                    'type' => 'field_group', 
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
                    'type' => 'field_group', // NO prefixing
                    'label' => "Toggle Content & Order",
                    'fields' => [
                        'cards_show_button' => ['type' => 'toggle', 'value' => "1",'label' => 'Show Button'],
                        'cards_show_description' => ['type' => 'toggle', 'value' => "1", 'label' => 'Show Description'],
                        'cards_show_image' => ['type' => 'toggle', 'value' => "1", 'label' => 'Show Image'],
                        'cards_show_title' => ['type' => 'toggle', 'value' => "1", 'label' => 'Show Title'],
                        'cards_button_position' => ['type' => 'select', 'value' => 4, 'options' => $select_options['positions'], 'label' => 'Button Pos', 'condition' => ['field' => 'cards_show_button', 'values' => ["1"]]],
                        'cards_description_position' => ['type' => 'select', 'value' => 3, 'options' => $select_options['positions'], 'label' => 'Description Pos', 'condition' => ['field' => 'cards_show_description', 'values' => ["1"]]],
                        'cards_image_position' => ['type' => 'select', 'value' => 1, 'options' => $select_options['positions'],'label' => 'Image Pos', 'condition' => ['field' => 'cards_show_image', 'values' => ["1"]]],
                        'cards_title_position' => ['type' => 'select', 'value' => 2, 'options' => $select_options['positions'],'label' => 'Title Pos', 'condition' => ['field' => 'cards_show_title', 'values' => ["1"]]],
                        'cards_image_field' => ['type' => 'select', 'value' => 'featured_image' , 'options' => $select_options['image_fields'],'label' => 'Image Field', 'condition' => ['field' => 'cards_show_image', 'values' => ["1"]]],
                    
                    ]
                ]
            ]
        ];
        $card_section = [
            'type' => 'visual_section',
            'label' => "Single Card Appearance",
            'fields' => [
                'card_alignment' => [
                    'type' => 'field_group', 
                    'label' => "Alignment & Background",
                    'fields' => [
                         'card_show_box_shadow' => ['type' => 'toggle', 'value' => "0"],
                         'card_background_color' => ['type' => 'colorpicker', 'value' => '#FFFFFFFF', 'css_property' => 'background-color'],
                         'card_width' => ['type' => 'text', 'value' => 'auto', 'css_property' => 'width'], 
                         'card_align_items' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['align_content'], 'css_property' => 'align-content'],
                         'card_justify_content' => ['type' => 'select', 'value' => 'space_between', 'options' => $select_options['justify_content'], 'css_property' => 'justify-content'],
                    ]
                ],
                'card_box_shadow' => [
                    'type' => 'field_group', 
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
                 'card_border' => [
                     'type' => 'field_group', 
                     'label' => "Border",
                     'fields' => [
                         'card_border_color' => ['type' => 'colorpicker', 'value' => '#000000FF', 'css_property' => 'border-color'],
                         'card_border_radius' => ['type' => 'text', 'value' => '5px', 'css_property' => 'border-radius'],
                         'card_border_width' => ['type' => 'text', 'value' => '1px', 'css_property' => 'border-width'],
                         'card_border_style' => ['type' => 'select', 'value' => 'solid', 'options' => $select_options['border_styles'], 'css_property' => 'border-style']
                      ]
                 ],
                 'card_spacing' => [
                     'type' => 'field_group', 
                     'label' => 'Padding & Margin',
                     'fields' => [
                        'card_margin_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-top'],
                        'card_margin_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-bottom'],
                        'card_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right'],
                        'card_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left'],
                        'card_padding_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-top'],
                        'card_padding_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-bottom'],
                        'card_padding_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-right'],
                        'card_padding_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-left']
                        ]
                 ]
            ]
        ]; // End card_section

        $image_section = [
        'type' => 'visual_section',
        'label' => "Image Settings",
        'condition' => ['field' => 'cards_show_image', 'values' => ["1"]],
        'fields' => [
            'image_content' => [
                'type' => 'field_group',
                'label' => "Sizing & Display",
                'fields' => [
                    'image_alignment' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['align_content'], 'css_property' => 'align-content'],
                    'image_object_fit' => ['type' => 'select', 'value' => 'cover', 'options' => $select_options['image_fit_options'], 'css_property' => 'object-fit'],
                    'image_aspect_ratio' => ['type' => 'select', 'value' => 'auto', 'options' => $select_options['image_aspect_ratios'], 'css_property' => 'aspect-ratio'],
                    'image_show_drop_shadow' => ['type' => 'toggle', 'value' => "0"],
                    'image_show_border' => ['type' => 'toggle', 'value' => "0"] 
                ]
            ],
            'image_margins' => [
                'type' => 'field_group',
                'label' => "Margins",
                'fields' => [
                    'image_margin_top' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-top'], 
                    'image_margin_bottom' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-bottom'],
                    'image_margin_right' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-right'],
                    'image_margin_left' => ['type' => 'text', 'value' => '0px', 'css_property' => 'margin-left'] 
                ]
            ],
            'image_drop_shadow' => [
                'type' => 'field_group', // YES prefixing
                'label' => "Drop Shadow Properties",
                'condition' => ['field' => 'image_show_drop_shadow', 'values' => ["1"]],
                'fields' => [
                    'image_drop_shadow_horizontal_offset' => ['type' => 'text', 'value' => '3px' ],
                    'image_drop_shadow_vertical_offset' => ['type' => 'text', 'value' => '3px' ],
                    'image_drop_shadow_blur_radius' => ['type' => 'text', 'value' => '3px' ],
                    'image_drop_shadow_color' => ['type' => 'colorpicker', 'value' => '#000000FF']
                ]
            ],
            'image_border' => [
                'type' => 'field_group', // YES prefixing
                'label' => "Border Properties",
                'condition' => ['field' => 'image_show_border', 'values' => ["1"]], // Condition remains "1" so fields show when border is enabled
                'fields' => [
                    'image_border_color' => ['type' => 'colorpicker', 'value' => '#FFFFFF00', 'css_property' => 'border-color'],
                    'image_border_width' => ['type' => 'text', 'value' => '0px', 'css_property' => 'border-width'], // Default to 0px as requested
                    'image_border_style' => ['type' => 'select', 'value' => 'solid', 'options' => $select_options['border_styles'], 'css_property' => 'border-style'],
                    'image_border_radius' => ['type' => 'text', 'value' => '0px', 'css_property' => 'border-radius'] // Default to 0px as requested for flush
                ]
            ]
        ]
    ];
         $title_section = [
            'type' => 'visual_section',
            'label' => "Title Settings",
            'condition' => ['field' => 'cards_show_title', 'values' => ["1"]],
            'fields' => [
                'title_text' => [
                    'type' => 'field_group', // NO prefixing
                    'label' => "Text Style",
                    'fields' => [
                        'title_text_font_weight' => ['type' => 'select', 'value' => '600', 'options' => $select_options['text_weight'], 'css_property' => 'font-weight'],
                        'title_text_transform' => ['type' => 'select', 'value' => 'capitalize', 'options' => $select_options['text_transform'], 'css_property' => 'text-transform'],
                        'title_text_align' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['text_align'], 'css_property' => 'text-align'],
                        'title_font_size' => ['type' => 'text', 'value' => '20px', 'css_property' => 'font-size'],
                        'title_heading_type' => ['type' => 'select', 'value' => 'h3', 'options' => $select_options['heading']]
                    ]
                ],
                'title_colors' => [
                    'type' => 'field_group', // NO prefixing
                    'label' => "Colors",
                    'fields' => [
                        'title_text_color' => ['type' => 'colorpicker', 'value' => $saved_colors['primary_color'], 'css_property' => 'color'],
                        'title_text_hover_color' => ['type' => 'colorpicker', 'value' => '#0032f9FF'] 
                    ]
                ],
                'title_margins' => [
                    'type' => 'field_group', // NO prefixing
                    'label' => "Margins",
                    'fields' => [
                        'title_margin_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-top'],
                        'title_margin_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-bottom'],
                        'title_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left'],
                        'title_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right']
                        ]
                ]
            ]
        ]; // End title_section
        $description_section = [
            'type' => 'visual_section',
            'label' => "Description Settings",
            'condition' => ['field' => 'cards_show_description', 'values' => ["1"]],
            'fields' => [
                'description_text' => [
                    'type' => 'field_group', 
                    'label' => "Text Style",
                    'fields' => [
                        'description_font_weight' => ['type' => 'select', 'value' => '400', 'options' => $select_options['text_weight'], 'css_property' => 'font-weight'],
                        'description_text_transform' => ['type' => 'select', 'value' => 'none', 'options' => $select_options['text_transform'], 'css_property' => 'text-transform'],
                        'description_text_align' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['text_align'], 'css_property' => 'text-align'],
                        'description_color' => ['type' => 'colorpicker', 'value' => '#000000FF', 'css_property' => 'color'],
                        'description_font_size' => ['type' => 'text', 'value' => '16px', 'css_property' => 'font-size']
                    ]
                ],
                'description_margins' => [
                    'type' => 'field_group', 
                    'label' => "Margins",
                    'fields' => [
                        'description_margin_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-top'],
                        'description_margin_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-bottom'],
                        'description_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left'],
                        'description_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right']
                        ]
                ]
            ]
        ]; 
          $button_section = [
    'type' => "visual_section",
    'label' => "Button Settings",
    'condition' => ['field' => 'cards_show_button', 'values' => ["1"]],
    'fields' => [
        'button_colors' => [
            'type' => 'field_group', 
            'label' => "Colors",
            'fields' => [
                'button_text_color' => ['type' => 'colorpicker', 'value' => '#000000FF', 'css_property' => 'color'],
                'button_text_hover_color' => ['type' => 'colorpicker', 'value' => '#000000FF'], 
                'button_background_color' => ['type' => 'colorpicker', 'value' => $saved_colors['primary_color'], 'css_property' => 'background-color'],
                'button_background_hover_color' => ['type' => 'colorpicker', 'value' => '#0032f9FF'] 
            ]
        ],
        'button_border' => [
            'type' => 'field_group', 
            'label' => "Border",
            'fields' => [
                'button_border_color' => ['type' => 'colorpicker', 'value' => '#00aef9FF', 'css_property' => 'border-color'],
                'button_border_color_hover' => ['type' => 'colorpicker', 'value' => '#0032f9FF'], // Hover handled by CSS
                'button_border_width' => ['type' => 'text', 'value' => '1px', 'css_property' => 'border-width'],
                'button_border_radius' => ['type' => 'text', 'value' => '5px', 'css_property' => 'border-radius'],
                'button_border_style' => ['type' => 'select', 'value' => 'solid', 'options' => $select_options['border_styles'], 'css_property' => 'border-style']
            ]
        ],
        'button_text' => [
            'type' => 'field_group', 
            'label' => "Text Style",
            'fields' => [
                'button_text_font_weight' => ['type' => 'select', 'value' => '400', 'options' => $select_options['text_weight'], 'css_property' => 'font-weight'],
                'button_font_size' => ['type' => 'text', 'value' => '16px', 'css_property' => 'font-size'],
                'button_text_transform' => ['type' => 'select', 'value' => 'uppercase', 'options' => $select_options['text_transform'], 'css_property' => 'text-transform'],
                'button_text_align' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['text_align']],
                'use_height_width' => ['type' => 'toggle', 'value' => "0"]
            ]
        ],
        'button_dimensions' => [
            'type' => 'field_group',
            'label' => "Fixed Dimensions",
            'condition' => ['field' => 'use_height_width', 'values' => ["1"]],
            'fields' => [
                'button_width' => ['type' => 'text', 'value' => 'auto', 'css_property' => 'width'],
                'button_height' => ['type' => 'text', 'value' => '45px', 'css_property' => 'height'],
                'button_align_horizontal' => ['type' => 'select', 'value' => 'center', 'options' => $select_options['text_align']]
            ]
        ],
        'button_paddings' => [
            'type' => 'field_group',
            'label' => "Padding (Auto Width/Height)",
            'condition' => ['field' => 'use_height_width', 'values' => ["0"]],
            'fields' => [
                'button_padding_top' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-top'],
                'button_padding_bottom' => ['type' => 'text', 'value' => '10px', 'css_property' => 'padding-bottom'],
                'button_padding_right' => ['type' => 'text', 'value' => '20px', 'css_property' => 'padding-right'],
                'button_padding_left' => ['type' => 'text', 'value' => '20px', 'css_property' => 'padding-left']
            ]
        ],
        'button_margins_top_bottom' => [
            'type' => 'field_group',
            'label' => "Top/Bottom Margins",
            'fields' => [
                'button_margin_top' => ['type' => 'text', 'value' => '5px', 'css_property' => 'margin-top'],
                'button_margin_bottom' => ['type' => 'text', 'value' => '5px', 'css_property' => 'margin-bottom']
            ]
        ],
        'button_margins_left_right' => [
            'type' => 'field_group',
            'label' => "Left/Right Margins (Auto Width/Height)",
            'condition' => ['field' => 'use_height_width', 'values' => ["0"]],
            'fields' => [
                'button_margin_right' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-right'],
                'button_margin_left' => ['type' => 'text', 'value' => '10px', 'css_property' => 'margin-left']
            ]
        ]
    ]
];
      return ['cards_section' => $cards_section, 'card_section' => $card_section, 'image_section' => $image_section, 'title_section' => $title_section, 'description_section' => $description_section, 'button_section' => $button_section]; // End return
}
function dibraco_get_select_field_options($context_data=[], $context_name ='') {


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
        'image_fields' => ['post_thumbnail' => 'Featured Image']
    ];

if (!empty($context_data)) {
    if ($context_data['repeater_images'] === "1") {
        $options['image_fields']['dibraco_landscape_1'] = 'Landscape Image 1';
        $options['image_fields']['dibraco_landscape_2'] = 'Landscape Image 2';
    }
    if ($context_data['portrait_images'] === "1") {
        $options['image_fields']['dibraco_portrait_1'] = 'Portrait Image 1';
        $options['image_fields']['dibraco_portrait_2'] = 'Portrait Image 2';
    }
    if ($context_data['term_icon'] === "1"){
        $options['image_fields']['term_icon'] = "Term Icon";
    }
    if ($context_name === 'locations') {
        $options['image_fields']['exterior_image'] = 'Exterior Image';
    }
}
return $options;
}

    

function dibraco_render_terms_checkbox_group($taxonomy, $terms, $selected_terms, $index) {
    $selected_terms = array_map('intval', $selected_terms); 
    ?>
    <div class="terms-checkbox-group dibraco-section" id="terms_section" data-variant-index="<?= $index; ?>">
        <h4>Apply to Terms for Variant <?= $index; ?></h4>
        <?php foreach ($terms as $term) :
            $term_id = $term->term_id;
            $checked = in_array($term_id, $selected_terms, true) ? 'checked' : ''; 
            if (empty($selected_terms)) {
                $checked = 'checked';
            }
        ?>
            <label style="margin-right: 10px; display: inline-block;">
                <input type="checkbox" name="variant_<?= $index ?>_terms[]" value="<?= $term_id; ?>" <?= $checked; ?>>
                <?= $term->name; ?>
            </label>
        <?php endforeach; ?>
    </div>
    <?php
}



 function modify_condition_field($item, $context_name, $variant_index) {
        if (isset($item['condition']['field'])) {
            $item['condition']['field'] = "{$context_name}_{$item['condition']['field']}_{$variant_index}";
        }
        return $item;  
    }
function apply_field_prefix_suffix($context_name, $variant_index, $context_data) {
    $config = dibraco_get_card_settings($context_data, $context_name);
    $modified_config = [];
    foreach ($config as $section_key => $section) {
        $modified_section = $section;
        $modified_section = modify_condition_field($modified_section, $context_name, $variant_index);
        foreach ($modified_section['fields'] as $field_group_key => $field_group) {
            $modified_field_group = $field_group;
            $modified_field_group = modify_condition_field($modified_field_group, $context_name, $variant_index);
            $modified_fields = []; 

            foreach ($modified_field_group['fields'] as $field_key => $field) {
                $field = modify_condition_field($field, $context_name, $variant_index);
                $label = ucwords(str_replace(['-','_'], ' ', $field_key));
                $label_minus = str_replace(['Cards', 'Card', 'Title', 'Description', 'Button', 'Image'], '', $label);
                $label_minus = str_replace(['Shadow'], 'Shdw', $label_minus);
                $label_minus = str_replace(['Background'], 'Bkgd', $label_minus);
                $label_minus = str_replace(['Horizontal'], 'Horiz', $label_minus); 
                $label_minus = str_replace(['Vertical'], 'Vert', $label_minus); 
                if (!isset($field['label'])){
                $field['label'] = $label_minus;
                }
                $modified_field_key = "{$context_name}_{$field_key}_{$variant_index}";
                $modified_fields[$modified_field_key] = $field;
            }
            $modified_field_group['fields'] = $modified_fields;
            $modified_section['fields'][$field_group_key] = $modified_field_group;
        }
        $modified_config[$section_key] = $modified_section;
    }
    return $modified_config;
}
function dibraco_add_new_variant_option($context_name, $context_type, $new_variant_index) {
    $base_option = get_option("{$context_name}_card_styles_0");
    if (!is_array($base_option)) $base_option = [];
    if($context_type ==='type'){
    $base_option['selected_terms'] = [];
    }
    $new_option_name = "{$context_name}_card_styles_{$new_variant_index}";
    add_option($new_option_name, $base_option, '', 'no');
    return 'variant_added';
}

function dibraco_save_card_styles_settings($context_name, $context_type, $post_data) {
    $max_variants = 10;
    $variants_to_save = [];

    foreach ($post_data as $key => $value) {
        if (preg_match('/^' . preg_quote($context_name, '/') . '_(.+)_(\d+)$/', $key, $matches)) {
            $field_key = $matches[1];
            $index     = (int) $matches[2];
            if ($index >= 0 && $index < $max_variants) {
                $variants_to_save[$index][$field_key] = $value;
            }
        }
    }

    foreach ($variants_to_save as $index => $data) {
        if ($context_type === 'type'){
        $terms_key = "variant_{$index}_terms";
        $data['selected_terms'] = array_map('intval', $post_data[$terms_key]);
        }
        $variants_to_save[$index] = $data;
    }

    $saved_count = 0;
    foreach ($variants_to_save as $index => $data) {
        $option_name = "{$context_name}_card_styles_{$index}";
        update_option($option_name, $data);
        dibraco_generate_dynamic_card_css_for_variant($index, $data, $context_name);
        $saved_count++;
    }

    return (bool) $saved_count;
}
function redirect_after_card_styles_action($query_args = []) {
    wp_safe_redirect(remove_query_arg('card_styles_nonce', add_query_arg($query_args, $_SERVER['REQUEST_URI'])));
    exit;
}
function dibraco_awesome_render_card_settings_page($context_data, $context_name) {
    $hyphenated_context_name = str_replace('_', '-', $context_name);
    $saved_settings = [];
    $max_variants = 10;
    $context_type = $context_data['context_type'];
    for($variant_index = 0; $variant_index < $max_variants; $variant_index++) {
        $option_name_for_variant = "{$context_name}_card_styles_{$variant_index}";
        $option_data = get_option($option_name_for_variant);
        if($option_data !== false) {
            $saved_settings[$variant_index] = $option_data;
        }
    }
    if(!isset($saved_settings[0])) {
        $saved_settings[0] = [];
    }
    if($context_type === 'type') {
        $taxonomy = $context_data['taxonomy'];
        $terms = [];
        $term_results = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
        if(!empty($term_results)) {
            $terms = $term_results;
        }
    }
    $nonce_action = 'dibraco_card_styles_actions_' . $context_name;

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_styles_nonce'])) {
        if(wp_verify_nonce($_POST['card_styles_nonce'], $nonce_action)) {
            $action_value = $_POST['submit_action'];
            if($action_value === 'save_card_styles') {
                if($context_type === 'type') {
                    for($i = 0; $i < $max_variants; $i++) {
                        if(empty($_POST["variant_{$i}_terms"])) {
                            foreach($_POST as $key => $value) {
                                if(preg_match("/^{$context_name}_.+_{$i}$/", $key)) {
                                    unset($_POST[$key]);
                                }
                            }
                            unset($_POST["variant_{$i}_terms"]);
                            delete_option("{$context_name}_card_styles_{$i}");
                        }
                    }
                    $has_any_selected_terms = false;
                    for($i = 0; $i < $max_variants; $i++) {
                        if(!empty($_POST["variant_{$i}_terms"])) {
                            $has_any_selected_terms = true;
                            break;
                        }
                    }
                    if(!$has_any_selected_terms) {
                        redirect_after_card_styles_action(['settings-updated' => 'no_terms_selected']);
                    }
                }
                dibraco_save_card_styles_settings($context_name, $context_type, $_POST);
                redirect_after_card_styles_action(['settings-updated' => '1']);
            } elseif($action_value === 'add_variant') {
                $next_variant_index = -1;
                for($i = 0; $i < $max_variants; $i++) {
                    if(get_option("{$context_name}_card_styles_{$i}") === false) {
                        $next_variant_index = $i;
                        break;
                    }
                }
                if($next_variant_index !== -1) {
                    dibraco_add_new_variant_option($context_name, $context_type, $next_variant_index);
                    redirect_after_card_styles_action(['settings-updated' => 'variant_added']);
                } else {
                    redirect_after_card_styles_action(['settings-updated' => 'add_failed_limit']);
                }
            } elseif(is_numeric($action_value)) {
                $remove_index = (int)$action_value;
                $option_name_to_remove = "{$context_name}_card_styles_{$remove_index}";
                delete_option($option_name_to_remove);
                redirect_after_card_styles_action(['settings-updated' => 'variant_removed']);
            }
        }
    }

    $actual_variant_count = count($saved_settings);
    ksort($saved_settings);
    ?>
<div id="dibraco-card-settings-<?= $hyphenated_context_name ?>" class="card-styles-settings">
    <form id="card-styles-form-<?= $hyphenated_context_name ?>" method="post" action="">
        <?php wp_nonce_field($nonce_action, 'card_styles_nonce'); ?>
        <div id="<?= $hyphenated_context_name ?>-card-styles-container" class="variants-container">

            <!-- Variant Tabs -->
            <ul class="variant-tabs">
                <?php foreach (array_keys($saved_settings) as $i): ?>
                    <li class="variant-tab<?= $i === 0 ? ' active' : '' ?>" data-tab-index="<?= $i ?>">
                        Variant <?= $i ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php foreach ($saved_settings as $index => $settings): ?>
                <div id="variant-<?= $index ?>" class="variant-wrap<?= $index === 0 ? ' active' : '' ?>">
                    <div class="heading-wrap">
                        <h3>Style Settings for Variant <?= $index ?></h3>
                    </div>

                    <?php
                    if ($context_type ==='type'){
                    $current_selected_terms = $settings['selected_terms'] ?? [];
                    dibraco_render_terms_checkbox_group($taxonomy, $terms, $current_selected_terms, $index);
                    }
                    $modified_sections = apply_field_prefix_suffix($context_name, $index, $context_data);
                    foreach ($modified_sections as $section_key => $section_data) {
                        $section_data_with_values = $section_data;
                        foreach ($section_data_with_values['fields'] as $group_key => $group_data) {
                            $modified_fields_with_values = [];
                            foreach ($group_data['fields'] as $modified_field_key => $field_config) {
                                $current_field_config = $field_config;
                                $original_field_key = str_replace(["{$context_name}_", "_{$index}"], "", $modified_field_key);
                                if (isset($settings[$original_field_key])) {
                                    $current_field_config['value'] = $settings[$original_field_key];
                                }
                                $modified_fields_with_values[$modified_field_key] = $current_field_config;
                            }
                            $section_data_with_values['fields'][$group_key]['fields'] = $modified_fields_with_values;
                        }
                        echo FormHelper::generateVisualSection($section_key, $section_data_with_values);
                    }
                    ?>
                    <?php if ($index > 0): ?>
                        <div class="variant-actions">
                            <button type="submit" name="submit_action" value="<?= $index ?>" class="button button-link-delete deletion"
                                    onclick="return confirm('Are you sure you want to remove Variant <?= $index ?>? This cannot be undone.');">
                                Remove Variant <?= $index ?>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <div class="my-buttons">
                <button type="submit" name="submit_action" value="save_card_styles" class="button button-primary">Save All Settings</button>
                <?php if ($actual_variant_count < 10): ?>
                    <button type="submit" name="submit_action" value="add_variant" class="button button-secondary">Add New Variant</button>
                    <span>(<?= ($max_variants - $actual_variant_count) ?> more allowed)</span>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.variant-tab');
    const wraps = document.querySelectorAll('.variant-wrap');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const index = tab.dataset.tabIndex;
            tabs.forEach(t => t.classList.remove('active'));
            wraps.forEach(w => w.classList.remove('active'));

            tab.classList.add('active');
            const activeWrap = document.getElementById(`variant-${index}`);
            activeWrap.classList.add('active');
        });
    });

    const contextName = '<?= esc_js($context_name); ?>';
    function getVariantIndex(element) {
        const nameParts = element.name.split('_');
        return parseInt(nameParts[nameParts.length - 1], 10);
    }

    function getCorrespondingSelect(radioElement) {
        const radioName = radioElement.name;
        const variantIndex = getVariantIndex(radioElement);
        const radioNameParts = radioName.split('_');
        const showWordIndex = radioNameParts.indexOf('show');
        const type = radioNameParts[showWordIndex + 1];
        const selectName = `${contextName}_cards_${type}_position_${variantIndex}`;
        return document.querySelector(`select[name="${selectName}"]`);
    }

    function getAllPositionSelectsForVariant(variantIndex) {
        const selector = `select[name^="${contextName}_cards_"][name$="_position_${variantIndex}"]`;
        return document.querySelectorAll(selector);
    }

    function setSelectValueAndUpdateNote(selectElement, newValue) {
        selectElement.value = newValue;
        selectElement.dataset.originalPosition = newValue;
    }

    function updateSelectOptionsForVariant(variantIndex) {
        const activeRadioSelector = `input[type="radio"][name^="${contextName}_cards_show_"][name$="_${variantIndex}"][value="1"]:checked`;
        const currentActiveCount = document.querySelectorAll(activeRadioSelector).length;
        const selectsToUpdate = getAllPositionSelectsForVariant(variantIndex);

        selectsToUpdate.forEach(selectElement => {
            for (let i = 1; i <= 4; i++) {
                const option = selectElement.querySelector(`option[value="${i.toString()}"]`);
                if (option) option.hidden = (i > currentActiveCount);
            }
            const selectedOptionObject = selectElement.options[selectElement.selectedIndex];
            if (selectedOptionObject && selectedOptionObject.value !== "" && selectedOptionObject.hidden) {
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
        const variantIndex = getVariantIndex(changedElement);
        const allSelects = getAllPositionSelectsForVariant(variantIndex);
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
        const variantIndex = getVariantIndex(radioElement);
        const correspondingSelect = getCorrespondingSelect(radioElement);
        const activeRadioSelector = `input[type="radio"][name^="${contextName}_cards_show_"][name$="_${variantIndex}"][value="1"]:checked`;
        const activeCount = document.querySelectorAll(activeRadioSelector).length;
        const newPositionValue = activeCount.toString();
        setSelectValueAndUpdateNote(correspondingSelect, newPositionValue);
        updateSelectOptionsForVariant(variantIndex);
    }

    function handleToggleOff(radioElement) {
        const variantIndex = getVariantIndex(radioElement);
        const correspondingSelect = getCorrespondingSelect(radioElement);
        const originalPosition = correspondingSelect.dataset.originalPosition || correspondingSelect.value;
        const oldNumeric = parseInt(originalPosition, 10);

        setSelectValueAndUpdateNote(correspondingSelect, '');

        if (!isNaN(oldNumeric) && oldNumeric > 0) {
            const allSelects = getAllPositionSelectsForVariant(variantIndex);
            allSelects.forEach(sel => {
                if (sel !== correspondingSelect) {
                    const current = parseInt(sel.dataset.originalPosition || sel.value, 10);
                    if (!isNaN(current) && current > oldNumeric) {
                        const newPos = (current - 1).toString();
                        setSelectValueAndUpdateNote(sel, newPos);
                    }
                }
            });
        }

        updateSelectOptionsForVariant(variantIndex);
    }
    const allInitialSelects = document.querySelectorAll(`select[name^="${contextName}_cards_"][name*="_position_"]`);
    allInitialSelects.forEach(sel => sel.dataset.originalPosition = sel.value);
    const uniqueVariantIndexes = new Set();
    allInitialSelects.forEach(sel => {
        const index = getVariantIndex(sel);
        if (!isNaN(index)) uniqueVariantIndexes.add(index);
    });
    uniqueVariantIndexes.forEach(index => updateSelectOptionsForVariant(index));
    allInitialSelects.forEach(select => {
        select.addEventListener('click', function() {
            this.dataset.originalPosition = this.value;
        });
        select.addEventListener('change', function() {
            handleManualSelectChange(this, this.dataset.originalPosition, this.value);
        });
    });
    document.querySelectorAll(`input[type="radio"][name^="${contextName}_cards_show_"]`).forEach(radio => {
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

function generateCardsContainerCSS($cards_section_data, $card_section_data, $context_name, $variant_index) {
    $css = '';
    $container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_item_unique_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $full_descendant_card_selector = $container_selector . ' ' . $card_item_unique_selector;

    $layout_fields = $cards_section_data['fields']['cards_layout']['fields'];
    $row_settings_fields = $cards_section_data['fields']['cards_row_settings']['fields'];
    $user_defined_width = $card_section_data['fields']['card_alignment']['fields']['card_width'];

    $card_spacing_fields = $card_section_data['fields']['card_spacing']['fields'];
    $card_margin_left = $card_spacing_fields['card_margin_left'];
    $card_margin_right = $card_spacing_fields['card_margin_right'];

    $display_style = $layout_fields['display_style'];
    $alignment = $layout_fields['cards_alignment'];
    $justify = $layout_fields['cards_justify'];

    $large_breakpoint  = (int) preg_replace('/\D/', '', $row_settings_fields['cards_large_breakpoint']);
    $medium_breakpoint = (int) preg_replace('/\D/', '', $row_settings_fields['cards_medium_breakpoint']);
    $small_breakpoint  = (int) preg_replace('/\D/', '', $row_settings_fields['cards_small_breakpoint']);
    
    $cards_per_row_settings = [
        'large'       => max(1, (int)$row_settings_fields['cards_large_card_row']),
        'medium'      => max(1, (int)$row_settings_fields['cards_medium_card_row']),
        'small'       => max(1, (int)$row_settings_fields['cards_small_card_row']),
        'extra_small' => max(1, (int)$row_settings_fields['cards_extra_small_card_row']),
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
function generateCardCSS($section_data, $cards_section_data, $toggles, $context_name, $variant_index) {
    $css = '';
    $card_base_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index); 

    $unique_container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);

    $card_full_selector = $unique_container_selector . ' ' . $card_base_selector;

 $spacing_fields = $section_data['fields']['card_spacing']['fields'];
    $layout_fields = $section_data['fields']['card_alignment']['fields'];
    $border_fields = $section_data['fields']['card_border']['fields'];
    $shadow_fields = $section_data['fields']['card_box_shadow']['fields'];

    $formatted_border_fields = [
        'border_width' => $border_fields['card_border_width'],
        'border_style' => $border_fields['card_border_style'],
        'border_color' => $border_fields['card_border_color']
    ];
$regular_styles = [
        'box-sizing'       => 'border-box',
        'display'          => 'flex',
        'flex-direction'   => 'column',
        'padding-top'      => $spacing_fields['card_padding_top'],
        'padding-right'    => $spacing_fields['card_padding_right'],
        'padding-bottom'   => $spacing_fields['card_padding_bottom'],
        'padding-left'     => $spacing_fields['card_padding_left'],
        'background-color' => $layout_fields['card_background_color'],
        'align-items'      => $layout_fields['card_align_items'],
        'justify-content'  => $layout_fields['card_justify_content'],
        'border-radius'    => $border_fields['card_border_radius'],
        'border'           => processBorderStyles($formatted_border_fields),
        'margin-top'        => $spacing_fields['card_margin_top'],
        'margin-bottom'     => $spacing_fields['card_margin_bottom'],
        'margin-right'      => $spacing_fields['card_margin_right'],
        'margin-left'       => $spacing_fields['card_margin_left'],
    ];

   if ($toggles['card_show_box_shadow'] === '1') {
        $regular_styles['box-shadow'] = "{$shadow_fields['card_box_shadow_horizontal_offset']} {$shadow_fields['card_box_shadow_vertical_offset']} {$shadow_fields['card_box_shadow_blur_radius']} {$shadow_fields['card_box_shadow_spread_radius']} {$shadow_fields['card_box_shadow_color']}";
    }

    $css .= formatCSSBlock($card_full_selector, $regular_styles); 
    return $css;
}

function generateImageCSS($section_data, $toggles, $context_name, $variant_index) {
    $css = '';
    $context_name = str_replace('_', '-', $context_name);
    $cards_selector = ".{$context_name}-cards-section-{$variant_index}";
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $wrapper_selector = "{$cards_selector} {$card_selector} .card-image-wrap";
    $img_selector = "{$wrapper_selector} img.card-image";

    $content_fields = $section_data['fields']['image_content']['fields'];
    $margin_fields = $section_data['fields']['image_margins']['fields'];
    $shadow_fields = $section_data['fields']['image_drop_shadow']['fields'];
    $border_fields = $section_data['fields']['image_border']['fields'];

    $wrapper_styles = [
        'display'         => 'flex',
        'justify-content' => $content_fields['image_alignment'],
        'align-items'     => 'center',
        'margin-top'      => $margin_fields['image_margin_top'],
        'margin-bottom'   => $margin_fields['image_margin_bottom'],
        'margin-left'     => $margin_fields['image_margin_left'],
        'margin-right'    => $margin_fields['image_margin_right'],
    ];

    $img_styles = [
        'display'    => 'block',
        'width'      => '100%',
        'height'     => 'auto',
        'object-fit' => $content_fields['image_object_fit'],
        'max-width'  => '100%',
    ];

    $aspect_ratio_val = $content_fields['image_aspect_ratio'];
    if ($aspect_ratio_val !== 'auto') {
        $img_styles['aspect-ratio'] = str_replace('_', '/', $aspect_ratio_val);
        unset($img_styles['height']);
    }

    $border_radius_value = $border_fields['image_border_radius'];
    $needs_clipping = false;

    if ($toggles['image_show_border'] === "1") {
        $formatted_border_fields = [
            'border_width' => $border_fields['image_border_width'],
            'border_style' => $border_fields['image_border_style'],
            'border_color' => $border_fields['image_border_color']
        ];
        $wrapper_styles['border'] = processBorderStyles($formatted_border_fields);

        if (!empty($border_radius_value) && $border_radius_value !== '0' && $border_radius_value !== '0px') {
            $wrapper_styles['border-radius'] = $border_radius_value;
            $needs_clipping = true;
        }
    } else {
        if (!empty($border_radius_value) && $border_radius_value !== '0' && $border_radius_value !== '0px') {
            $wrapper_styles['border-radius'] = $border_radius_value;
            $needs_clipping = true;
        }
    }

    if ($needs_clipping) {
        $wrapper_styles['overflow'] = 'hidden';
    }

    if ($toggles['image_show_drop_shadow'] === "1") {
        $ds_h_offset = $shadow_fields['image_drop_shadow_horizontal_offset'];
        $ds_v_offset = $shadow_fields['image_drop_shadow_vertical_offset'];
        $ds_blur = $shadow_fields['image_drop_shadow_blur_radius'];
        $ds_color = $shadow_fields['image_drop_shadow_color'];
        if (!empty($ds_color)) {
            $img_styles['filter'] = "drop-shadow({$ds_h_offset} {$ds_v_offset} {$ds_blur} {$ds_color})";
        }
    }
    $css .= formatCSSBlock($wrapper_selector, $wrapper_styles);
    $css .= formatCSSBlock($img_selector, $img_styles);
    return $css;
}


function generateTitleCSS($section_data, $context_name, $variant_index) {
    $css = '';
    $context_name = str_replace('_', '-', $context_name);
    $cards_selector = ".{$context_name}-cards-section-{$variant_index}";
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $text_fields = $section_data['fields']['title_text']['fields'];
    $color_fields = $section_data['fields']['title_colors']['fields'];
    $margin_fields = $section_data['fields']['title_margins']['fields'];
    $section_selector = "{$cards_selector} {$card_selector} .{$context_name}-title-section";
    $heading_tag = esc_attr($text_fields['title_heading_type']);
    $heading_selector = "{$cards_selector} {$card_selector} {$heading_tag}.card-title";
    $link_selector = "{$heading_selector} .card-title-link";
    $heading_hover_selector = "{$heading_selector}:hover";
    $link_hover_selector = "{$heading_selector}:hover .card-title-link";
    $section_styles = [
        'margin-top'     => $margin_fields['title_margin_top'],
        'margin-bottom'  => $margin_fields['title_margin_bottom'],
        'margin-left'    => $margin_fields['title_margin_left'],
        'margin-right'   => $margin_fields['title_margin_right'],
    ];
    $heading_styles = [
        'text-align'        => $text_fields['title_text_align'],
		'line-height'       => '1.2',
        'font-size'         => $text_fields['title_font_size'],
        'font-weight'       => $text_fields['title_text_font_weight'],
        'text-transform'    => $text_fields['title_text_transform'],
        'color'             => $color_fields['title_text_color'],
    ];

    $link_styles = [
        'color'             => $color_fields['title_text_color'],
        'text-decoration'   => 'none',
    ];
    
    $hover_styles = [
        'color' => $color_fields['title_text_hover_color'],
    ];
    $css .= formatCSSBlock($section_selector, $section_styles);
    $css .= formatCSSBlock($heading_selector, $heading_styles);
    $css .= formatCSSBlock($link_selector, $link_styles);
    $css .= formatCSSBlock($heading_hover_selector, $hover_styles);
    $css .= formatCSSBlock($link_hover_selector, $hover_styles);
    
    return $css;
}

function generateDescriptionCSS($section_data, $context_name, $variant_index) {
    $css = '';
   $context_name = str_replace('_', '-', $context_name);
    $cards_selector = ".{$context_name}-cards-section-{$variant_index}";
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $p_selector = "{$cards_selector} {$card_selector} p.card-description";
    $text_fields = $section_data['fields']['description_text']['fields'];
    $margin_fields = $section_data['fields']['description_margins']['fields'];

    $p_styles = [
        'color'          => $text_fields['description_color'],
        'font-size'      => $text_fields['description_font_size'],
        'font-weight'    => $text_fields['description_font_weight'],
        'text-transform' => $text_fields['description_text_transform'],
        'text-align'     => $text_fields['description_text_align'],
        'margin-top'     => $margin_fields['description_margin_top'],
        'margin-bottom'  => $margin_fields['description_margin_bottom'],
        'margin-left'    => $margin_fields['description_margin_left'],
        'margin-right'   => $margin_fields['description_margin_right'],
    ];

    $css .= formatCSSBlock($p_selector, $p_styles);
    return $css;
}

function generateButtonCSS($section_data, $toggles, $context_name, $variant_index) {
    $css = '';
    $color_fields = $section_data['fields']['button_colors']['fields'];
    $border_fields = $section_data['fields']['button_border']['fields'];
    $text_fields = $section_data['fields']['button_text']['fields'];
    $margin_tb_fields = $section_data['fields']['button_margins_top_bottom']['fields'];
    $margin_lr_fields = $section_data['fields']['button_margins_left_right']['fields'];
    $dimension_fields = $section_data['fields']['button_dimensions']['fields'];
    $padding_fields = $section_data['fields']['button_paddings']['fields'];
    $use_fixed_dimensions = $text_fields['use_height_width'];
    $card_selector = dibraco_get_css_selector('card-section', $context_name, $variant_index);
    $unique_container_selector = dibraco_get_css_selector('cards-section', $context_name, $variant_index);
    $card_selector_with_container = $unique_container_selector . ' ' . $card_selector; 

    $button_selector = "{$card_selector_with_container} a.card-button";
    $button_hover_selector = "{$button_selector}:hover";

    $button_base_styles = [
        'color'              => $color_fields['button_text_color'],
        'display' => 'flex',
        'background-color'   => $color_fields['button_background_color'],
        'border-radius'      => $border_fields['button_border_radius'],
        'font-weight'        => $text_fields['button_text_font_weight'],
        'font-size'          => $text_fields['button_font_size'],
        'text-transform'     => $text_fields['button_text_transform'],
        'text-align'         => $text_fields['button_text_align'],
        'align-items'        => 'center',    
        'justify-content'    => 'center',    
        'align-content'      => 'center',
        'border'             => processBorderStyles([ 
                                    'border_width' => $border_fields['button_border_width'],
                                    'border_style' => $border_fields['button_border_style'],
                                    'border_color' => $border_fields['button_border_color']
                                ]),
        'margin-top'         => $margin_tb_fields['button_margin_top'],
        'margin-bottom'      => $margin_tb_fields['button_margin_bottom'],
    ];

    if ($use_fixed_dimensions === "1") { 
        $button_base_styles['width']  = $dimension_fields['button_width'];
        $button_base_styles['height'] = $dimension_fields['button_height'];
        $button_base_styles['padding']    = '0px';
        $alignment_choice = $dimension_fields['button_align_horizontal'];
        $flex_map = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'];
        $button_base_styles['align-self'] = $flex_map[$alignment_choice];
 
    } else { 
        // Set padding based on fields
        $button_base_styles['padding-top']    = $padding_fields['button_padding_top'];
        $button_base_styles['padding-bottom'] = $padding_fields['button_padding_bottom'];
        $button_base_styles['padding-left']   = $padding_fields['button_padding_left'];
        $button_base_styles['padding-right']  = $padding_fields['button_padding_right'];
        
        $alignment_choice = $text_fields['button_text_align'];
        $flex_map = ['left' => 'flex-start', 'center' => 'center', 'right' => 'flex-end'];
        $button_base_styles['align-self'] = $flex_map[$alignment_choice];
        $button_base_styles['margin-left'] = $margin_lr_fields['button_margin_left'];
        $button_base_styles['margin-right'] = $margin_lr_fields['button_margin_right'];
    }

    $button_hover_styles = [
        'color'              => $color_fields['button_text_hover_color'],
        'background-color'   => $color_fields['button_background_hover_color'],
    ];
    if ($button_base_styles['border'] !== 'none' && !empty($border_fields['button_border_color_hover'])) { 
        $button_hover_styles['border-color'] = $border_fields['button_border_color_hover'];
    }

    $css .= formatCSSBlock($button_selector, $button_base_styles);
    $css .= formatCSSBlock($button_hover_selector, $button_hover_styles);
    return $css;
}


function dibraco_generate_dynamic_card_css_for_variant($index, $data_to_save, $context_name) {
    $sections = dibraco_get_card_settings(); 
    $populated_sections = [];
    $css = '';
    $toggles = [
        'image_show_drop_shadow' => $data_to_save['image_show_drop_shadow'],
        'image_show_border'      => $data_to_save['image_show_border'],
        'card_show_box_shadow'   => $data_to_save['card_show_box_shadow'],
        'cards_show_button'      => $data_to_save['cards_show_button'],
        'cards_show_description' => $data_to_save['cards_show_description'],
        'cards_show_image'       => $data_to_save['cards_show_image'],
        'cards_show_title'       => $data_to_save['cards_show_title'],
        'use_height_width'       => $data_to_save['use_height_width']
    ];

    foreach ($sections as $section_name => $section_data) {
        $populated_sections[$section_name] = ['fields' => []];
        foreach ($section_data['fields'] as $field_group_name => $field_group_data) {
            $populated_field_group = ['fields' => []];
            foreach ($field_group_data['fields'] as $field_name => $field_data_definition) {
                $populated_field_group['fields'][$field_name] = $data_to_save[$field_name];
            }
            $populated_sections[$section_name]['fields'][$field_group_name] = $populated_field_group;
        }
    }


    $css .= generateCardsContainerCSS($populated_sections['cards_section'], $populated_sections['card_section'], $context_name, $index);
    $css .= generateCardCSS($populated_sections['card_section'], $populated_sections['cards_section'], $toggles, $context_name, $index);

    if (($toggles['cards_show_image']) === "1") { 
        $css .= generateImageCSS($populated_sections['image_section'], $toggles, $context_name, $index); 
    }
    if (($toggles['cards_show_title']) === "1") { 
        $css .= generateTitleCSS($populated_sections['title_section'], $context_name, $index); 
    }
    if (($toggles['cards_show_description']) === "1") { 
        $css .= generateDescriptionCSS($populated_sections['description_section'], $context_name, $index);
    }
    if (($toggles['cards_show_button']) === "1") { 
        $css .= generateButtonCSS($populated_sections['button_section'], $toggles, $context_name, $index); 
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
    $css_file = $target_dir . "/card-{$context_name}-{$index}.css";
    file_put_contents($css_file, $css);
}