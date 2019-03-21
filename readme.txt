=== Kraken.io Image Optimizer ===
Contributors: karim79
Tags: anigif, compress image, exif, image optimizer, image resize, jpg, media, Optimization, optimize, optimize animated gif, optimize gif, optimize jpeg, optimize png, PageRank, PageSpeed Insights, performance, photos, png, Reduce Image Size, retina, seo, sitespeed, speed up site, svg, upload, svg, upload, gtmetrix speed test, EXIF, image resize, kraken.io, smush
Requires at least: 3.0.1
Requires PHP: 5.0.0
Tested up to: 4.9.6
Donate link: https://kraken.io
Stable tag: 2.6.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

This plugin allows you to optimize your WordPress images through the Kraken.io API, the world's most advanced image optimization and resizing API.

== Description ==

This plugin allows you to optimize and resize new and existing Wordpress image uploads through [Kraken.io Image Optimizer's](https://kraken.io "Kraken.io Image Optimizer") API. Both lossless and intelligent lossy optimization modes are supported. Supported filetypes are JPEG, PNG and GIF (including animated GIF). Maximum filesize limit is 32 MB. Even when using Kraken.io's lossy optimization, our system goes the extra mile to ensure that the results are of high quality, every time. You can just install the plugin and stop worrying.
For more details, including detailed documentation and plans and pricing, please visit [Kraken.io](https://kraken.io "Kraken.io Image Optimizer").

> **Get your FREE account with us, or a subscription starting from just [USD $5 per month](https://kraken.io/plans "Kraken.io - Plans and Pricing")**

> Sign up for your [FREE Kraken.io Account](https://kraken.io/plans "Kraken.io - Plans and Pricing") and try out our plugin with and the rest of our features now, including:

> * 100MB of free testing quota
> * API Access, with dozens of ready-to-use libraries and modules
> * Web Interface PRO with Image Resizing and sync-to-Dropbox
> * URL Paster
> * Page Cruncher
> * Optimization Stats and History
> * ...and more.

= Quick start tutorial: =
https://www.youtube.com/watch?v=Wqtl0_cavx0

= About the plugin =
* You can use your Kraken.io API key and secret on as many sites/blogs as you like. We have no per-site license.
* All images uploaded throught the media uploader are optimized on-the-fly. All generated thumbnails are optimized too.
* The main image upload can be optionally resized - this is useful for preventing user uploads with unnecessarily large dimensions. You can specify the maximum width and/or height in Kraken.io->Settings.
* When restricting the maximum dimensions of the main image using the resizing feature, the resulting image is **enhanced** using various advanced techniques, to help prevent downsample artifacts and "haloing" and produce a sharper result.
* You can optionally preserve one or more of the Date, Copyright, Geotag, Orientation, Profile EXIF metadata tags.
* Images can be automatically oriented according to their EXIF Orientation value - no need to manually rotate images.
* All images already present in the media library can be optimized individually, or using the Bulk Action menu "Krak 'em all" feature.
* This plugin does not require any root or command-line access. No compilation and installation of any binaries is necessary.
* All optimization is carried out by sending images to Kraken.io's infrastructure, and pulling the optimized files to your Wordpress installation.
* To use this plugin, you must obtain a full API key and secret from [https://kraken.io/plans](https://kraken.io/plans "Kraken.io - Plans and Pricing"). Our free account comes with a limited quota for testing our premium features, including this plugin.
* Works great with WPEngine hosted blogs, including the staging area.
* Since version 1.0.4, the plugin will work with local WordPress installations; the client site does not need to be published on the web.


> ★★★★★ **Excellent Option for Image Optimization**
> "The real power of Kraken is their "intelligent lossy" optimization. I use it on all my sites and have never once needed to roll back an image because of too much quality degredation. While I hope to see some more advanced settings added to the WordPress plugin in the future, it is a perfect solution as is." - [collin](https://profiles.wordpress.org/collinmbarrett)
>
> ★★★★★ **Quality results, quality service**
> "The plugin works really well and effortlessly, and the support is prompt, thoughtful, and thorough. I'm hooked." — [illustrata](https://profiles.wordpress.org/illustrata)
>
> ★★★★★ **Optimize according to Google Pagespeed**
> "Kraken was instrumental in optimizing images to comply with Google's Pagespeed analyzing tool. Our travel blog travelmemo.com now sports Google's 'mobile friendly' tag for mobile searches" — [Walter Schaerer](https://profiles.wordpress.org/qualterio)
>
> ★★★★★ **Perfect solution to speed up site!**
> "I love this plugin! All the questions I had are quickly responded to and I see a huge saving with image size without losing the quality. I highly recommend this plugin!" — [ezone69](https://profiles.wordpress.org/ezone69)
>

Once you have obtained your credentials, from your Wordpress admin, go to the Kraken.io settings page. The from there you can enter your API credentials, and select your optimization preferences. Once you have done this, click on **Save**. If everything is in order, it will simply say "settings saved" and give you a reassuring green tick that your credentials are valid. You can now start optimizing images from within Media Library. Any image you upload from now on, through any of the media upload screens will be optimized on-the-fly by Kraken.io.

For advanced users, there is a third party WordPress Command Line Interface (CLI) tool to allow image optimization from the command line, or by using cron. For details, visit: https://github.com/tillkruss/wp-cli-kraken

Please send bug reports, problems, feature requests and so on to support (at) Kraken dot io, or directly to the author of this plugin.

= Connect with Kraken.io =
* Website: https://kraken.io
* [Twitter](https://twitter.com/KrakenIO "@KrakenIO")
* [Google+](https://plus.google.com/107209047753760492207/ "Google+")
* [Facebook](https://www.facebook.com/krakenio "Kraken Image Optimizer")
* [Github](https://github.com/kraken-io "Kraken.io on Github")

== Installation ==

To install the Kraken Wordpress Plugin:

1. Upload `kraken.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Enter your Kraken.io API key and secret into the new **Kraken.io Settings** section of Settings->Media.
4. Any images you upload from now on using Wordpress's Media Upload will be optimized according to your settings. Auto-generated thumbnails will also be optimized.
5. Images already present can be optimized from within the Media Library.

== Screenshots ==

1. This screenshot shows the Kraken.io Settings page. You must enter your credentials, and select your optimization mode from there, then hit **save**. Advanced options are also available.
2. This screenshot shows the two columns added by Kraken.io Image Optimizer: **original image** and **Kraked size**, as well as the new **Optimize This Image** button which is present for images which already exist in your media library. Stats and optimization type are shown for optimized images.
3. This screenshot shows the bulk optimizer aka the "Krak 'em all" feature which appears as an overlay.

== Frequently Asked Questions ==

= Can I test the plugin before I purchase an account from Kraken.io? =

Yes you can. All of our plans require that you first create your free Kraken.io account. No credit card is required, and we give you free testing quota of 50 MB, with which you can test all the features we offer, including this plugin.

Additionally, if you would like to test the performance and results of Kraken.io Image Optimizer, you can try the free Web Interface at https://kraken.io/web-interface which does not require any registration.

= Where can I purchase an API key and secret? =

From our plans page, right [here](https://kraken.io/plans "Kraken.io plans and pricing"). In addition to being able to use our Wordpress Plugin, you can also use the API in your own applications, and take advantage of our [Web Interface PRO ](https://kraken.io/pro "Kraken.io Web Interface PRO") feature (and much more!) for as little as USD $5 per month.

= Will the optimized images remain on my blog if I uninstall the plugin? =

Yes, of course they will. Our plugin simply replaces the image files on your blog with the ones optimized by us.

= Where can I find the option to optimize my Media Libary images? =

You will need to switch the Media Library from the Grid view to the List view. In the "Kraked Size" column, you will then see the "Optimize This Image" button for unoptimized images, or the results of the optimization where the image has already been optimized by our plugin.

= What is the difference between Kraken.io and other plugins such as Optimus, EWWW, WP Smush, Imagify and TinyPNG/TinyJPG? =
Kraken.io's service emphasizes finding the precise balance between image quality and file size reduction. Our API utilizes various mechanisms for ensuring that the result cannot be distinguished from the original by the human eye, even upon close inspection. If want to get the greatest possible savings without ever having to check the optimized image against the original, this is the plugin for you.

== Changelog ==

= 2.6.3 =
* Verified the plugin's compatibility with PHP 7 and WordPress 4.9

= 2.6.2 =
* Fixed a rare bug which prevented filepaths containing double-forward-slashes from getting optimized.

= 2.6.1 = 
* Bug fixes related to new features

= 2.6.0 =
* Added ability to choose which post sizes get optimized (defaults to all)
* Added ability to change the chroma subsampling scheme for JPEG images (defaults to 4:2:0)
* Stability and compatibility improvements
* Various frontend CSS fixes and improvements
* WordPress version compatibility bumped to 4.6

= 2.5.1 =
* Fix fatal error on older PHP versions resulting from recent PHP array syntax

= 2.5.0 = 
* Ability to disable optimization of main image, allowing faster uploads from Media Library. You can optimize the main image later from within your Media Library.
* Ability to restrict the maximum dimensions of image uploads (resizing), by width and/or height.
* When using resize feature, resized images are enhanced for sharper results using various advanced techniques.
* Ability to force JPEG quality to a discrete "quality" value, for greater savings if you know what you're doing.
* Ability to preserve certain EXIF metadata tags, including Date, Copyright, Orientation, Geotag and Profile.
* Ability to automatically orient images according to their Orientation EXIF metadata.
* Improvements and simplifications to interface elements and Kraken.io Settings page.

= 2.0.0 =
* Please read! Kraken.io settings have now moved to an own section (Settings->Kraken.io), in order to reduce clutter in Media Settings, and to accomodate new features on the way.
* Advanced settings grouped in "Advanced Settings" section of settings page.
* Direct link to Kraken.io settings from Kraken.io in the plugins section.
* Updated screenshots.
* WordPress version compatibility bumped to 4.2.

= 1.0.9.1 =
* Fixed another reported bootstrap CSS conflict.

= 1.0.9 =
* Fixed potential conflict with Bootstrap Modal on blogs using Twitter Bootstrap.
* Better bundling of scripts for faster loading of the plugin.

= 1.0.8 =
* Added the ability to control the number of images the bulk tool optimizes at once. The default settings of 4 is recommended. Blogs with limited resources, for example those on small shared hosting plans should try a lower value if they run into issues with bulk optimization. Blogs on larger hosting plans can experiment with higher values.

= 1.0.7 =
* Added the ability to reset (or remove Kraken.io metadata) from individual images or all images at once, allowing further optimization in certain cases, for example, reoptimizing a previously losslessly optimized image as lossy.

= 1.0.6 =
* Better error handling.

= 1.0.5.9 =
* Cleanup release prior to major feature release. Paved the way for "reset" feature, and more.
* Added tags.

= 1.0.5.8 =
* Better debugging for customers by including WordPress version and Kraken.io plugin version per request.

= 1.0.5.7 =
* Added ability to disable automatic optimization of uploads.

= 1.0.5.6 =
* Rolled back to old way of replacing images in light of reported issues.

= 1.0.5.5 =
* Fixed potentially breaking change to do with new PHP syntax.

= 1.0.5.4 =
* More reliable handling of image fetching and overwriting.

= 1.0.5.3 =
* Fixed broken spinner by updating the URL to our new CDN.
* Added link to WP-CLI tools to readme.

= 1.0.5.2 =
* Removed hack which allows uploading of filenames with non-Latin alphabet, since Kraken.io API now supports it.
* Updated readme with information about free account.

= 1.0.5.1 =
* Tested with WordPress 4.1.
* Better naming convention for temporary files created.
* Updated readme.txt

= 1.0.5 =
* Fixed CURL issues related to latest PHP versions.
* Can now optimize filenames with non-Latin alphabet (such as Germanic umlauts, Cyrillic alphabet, etc).
* Performance improvement when optimizing through Media Library or using the bulk optimizer.
* CURL not present warning in Media Settings page.
* Stability and reliability improvements.

= 1.0.4 =
* Utilizes Kraken.io's upload API instead of URL. Images are uploaded to Kraken.io from WordPress installations, rather than fetched by Kraken.
* Now works will local WordPress installations since hosted images are no longer fetched by URL, but uploaded by the client blog.

= 1.0.3.4 =
* Performance improvements.

= 1.0.3.3 =
* Bug and cleanup release prior to next feature release (Amazon S3 support, in the near future).
* Fixed bug related to SSL certificates on some blogs.
* Fixed bug causing preventing WPEngine users from copying live site to and from staging.
* JavaScripts and styles now only included where they need to be.
* Increased HTTP timeouts for users with extremely large numbers of thumbnails.
* Fixed issue preventing optimization of images on WordPress installations using relative image URIs. One plugin which used to cause this problem is Root Relative URLs by Marcus E. Pope.

= 1.0.3.2 =
* Fixed bug related to storing optimized thumbnails metadata.

= 1.0.3.1 =
* When using the Regenerate Thumbnails plugin with kraked images, meta data is now correctly updated per image.
* Optimization mode (lossy/lossless) is now stored with kraken.io thumbnail metadata (for future Stats page).

= 1.0.3 =
* Bulk Actions menu in Media Library is now extended with "Krak 'em all", our Bulk Optimization feature.
* Fixed a bug which caused old images' thumbnails to not be optimized.
* Fixed a failure condition which occured only on WPEngine-hosted systems.

= 1.0.2.1 =
* Fixed bug which led to kraked file not being retrieved in rare cases.
* Increase ajax timeout for media library inline kraking to be kinder to slower WordPress blogs.

= 1.0.2 =
* Thumbnails are now optimized when triggering an image optimization from within the media library.
* Number of Kraked thumbnails is now shown in media library in "Kraked Size" column.
* "Failed! Hover here" error notification does not persist where an image was not optimized. It goes away after page refresh.
* Optimize Image button no longer shown for incompatible media types.
* Information about thumbnail optimization is persisted for future fun-stats page/widget.
* Minor CSS tweaks.

= 1.0.1 =
* Minor cleanup release.

= 1.0 =
* First version. Supports lossy and lossless optimization of JPG, PNG and GIF (including aniGIF) image formats
* Hooks to Media Uploader to optimize all uploaded images, including generated thumbnails.
* Allows optimization of existing images in Wordpress Media Library.

== Notes and Incompatible plugins ==
If you use the WP Super Cache plugin, you must **disable the plugin** prior to performing any image optimization, as it is known to cause strange and buggy behaviour with the Kraken.io Image Optimizer plugin.

== Any Questions? ==
We love to hear from you! Just shoot an email to support (at) kraken dot io and let's talk.
