=== Plugin Name ===
Contributors: swhitley
Tags: Twitter, avatar, profile image, cache
Requires at least: 2.7.0
Tested up to: 2.9.2
Stable tag: 1.05

Static Profile Image Urls for Twitter.  

Serve Twitter profile images from your WordPress blog.

Requires PHP 5



This code can be used with WordPress or as a standalone caching application.

Example Twitter profile image link:

&lt;img src='http://{my wordpress blog}/?spiurl_user={Twitter Username}' alt='' />

Other WordPress plugin authors can check for the existence of this plugin and use the same cache.

Example for Plugin Authors:

   if(function_exists('spi_profile_image_get')) {
        echo '&lt;img src="'.get_option('home').'/?spiurl_user=ev" alt="Ev" />';
    }
    
Changes in Version 1.05

- Added options to turn off the domain check and redirect to image files.


Troubleshooting

There are two options in the file.  Edit /plugins/spiurl/spiurl.php  

$spiurl_domaincheck = 'Y';
$spiurl_redirect = 'N';


Turn off ('N') $spiurl_domaincheck if you want to test SPIURL.

Turn on ('Y') $spiurl_redirect if your web server isn't serving the image.
    
    
== Installation ==

1. Upload `spiurl.php` to the `/wp-content/plugins/` directory.
1. Create `wp-content/plugins/spiurl/cache` and make it writable.
1. Activate the plugin through the 'Plugins' menu in WordPress.



== Change Log ==

1.00
03/05/2010

- Initial Release.
