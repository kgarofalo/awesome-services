<?function render_dibraco_main_posts_screen() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dibraco_main_posts_nonce'])) {
        // ... (your existing save logic is correct) ...
    }

    // --- 2. Render The Dynamic Form ---
    $contexts = get_option('enabled_type_contexts');
    if (empty($contexts)) {
        echo '<div class="wrap"><h1>Main Posts by Term</h1><p>No enabled contexts found.</p></div>';
        return;
    }
    echo '<div class="wrap"><h1>Edit Main Posts by Term</h1>';
    
    foreach ($contexts as $context_key => $context) {
        if ($context['post_per_term'] !== '1') continue;
        
        echo "<h2>Context: {$context_key}</h2>";
        $taxonomy = $context['taxonomy'];
        $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true]);
        ?>
        <form method="POST" action="">
            <input type="hidden" name="context_to_update" value="<?php echo esc_attr($context_key); ?>">
            <?php wp_nonce_field('dibraco_save_main_posts', 'dibraco_main_posts_nonce'); ?>
            
            <table class="form-table">
                <tbody>
                    <?php
                    foreach ($terms as $term) {
                        $current_main_post_id = get_term_meta($term->term_id, 'main_post_for_term', true);
                        $current_post_type = get_post_type($current_main_post_id);

                        // Get all possible post types for this taxonomy
                        $possible_post_types = get_taxonomy($taxonomy)->object_type;
                        
                        $grouped_options = [];
                        foreach ($possible_post_types as $post_type) {
                            $tax_query = [['taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => [$term->term_id]]];
                            $possible_posts = get_posts([
                                'post_type' => $post_type,
                                'posts_per_page' => -1,
                                'tax_query' => $tax_query,
                                'post_status' => 'publish',
                            ]);
                            
                            if (!empty($possible_posts)) {
                                $options = [];
                                foreach ($possible_posts as $p) {
                                    $options[$p->ID] = $p->post_title;
                                }
                                $grouped_options[$post_type] = $options;
                            }
                        }
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($term->name); ?></th>
                            <td>
                                <?php
                                echo FormHelper::generateField("post_type_selector_{$term->term_id}",[ 'type'=>'radio', 'label'=> 'Select PostType:', 'value'=> $current_post_type,'options'=>
array_combine(array_keys($grouped_options), array_keys($grouped_options))]);
                                
                                foreach ($grouped_options as $post_type => $options) {
                                    echo FormHelper::generateField( "main_posts[{$term->term_id}]",['type'=>'select', 'label'=>"Select a Post",'value'=> $current_main_post_id,'options'=> $options,
                                        'condition'=>['field' => "post_type_selector_{$term->term_id}", 'values' => [$post_type], 'current_value' => $current_post_type]]
                                    );
                                }
                                ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
            <?php submit_button('Save Main Posts'); ?>
        </form>
        <hr>
        <?php
    }
    echo '</div>';
}