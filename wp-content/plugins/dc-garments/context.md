# Product Management Plugin – Context & Requirements

## Overview

This plugin is intended to extend WooCommerce functionality by enabling staff members to manage key product details without full admin access. The goal is to provide a structured way to update product information, including custom attributes, pricing, supplier data, and stock alerts, while maintaining consistency and data integrity across all products.

---

## Functional Requirements

### 1. Product Data Management by Staff

Staff users need limited yet powerful access to manage products efficiently. The following fields should be editable:

- **Custom Field: Fabric Color**
  - A text field representing the fabric's color.
  - This will be used in the product title and for customer clarity.

- **Product Category**
  - Allow staff to change the product’s category so products can be organized appropriately.
  - Categories also influence the auto-generated product title.

- **MONTE NAPOLEONE FABRICS NO (Dropdown)**
  - A dropdown field with predefined values that staff can choose from.
  - This value is important for identifying premium fabrics.
  - Will also be used in the product title.

- **Price (Shared Across Variations)**
  - A single price field that applies to all product variations.
  - Staff should be able to update it once, and it will automatically apply to every variation.
  - This simplifies pricing management when all variants have the same cost.

- **Stock in pcs (Per Variation)**
  - Each variation's stock level (in number of pieces) must be updated individually.
  - Important for accurate stock tracking and alerts.

- **MOQ (Minimum Order Quantity)**
  - A custom numeric field indicating the minimum quantity that can be ordered.
  - Useful for wholesale or B2B customers.

- **B2B Product Toggle**
  - A simple switch (yes/no) to indicate whether a product is available for B2B customers.
  - Helps filter and manage B2B-exclusive products separately.

---

### 2. Supplier Information (Per Product)

Each product should be linked to a **dedicated supplier**, and the supplier’s information should be visible and editable directly in the product interface.

#### Explanation of Fields:

- `Supplier Name`: Name of the fabric or material supplier.
- `SKU`: Supplier's internal reference number for the product.
- `Quality`: Description of material quality (e.g., Premium, Standard).
- `Fabric Width`: The width of the fabric in centimeters or inches.
- `Weight (GSM)`: Fabric density, measured in grams per square meter.
- `Supplier Price ($ per meter)`: The price you pay to the supplier for 1 meter of fabric.

> Staff should be able to either select an existing supplier or create a new one when editing a product.

---

## Staff Access

- Staff should **log in with separate credentials** using a dedicated WordPress user role.
- They will not have access to full admin functionalities.
- Their role will focus exclusively on managing products and suppliers using the plugin’s interface.

---

## Notifications & Alerts

- Implement a **notification/alert system** to automatically notify staff when product stock (for any variation) falls below a certain threshold.
- Helps ensure timely restocking and prevents overselling.

---

## Product Title Automation

The product title should be **automatically generated** and **dynamically updated** based on selected or input values in the product edit screen. This ensures a standardized naming format across all products and simplifies product identification.

### 🔧 Product Title Format Logic

The final product title must be generated using a **combination of the following three fields**:

1. **Fabric Color** (custom field)
2. **Product Category Name**
3. **MONTE NAPOLEONE FABRICS NO** (dropdown)

### 🧩 Concatenation Format:

```
{Fabric Color} {Product Category} #{MONTE NAPOLEONE FABRICS NO}
```

> 🔁 This should update automatically whenever **any** of the three fields are changed.

### 💡 Examples:

| Fabric Color | Category Name | Fabric No | Final Title                     |
|--------------|----------------|-----------|----------------------------------|
| BEIGE        | LINSKJORTER    | L5        | BEIGE LINSKJORTER #L5            |
| NAVY         | BLAZERS        | F10       | NAVY BLAZERS #F10                |
| IVORY        | TROUSERS       | M1        | IVORY TROUSERS #M1              |

### ⚙️ Implementation Notes

- The plugin should **automatically update the WooCommerce product title** during both:
  - **Product creation**
  - **Product updates (when any of the three fields are modified)**

- Admins and staff should **not need to manually type the title**.

- A small **preview area** or field above the title can be shown to display the live generated title.

- **Slug (URL)** should also be updated according to the title (if product is being created).

### 🛡️ Validation

- Ensure all three components exist before generating the title.
- If any of the values are missing (e.g., fabric color is blank), title generation should either:
  - Show a validation warning
  - Or fallback to a default

### 🧩 Optional Enhancement:

Allow toggling between **auto-generated titles** and **manual override** with a checkbox:

```
[ ] Use custom title instead of auto-generated
```

If checked, the user can manually type a title.

---

## Technical Requirements

- Must be developed as a **standalone WordPress plugin**.
- Built using **WordPress and WooCommerce coding standards**.
- Support for **product variations** is essential.
- Use WordPress APIs to register and manage custom fields.
- Organize code into separate classes/modules:
  - Product Handler
  - Supplier Manager
  - Staff Role Manager
  - Notification System
  - Title Generator

- Admin UI should be user-friendly, clean, and intuitive for non-technical staff.

---

## Optional Enhancements

- **AJAX-based Saving**: To improve performance and reduce page reloads.
- **Activity Logs**: Track what staff users updated and when.
- **Dashboard Widget**: A visual summary showing low stock alerts or supplier price changes.

---

## Notes

- Dropdown options for **MONTE NAPOLEONE FABRICS NO** will be hardcoded for simplicity.
- Supplier creation and management may be done through:
  - A custom post type (`Supplier`)
  - A modal or dropdown selector integrated into the product edit screen

