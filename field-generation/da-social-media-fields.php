<?php
function get_social_media_fields() {
	$field_names = get_option('custom_social_media_keys', []);
	if (empty($field_names)){
	    $field_names = ['facebook', 'gmb', 'yelp', 'instagram', 'twitter', 'bbb', 'linkedin'];
	}
$special_labels = ['gmb' => 'Google Business Profile', 'bbb' => 'BBB', 'linkedin' => 'LinkedIn'];
 $fields = [];
    foreach ($field_names as $field_name) {
        $field_config = ['type' => 'text'];
        if (isset($special_labels[$field_name])) {
            $field_config['label'] = $special_labels[$field_name];
        }
       $fields[$field_name] = $field_config;
    }
    return $fields;
}


function render_social_media_options_page() {
    $option_key = 'custom_social_media_keys';

    $field_names = get_option($option_key, []);
    if (empty($field_names)) {
        $field_names = ['facebook', 'gmb', 'yelp', 'instagram', 'twitter', 'bbb', 'linkedin'];
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
       if (isset($_POST['add'])) {
        $new_field_name = strtolower(str_replace(' ', '_', sanitize_text_field($_POST['new_social_media_field'])));
        if (($new_field_name !== '') && (!in_array($new_field_name, $field_names))) {
                $field_names[$new_field_name] = $new_field_name;
            }
        }
       if (isset($_POST['delete'])) {
                unset($field_names[$_POST['delete']]);
            }
        update_option($option_key, $field_names);
    }
    ?>
    <div class="wrap">
        <h1>Manage Social Media Fields</h1>
        <form method="post">
            <h2>Add New Field</h2>
            <?= FormHelper::generateField('new_social_media_field', ['type' => 'text']); ?>
            <button type="submit" name="add" value="new_social_media_field" class="button button-primary">Add New Field</button>

            <hr>
            <h2>All Social Media Fields</h2>
            <ul>
                <? foreach ($field_names as $field_name){
                    $printed = ucwords(str_replace('_', ' ',  $field_name));
                    echo "<li>$printed";
                            echo "<button type=submit name=delete value={$field_name} class=button button-secondary button-small style=margin-left:10px> Delete </button>";
                    echo "</li>";    
                }
               ?>
            </ul>
        </form>
    </div>
    <?php
}


