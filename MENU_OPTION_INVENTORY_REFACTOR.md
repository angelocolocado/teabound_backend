# Commit Message

```text
refactor: deduct inventory per menu option
```

# Menu Option Inventory Refactor

## Overview

The inventory flow was refactored to match the POS behavior: products are sold per
`MenuOption`, not directly per `Menu`.

Prices remain independent on `MenuOption`. The menu-level `price` field is no longer
used.

This supports:

- Cup sizes with different inventory requirements.
- Toppings and add-ons with their own inventory requirements.
- Multiple menu options sharing the same inventory item.
- Independent prices per menu option.
- Accurate stock deduction based on the selected option.

## Relationship Changes

### Previous relationship

```text
Menu
├── MenuContents
└── MenuOptions
```

With this structure, every option under one menu used the same menu contents. A
large cup, small cup, and topping could not have different inventory requirements.

### Current relationship

```text
Menu
└── MenuOptions
    └── MenuContents
        └── Inventory
```

The model relationships are now:

```text
Menu hasMany MenuOption
MenuOption belongsTo Menu
MenuOption hasMany MenuContent
MenuContent belongsTo MenuOption
MenuContent belongsTo Inventory
```

`Menu` no longer owns a direct `menuContents()` relationship.

## MenuContent Schema Changes

`menu_contents` now uses `menu_option_id` to identify the option that requires the
inventory item.

Relevant fields:

```text
menu_option_id
inventory_id
name
stock_to_deduct_in_pcs
```

The migration makes the existing `menu_id` column nullable for forward
compatibility. New menu content records should use `menu_option_id`.

A unique constraint prevents assigning the same inventory item more than once to
the same menu option:

```text
(menu_option_id, inventory_id)
```

## Example Configuration

```text
Large Milk Tea
├── Tea Leaves: 2 pcs
└── Large Cup: 1 pc

Small Milk Tea
├── Tea Leaves: 1 pc
└── Small Cup: 1 pc

Pearls Topping
└── Pearls: 1 serving
```

Each selected option now deducts only the inventory requirements configured for
that option.

## MenuContent API

### List contents for a menu option

```http
GET /api/v1/menus/{menu}/options/{menu_option}/contents
```

The route uses scoped model binding. If the menu option does not belong to the
specified menu, the API returns `404`.

### Add content to a menu option

```http
POST /api/v1/menus/{menu}/options/{menu_option}/contents
```

Example request:

```json
{
  "inventory_id": 1,
  "name": "tea leaves",
  "stock_to_deduct_in_pcs": 2
}
```

Only administrators can create menu contents.

### Update content

```http
PUT /api/v1/menu-contents/{menu_content}
```

Only administrators can update menu contents.

### Delete content

```http
DELETE /api/v1/menu-contents/{menu_content}
```

Only administrators can delete menu contents.

## Menu API Responses

Menu responses now load option contents through the complete relationship chain:

```text
Menu
└── menu_options
    └── menu_contents
        └── inventory
```

This allows the cashier interface to receive each menu option together with its
own inventory requirements.

## Inventory Deduction

`InventoryService::deductInventory()` now receives selected menu options through
the existing order item structure:

```json
{
  "items": [
    {
      "product_option_id": 10,
      "quantity": 2
    }
  ]
}
```

The service:

1. Validates option IDs and quantities.
2. Aggregates duplicate order items by `product_option_id`.
3. Loads selected options with their menu contents and inventory records.
4. Rejects missing or inactive options.
5. Calculates the total requirement per inventory item.
6. Locks relevant inventory rows.
7. Checks all stock requirements before updating anything.
8. Deducts inventory inside one database transaction.

## Shared Inventory Aggregation

When multiple selected options use the same inventory item, requirements are
combined before deduction.

Example:

```text
Large Size:
Tea Leaves x 2

Pearls Topping:
Tea Leaves x 1

Order:
Large Size x 2
Pearls Topping x 3
```

The final deduction is:

```text
Tea Leaves = (2 x 2) + (1 x 3)
Tea Leaves = 7 pcs
```

## Transaction and Stock Safety

Inventory deduction is atomic.

If any selected option does not have enough stock:

- No inventory is deducted.
- The database transaction is rolled back.
- Checkout returns HTTP `422`.
- The response includes shortage details.

Example response:

```json
{
  "message": "Insufficient inventory for one or more menu options.",
  "shortages": [
    {
      "inventory_id": 1,
      "required": 10,
      "available": 4
    }
  ]
}
```

This prevents partial deductions when one inventory requirement fails after
another requirement has already been checked.

## Order Validation

Order quantities must be positive integers:

```text
items.*.quantity: required|integer|min:1
```

Selected options must exist:

```text
items.*.product_option_id: required|integer|exists:menu_options,id
```

Invalid, missing, or inactive options return HTTP `422` during checkout.

## Checkout Response

A successful checkout response includes the checkout calculation and inventory
deduction result:

```json
{
  "checkout": {
    "total": 150
  },
  "inventory": {
    "deducted": {
      "1": 7,
      "2": 3
    }
  }
}
```

The checkout total continues to use the selected `MenuOption` prices.

## Migrations

The refactor adds these migrations:

```text
2026_11_03_000001_add_menu_option_id_to_menu_contents_table.php
2026_11_03_000002_unique_menu_option_inventory_contents.php
```

Run the migrations with:

```bash
php artisan migrate
```

For a clean local database:

```bash
php artisan migrate:fresh
```

Existing menu content records should be assigned to the correct
`menu_option_id` before the legacy `menu_id` column is removed in a future
cleanup migration.

## Tests

Regression coverage includes:

- Listing contents belonging to a menu option.
- Creating contents for a menu option.
- Rejecting an option from another menu through scoped binding.
- Deducting inventory from the selected option only.
- Aggregating shared inventory requirements.
- Preventing partial deductions when stock is insufficient.
- Validating positive order quantities.
- Returning checkout inventory errors as HTTP `422`.

Latest validation result:

```text
38 tests passed
127 assertions passed
```

## Scramble Documentation

The refactored controllers and endpoints use PHPDoc blocks for automated
Scramble documentation. No separate API specification file is required.

To verify the generated OpenAPI document:

```bash
php artisan scramble:export --stdout --silent
```
