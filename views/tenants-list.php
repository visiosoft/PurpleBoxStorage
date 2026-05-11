<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Tenants', 'purplebox-storage'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-tenants&action=edit&tenant_id=0')); ?>" class="page-title-action">
        <?php esc_html_e('Add New Tenant', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])) : ?>
        <?php if ($_GET['saved'] === 'created') : ?>
            <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e('Tenant added successfully.', 'purplebox-storage'); ?></p></div>
        <?php else : ?>
            <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e('Tenant updated successfully.', 'purplebox-storage'); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Tenant deleted.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <?php $table->views(); ?>

    <form method="get">
        <input type="hidden" name="page" value="purplebox-tenants">
        <?php if (!empty($_REQUEST['tenant_status'])) : ?>
            <input type="hidden" name="tenant_status" value="<?php echo esc_attr($_REQUEST['tenant_status']); ?>">
        <?php endif; ?>
        <?php $table->search_box(__('Search Tenants', 'purplebox-storage'), 'tenant'); ?>
        <?php $table->display(); ?>
    </form>
</div>
