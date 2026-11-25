# WP Scroll Words

WordPress plugin for large statement texts whose words light up one by one as the page scrolls, with small rounded photos between the words. No theme dependency; with Woodmart it offers the theme's fonts and colors. WPBakery integration is optional.

## Using it

Activate the plugin, open **WP Scroll Words → Add text**:

1. Write the text. Put `{1}`, `{2}`… where a photo goes, punctuation right after it (`kaffe {4}. Vi baker`).
2. **Add photos** from the media library, drag them into order: the first is `{1}`.
3. **Text style**: Font, Weight, Font size (desktop / tablet / mobile; tablet and mobile empty follow the larger screen), Color, HTML tag, Dimmed opacity (the words before they light up: 0 hidden, 1 no effect), Extra class name (classes for your CSS on the text's block; the element's / shortcode's `el_class` is added to them), Open photos in lightbox.
4. Publish, then add the **Scroll Words** element in WPBakery (under Woodmart's "Theme elements" when the theme is active) or paste the shortcode shown next to the text: `[scroll_words id="123"]` (optional `el_class="…"`).

**WP Scroll Words → Settings** (administrators): what each role may do — Edit texts, Add, Delete, WPBakery element.

## Releasing

1. Set the new version in `wp-scroll-words.php` (header `Version` and `WPSW_VERSION`) and `readme.txt` (`Stable tag`), add it to the Changelog.
2. Commit, tag `vX.Y.Z`, push the tag.
3. `.github/workflows/release.yml` checks the versions match, the PHP syntax, builds `wp-scroll-words.zip` (without the files `.gitattributes` marks `export-ignore`) and publishes the release. Sites see the update under Plugins.

## Files

- `wp-scroll-words.php`: header, constants, post type (`wpsw_text`), updates (Plugin Update Checker), loader.
- `includes/class-wpsw-settings.php`: a text's settings (`_wpsw_settings` meta): defaults and sanitizing.
- `includes/class-wpsw-render.php`: the shortcode; words split into spans on the server, photo pills, lightbox buttons.
- `includes/class-wpsw-admin.php`, `assets/js/admin.js`: edit screen (editor, photos), shortcode box and column.
- `includes/class-wpsw-admin-text.php`, `assets/js/admin-text.js`: the Text style card (font list, weights, sizes per device, Pickr color).
- `includes/class-wpsw-fonts.php`, `includes/class-wpsw-palette.php`: Woodmart's fonts and the theme's colors (guarded: without Woodmart, a text field and the basic colors).
- `includes/class-wpsw-wpbakery.php`, `assets/js/wpbakery.js`: the WPBakery element (text list, Edit / Add text).
- `includes/class-wpsw-access.php`, `assets/js/roles.js`: Settings → roles (capabilities given at run time, roles never changed).
- `assets/js/scroll-words.js`, `assets/css/scroll-words.css`: the effect on the page.
- `assets/js/lightbox.js`, `assets/css/lightbox.css`: the lightbox (loaded only where a text has it on).
- `uninstall.php`: removes the texts, the roles option and the update checker's state.
- `vendor/plugin-update-checker` (MIT), `assets/vendor/pickr` (MIT): third-party libraries.
