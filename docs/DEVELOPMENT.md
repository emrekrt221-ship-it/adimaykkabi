# Local development

Install a supported PHP/MySQL environment and WordPress, then WooCommerce. Copy this repository's `wp-content/themes/adim` to the WordPress themes directory, activate the theme and configure a primary menu. Enable WooCommerce HPOS and configure sample products that are legally authorized for sale. Never use live card details in development.

## Validation
- Run `php -l` on every PHP theme file.
- Verify theme activation, responsive layout and WooCommerce catalog with a local WordPress install.
- Run automated browser and payment sandbox tests before launch.

## Security
Keep WordPress core, plugins and customer uploads outside git. Never commit wp-config.php, secrets or customer information. Use HTTPS and offsite backups in production.
