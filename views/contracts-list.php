<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap purplebox-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Contracts', 'purplebox-storage'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=purplebox-contract-new')); ?>" class="page-title-action">
        <?php esc_html_e('New Contract', 'purplebox-storage'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (isset($_GET['ended'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Contract ended. Unit returned to available inventory.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Contract deleted.', 'purplebox-storage'); ?></p></div>
    <?php endif; ?>

    <?php $table->views(); ?>

    <form method="get">
        <input type="hidden" name="page" value="purplebox-contracts">
        <?php if (!empty($_REQUEST['contract_status'])) : ?>
            <input type="hidden" name="contract_status" value="<?php echo esc_attr($_REQUEST['contract_status']); ?>">
        <?php endif; ?>
        <?php $table->display(); ?>
    </form>
</div>
