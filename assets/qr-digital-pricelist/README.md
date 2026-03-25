# QR Digital Pricelist for Bars

A mobile-first digital pricelist/menu plugin for bars, accessible via QR code. Manage menu items, categories, and pricing through WordPress admin.

## Features

- **Mobile-first design** optimized for bar environments
- **Easy management** through WordPress admin interface
- **Flexible pricing** with multiple variants per item (sizes, volumes)
- **Category organization** with custom sorting
- **QR code ready** - display menu on any page via shortcode
- **Theme independent** - works with any WordPress theme
- **Gutenberg block** support for modern WordPress
- **Internationalization ready**

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the `qr-digital-pricelist.zip` file
4. Activate the plugin
5. Configure settings under **QR Digital Pricelist** → **Settings**

## Quick Setup

### 1. Create Categories
- Go to **QR Digital Pricelist** → **Categories**
- Add categories like "Beers", "Wines", "Spirits", "Cocktails"
- Set sort order to control display order

### 2. Add Menu Items
- Go to **QR Digital Pricelist** → **Items**
- Add new items with title and description
- Assign to primary category
- Add price variants (different sizes/volumes)

### 3. Configure Units
- Go to **QR Digital Pricelist** → **Units**
- Default units: ml, l, cup, shot, glass, bottle
- Add custom units as needed

### 4. Display Your Menu
- Create a new page called "Menu" or "Price List"
- Add the shortcode: `[qr_digital_pricelist]`
- Or use the "QR Digital Pricelist" Gutenberg block
- Generate a QR code pointing to this page

## Usage

### Shortcode

Basic usage:
```html
[qr_digital_pricelist]
```

With optional attributes:
```html
[qr_digital_pricelist category="beers"]
[qr_digital_pricelist show_disabled="1"]
[qr_digital_pricelist category="wines" show_disabled="0"]
```

**Attributes:**
- `category="slug"` - Display only items from specific category
- `show_disabled="1"` - Include disabled items and categories

### Gutenberg Block

In the WordPress block editor:
1. Add a new block
2. Search for "QR Digital Pricelist"
3. Configure block settings (category filter, show disabled)

## Admin Interface

### Main Menu Structure
- **Dashboard** - Overview and quick actions
- **Categories** - Manage menu categories
- **Items** - Manage menu items and pricing
- **Units** - Configure measurement units
- **Settings** - Currency symbol and venue name

### Categories
- Hierarchical organization
- Enable/disable toggle
- Custom sort order
- Description support

### Menu Items
- Title and description
- Primary category assignment
- Enable/disable toggle
- Custom sort order
- Multiple price variants:
  - Volume value (e.g., "0.5", "500")
  - Unit selection (ml, l, shot, etc.)
  - Price per variant
  - Enable/disable per variant
  - Sort order for variants

### Units
- CRUD interface for measurement units
- Slug + label configuration
- Used by item variants
- Default units seeded on activation

### Settings
- Currency symbol (default: €)
- Optional venue/display name
- Usage instructions included

## Frontend Display

The menu displays with:
- **Venue name** (if configured)
- **Categories** sorted by sort order then name
- **Items** sorted by sort order then title
- **Variants** sorted by sort order then volume
- **Mobile-first** responsive design
- **High readability** suitable for bar environments

## Data Model

### Custom Post Type: `qr_menu_item`
- Title: Item/beverage name
- Content: Description (optional)
- Featured Image: Item image (optional)
- Meta: enabled, sort_order, variants

### Taxonomy: `qr_menu_category`
- Hierarchical categories
- Meta: enabled, sort_order

### Item Variants (repeatable meta)
- `volume_value` - Volume/size value
- `unit_slug` - Unit identifier
- `price` - Price in decimal
- `enabled` - Enable/disable variant
- `sort_order` - Display order

### Units
- Stored in WordPress options
- Slug + label pairs
- Configurable via admin interface

## Security & Quality

- All input sanitized and validated
- All output escaped
- Nonce verification for admin actions
- Capability checks throughout
- Compatible with WordPress 6.x
- Compatible with PHP 8.0+
- Internationalization ready (text domain: `qr-digital-pricelist`)

## File Structure

```
qr-digital-pricelist/
├── qr-digital-pricelist.php          # Main plugin file
├── includes/
│   ├── cpt-taxonomies.php            # CPT and taxonomy registration
│   ├── admin-menu.php                # Admin menu structure
│   ├── category-meta.php             # Category meta fields
│   ├── item-meta.php                 # Item meta fields
│   ├── units.php                     # Units management
│   ├── settings.php                  # Settings page
│   ├── save-handlers.php             # Save handlers & validation
│   ├── shortcode.php                 # Frontend rendering
│   └── helpers.php                   # Helper functions
├── assets/
│   ├── admin.js                      # Admin JavaScript
│   ├── admin.css                     # Admin styles
│   └── frontend.css                  # Frontend styles
└── README.md                         # This file
```

## Testing Checklist

### Installation
- [ ] Plugin activates without errors
- [ ] Default units created on activation
- [ ] Admin menu items appear correctly
- [ ] Permalinks flushed properly

### Categories
- [ ] Can create/edit/delete categories
- [ ] Enable/disable toggle works
- [ ] Sort order affects display
- [ ] Bulk actions work (enable/disable)

### Menu Items
- [ ] Can create/edit/delete items
- [ ] Category assignment works
- [ ] Variants repeater functions correctly
- [ ] Add/remove variants works
- [ ] Enable/disable works for items and variants
- [ ] Sort order affects display

### Units
- [ ] Can view all units
- [ ] Can add new units
- [ ] Can edit existing units
- [ ] Can delete custom units (not defaults)
- [ ] Units appear in item variant dropdown

### Settings
- [ ] Can save currency symbol
- [ ] Can save venue name
- [ ] Settings persist correctly

### Frontend Display
- [ ] Shortcode renders menu
- [ ] Category filter works
- [ ] Show disabled option works
- [ ] Mobile responsive design
- [ ] Print styles work
- [ ] Dark mode support

### Gutenberg Block
- [ ] Block appears in block editor
- [ ] Block renders correctly
- [ ] Block settings work

### Security
- [ ] All admin actions require proper capabilities
- [ ] Nonce verification works
- [ ] Input sanitization works
- [ ] Output escaping works

## Support

For support and updates:
- Plugin by: Etherr
- Website: https://etherr.com

## License

GPL-2.0-or-later

---

**Version:** 1.0.0  
**Copyright:** © 2026 Etherr  
**Text Domain:** qr-digital-pricelist
