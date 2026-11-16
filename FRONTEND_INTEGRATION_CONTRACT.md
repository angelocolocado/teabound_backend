# TeaBound Frontend Integration Contract

This document is the current backend contract for the Copilot UI. The API is
served under `/api`; all `/api/v1/*` endpoints require an authenticated
Laravel Sanctum session. JSON resources are returned as top-level objects or
arrays (not an additional `{ "data": ... }` wrapper).

## Authentication

| Method | Endpoint | Contract |
|---|---|---|
| `GET` | `/api/user` | Returns the authenticated user. |
| `POST` | `/logout` | Ends the current session. |

Unauthenticated API requests return `401`. Validation failures return `422`
with Laravel's `message` and `errors` fields. Forbidden role actions return
`403`.

## Menu and categories

All list/show responses include `id`, timestamps, and the fields shown below.
Names are title-cased in resource responses.

| Method | Endpoint | Request |
|---|---|---|
| `GET` | `/api/v1/categories` | None |
| `POST` | `/api/v1/categories` | `{"name":"Milk Tea"}` |
| `GET` | `/api/v1/categories/{category}` | None |
| `PUT/PATCH` | `/api/v1/categories/{category}` | `{"name":"Drinks"}` |
| `DELETE` | `/api/v1/categories/{category}` | None |
| `GET` | `/api/v1/menus` | None |
| `POST` | `/api/v1/menus` | `{"category_id":1,"name":"Classic Milk Tea","notes":"Cold drink"}` |
| `GET` | `/api/v1/menus/{menu}` | None |
| `PUT/PATCH` | `/api/v1/menus/{menu}` | `category_id`, `name`, optional `notes` |
| `DELETE` | `/api/v1/menus/{menu}` | None |
| `POST` | `/api/v1/menus/{menu}/image` | Multipart field `image` (image, max 5 MB) |
| `GET` | `/api/v1/menus/{menu}/options` | None |
| `POST` | `/api/v1/menus/{menu}/options` | See option contract below |
| `GET` | `/api/v1/menu-options/{menu_option}` | None |
| `PUT` | `/api/v1/menu-options/{menu_option}` | Partial option fields |
| `DELETE` | `/api/v1/menu-options/{menu_option}` | None |

Menu response:

```json
{
  "id": 1,
  "name": "Classic Milk Tea",
  "category": "Milk Tea",
  "notes": "Cold drink",
  "status": "active",
  "image_url": "http://localhost/storage/menu-images/example.jpg",
  "menu_options": []
}
```

The image endpoint returns the updated menu resource. Admin users may publish,
deactivate, flag pulled-out, or mark out-of-stock:

```text
PUT /api/v1/menus/{menu}/publish
PUT /api/v1/menus/{menu}/deactivate
PUT /api/v1/menus/{menu}/flag-pulled-out
PUT /api/v1/menus/{menu}/mark-out-of-stock
```

Menu option request and response fields:

```json
{
  "name": "Large",
  "type": "size",
  "price": 25,
  "is_active": true,
  "sort_order": 1
}
```

`type` is one of `size`, `topping`, `combo`, or `addon`. A successful create
returns the created option with its numeric `id` and `menu_id` at the top
level. Menu option contents use:

```text
GET/POST /api/v1/menus/{menu}/options/{menu_option}/contents
PUT/DELETE /api/v1/menu-contents/{menu_content}
```

The create request is
`{"inventory_id":1,"name":"Tea Leaves","stock_to_deduct_in_pcs":2}`.

## Inventory

| Method | Endpoint | Request |
|---|---|---|
| `GET` | `/api/v1/inventories` | None |
| `GET` | `/api/v1/inventories/low-stock` | None |
| `POST` | `/api/v1/inventories` | See below |
| `GET` | `/api/v1/inventories/{inventory}` | None |
| `PUT/PATCH` | `/api/v1/inventories/{inventory}` | Metadata and threshold fields |
| `PUT` | `/api/v1/inventories/{inventory}/adjust-stock` | `{"stock_in_pcs":120}` |
| `DELETE` | `/api/v1/inventories/{inventory}` | None |

Create/update fields:

```json
{
  "name": "Tea Leaves",
  "type": 5,
  "stock_in_pcs": 100,
  "low_stock_threshold": 20,
  "display_unit": "bags"
}
```

