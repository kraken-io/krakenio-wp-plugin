# Kraken.io Wordpress Plugin
### Optimizes all your Wordpress images

Optimize new and existing Wordpress image uploads through [Kraken Image Optimizer's](https://kraken.io) API. Both lossless and lossy optimization modes are supported.

 - All image uploaded throught the media uploader are optimized on-the-fly. All generated thumbnails are optimized too.
 - All images already present in the media library can be optimized individually.
 - This plugin not require any root or command-line access. No compilation and installation of any binaries is necessary. 
 - All optimization is carried out by sending images to Kraken.io's infrastructure, and downloading the optimized files to your Wordpress installation.


**This is not currently hosted on Wordpress. You must clone this repository in your wp-content/plugins folder, or upload the zipped contents of this repository to your Wordpress blog (Plugins -> Add New -> Upload Zip)**


To use this plugin, you must obtain an API key and secret from [Kraken.io](https://kraken.io)

Once you have obtained your credentials, from your Wordpress admin, go to Settings->Media. 
The Kraken Wordpress plugin adds a **Kraken.io Settings** section to the bottom of the page, from where you can enter your API credentials, and select your Optimization preferences. Once you have done this, click "Save". If everything is in order, it will simply say "settings saved" and give you a reassuring green tick in the Kraken.io settings section.







