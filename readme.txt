=== WP_Query Route To REST API ===
Contributors: teemusuoranta, samikeijonen, christian-nikkanen
Tags: WordPress, REST API, WP_Query
Requires at least: 4.7.3
Tested up to: 5.9.3
Stable tag: 1.3.1
Requires PHP: 7.0
License: GPLv2+

Adds new route /wp-json/wp_query/args/ to REST API.

== Description ==

= Features =

* Adds new route /wp-json/wp_query/args/ to REST API.
* You can query content with WP_Query args.
* There's extensive filters and actions to limit or extend functionality.
* Built-in compatibility for [Relevanssi ('s' argument)](https://wordpress.org/plugins/relevanssi/) and [Polylang ('lang' argument)](https://wordpress.org/plugins/polylang/)


== Installation ==

Download and activate. That's it.

== Changelog ==

= 1.3.2 =
*Release Date - 12 April 2022*

Fix PHP warning caused by 1.3.0 argument sanitizing refactoring.

= 1.3.1 =
*Release Date - 7 April 2022*

Fix composer license to a valid license.

= 1.3.0 =
*Release Date - 5 April 2022*

Compatibility release with a few new features. 100% backwards compatible.

* Custom HTML output is now allowed (#10)
* Instance and argument sanitizing can be now reused (#11)
* Typos in filter names are fixed while keeping old names also working (#5)
* Updates WP version, plugin version and some readme tweaks

= 1.2.0 =
*Release Date - 25 July 2019*

*WordPress.org release

= 1.1.1 =
*Release Date - 3 June 2017*

* Added advanced example in readme for getting PHP WP_Query for JS. Added table of contents. Made the title hierarchy more logical.

= 1.1 =
*Release Date - 5 April 2017*

* Make the return data structure same as /wp-json/wp/posts/. The data schema was missing some data before. Now the structure is inherited from the WP_REST_Posts_Controller as it should have from the start.
