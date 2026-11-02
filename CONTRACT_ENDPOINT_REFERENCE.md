# Contract Coverage and API Endpoint Reference

All endpoints below use the `/api/v1` prefix and require an authenticated Sanctum user unless stated otherwise.

## Contract Coverage Summary

| Contract area | Status | Notes |
|---|---|---|
| POS order recording and option-based pricing | **Satisfied** | Checkout accepts menu options, quantities, discounts, payment details, and deducts inventory atomically. |
| Order status monitoring | **Satisfied** | Orders can be filtered by `all`, `preparing`, `ready`, and `claimed`. |
| Automatic totals and discounts | **Satisfied** | Subtotal, discount, and total are calculated from `MenuOption.price` and coupon rules. |
| Cash change calculation | **Not satisfied** | The API does not currently accept a cash tendered amount or return change. |
| Editing pending orders | **Partially satisfied** | Status updates and admin voiding exist, but there is no endpoint to edit order items before confirmation. |
| Cancelling orders | **Partially satisfied** | Admin voiding exists; there is no separate cashier/staff cancellation workflow. |
| Category, menu, price, size, topping, and add-on management | **Satisfied for admin CRUD** | Prices are stored on `MenuOption`; supported option types include size, topping, combo, and add-on. |
| Staff management of cashier menu display | **Partially satisfied** | Admin and cashier can view menu data; only admin can create, edit, or delete menu configuration. |
| Promo bundles and discounts | **Satisfied** | Coupon CRUD supports percentage/fixed discounts, minimum purchase, cap, expiry, and active status. |
| Cash and manual GCash recording | **Satisfied** | GCash reference number is required; customer/mobile number is optional. |
| Completed payment transaction logging | **Satisfied** | Checkout creates a completed payment record linked to the order and user. |
| Inventory tracking and deduction | **Satisfied** | Inventory is deducted per selected menu option within a transaction. |
| Low-stock alerts | **Not satisfied** | No low-stock threshold, alert model, or alert endpoint currently exists. |
| Automatic stock-based product availability | **Partially satisfied** | Menu status can be manually changed; availability is not automatically derived from inventory. |
| Sales, revenue, payment, and product reports | **Satisfied at API level** | Sales report returns totals, products, sizes, toppings, and peak hours. |
| Visual analytics | **Partially satisfied** | The backend returns analytics JSON; no web dashboard or chart endpoint is included in this backend. |
| Role-based access and password reset | **Satisfied at backend level** | Admin/cashier policies and Fortify authentication/password reset are configured. |

## POS and Order Processing

### Calculate an order

`POST /api/v1/orders/calculate`

Request body:

```json
{
  "intent": "calculate",
  "discount_code": "SAVE10",
  "items": [
    {
      "product_option_id": 1,
      "quantity": 2
    }
  ]
}
```

Response contains `subtotal`, `discount`, and `total`.

### Checkout an order

`POST /api/v1/orders/checkout`

Request body:

```json
{
  "intent": "check-out",
  "payment_method": "cash",
  "discount_code": "SAVE10",
  "items": [
    {
      "product_option_id": 1,
      "quantity": 2
    }
  ]
}
```

For GCash:

```json
{
  "intent": "check-out",
  "payment_method": "gcash",
  "gcash_reference_number": "GCASH-12345",
  "gcash_customer_number": "09171234567",
  "items": [
    {
      "product_option_id": 1,
      "quantity": 1
    }
  ]
}
```

`gcash_reference_number` is required for GCash. `gcash_customer_number` is optional. Checkout creates the order, order items, payment record, and inventory deduction atomically.

> Cash change is not currently calculated. A future request body should add a field such as `cash_received`, and the response should return `change`.

### List orders

`GET /api/v1/orders?status=all`

Supported `status` values:

- `all`
- `preparing`
- `ready`
- `claimed`

### Update order status

`PUT /api/v1/orders/{order}/status`

Request body:

```json
{
  "status": "ready"
}
```

Allowed status values are `preparing`, `ready`, and `claimed`.

### Void an order

`POST /api/v1/orders/{order}/void`

Admin only.

Request body:

```json
{
  "reason": "customer_request"
}
```

Allowed reasons:

- `customer_request`
- `payment_issue`
- `incorrect_order`
- `unavailable_item`
- `other`

Voiding restores the inventory deducted during checkout and retains the payment record for audit purposes.

> There is currently no separate endpoint for editing pending order items or cashier cancellation.

## Menu, Product, Category, and Add-On Management

### Categories

| Method | Endpoint | Request body |
|---|---|---|
| `GET` | `/api/v1/categories` | None |
| `POST` | `/api/v1/categories` | `{"name":"Milk Tea"}` |
| `GET` | `/api/v1/categories/{category}` | None |
| `PUT/PATCH` | `/api/v1/categories/{category}` | `{"name":"Drinks"}` |
| `DELETE` | `/api/v1/categories/{category}` | None |

Category creation, update, and deletion are admin-only.

### Menus/products

