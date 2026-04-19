# Salon Reservations (MVP)

Backbone plugin for a hair salon reservation system.

## Setup
1. Activate the plugin from the WordPress admin.
2. Ensure employee users exist (role: Editor).
3. Go to **Salon Reservations → Employees** and sync employees.
4. Go to **Salon Reservations → Settings** and adjust buffer/lead time.
5. Add the shortcode `[salon_reservations]` to a page.

## Assumptions
- **Time slot granularity** is 15 minutes (configurable in Settings).
- **One reservation = one service**.
- **Employee = WP user** with role Editor (employee ID stored as `wp_users.ID`).
- **Time storage**: all `*_datetime` fields are stored in **UTC**. Inputs/outputs in the UI use the site timezone.
- **Opening hours (24-hour format)**:
  - Monday–Friday: 08:00–20:00
  - Saturday: 08:00–13:00
  - Sunday: Closed
- **Shift templates (24-hour format)**:
  - 08:00–16:00
  - 14:00–20:00
  - 10:00–18:00

## Roles & Capabilities
Capabilities are added on activation. Roles are only for defaults.
- Administrator: all `salon_*` capabilities.
- Editor: `salon_manage_shifts_own`, `salon_view_reservations_own`, `salon_request_shift_change`.

## Database Tables
- `wp_salon_employees`
- `wp_salon_services`
- `wp_salon_shifts`
- `wp_salon_reservations`
- `wp_salon_shift_change_requests`

## Shortcode
`[salon_reservations]` renders:
- Employee selector
- Service selector
- Date picker + available slots
- Reservation request form

## REST API
- `GET /wp-json/salon/v1/slots`
  - Params: `employee_id`, `service_id`, `start_date` (YYYY-MM-DD), `end_date` (optional)
- `POST /wp-json/salon/v1/reservations`
  - Body: `employee_id`, `service_id`, `start_datetime` (ISO), `customer_name`, `customer_email`, `customer_phone`, `notes`, `nonce`

## Uninstall Cleanup
By default, the plugin **drops all tables** and removes options on uninstall.
- Option: `salon_reservations_uninstall_cleanup` (set to `no` to retain data).

## TODO / Future Features
- Payments integration
- SMS notifications
- Customer “My Reservations” page
- Google Calendar sync per employee
- Multiple locations
- Recurring shifts/templates
- Holidays/blackout dates
- Capacity > 1 per slot
- Employee pre-approval workflow

## Next Milestones
1. Add employee management UI and mapping tools.
2. Add full reservation approval workflow with employee pre-approval.
3. Add customer account and reservation history.
4. Add reporting and analytics.
