<?php
/*
Plugin Name: SPIURL
Author:  Shannon Whitley 
Author URI: http://voiceoftech.com/swhitley/
Plugin URI: 
Description: Static Profile Image Urls for Twitter.
Acknowledgments:
	The original SPIURL (same author) http://purl.org/net/spiurl/
	Tom Hengst http://www.tom-hengst.de - Portions of code adapted from TwitterGrid.
Version: 1.05
************************************************************************************
M O D I F I C A T I O N S
1. 03/05/2010 Shannon Whitley - Initial Release
2. 04/02/2010 Shannon Whitley - Options: Turn off domain check.  Redirect to file.
3. 04/06/2010 Shannon Whitley - Fixed bug where image would not display on first
                                get.
************************************************************************************
************************************************************************************
I N S T R U C T I O N S

Create links to Twitter profile images on your blog using the following format:

http://{blog_home}/?spiurl_user={username}&spiurl_size={size}

spiurl_user = Twitter Username
spiurl_size = {blank}, mini, normal, original, bigger, large

Example:

    Insert the following line into the html of post:

    <img src="http://myblog.com/?spiurl_user=swhitley" alt="swhitley" />
    
Note:   You cannot test the link by navigating to it directly (unless you turn off
        the domain check below).
        
Cache:  The images will be cached on your server in a directory called 'cache' 
        relative to this plugin file.  The cache may be cleared at any time and 
        the images will be refetched.
************************************************************************************

*/

//Perform a domain check (can prevent some types of unauthorized use).
//Set this to 'N' to test the plugin.  You can then access the php file directly.
//'Y' or 'N' -- Default  = 'Y'
$spiurl_domaincheck = 'Y';

//Redirect to the file or read the file and display the contents.
//Some web servers will not display the image correctly if we read the contents.
//When this happens, change this option to 'Y' and we will simply redirect to the file.
//'Y' or 'N' -- Default = 'N'
$spiurl_redirect = 'N';

$spiurl_cache_url = 'changeme_if_not_WP';

//Run inside or outside WP.
if(function_exists('get_option'))
{
    add_action('init', 'spi_init');
    $spiurl_cache_url = WP_PLUGIN_URL.'/spiurl/cache/';
}
else
{
    spi_init();
}

function spi_init()
{
    global $spiurl_domaincheck;
    
    
	//Twitter screen_name.
    $user = '';
    //mini, normal, original, bigger, large
    $size = 'normal';
    
    if(isset($_REQUEST['spiurl_user']))
    {
        //WP Domain
        if(function_exists('get_option'))
        {
            $site_domain = get_option('home');
        }
        else
        {
            $site_domain = 'changeme_if_not_WP';
        }
	    $site_domain = parse_url($site_domain);
	    $site_domain = $site_domain['host'];
	    $site_domain = preg_replace("/^www\./i", '', $site_domain);	
		if(empty($site_domain))
		{
			$site_domain = 'localhost';
		}
	 
	    $referer = $_SERVER['HTTP_REFERER'];

	    //The referrer domain and the images must be on the same site.
	    //An attempt to prevent outside use.  Not 100% effective.  
	    $ref_domain = parse_url($referer);
   	    $ref_domain = preg_replace("/^www\./i", '', $ref_domain['host']);	
		if(empty($ref_domain))
		{
			$ref_domain = 'localhost';
		}

	    if($ref_domain != $site_domain) {
	        if($spiurl_domaincheck == 'Y')
	        {
		        die('Domain does not match: '.$site_domain);
		    }
	    }
    
        $user = $_REQUEST['spiurl_user'];
		if(strlen($user) > 15)
		{
			die('Maximum length for a Twitter user name is 15 characters.');
		}
        if(isset($_REQUEST['spiurl_size']))
        {
            $size = trim($_REQUEST['spiurl_size']);
        }
		switch($size)
		{
			case '':
			case 'mini':
			case 'normal':
			case 'original':
			case 'bigger':
			case 'large':
				break;
			default:
				$size = 'normal';
		}
		
		//Displays the image data or redirects to the image file.
        spi_profile_image_get($user, $size);
        exit(0);
    }
}


