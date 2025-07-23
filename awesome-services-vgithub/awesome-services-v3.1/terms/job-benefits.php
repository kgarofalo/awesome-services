<?php
add_action('admin_menu', function () {
    $jobs_post_type = get_option('enabled_type_contexts')['jobs']['post_type'] ?? null;
    if (!$jobs_post_type) {
        return;
    }
    $parent_slugs = [
        "edit.php?post_type={$jobs_post_type}", // Jobs post type menu
        'relationships',                       // Relationships menu
    ];
    foreach ($parent_slugs as $parent_slug) {
        add_submenu_page(
            $parent_slug,
            'Manage Job Benefits',
            'Job Benefits',
            'manage_options',
            'manage-global-benefits',
            'render_global_benefits_page'
        );
    }
}, 20);

function render_global_benefits_page() {
    $default_benefits = [
        'health_benefits' => 'Health Insurance',
        'paid_time_off' => 'Paid Time Off',
        'retirement_plan' => 'Retirement Plan',
        'flexible_schedule' => 'Flexible Schedule',
        'tuition_reimbursement' => 'Tuition Reimbursement',
    ];
    $global_benefits = get_option('global_benefits', $default_benefits);
    if (empty($global_benefits)) {
        update_option('global_benefits', $default_benefits);
    }

    // Handle form submissions for adding or removing benefits
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle adding a new benefit
        if (!empty($_POST['new_benefit']) && $_POST['action'] === 'add') {
            $new_benefit = $_POST['new_benefit'];
            $benefit_key = strtolower(str_replace(' ', '_', $new_benefit));
            if (!isset($global_benefits[$benefit_key])) {
                $global_benefits[$benefit_key] = $new_benefit;
                update_option('global_benefits', $global_benefits);
            }
        }

        foreach ($global_benefits as $key => $benefit) {
            if (isset($_POST[$key])) {  
                unset($global_benefits[$key]); 
                update_option('global_benefits', $global_benefits);  
            }
        }
    }

    ?>
    <form method="post">
        <p><label for="new_benefit">Add New Benefit:</label><br />
        <input type="text" name="new_benefit" class="regular-text" required>
        <input type="hidden" name="action" value="add">
        <input type="submit" value="Add Benefit" class="button button-primary"></p>
    </form>

    <h2>Available Benefits</h2>
    <ul style="list-style-type: none; padding-left: 0;">
        <?php foreach ($global_benefits as $key => $benefit): ?>
            <li style="margin-bottom: 10px; display: flex; justify-content: flex-start; align-items: center;">
                <span style="margin-right: 10px;"><?php echo $benefit; ?></span>
                <form method="post" style="margin: 0;">
                    <!-- The benefit key is used directly as the name for the input field -->
                    <input type="hidden" name="<?= $key ?>" value="<?= $key ?>"> <!-- The field name is the benefit key -->
                    <button type="submit" style="font-size: 12px; background: none; color: #f44336; border: none; cursor: pointer; text-decoration: underline; padding: 0; margin-left: 10px;">Remove</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}

