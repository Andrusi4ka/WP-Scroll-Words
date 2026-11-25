=== WP Scroll Words ===
Contributors: andrusi4ka
Tags: scroll, text, animation, lightbox, wpbakery
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Large statement texts whose words light up one by one as the page scrolls, with small rounded photos between the words.

== Description ==

WP Scroll Words adds a "WP Scroll Words" menu where you write texts and show them anywhere with `[scroll_words id="123"]` or the "Scroll Words" WPBakery element. It works with any theme; with Woodmart it also offers the theme's fonts and colors.

* Write the text in WordPress's editor (bold, italic, link, text color, line break) and put `{1}`, `{2}`… where a photo goes.
* Add photos from the media library and drag them into order: the first one is `{1}`.
* Text style: font (Woodmart's fonts, searchable; a Google font is loaded where the text is), weight (the font's own), size per device (tablet and mobile follow the larger screen until given their own), color (the theme's palette), HTML tag, how dim the words are before they light up, and extra class names for your CSS.
* Open photos in lightbox, per text: a click shows the photo large, with arrows, keys and swipe between the photos of the text, its caption from the media library, Esc or the background to close.
* The words light up as the text scrolls into view; the photos pop in. Without JavaScript, or with "reduce motion" on, the whole text is shown at once.
* The WPBakery element lists the texts, with "Edit text" and "Add text" (new tab); back in the editor, a text added there is chosen.
* WP Scroll Words → Settings (administrators only): what each role may do with the texts — edit, add, delete, use the WPBakery element.
* Updates come from the plugin's GitHub releases and show up under Plugins like any other update.
* Deleting the plugin removes its texts and settings. Photos stay in the media library.

== Changelog ==

= 1.0.1 =
* Fix: with "Open photos in lightbox" on, the photos hung below the line and pushed the lines apart; they now sit on the line as without it.

= 1.0.0 =
* First version: texts with photos between the words, lit up on scroll; text style per text (font, weight, size per device, color, tag, dimmed opacity, extra class name); lightbox; WPBakery element with Edit / Add text; roles; updates from GitHub.
