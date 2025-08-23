<?php
function get_dibraco_day_map(){
  $day_map = ['monday' => 'mon', 'tuesday' => 'tue', 'wednesday' => 'wed', 'thursday' => 'thu', 'friday' => 'fri', 'saturday' => 'sat', 'sunday' => 'sun'];
return $day_map;
}
function get_hours_array_keys(){
    return [ "open_247", "open_monday", "mon_open_hour", "mon_close_hour", "open_tuesday", "tue_open_hour", "tue_close_hour", "open_wednesday", "wed_open_hour", "wed_close_hour", "open_thursday", "thu_open_hour",
    "thu_close_hour", "open_friday", "fri_open_hour", "fri_close_hour", "open_saturday", "sat_open_hour", "sat_close_hour", "open_sunday", "sun_open_hour", "sun_close_hour" ];
}

function get_hours_of_operation_fields() {
    $days = get_dibraco_day_map(); 
       foreach ($days as $full_day => $short_day) {
           
        $upper_short_day = ucfirst($short_day);
        $day_fields["open_{$full_day}"] = [
            'type'          => 'toggle',
            'label'         => "$upper_short_day Open?",
            'value'         => "1",
            'options_label' => ["1" => 'Closed', "0" => 'Open']
        ];
        $day_fields[$short_day] = [
            'type'      => 'group',
            'condition' => ['field' => "open_$full_day", 'values' => ["1"]],
            'fields'    => [
                'open_hour'  => ['type' => 'time', 'value' => '09:00:00'],
                'close_hour' => ['type' => 'time', 'value' => '17:00:00'],
                'apply_prev' => ['type' =>'button', 'label' =>'Apply Prev Hrs', 'class' => 'apply-prev-day-times']
            ],
        ];

    }
    unset($day_fields['mon']['fields']['apply_prev']);
    $fields = [
        "open_247" => [
            'type'          => 'toggle',
            'label'         => 'Open 24/7?',
            'value'         => "0",
            'options_label' => ["1" => 'Not 24/7', "0" => '24/7'],
        ],
        "day_row" => [
            'type'      => 'visual_section',
            'fields'    => $day_fields,
            'condition' => ['field' => "open_247", 'values' => ["0"]]
        ],
    ];

    return $fields;
}

/*
      $day_fields[$short_day] = [
            'type'      => 'group',
            'condition' => ['field' => "open_$full_day", 'values' => ["1"]],
            'fields'    => [
                'open_hour'  => ['type' => 'time', 'value' => '09:00:00'],
                'close_hour' => ['type' => 'time', 'value' => '17:00:00'],
                'apply_prev' => ['type' =>'button', 'label' =>'Apply Prev Hrs', 'class' => 'apply-prev-day-times', 'condition' => ['field' => "{$prev_day}_open_hour", 'values' => ["1"]]]
            ],
        ];
         unset($day_fields['mon']['fields']['apply_prev']);
    }
        
*/       