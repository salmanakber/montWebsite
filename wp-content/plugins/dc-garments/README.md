# DC Product Manager

A WooCommerce extension for managing product details, supplier information, and automated product titles.

## Features

### Product Management
- Custom field for Fabric Color
- Product Category management
- MONTE NAPOLEONE FABRICS NO dropdown
- Shared price across variations
- Stock management per variation
- MOQ (Minimum Order Quantity) setting
- B2B product toggle

### Supplier Management
- Dedicated supplier post type
- Supplier information fields:
  - Supplier Name
  - SKU
  - Quality (Premium/Standard)
  - Fabric Width
  - Weight (GSM)
  - Price per meter

### Staff Access
- Custom staff role with limited permissions
- Focused interface for product and supplier management
- Secure access control

### Automated Product Titles
- Dynamic title generation based on:
  - Fabric Color
  - Product Category
  - MONTE NAPOLEONE FABRICS NO
- Live title preview
- Optional custom title override

### Stock Management
- Stock level tracking per variation
- Low stock alerts
- Stock history and notifications
- Email notifications for staff

## Installation

1. Upload the plugin files to the `/wp-content/plugins/dc-product-manager` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WooCommerce is installed and activated.
4. Configure the plugin settings under the Product Management menu.

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Usage

### Managing Products
1. Navigate to Products > Add New
2. Fill in the required fields:
   - Fabric Color
   - Product Category
   - MONTE NAPOLEONE FABRICS NO
3. Set the price (will apply to all variations)
4. Add variations if needed
5. Set stock levels for each variation
6. Configure MOQ and B2B settings

### Managing Suppliers
1. Go to Suppliers > Add New
2. Enter supplier details:
   - Name
   - SKU
   - Quality
   - Fabric Width
   - Weight
   - Price per meter
3. Save the supplier
4. Assign suppliers to products

### Staff Access
1. Create a new user with the 'DC Staff' role
2. Staff members can access:
   - Product management
   - Supplier management
   - Stock alerts
   - Limited admin functions

## Support

For support, please contact [your-email@example.com]

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0
- Initial release
- Basic product management features
- Supplier management
- Staff role implementation
- Automated title generation
- Stock management and alerts 