//*****************************************************************************
//* spi_profile_image_get - Get the Twitter profile image data.
//*****************************************************************************
function spi_profile_image_get($user, $size)
{
    global $spiurl_redirect, $spiurl_cache_url;
    
    $cachedir = dirname(__FILE__).'/cache/';
    $cachefile = $cachedir.$user.'_'.$size;
    $cachemeta = $cachedir.$user.'_meta_'.$size;
	
	if(!file_exists($cachedir))
	{
		mkdir($cachedir);
	}
    
    if(!is_writeable($cachedir)) { 
		die('The cache directory must be writeable:'.$cachedir);
    }
   
    $getfile = false;
    
    if(!file_exists($cachefile) || !file_exists($cachemeta)) {
      $getfile = true;
    }
    elseif(time() - filemtime($cachemeta) > (86400)) {
	   //Only check every 24 hours.
      $getfile = true;
    }
    
	//Use the cache.
    if(!$getfile) {
      $header = explode('|',file_get_contents($cachemeta));
	  //Set the Content-Type
      header($header[1]);
	  //Display the image data.
	  if($spiurl_redirect == 'N')
	  {
          print(file_get_contents($cachefile));
      }
      else
      {
          header('Location: '.$spiurl_cache_url.$user.'_'.$size);
      }
	  return;
    }
	
	//Snoopy Path
	$snoopy_path = dirname(__FILE__);
    if(function_exists('get_option'))
    {
        $snoopy_path = ABSPATH . WPINC;
    }
	
	//Not using the cache.    
    if(!class_exists('Snoopy')) {
      if(!@include_once($snoopy_path . '/class-snoopy.php')) {
        return false;
      }
    }
 
    $Snoopy = new Snoopy();
    
    if(file_exists($cachemeta))
    {
        //Check url to see if it's still valid.
        $header = explode('|',file_get_contents($cachemeta));
		$url = $header[0];
        if(@$Snoopy->fetch($url)) {
		   $content_type = spi_content_type($Snoopy->headers);
          if($content_type == $header[1]) {
			//Url is still good -- rewrite meta file & display data.
            file_put_contents($cachemeta, $url.'|'.$content_type); 
			//Set the Content-Type
			header($content_type);     
            if($spiurl_redirect == 'N')
	        {
                print(file_get_contents($cachefile));
            }
            else
            {
                header('Location: '.$spiurl_cache_url.$user.'_'.$size);
            }
			return;
          }
        }
    }
    
	//Retrieve current url from Twitter.
    if(@$Snoopy->fetch('http://api.twitter.com/1/users/show.xml?id=' . $user)) {
      if(!empty($Snoopy->results)) {
        if(preg_match_all('/<user>(.*?)<\/user>/s', $Snoopy->results, $matches)) {
          $result = array();
          foreach($matches[0] as $match) {
              $result = spi_getToken($match, 'profile_image_url');
          }
        }
        else
        {
            //default image
            $result = 'http://s.twimg.com/a/1267135446/images/default_profile_1_normal.png';
        }
        //Set the image name based on its size.
        $url = spi_sizeIt($size, $result);
        if(@$Snoopy->fetch($url))
        {
            if(!empty($Snoopy->results)) {
                $data = $Snoopy->results;
                $content_type = spi_content_type($Snoopy->headers);
                //Write the meta file.
                file_put_contents($cachemeta, $url.'|'.$content_type);
                //Write the data file.
                file_put_contents($cachefile, $data);
                //Set the header
                header($content_type);

				//Show the image or redirect to it.
	            if($spiurl_redirect == 'N')
	            {
                    print($data);
                }
                else
                {
                    header('Location: '.$spiurl_cache_url.$user.'_'.$size);
                }
            }
        }
      }
    }
 }
 

//*****************************************************************************
//* spi_content_type - Extract the Content-Type from the header.
//*****************************************************************************
 function spi_content_type($http_response_header)
 {
	$content_type = '';
	$nlines = count( $http_response_header );
	for ( $i = $nlines-1; $i >= 0; $i-- ) 
	{
		$line = $http_response_header[$i];
		if(strlen($line) > 12)
		{
		    if ( substr( $line, 0, 12 ) == 'Content-Type') {
			    $content_type = $line;
			    break;
		    }
		}
    }
	return $content_type;
} 
 

//*****************************************************************************
//* spi_getToken - Helper for xml parsing.
//*****************************************************************************
function spi_getToken($data, $pattern) 
{
    if(preg_match('|<' . $pattern . '>(.*?)</' . $pattern . '>|s', $data, $matches)) 
    {
        return $matches[1];
    }
    return '';
}

//*****************************************************************************
//* spi_sizeIt - Alter the name of the image url based on the requested size.
//*****************************************************************************
function spi_sizeIt($size, $url)
{
	$temp_url = $url;
	// mini, normal, original, bigger, large
	if($size == "mini")
	{
		$temp_url = str_replace("_normal","_mini", $url);
	}
	if($size == "large")
	{
		$temp_url = str_replace("_normal","_bigger", $url);
	}
	if($size == "bigger")
	{
		$temp_url = str_replace("_normal","_bigger", $url);
	}
	if($size == "original")
	{
		$temp_url = str_replace("_normal","", $url);
	}
		
	return $temp_url;
}
?>