<?php

function get_global_benefit_fields() {
 $default_benefit_field_names = ['health_benefits', 'paid_time_off', 'medical', 'flexible_schedule', '401k', 'tuition_reimbursement'];

 $field_names =  get_option('global_benefits', []);
  if (empty($field_names)){    
      foreach ($default_benefit_field_names as $benefit_name){
          $field_names[$benefit_name] = $benefit_name;
      }
     }
   $fields = [];
    foreach ($field_names as $field_name) {
    foreach ($field_names as $field_name => $also_field_name) {
        $field_config = ['type' => 'checkbox', 'value' => '0'];
       $fields[$field_name] = $field_config;
    }
    return $fields;
}
function render_global_benefits_page() {
  $default_benefit_field_names = ['health_benefits', 'paid_time_off', 'medical', 'flexible_schedule', '401k', 'tuition_reimbursement'];

  $option_key = 'global_benefits';
  $field_names =  get_option($option_key, []);
      if (empty($field_names)){    
      foreach ($default_benefit_field_names as $benefit_name){
          $field_names[$benefit_name] = $benefit_name;
      }
     }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add'])) {
            $new_field_name = strtolower(str_replace(' ', '_', sanitize_text_field( $_POST['new_job_benefit_field'])));
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
         <h1>Job Posting Benefits</h1>
         <form method="post">
              <p><label for="new_benefit">Add New Benefit:</label><br />
             <?= FormHelper::generateField('new_job_benefit_field', ['type' => 'text']); ?>
             <button type="submit" name="add" value="new_job_benefit_field" class="button button-primary">Add New Field</button>
              <hr>
               <h2>Available Benefits</h2>
                <ul style="list-style-type: none; padding-left: 0;">
                 <? foreach ($field_names as $field_name){
                     $printed = ucwords(str_replace('_', ' ', $field_name)); ?>
                    <li>
                    <?= $printed ?>
                   <button type=submit name=delete value=<?=$field_name?> class=button button-secondary button-small style=margin-left:10px> Delete </button>
                   </li>
                   <?    
                }
               ?>
             </ul>
         </form>
     </div>
    <?php
}
 