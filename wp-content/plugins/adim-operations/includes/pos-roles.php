<?php
/**
 * ADIM POS dedicated permissions.
 *
 * Cashier can only use the POS screen; no WooCommerce management privileges.
 */
defined('ABSPATH') || exit;

/**
 * Installer can safely run again after a site upgrade.
 * Woo managers/admins retain access; cashiers receive only read + pos sale.
 */
function adim_pos_install_roles(): void
{
    add_role('adim_cashier', __('ADIM Kasiyer', 'adim-operations'), [
        'read' => true,
        'adim_pos_sell' => true,
    ]);

    foreach (['administrator', 'shop_manager'] as $role_name) {
        $role = get_role($role_name);
        if ($role && !$role->has_cap('adim_pos_sell')) {
            $role->add_cap('adim_pos_sell', true);
        }
    }
}

function adim_pos_can_sell(): bool
{
    return current_user_can('adim_pos_sell');
}

function adim_pos_can_reconcile(): bool
{
    // Deliberately exclude the cashier role from acknowledging uncertain stock.
    return current_user_can('manage_woocommerce');
}
