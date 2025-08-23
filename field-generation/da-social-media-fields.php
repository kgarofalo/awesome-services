<?php
function get_social_media_fields() {
	$option_key = 'custom_social_media_keys';
	$all_keys = get_option($option_key, []);
    if (!isset($all_keys['facebook'])) {
		$default_keys = ['facebook', 'gmb', 'yelp', 'instagram', 'twitter', 'bbb', 'linkedin'];
		$all_keys = array_merge(array_combine($default_keys, $default_keys), $all_keys);
		update_option($option_key, $all_keys);
	} 
	$special_labels = ['gmb' => 'Google My Business', 'bbb' => 'BBB', 'linkedin' => 'LinkedIn'];
	$formatted_fields = [];
	foreach ($all_keys as $key => $value) {
		$field_definition = ['type' => 'text'];
		if (isset($special_labels[$key])) {
			$field_definition['label'] = $special_labels[$key];
		}
		$formatted_fields[$key] = $field_definition;
	}
	return $formatted_fields;
}
function render_social_media_options_page() {
	if (!current_user_can('manage_options')) {
		return;
	}
	$non_deletable_keys = ['facebook', 'gmb', 'yelp', 'instagram', 'twitter', 'bbb', 'linkedin'];
	$all_keys = get_option('custom_social_media_keys', []);
	if (isset($_POST['submit']) && !empty(trim($_POST['custom_social_media_field_label']))) {
		$raw_input = trim($_POST['custom_social_media_field_label']);
		$clean_input = strtolower(sanitize_text_field($raw_input));
		$new_field_key = str_replace(' ', '_', $clean_input);
		if ($new_field_key && !array_key_exists($new_field_key, $all_keys)) {
			$all_keys[$new_field_key] = $new_field_key;
			update_option($option_key, $all_keys);
		}
	}
	if (isset($_POST['delete_field_key'])) {
		$key_to_delete = $_POST['delete_field_key'];
		unset($all_keys[$key_to_delete]);
		update_option($option_key, $all_keys);
	}
	?>
	<div class="wrap">
		<h1>Manage Social Media Fields</h1>
		<?php settings_errors('social_media_fields'); ?>
		<h2>Add New Field</h2>
		<form method="post" style="width:30%">
			<?php echo FormHelper::generateField('custom_social_media_field_label', ['type' => 'text', 'label' => 'New Social Media Property']); ?>
			<?php submit_button('Add New Field'); ?>
		</form>
		<hr>
		<h2>All Social Media Fields</h2>
		<form method="post">
			<ul>
				<?php foreach ($all_keys as $key => $value): ?>
					<li>
						<?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>
						<?php if (!in_array($key, $non_deletable_keys)): ?>
							<button type="submit" name="delete_field_key" value="<?php echo esc_attr($key); ?>" class="button button-secondary button-small" style="margin-left: 10px;">
								Delete
							</button>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</form>
	</div>
	<?php
}