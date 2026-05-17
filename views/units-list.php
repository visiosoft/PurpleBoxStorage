<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Storage Inventory', 'purplebox-storage'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-unit-edit')); ?>" class="page-title-action">
        <?php esc_html_e('Add Inventory', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['saved'])) : ?>
        <?php if ($_GET['saved'] === 'created') : ?>
            <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e('Unit added to inventory.', 'purplebox-storage'); ?></p></div>
        <?php else : ?>
            <div class="notice notice-success is-dismissible"><p>✅ <?php esc_html_e('Unit updated successfully.', 'purplebox-storage'); ?></p></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Inventory item deleted.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <?php if (isset($_GET['bulk_created'])) : ?>
        <div class="notice notice-success is-dismissible"><p>
            <?php printf(esc_html__('%d units created successfully.', 'purplebox-storage'), (int) $_GET['bulk_created']); ?>
        </p></div>
    <?php endif; ?>

    <?php if (isset($_GET['seed_imported'])) : ?>
        <div class="notice notice-success is-dismissible"><p>
            <?php printf(esc_html__('Excel import complete: %d units inserted, %d skipped (already exist), %d marked as rented.', 'purplebox-storage'), (int) $_GET['seed_imported'], (int) ($_GET['seed_skipped'] ?? 0), (int) ($_GET['seed_rented'] ?? 0)); ?>
        </p></div>
    <?php endif; ?>

    <form method="get">
        <input type="hidden" name="page" value="purplebox-units">
        <?php $table->search_box(__('Search', 'purplebox-storage'), 'unit'); ?>
        <?php $table->display(); ?>
    </form>
</div>
