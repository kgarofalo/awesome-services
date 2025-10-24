<?php
/**
 * Smart data filtering for repeater fields
 * Handles both exact matches and pattern matches for flattened repeater data
 */

/**
 * Intelligently filters saved data to include only valid fields
 * Handles repeater fields stored as flattened arrays
 *
 * @param array $saved_data The raw saved data with all keys
 * @param array $storage_keys The valid field keys from extract_nested_arrays_test
 * @return array Filtered data containing only valid fields
 */
function dibraco_filter_saved_data($saved_data, $storage_keys) {
    $filtered = [];

    // Collect repeater field names (keys that have array values in storage_keys)
    $repeater_fields = [];
    foreach ($storage_keys as $key => $value) {
        if (is_array($value)) {
            $repeater_fields[] = $key;
        }
    }

    // Filter the saved data
    foreach ($saved_data as $data_key => $data_value) {
        // Check for exact match (non-repeater fields and row_counts)
        if (array_key_exists($data_key, $storage_keys)) {
            $filtered[$data_key] = $data_value;
            continue;
        }

        // Check if this is part of a repeater field (pattern match)
        // Repeater data is stored as: repeater_name[0][field], repeater_name[1][field], etc.
        foreach ($repeater_fields as $repeater_name) {
            // Match keys that start with repeater_name followed by [
            if (strpos($data_key, $repeater_name . '[') === 0) {
                $filtered[$data_key] = $data_value;
                break;
            }
        }
    }

    return $filtered;
}
