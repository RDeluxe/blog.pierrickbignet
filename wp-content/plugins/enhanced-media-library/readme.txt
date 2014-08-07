=== Enhanced Media Library ===
Contributors: webbistro
Tags: media library, taxonomy, taxonomies, mime, mime type, attachment, media category, media categories, media tag, media tags, media taxonomy, media taxonomies, media filter, media organizer, file types, media types, media uploader, custom, media management, attachment management, files management, ux, user experience, wp-admin, admin
Requires at least: 3.5
Tested up to: 3.9.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



A better management for WordPress Media Library



== Description ==

This plugin will be handy for those who need to manage a lot of media files.

= Taxonomies =

* create unlimited amount of media taxonomies (like categories and tags),
* be in total control of your custom taxonomies' parameters via admin,
* edit and delete your custom media taxonomies,
* assign existed taxonomies to Media Library (for example, you can use post categories as a taxonomy for your media files),
* unassign any media taxonomy from Media Library via admin,
* immediately set taxonomy term to any media file during upload via Media Uploader,
* filter media files in Media Library by your custom taxonomies, and choose which taxonomies you are willing to use for that filter,
* filter media files in Media Uploader by your custom taxonomies, and choose which taxonomies you are willing to use for that filter,
* have you attachment post type's archive page (front-end) working by default

= MIME Types =

* create new MIME types (media file types),
* delete any MIME type,
* allow/disallow uploading for any MIME type,
* filter media files by MIME types in Media Library / Media Uploader (for example, PDFs, Documents, V-Cards, etc),
* be in total control of the names of your MIME type filters

= Coming =

New features and improvements coming...



== Installation ==

1. Upload plugin folder to '/wp-content/plugins/' directory

2. Activate the plugin through 'Plugins' menu in WordPress admin

3. Adjust plugin's settings on **Media Settings -> Taxonomies** or **Media Settings -> MIME Types**

4. Enjoy Enhanced Media Library!



== Screenshots ==

1. Enhanced Media Library Taxonomies Settings

2. Taxonomies in Nav Menu

3. Edit media taxonomies just like any others

4. Edit media taxonomies just like any others

5. Taxonomy columns and filters, sorting by MIME types in Media Library

6. MIME type filter in Media Uploader

7. Taxonomy filter in Media Uploader

8. Set taxonomy term right in Media Uploader

9. MIME type manager



== Changelog ==


= 1.1.1 =
* **Improvements**
	* Filters added for Appearance -> Header and Appearance -> Background [Support Request](https://wordpress.org/support/topic/missing-category-filter-on-media-select-window)

* **Bugfixes**
	* Fixed EML 1.1 bug with disappearing widgets on Appearance -> Customize [Support Request](http://wordpress.org/support/topic/customize-missing-widgets)
	* Fixed EML 1.1 bug with disappearing scrollbar [Support Request](http://wordpress.org/support/topic/scroll-bar-disappeared-in-media-window)


= 1.1 =

* **Improvements**
	* Filters added to /wp-admin/customize.php page [Support Request](https://wordpress.org/support/topic/missing-category-filter-on-media-select-window)
	* Reconsidered the mechanism of checkboxes' checking in Media Uploader for more stable operation [Support Request](https://wordpress.org/support/topic/instability-in-the-media-insertion-panel)
	* Media Uploader filters now work without page refreshing when you change category for you images

* **Bugfixes**
	* Fixed "Uploads not showing" issue [Support Request](http://wordpress.org/support/topic/uploads-not-showing)
	* Reconsidered CSS for filters area [Support Request](http://wordpress.org/support/topic/missing-search-box)
	* Fixed CSS and JS files wrong path definitions [Support Request](http://wordpress.org/support/topic/little-bug-2)


= 1.0.5 =

* Bugfixes
	* Fixed disappearing filter in Media Uploader [Support Request](https://wordpress.org/support/topic/any-chance-of-adding-a-drop-down-in-the-insert-media-screen)
	* Added WP 3.9 compatibility [Support Request](https://wordpress.org/support/topic/great-plugin-but-breaks-the-new-add-media-in-39)


= 1.0.4 =

* Bugfixes
	* Fixed filter mechanism in Media Library [Support Request](http://wordpress.org/support/topic/filter-in-media-not-working-properly)
	* Fixed the bug with saving of assigned post categories and tags in Media Uploader


= 1.0.3 =

* Improvements
	* Better term sorting in Media Uploader
	* Minor code improvements

* Bugfixes
	* Fixed the bug with sorting of post categories and tags assigned to Media Library


= 1.0.2 =

* Bugfixes
	* Fixed assigned non-media taxonomies archive page [Support Request](http://wordpress.org/support/topic/plugin-woocommerce-products-stopped-displaying)


= 1.0.1 =

* Bugfixes
	* Media Uploader filter now shows nested terms.
	* Media Uploader filter now works correctly with multiple taxonomies.


= 1.0 =

* New: 
	* Enhanced Media Library initial release.
