# Backend integration gaps

The admin page now uses Axios for the documented operations that match the
existing controls:

- Authenticated user context: `GET /api/user`
- Logout: `POST /logout`
- Menu product creation: `POST /api/v1/categories`, `POST /api/v1/menus`,
  and `POST /api/v1/menus/{menu_id}/options`
- Sales dashboard data: `GET /api/v1/orders/sales-report` for daily, weekly,
  and monthly periods

The following backend capabilities are still needed before the remaining
screens can use live data without guessing undocumented routes or response
shapes.

## Inventory screen

Add a documented endpoint such as `GET /api/v1/inventories` returning, for
each item:

- `id`
- `name`
- `type`
- `stock_in_pcs`
- a low-stock threshold
- a display unit, if the UI should continue showing units other than pieces

The existing stock adjustment endpoint is documented as
`PUT /api/v1/inventories/{inventory_id}/adjust-stock`, but the page cannot use
it reliably until inventory items can be listed with their real IDs. The
backend should also document whether `stock_in_pcs` is the current absolute
stock or an increment.

## Categories

Add `GET /api/v1/categories`, or a documented category lookup by name. The
current product form contains category names, while menu creation requires a
numeric `category_id`. The current integration creates a category when that
name has not already been created during the current page session; a lookup
endpoint is needed to avoid duplicate categories across page loads.

## Login history and audit activity

The guide does not define an endpoint for either:

- login history records (`name`, `role`, date, time, and activity type)
- admin audit records (action, description, timestamp, and status)

Add separate documented read endpoints and response fields so the Login
History screen and Dashboard Audit tab can stop using local placeholder data.

## Product photos

The product form accepts an image, but the menu creation API documented in the
guide has no image field or upload endpoint. Add a documented upload flow and
the field that associates the uploaded asset with a menu product, or remove
the expectation that the selected photo is persisted.

## Product creation response contracts

The frontend needs the created IDs from category, menu, and menu-option
responses. The backend should document a stable response shape, preferably
`{ "data": { "id": ... } }`, for those endpoints.

The form's `Amount` field is currently sent as the menu option `price`, and
`Available Size` is sent as a `size` option named `Regular` when blank. Confirm
that this matches the intended product model. If products can have multiple
sizes, the UI needs a repeatable option-creation flow or a bulk option
request.

## Sales report response contract

The guide lists the report contents but does not define exact property names
or nesting. Document the response keys for revenue totals and peak ordering
hours so the dashboard can consume them without compatibility heuristics.

## Cash payment change

The guide states that cash checkout does not yet accept `cash_received` or
return change. Add those fields if the cashier flow must calculate and display
change from backend-validated values.