`type` codes are `5` ingredients, `10` supplies, and `15` products.
`stock_in_pcs` is the current absolute stock; `adjust-stock` replaces it,
rather than incrementing it. Inventory responses include `id`, `name`, `type`,
`type_code`, `stock_in_pcs`, `low_stock_threshold`, `display_unit`, and
timestamps. The low-stock endpoint returns items where
`stock_in_pcs <= low_stock_threshold`. Inventory writes are admin-only;
authenticated admin/cashier users can read inventory.

## Orders and checkout

### Calculate

`POST /api/v1/orders/calculate`

```json
{
  "intent": "calculate",
  "discount_code": "SAVE10",
  "items": [{"product_option_id": 1, "quantity": 2}]
}
```

Response:

```json
{"subtotal":200,"discount":20,"total":180}
```

### Checkout

`POST /api/v1/orders/checkout`

```json
{
  "intent": "check-out",
  "payment_method": "cash",
  "cash_received": 200,
  "items": [{"product_option_id": 1, "quantity": 2}]
}
```

`cash_received` is optional for backward compatibility. When supplied for a
cash payment it must be at least the calculated total; the persisted payment
record and checkout response include `cash_received` and `change_amount`.
Without it, both values are `null`. GCash requires
`gcash_reference_number`; `gcash_customer_number` is optional.

The `201` response is:

```json
{
  "order": {
    "id": 1,
    "order_number": "ORD-20261116202527-ABCD",
    "status": "preparing",
    "subtotal": "200.00",
    "discount_amount": "20.00",
    "total": "180.00",
    "items": [],
    "payment_record": {
      "payment_method": "cash",
      "status": "completed",
      "amount": "180.00",
      "cash_received": "200.00",
      "change_amount": "20.00"
    }
  }
}
```

Checkout deducts inventory and creates the order, order items, payment record,
and audit record atomically. Insufficient stock or an invalid discount returns
`422` with `message` and, for stock shortages, `shortages`.

### Order operations

| Method | Endpoint | Request/response |
|---|---|---|
| `GET` | `/api/v1/orders?status=all` | Array of orders; status may be `all`, `preparing`, `ready`, or `claimed`. |
| `PUT` | `/api/v1/orders/{order}/status` | `{"status":"ready"}`; returns the updated order. |
| `POST` | `/api/v1/orders/{order}/void` | `{"reason":"customer_request"}`; returns the voided order and restores stock. |

Void reasons are `customer_request`, `payment_issue`, `incorrect_order`,
`unavailable_item`, and `other`. Voiding is admin-only.

## Reports and payment records

`GET /api/v1/orders/sales-report` accepts `period=daily|weekly|monthly`,
optional `from`, `to`, and `payment_method` filters. The exact response is:

```json
{
  "sales": {"orders": 12, "items": 31},
  "revenue": {"subtotal": 1200, "discounts": 80, "total": 1120},
  "top_selling_items": [{"name": "Classic Milk Tea", "quantity": 10}],
  "popular_sizes": [{"name": "Large", "quantity": 8}],
  "popular_toppings": [{"name": "Pearls", "quantity": 6}],
  "peak_ordering_hours": [{"hour": "14:00", "orders": 5}]
}
```

`GET /api/v1/payment-records` returns payment records and accepts
`payment_method`, `status`, `gcash_reference_number`, `from`, and `to`.

## Login history and audit activity

| Method | Endpoint | Query |
|---|---|---|
| `GET` | `/api/v1/login-history` | Optional `limit` (default 100, max 500) |
| `GET` | `/api/v1/audit-activity` | Optional `limit` (default 100, max 500) |

Login history items contain `id`, `name`, `role`, `date`, `time`,
`activity_type` (`login` or `logout`), and `occurred_at`. Audit items contain
`id`, `action`, `description`, `status`, `user`, `timestamp`, and `metadata`.
Checkout, void, and stock adjustment actions are recorded automatically.

## Roles

Admin users can manage categories, menus, options, inventory, coupons, menu
images, and void orders. Admin and cashier users can read menu, inventory,
order, payment, report, login-history, and audit endpoints, and can perform
checkout and order status updates.
