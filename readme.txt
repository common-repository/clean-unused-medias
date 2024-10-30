=== Clean Unused Medias ===
Contributors: xuxu.fr
Donate link: http://goo.gl/SORljr
Tags: Media, Attachment, Clean, ACF, Advanced Custom Fields
Requires at least: 4.6.1
Tested up to: 5.8
Stable tag: 1.10
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Clean Unused Medias, another simple way to delete the medias you don't need anymore.

List the medias you don't used anymore.

Works with post, page, custom post type, and ACF (Advanced Custom Fields).

Filters available :

*   The media used as site favicon
*   The medias used as post thumbnail
*   The medias uploaded in a post (and so related to it)
*   The medias used in ACF fields (image and file)
*   The medias' URL inserted or used in a post content
*   The medias' URL inserted or used in `wp_postmeta`
*   The medias' URL inserted or used in `wp_usermeta`
*   The medias' URL inserted or used in `wp_options`

Page dedicated to this plugin : https://xuxu.fr/2016/09/28/supprimer-les-fichiers-non-utilises-sous-wordpress/

You can contact me on :

*   My blog: https://xuxu.fr/contact
*   My Twitter account:  https://twitter.com/xuxu

You can donate here ^_^ : http://goo.gl/SORljr

== Installation ==

1. Extract and upload the directory `clean-unused-medias` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Medias -> Clean Unused Medias menu.
4. Enjoy.

== Frequently Asked Questions ==

= Why a crawler ? =

Because the feature to check if a media url is used in a post content take a very long time. So caching those medias is the best way to keep the interface fast and furious.

== Screenshots ==

1. Dashboard
2. This media is used in the post content
3. This media is used as a featured image / post thumbnail
4. This media is used anywhere
5. You can check their status in the media list
6. And know if the media is used somewhere

== Changelog ==

= 1.10 =
1. Clean and fix scripts
2. Test up with WordPress 5.7.2

= 1.09 =
1. Test with WordPress 4.7.1 : no problem o/

= 1.08 =
1. Fix log where the media is used
2. Improve the crawler
3. Hashtags and popin details available in media library

= 1.07 =
1. Log where the file is used
2. Display where the media is used
3. Fix ACF check error

= 1.06 =
1. Add more filters
2. Improve the crawl and fix some errors
3. Add some notices

= 1.05 =
1. Site favicon are now not considered as unused
2. Improve the crawler : search now in `wp_options`, `wp_postmeta` and `wp_usermeta`

= 1.04 =
1. Check if the media ID is used in the content with the native WordPress shortcode [gallery]
2. Minor user interface changes
3. Add plugin icon :)

= 1.03 =
1. Add progress bar 
2. Refresh results after deleting all medias
3. Pause and resume the crawler
4. Filter the results with keyword
5. Resume the crawl if new medias were uploaded

= 1.02 =
1. Make it really Translate ready! :p

= 1.01 =
1. Fix security issues
2. Translate ready!
3. Add French translation
4. Add WordPress notices
5. Minor changes

= 1.00 =
Welcome!

== Upgrade Notice ==

= 1.09 =
1. Just verify if it works with the latest version of WordPress. Waiting for some times and ideas to add more features \o/

= 1.08 =
1. The update post meta log was outside the loop :/
2. Filter some `option_name`in the crawler
3. Display the hashtags and show more details in media library

= 1.07 =
1. Store where the media is used (in content, post / user meta, option, ACF field) while crawling
2. Show in a popin the details about where is used the media
3. Remove sql LIMIT check :/

= 1.06 =
1. New filters incoming : Not in `wp_options`, `wp_postmeta` `wp_usermeta`
2. Fix filters problem and debug/improve crawler & WP-Cron task
3. Add some notices

= 1.05 =
1. If you use the feature WordPress Customise Theme, media used for the favicon will not be listed
2. The crawler now search for media URL and thumbs URL in `wp_options`, `wp_postmeta` and `wp_usermeta` (the media URL can be used for theme cutomization, widgets, or other themes and plugins)

= 1.04 =
1. The plugin check if the media ID is used the shortcode [gallery] (for example : [gallery ids="1234,5678,90"])
2. I really don't remember ;)
3. It looks like more professionnal u_u

= 1.03 =
1. A loading bar to visualize the crawl progress
2. Reload the results if every medias displayed have been deleted
3. You can pause and resume the crawler when you want
4. You can filter the results by typing some keywords (matching on `post_title`, `post_content` and `post_name`)
5. If you upload new medias since the last crawl was completed, the process will be resumed to check again

= 1.02 =
1. Move .mo & .po in sub directory languages
2. Set "valid" text-domain : "clean-unused-medias" (without underscore)

= 1.01 =
1. Fix security issues after first review by the Team WordPress

