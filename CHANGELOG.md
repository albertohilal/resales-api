# Changelog

All notable changes to this project will be documented in this file.

## [v0.9.0] - 2025-10-18
### Added
- Exclude properties without images from the property grid (shortcodes).
- Shortcode now attempts to fill the grid to `page_size` by paginating using `P_QueryId`/`P_PageNo` and collecting valid items with images (limit: 20 extra pages).
- Fallback to `PropertyDetails` (cached per reference) when `SearchProperties` returns no images.
- Added safe logs and debug traces for image fallback and skipped properties.

### Notes
- Tag: `v0.9.0-exclude-no-image` (annotated)
- Commit: 7a55140 — implementation that completes grid with valid image cards.