| Method | Endpoint | Request body |
|---|---|---|
| `GET` | `/api/v1/menus` | None |
| `POST` | `/api/v1/menus` | `{"category_id":1,"name":"Classic Milk Tea","notes":"Cold drink"}` |
| `GET` | `/api/v1/menus/{menu}` | None |
| `PUT/PATCH` | `/api/v1/menus/{menu}` | `{"category_id":1,"name":"Classic Milk Tea","notes":"Updated"}` |
| `DELETE` | `/api/v1/menus/{menu}` | None |

Menu prices are intentionally not stored on `menus`; selling prices are stored on menu options.

### Menu status

| Method | Endpoint | Request body |
|---|---|---|
| `PUT` | `/api/v1/menus/{menu}/publish` | None |
| `PUT` | `/api/v1/menus/{menu}/deactivate` | None |
| `PUT` | `/api/v1/menus/{menu}/flag-pulled-out` | None |
| `PUT` | `/api/v1/menus/{menu}/mark-out-of-stock` | None |

### Sizes, toppings, combos, and add-ons

| Method | Endpoint | Request body |
|---|---|---|
| `GET` | `/api/v1/menus/{menu}/options` | None |
| `POST` | `/api/v1/menus/{menu}/options` | See example below |
| `GET` | `/api/v1/menu-options/{menu_option}` | None |
| `PUT` | `/api/v1/menu-options/{menu_option}` | Partial option fields |
| `DELETE` | `/api/v1/menu-options/{menu_option}` | None |

Create option body:

```json
{
  "name": "Large",
  "type": "size",
  "price": 25,
  "is_active": true,
  "sort_order": 1
}
```

Supported `type` values are `size`, `topping`, `combo`, and `addon`.

### Inventory requirements for a menu option

| Method | Endpoint | Request body |
|---|---|---|
| `GET` | `/api/v1/menus/{menu}/options/{menu_option}/contents` | None |
| `POST` | `/api/v1/menus/{menu}/options/{menu_option}/contents` | See example below |
| `PUT` | `/api/v1/menu-contents/{menu_content}` | Partial content fields |
| `DELETE` | `/api/v1/menu-contents/{menu_content}` | None |

Create menu-content body:

```json
{
  "inventory_id": 1,
  "name": "Tea Leaves",
  "stock_to_deduct_in_pcs": 2
}
```

## Inventory Management

| Method | Endpoint | Request body |
|---|---|---|
| `GET` | `/api/v1/inventories` | None |
| `POST` | `/api/v1/inventories` | `{"name":"Tea Leaves","type":5,"stock_in_pcs":100}` |
| `GET` | `/api/v1/inventories/{inventory}` | None |
| `PUT/PATCH` | `/api/v1/inventories/{inventory}` | `{"name":"Green Tea Leaves","type":5}` |
| `PUT` | `/api/v1/inventories/{inventory}/adjust-stock` | `{"stock_in_pcs":120}` |
| `DELETE` | `/api/v1/inventories/{inventory}` | None |

Inventory creation, updates, stock adjustment, and deletion are admin-only. Listing and viewing are available to authenticated cashier/admin users.

> Low-stock alerts are not currently exposed. A threshold field and endpoint such as `GET /api/v1/inventories/low-stock` are still needed to satisfy that contract item.

## Coupons and Discount Bundles

| Method | Endpoint | Request body |
|---|---|---|
| `GET` | `/api/v1/coupons` | None |
| `POST` | `/api/v1/coupons` | See example below |
| `GET` | `/api/v1/coupons/{coupon}` | None |
| `PUT/PATCH` | `/api/v1/coupons/{coupon}` | Partial coupon fields |
| `DELETE` | `/api/v1/coupons/{coupon}` | None |

Create coupon body:

```json
{
  "code": "SAVE10",
  "type": "percentage",
  "value": 10,
  "minimum_purchase": 100,
  "discount_cap": 50,
  "expires_at": "2026-12-31 23:59:59",
  "is_active": true
}
```

`type` may be `percentage` or `fixed`. Coupon management is admin-only; authenticated staff can list and view coupons.

## Payment Records and Transaction Logging

### Payment audit records

`GET /api/v1/payment-records`

Optional query filters:

```text
?payment_method=cash
?status=completed
?gcash_reference_number=GCASH-12345
?from=2026-11-01&to=2026-11-30
```

Payment records are created automatically by checkout and contain the payment method, amount, completed status, recorded user, and GCash details when applicable.

## Reports and Analytics

### Sales and revenue report

`GET /api/v1/orders/sales-report`

Optional query filters:

```text
?period=daily
?period=weekly
?period=monthly
?from=2026-11-01&to=2026-11-30
?payment_method=gcash
```

The response includes:

- sales order and item counts;
- subtotal, discounts, and total revenue;
- top-selling menu items;
- popular sizes;
- popular toppings;
- peak ordering hours.

The endpoint returns JSON analytics for a frontend dashboard. Visual charts are not generated by this backend.

## Authentication and Access

The API uses `auth:sanctum`. The public authenticated-user endpoint is:

`GET /api/user`

The application also uses Laravel Fortify for authentication and password reset flows through the configured authentication routes.

