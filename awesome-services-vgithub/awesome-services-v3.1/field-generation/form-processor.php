<?php
class FormProcessor {

  public static function flatten_array($array, $prefix = '') {
    $flattened = [];

    foreach ($array as $key => $value) {
      $new_key = $prefix ? "{$prefix}[{$key}]" : $key;
      if (is_array($value)) {
        $nested = self::flatten_array($value, $new_key);
        foreach ($nested as $nested_key => $nested_value) {
          $flattened[$nested_key] = $nested_value;
        }
      } else {
        $flattened[$new_key] = $value;
      }
    }

    return $flattened;
  }
}