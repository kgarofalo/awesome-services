<?php
function get_hours_array_keys(){
    return [ "open_247", "open_monday", "mon_open_hour", "mon_close_hour", "open_tuesday", "tue_open_hour", "tue_close_hour", "open_wednesday", "wed_open_hour", "wed_close_hour", "open_thursday", "thu_open_hour",
    "thu_close_hour", "open_friday", "fri_open_hour", "fri_close_hour", "open_saturday", "sat_open_hour", "sat_close_hour", "open_sunday", "sun_open_hour", "sun_close_hour" ];
}

function get_dibraco_day_map(){
  $day_map = ['monday' => 'mon', 'tuesday' => 'tue', 'wednesday' => 'wed', 'thursday' => 'thu', 'friday' => 'fri', 'saturday' => 'sat', 'sunday' => 'sun'];
return $day_map;
}

function get_hours_of_operation_fields() {
$days = get_dibraco_day_map();
$fields =['open_247' =>[ 
            'type' => 'toggle',
            'label' => 'Open 24/7?', 
            'value' => "0",
            'options_label' => [ "1" => "Not 24/7", "0" => "24/7" ]
            ]
            ];

foreach ($days as $full_day => $short_day) {
    $upper_short_day = ucfirst($short_day);
    $fields["open_{$full_day}"]=[
        'type' => 'toggle',
        'label' => "{$full_day}_open",
        'value' => "1",
        'options_label' => ["0" => 'Open', '1' => 'closed']
        ]; 
    $fields["{$short_day}_open_hour"] = [
        'type' => 'time',
        'value' => '09:00:00',
        'condition' => ['field' => "open_{$full_day}", 'values' => ['1']], 
        ]; 
    $fields["{$short_day}_close_hour"] = [
        'type' => 'time',
        'value' => '17:00:00',
        'condition' => ['field' => "open_{$full_day}", 'values' => ['1']],
        ];
    if ($short_day !== 'mon') {
        $fields["{$short_day}_apply_prev"] = [
            'type' => 'button', 
            'label' => 'Apply Prev Hrs',
            'class' => 'apply-prev-day-times',
            'condition' => ['field' => "open_{$full_day}", 'values' => ['1']]
            ];
        }
    } return $fields; }
