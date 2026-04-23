# Etherr Services Section — Implementation Brief for Codex

## Goal
Update the existing Etherr website services section by replacing all current generic content with the final content defined in `01-services-content-spec.md`.

This is a content and component-logic update, not a redesign.

---

## Required Outcome
The existing services section must keep its current design language and interaction model while displaying richer content:
- group label
- service title
- service description
- visible “Includes” list in the active detail panel
- service counter/index

The contact/intake section must also be updated so its selectable options exactly match the final service taxonomy.

---

## Non-Negotiable Constraints

### 1. Preserve the existing visual system
Do not redesign the section.
Keep:
- existing layout
- spacing rhythm
- card treatment
- border radius
- shadows
- typography hierarchy
- hover/active states
- responsive behavior
- Etherr color language

### 2. Preserve the interaction pattern
Keep the current interaction logic:
- service groups and selectable service items on the left
- selected service detail card on the right
- default active item on initial load

### 3. Extend the active detail card
The active service detail card must show:
- service title
- short description
- “Includes” heading
- bullet list of included items
- group name
- service counter (e.g. `01 / 02`, `02 / 03`)

### 4. Fit within the current design
The new content must feel native to the current interface.
If necessary, adjust only minor internal spacing/padding/line-height/text-size so the content fits elegantly.
Do not introduce a visually heavier card or dense wall of text.

---

## Data Model Recommendation
Centralize all service content into one structured data object/array.

Recommended structure per group:

```ts
{
  id: 'digital-platforms',
  title: 'Digital Platforms',
  description: 'Websites, systems and custom applications.',
  services: [
    {
      id: 'web-development',
      index: '01',
      title: 'Web Development',
      description: 'Custom websites, webshops and web applications built for performance, scalability and conversion.',
      includes: [
        'Business websites',
        'Landing pages',
        'Webshops',
        'Web applications',
        'CMS implementation',
        'Technical SEO foundations',
        'Performance optimisation'
      ]
    }
  ]
}
```

Use a single source of truth for:
- services section
- right-side detail card
- contact/intake selectable options

This avoids duplicated content.

---

## UI Behavior Requirements

### Services section
- Left side:
  - group cards/containers remain
  - each group still displays its services as selectable items
- Right side:
  - selected service detail updates on click
  - includes list must render below description
  - content must remain visually balanced in the available card height

### Responsive behavior
- Prevent text overflow on tablet/mobile
- Preserve readability
- If required:
  - reduce includes-list font size slightly
  - tighten bullet spacing slightly
  - stack gracefully on narrower breakpoints

### Default state
Initial active service:
- `Web Development`

---

## Intake / Contact Section Requirements
Replace the current service option buttons/cards with the new 9 services from the content spec.

Each option must display:
- service title
- corresponding group label

The options must remain aligned with the site’s current intake UX.

---

## Content Source
Use the exact content from:
- `01-services-content-spec.md`

Minor wording adjustments are allowed only if needed to prevent layout issues, but the service taxonomy and meaning must remain unchanged.

---

## Acceptance Criteria
Implementation is complete only if:
- all old generic services are removed
- all new groups/services are present
- the detail card shows both description and includes list
- the intake options match the same taxonomy
- active state logic still works
- no overflow or broken spacing appears
- mobile layout remains clean
- content is managed from a central data structure
