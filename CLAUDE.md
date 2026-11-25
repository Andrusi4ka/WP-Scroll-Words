# WP Scroll Words — notes for the next agent

Read this first, then `README.md` (how it works, files, releasing) and `readme.txt` (features, changelog).

## What it is

Texts whose words light up as the page scrolls, with photo pills between the words. Built for the user's sites (WordPress + Woodmart + WPBakery), modelled on their WP Swiper and WP Banner plugins (same structure: post type + meta, Settings → roles, WPBakery element with Edit / Add buttons, updates from GitHub releases, `uninstall.php`). Author: Relevant AS (relevant.no).

The user wants it **minimal** and files **small**. Removed on purpose — do not re-add without being asked: the Italic color setting, the old free-text font size (now px per device), the separate Style box.

## Rules that matter

- Words are split **on the server** (`WPSW_Render::build()`), between the editor's tags; the script only toggles `.lit`. Keep it that way: it survives editors wrapping the text.
- `WPSW_Settings::sanitize()` runs on save **and on every read**; it takes unslashed values. `save()` slashes for `update_post_meta()`. Do not add `wp_unslash()` inside `sanitize()` (backslashes in texts would be lost).
- The text allows only what the editor toolbar makes (`allowed_tags()`); a `<span>` keeps only `color`.
- Everything that reads Woodmart is guarded (`WPSW_Fonts::guarded()`): without Woodmart the plugin still works (a text field for the font, basic colors).
- Capabilities are given at run time (`user_has_cap`), never written into roles.
- Front-end assets load only where a text is (`wp_enqueue_*` inside the shortcode); the lightbox only for texts with it on.
- Breakpoints: tablet ≤ 1024px, mobile ≤ 767px (as Woodmart's).
