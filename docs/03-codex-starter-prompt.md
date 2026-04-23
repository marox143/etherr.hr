# Codex Starter Prompt — Etherr Services Section Update

Use this as the starting instruction for Codex.

---

You are updating an existing Etherr website codebase.

Your task is to replace the current services-section content and intake options with the final structured taxonomy defined in the markdown files in this folder.

## Read first
1. Read `01-services-content-spec.md`
2. Read `02-implementation-brief.md`

## Task
Implement the new services taxonomy inside the existing site without redesigning the section.

## Working rules
- First inspect the existing codebase and identify:
  - the services section component(s)
  - the active service detail card logic
  - the contact/intake service selection component(s)
  - where current services data is stored
- Then replace the current content with the new structure from the spec.
- Reuse existing components where possible.
- Refactor only what is necessary to support:
  - description + includes list in the active detail card
  - new groups and services
  - updated intake options
  - centralized content structure

## Design constraints
Do not redesign the section.
Preserve:
- layout
- card style
- spacing system
- type scale
- active/hover states
- responsive behavior
- current Etherr visual language

## Required UI output
### In the services section:
Left side remains:
- service groups
- selectable service items

Right side active detail card must show:
- selected service title
- selected service description
- “Includes” heading
- bullet list of included items
- group label
- service counter

### In the contact/intake section:
Replace current options with the 9 new services and correct group labels.

## Implementation preference
Create one structured source of truth for service content and reuse it across both sections.

## Default state
Default active service must be:
- Web Development

## Validation checklist
Before finishing, verify:
- all old generic content is removed
- no duplicate service definitions remain
- the detail card renders description and includes cleanly
- no overflow on desktop/tablet/mobile
- click/active logic still works
- contact/intake options exactly match the spec

## Deliverable format
When done:
1. Summarize which files were changed
2. Explain how the service data is structured
3. Confirm any small layout adjustments you made
4. Note any assumptions

Now inspect the codebase and implement the update.
