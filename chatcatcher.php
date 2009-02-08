<?php
//*****************************************************************************
//* Chat Catcher Script
//* This script can be used with any blog engine.
//* 
//*****************************************************************************
$ccVersion = 2.2;

//*****************************************************************************
//* REQUIRED SETTINGS 
//*****************************************************************************

//*****************************************************************************
//* Secret 
//* Example: $cc_secret = '740757a0-e1a3-11dd-ad8b-0800200c9a66';
//* A secret is just an id that is shared between this script and Chat Catcher.
//* The secret can be anything you like.
//* You may use the link below to generate a secret:
//* http://www.famkruithof.net/uuid/uuidgen
//*****************************************************************************
$cc_secret = '%%secret%%';

//*****************************************************************************
//* Admin Email - Leave this blank for WordPress.
//* All other blogs must supply an email address.
//*****************************************************************************
$admin_email='';

//*****************************************************************************
//* Blog Home - Leave this blank for WordPress.
//* All other blogs must supply the blog's home url.
//* You may include the 'www' or leave it out.  We will search for both.
//*****************************************************************************
$blog_home='http://';


//*****************************************************************************
//* OPTIONAL SETTINGS - You can change the settings below. 
//*****************************************************************************

//*****************************************************************************
//* Exclusions
//* 
//* A list of users who will be rejected.  You may include your own
//* username.  Each username should begin on a separate line.  Start by
//* replacing FirstUser, SecondUser, and ThirdUser.  Add additional lines
//* as needed, above the last KEEPME1 line.  
//*****************************************************************************
//Do not include '@'
$cc_exclude = <<<KEEPME1
FirstUser
SecondUser
ThirdUser
KEEPME1;

//*****************************************************************************
//* Log - Writes a log file.
//* Values 'Y' or 'N'
//*****************************************************************************
$cclog='N';

//*****************************************************************************
//* WORDPRESS SPECIFIC - Stop here for all other blogs.
//*****************************************************************************
/*
	Plugin Name: Chat Catcher
	Plugin URI: http://chatcatcher.com
	Description: Post comments from social media services to your blog.
	Author: Shannon Whitley
	Author URI: http://chatcatcher.com
	Version: 2.2
*/

//*****************************************************************************
//* WordPress: Comment Template - You can modify the HTML below.
//*
//* Special variables are enclosed in double-percent signs.
//* Do not remove %%excerpt%% (the main comment).
//*****************************************************************************
$cc_template = <<<KEEPME2
<strong>%%title%%</strong>
<a href="%%profile_link%%" title="%%title%%">
<div title="%%blog_name%%" style="float:left;margin-right:10px;padding:0;width:60px;height:60px;background:url(http://s3.amazonaws.com/static.whitleymedia/picbg.jpg) no-repeat top;cursor:hand;">
</div>
<div title="%%blog_name%%" style="float:left;margin-left:-70px;margin-right:10px;padding:0;width:60px;height:60px;background:url(%%pic%%) no-repeat top;cursor:hand;">
</div>
</a>
%%excerpt%%
KEEPME2;

//*****************************************************************************
//* WordPress: Post As Trackback
//*                           
//* Values 'Y' or 'N'
//* 'Y' to post as a trackback.  'N' to post as a regular comment.
//*****************************************************************************
$postTrackbacks = 'Y';

//*****************************************************************************
//* WordPress: Post As Admin - Avoids several moderation checks.
//*                           
//* Values 'Y' or 'N'
//* Set to 'N' if you'd like to moderate Chat Catcher comments.
//*****************************************************************************
$postAsAdmin = 'Y';

//*****************************************************************************
//* END SETTINGS - Stop!!!
//*****************************************************************************

/*
Copyright (c) 2008 Whitley Media

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

$WPBlog = 'N';
$pageShow = 'Y';
if(function_exists('get_option') || file_exists('../../../wp-load.php'))
{
    $WPBlog = 'Y';
    if (function_exists('get_option')) {
        register_activation_hook( __FILE__, 'ccPlugin_Activate');
        register_deactivation_hook( __FILE__, 'ccPlugin_Deactivate' );
        add_action('plugins_loaded','ccPlugin_Loaded');
        $pageShow = 'N';
    }
    else
    {
        include_once('../../../wp-load.php');
        wp('tb=1');
    }
}

if(isset($_REQUEST["secret"]))
{
    if($_POST["secret"] == $cc_secret)
    {
        if(isset($_POST['trackback-uri']))
        {
            ccTrackBack();
        }
        else
        {
        	header('Content-Type: text; charset=UTF-8');
            die('trackback-uri not found');
        }        
    }
}
else
{
    $postComments = 'N';
    if($WPBlog == 'Y' && $postTrackbacks == 'N')
    {
        $postComments = 'Y';
    }
    if($pageShow == 'Y')
    {
    	header('Content-Type: text/html; charset=UTF-8');
    	echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        echo '<html><head><title>Chat Catcher</title></head><body>';
        echo '<div style="font-family:Arial"><h1>Chat Catcher</h1>';
        if($WPBlog != 'Y')
        {
            echo '<p><a href="chatcatcher.php?blog_activate=1">Activate This Script</a></p>';
        }
        echo '<p><a href="http://www.chatcatcher.com/tester.aspx?&a='.cc_selfURL().'">Test This Script</a></p>';
        echo '<p>&nbsp;</p><p><strong>System Information</strong>';
        echo '<br/>Version: '.$ccVersion.'<br/>';
        echo '<br/>WordPress Blog: '.$WPBlog.'<br/>Post Comments: '.$postComments.'<br/>Post Trackbacks: '.$postTrackbacks.'</p>';
        echo '<p><a href="http://www.chatcatcher.com">Chat Catcher</a></p>';
        echo '</div>';
        echo '</body></html>';
    }
}

if(isset($_GET['blog_activate']))
{
    ccBlog_Register();
}

//*****************************************************************************
//* ccTrackBack - Main Process
//*****************************************************************************
function ccTrackBack() {

    global $cc_exclude, $WPBlog, $postTrackbacks;
    
    $cc_exclude = str_replace("\r","",$cc_exclude);
    $excludeArray = explode("\n",$cc_exclude);
    
    $title = $_POST['title'];
    $excerpt = $_POST['excerpt'];
    $url = $_POST['url'];
    $blog_name = $_POST['blog'];
    $tb_url = $_POST['trackback-uri'];
    $pic = $_POST['pic'];
    $profile_link = $_POST['profile_link'];
    
    $temp = explode('(',$blog_name);
    $blog_screen_name = trim($temp[0]);
    
    foreach($excludeArray as $screen_name)
    {
        $screen_name = trim($screen_name);
        if($blog_screen_name == $screen_name)
        {
            ccTrackback_response(0, 'Excluded User');
            return;
        }
    }
    
    if($WPBlog == 'Y')
    {
        //WordPress Comment
        ccWPComment($title, $excerpt, $url, $blog_name, $tb_url, $pic, $profile_link);
    }
    else
    {
        //Standard Trackback
        $title = urlencode(stripslashes($title));
        $excerpt = urlencode(stripslashes($excerpt));
        $url = urlencode($url);
        $blog_name = urlencode(stripslashes($blog_name));

	    $query_string = "title=$title&url=$url&blog_name=$blog_name&excerpt=$excerpt";
	    $trackback_url = parse_url($tb_url);
	    $http_request = 'POST ' . $trackback_url['path'] . ($trackback_url['query'] ? '?'.$trackback_url['query'] : '') . " HTTP/1.0\r\n";
	    $http_request .= 'Host: '.$trackback_url['host']."\r\n";
	    $http_request .= 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8'."\r\n";
	    $http_request .= 'Content-Length: '.strlen($query_string)."\r\n";
	    $http_request .= "User-Agent: Chat Catcher/1";
	    $http_request .= "\r\n\r\n";
	    $http_request .= $query_string;
	    $port = 80;
	    if(isset($trackback_url['port']))
	    {
	        $port = $trackback_url['port'];
	    }
	    $fs = @fsockopen($trackback_url['host'], $port, $errno, $errstr, 4);
	    @fputs($fs, $http_request);
	    @fclose($fs);
	}
	
    if(strlen($errstr) > 0)
    {
        ccTrackback_response(1, $errstr);
    }
    else
    {
        ccTrackback_response(0, '');
    }	
}
//*****************************************************************************
//* ccWPComment - WordPress Comment Processing
//*****************************************************************************
function ccWPComment($title, $excerpt, $url, $blog_name, $tb_url, $pic, $profile_link)
{
    global $wpdb, $wp_query, $postAsAdmin, $cc_template, $postTrackbacks;
    
    $title     = stripslashes($title);
    $excerpt   = stripslashes($excerpt);
    $blog_name = stripslashes($blog_name);
   
    $tb_id = url_to_postid($tb_url); 

    if ( !intval( $tb_id ) )
	    ccTrackback_response(1, 'I really need an ID for this to work.');

    //Convert to standard comment.
    $comment_type = '';
    if($postTrackbacks == 'Y')
    {
        $comment_type = 'trackback';
    }
	$comment_post_ID = (int) $tb_id;
	$comment_author = $blog_name;
	$comment_author_email = '';
	$comment_author_url = $url;
	$comment_content = $cc_template;

    //Process Template Variables	
	$comment_content = str_replace('%%title%%',$title,$comment_content);
	$comment_content = str_replace('%%pic%%',$pic,$comment_content);
    $comment_content = str_replace('%%profile_link%%',$profile_link,$comment_content);
	$comment_content = str_replace('%%excerpt%%',$excerpt,$comment_content);
	$temp = explode('(',$blog_name);
	$screen_name = trim($temp[0]);
	$comment_content = str_replace('%%screen_name%%',$screen_name,$comment_content);
	$comment_content = str_replace('%%blog_name%%',$blog_name,$comment_content);

	
	$dupe = $wpdb->get_results( $wpdb->prepare("SELECT * FROM $wpdb->comments WHERE comment_post_ID = %d AND comment_author_url = %s", $comment_post_ID, $comment_author_url) );
	if ( $dupe )
		ccTrackback_response(1, 'We already have a ping from that URL for this post.');

    //Post as admin user to avoid spam filter.
    //Retrieve admin users
    if($postAsAdmin == 'Y')
    {
        $users = $wpdb->get_results( "SELECT user_id FROM $wpdb->users, $wpdb->usermeta WHERE " . $wpdb->users . ".ID = " . $wpdb->usermeta . ".user_id AND meta_key = '" . $wpdb->prefix . "capabilities' and meta_value='10' LIMIT 1 " );
        foreach($users as $user)
        {
            $user_id = $user["user_id"];
        }
	    $userdata = get_userdata($user_id);
    }
	$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'userdata');

    //Allows HTML
    kses_remove_filters();
    //Remove Subscribe To Comments (Subscribers are confused by HTML in message)
    ccRemoveSubscribeToComments();
    
    //Post the Comment
	$commentdata['comment_post_ID'] = (int) $commentdata['comment_post_ID'];
	$commentdata['user_ID']         = (int) $commentdata['user_ID'];

	$commentdata['comment_parent'] = absint($commentdata['comment_parent']);
	$parent_status = ( 0 < $commentdata['comment_parent'] ) ? wp_get_comment_status($commentdata['comment_parent']) : '';
	$commentdata['comment_parent'] = ( 'approved' == $parent_status || 'unapproved' == $parent_status ) ? $commentdata['comment_parent'] : 0;

	$commentdata['comment_author_IP'] = preg_replace( '/[^0-9a-fA-F:., ]/', '',$_SERVER['REMOTE_ADDR'] );
	$commentdata['comment_agent']     = $_SERVER['HTTP_USER_AGENT'];

	$commentdata['comment_date']     = current_time('mysql');
	$commentdata['comment_date_gmt'] = current_time('mysql', 1);

	$commentdata = wp_filter_comment($commentdata);

	$commentdata['comment_approved'] = wp_allow_comment($commentdata);

	$comment_ID = wp_insert_comment($commentdata);

	do_action('comment_post', $comment_ID, $commentdata['comment_approved']);

	if ( 'spam' !== $commentdata['comment_approved'] ) { // If it's spam save it silently for later crunching
		if ( '0' == $commentdata['comment_approved'] )
			wp_notify_moderator($comment_ID);

		$post = &get_post($commentdata['comment_post_ID']); // Don't notify if it's your own comment

		if ( get_option('comments_notify') && $commentdata['comment_approved'] && $post->post_author != $commentdata['user_ID'] )
			wp_notify_postauthor($comment_ID, $commentdata['comment_type']);
	}
	
	//disqus
	if(function_exists('dsq_is_installed') && file_exists('../disqus/export.php'))
	{
	        global $wp_version;
	    	require_once('../disqus/export.php');
	        dsq_export_wp();
	}

}

function ccTrackback_response($error = 0, $error_message = '') {
    ccLogIt($error.' '.$error_message);
	header('Content-Type: text/xml; charset=UTF-8');
	if ($error) {
		echo '<?xml version="1.0" encoding="utf-8"?'.">\n";
		echo "<response>\n";
		echo "<error>1</error>\n";
		echo "<message>$error_message</message>\n";
		echo "</response>";
		die();
	} else {
		echo '<?xml version="1.0" encoding="utf-8"?'.">\n";
		echo "<response>\n";
		echo "<error>0</error>\n";
		echo "</response>";
	}
}

function ccRemoveSubscribeToComments()
{
    global $wp_filter;

	$action_ref	= 'comment_post';
	$filter 	= $wp_filter[$action_ref];
	$_lambda	= array();

	//Priority is 50 for Subscribe to Comments.
	foreach(range(50,50) as $priority){
		if (isset($filter[$priority]))
		{
			foreach($filter[$priority] as $registered_filter ){
				$callback = (string) $registered_filter['function'];
				if ( preg_match("/lambda/", $callback) ) {
		 	 		$_lambda[$priority][] = $callback;
				}
			}
		}
	}

	if ( count($_lambda) >= 0 ){
		foreach($_lambda as $priority => $callback) {
			if ( has_filter($action_ref,$callback) ){
				remove_filter($action_ref, $callback, $priority, 1);
			}
		}
	}
}

function ccLogIt($msg)
{
	global $cclog;
	if ($cclog == 'Y') {
		$fp = fopen("./cc.log","a+");
		$date = gmdate("Y-m-d H:i:s ");
		fwrite($fp, "\n\n".$date.$msg);
		fclose($fp);
	}
	return true;
}

function ccPlugin_Register() {

    global $cc_secret;
    
    if ( !class_exists('Snoopy') ) {
        include_once( ABSPATH . WPINC . '/class-snoopy.php' );
    } 
    $siteurl = get_option('siteurl');
    $home = get_option('home');
    $plugin_url = $siteurl.'/wp-content/plugins/chatcatcher/chatcatcher.php';
    $email = get_option("admin_email");

    $snoopy = new Snoopy();
    $snoopy->agent = 'WP-ChatCatcher (Snoopy)';
    $snoopy->host = $_SERVER[ 'HTTP_HOST' ];
    $snoopy->read_timeout = "180";
    $snoopy->use_gzip = MAGPIE_USE_GZIP;
    $url = 'http://api.chatcatcher.com/register.aspx';
    $post = array();
    $post['script'] = $plugin_url;
    $post['secret'] = $cc_secret;
    $post['email'] = $email;
    $post['homeurl'] = $home;

    
    if(@$snoopy->submit($url,$post))
    {
        $results = $snoopy->results;
        if(strlen($results) > 0 && strpos($results,'Congratulations') === false)
        {
            if(function_exists('deactivate_plugins'))
            {
                deactivate_plugins('chatcatcher/chatcatcher.php');
            }
            else
            {
                $results.='<p>Please deactivate this plugin and try again.</p>';
            }
            wp_die($results.'<p><a href="plugins.php">Return</a></p>');
        }
    } 
    else {
        $results = "Error contacting Chat Catcher: ".$snoopy->error."\n";
        if(function_exists('deactivate_plugins'))
        {
            deactivate_plugins('chatcatcher/chatcatcher.php');
        }
        else
        {
            $results.='<p>Please deactivate this plugin and try again.</p>';
        }
        wp_die($results.'<p><a href="plugins.php">Return</a></p>');
    }
}

function ccPlugin_Loaded()
{
    $ccIni = get_option('ChatCatcherInstalled');
    if(!empty($ccIni))
    {
        if($ccIni == 'N')
        {
            update_option('ChatCatcherInstalled','Y');
            ccPlugin_Register();
        }
    }
}

function ccBlog_Register() {

    global $cc_secret, $blog_home, $admin_email;

    $home = $blog_home;
    $plugin_url = cc_selfURL();
    $email = $admin_email;
    
    $post = 'script='.$plugin_url.'&secret='.$cc_secret.'&email='.$email.'&homeurl='.$home;
    
    $http = new Http();
    $http->setMethod('POST');
    $http->addParam('script', $plugin_url);
    $http->addParam('secret', $cc_secret);
    $http->addParam('email', $email);
    $http->addParam('homeurl', $home);
                    
    $http->setTimeout(60);                    
    $http->execute('http://api.chatcatcher.com/register.aspx');
    $results = ($http->error) ? $http->error : $http->result;

    $results = '<div style="color:red;"><hr/><p>'.$results.'</p></div>';
    
    if(strpos($results,'Congratulations') != false)
    {
        die($results);
    }
    if(strlen($results) > 0 && strpos($results,'Congratulations') === false)
    {
        die($results);
    }
    else {
        $results = '<div style="color:red"><hr/>Error contacting Chat Catcher: '.$http->error.'</div>';                
        die($results);
    }
}


function ccPlugin_Activate()
{
    $ccIni = get_option('ChatCatcherInstalled');
    if(empty($ccIni))
    {
        update_option('ChatCatcherInstalled','N');
    }
}

function ccPlugin_Deactivate()
{
    $ccIni = get_option('ChatCatcherInstalled');
    if(!empty($ccIni))
    {
        delete_option('ChatCatcherInstalled');
    }
}

function cc_selfURL() {
    $addl = '';
    if(isset($_SERVER['REQUEST_URI']))
    {
        $addl = $_SERVER['REQUEST_URI'];
    }
    if(!isset($_SERVER['REQUEST_URI']))
    {
        $addl = $_SERVER['SCRIPT_NAME'];
    }    
    $s = empty($_SERVER["HTTPS"]) ? "" : ($_SERVER["HTTPS"] == "on") ? "s" : "";
    $protocol = cc_strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
    $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
    return $protocol."://".$_SERVER['SERVER_NAME'].$port.$addl;
}

function cc_strleft($s1, $s2) {
    $values = substr($s1, 0, strpos($s1, $s2));
    return  $values;
}

/**
 * HTTP Class
 *
 * This is a wrapper HTTP class that uses either cURL or fsockopen to 
 * harvest resources from web. This can be used with scripts that need 
 * a way to communicate with various APIs who support REST.
 *
 * @author      Md Emran Hasan <phpfour@gmail.com>
 * @package     HTTP Library
 * @copyright   2007-2008 Md Emran Hasan
 * @link        http://www.phpfour.com/lib/http
 * @since       Version 0.1
 */

class Http
{
    /**
     * Contains the target URL
     *
     * @var string
     */
    var $target;
    
    /**
     * Contains the target host
     *
     * @var string
     */
    var $host;
    
    /**
     * Contains the target port
     *
     * @var integer
     */
    var $port;
    
    /**
     * Contains the target path
     *
     * @var string
     */
    var $path;
    
    /**
     * Contains the target schema
     *
     * @var string
     */
    var $schema;
    
    /**
     * Contains the http method (GET or POST)
     *
     * @var string
     */
    var $method;
    
    /**
     * Contains the parameters for request
     *
     * @var array
     */
    var $params;
    
    /**
     * Contains the cookies for request
     *
     * @var array
     */
    var $cookies;
    
    /**
     * Contains the cookies retrieved from response
     *
     * @var array
     */
    var $_cookies;
    
    /**
     * Number of seconds to timeout
     *
     * @var integer
     */
    var $timeout;
    
    /**
     * Whether to use cURL or not
     *
     * @var boolean
     */
    var $useCurl;
    
    /**
     * Contains the referrer URL
     *
     * @var string
     */
    var $referrer;
    
    /**
     * Contains the User agent string
     *
     * @var string
     */
    var $userAgent;
    
    /**
     * Contains the cookie path (to be used with cURL)
     *
     * @var string
     */
    var $cookiePath;
    
    /**
     * Whether to use cookie at all
     *
     * @var boolean
     */
    var $useCookie;
    
    /**
     * Whether to store cookie for subsequent requests
     *
     * @var boolean
     */
    var $saveCookie;
    
    /**
     * Contains the Username (for authentication)
     *
     * @var string
     */
    var $username;
    
    /**
     * Contains the Password (for authentication)
     *
     * @var string
     */
    var $password;
    
    /**
     * Contains the fetched web source
     *
     * @var string
     */
    var $result;
    
    /**
     * Contains the last headers 
     *
     * @var string
     */
    var $headers;
    
    /**
     * Contains the last call's http status code
     *
     * @var string
     */
    var $status;
    
    /**
     * Whether to follow http redirect or not
     *
     * @var boolean
     */
    var $redirect;
    
    /**
     * The maximum number of redirect to follow
     *
     * @var integer
     */
    var $maxRedirect;
    
    /**
     * The current number of redirects
     *
     * @var integer
     */
    var $curRedirect;
    
    /**
     * Contains any error occurred
     *
     * @var string
     */
    var $error;
    
    /**
     * Store the next token
     *
     * @var string
     */
    var $nextToken;
    
    /**
     * Whether to keep debug messages
     *
     * @var boolean
     */
    var $debug;
    
    /**
     * Stores the debug messages
     *
     * @var array
     * @todo will keep debug messages
     */
    var $debugMsg;
    
    /**
     * Constructor for initializing the class with default values.
     * 
     * @return void  
     */
    function Http()
    {
        $this->clear();    
    }
    
    /**
     * Initialize preferences
     * 
     * This function will take an associative array of config values and 
     * will initialize the class variables using them. 
     * 
     * Example use:
     * 
     * <pre>
     * $httpConfig['method']     = 'GET';
     * $httpConfig['target']     = 'http://www.somedomain.com/index.html';
     * $httpConfig['referrer']   = 'http://www.somedomain.com';
     * $httpConfig['user_agent'] = 'My Crawler';
     * $httpConfig['timeout']    = '30';
     * $httpConfig['params']     = array('var1' => 'testvalue', 'var2' => 'somevalue');
     * 
     * $http = new Http();
     * $http->initialize($httpConfig);
     * </pre>
     *
     * @param array Config values as associative array
     * @return void
     */    
    function initialize($config = array())
    {
        $this->clear();
        foreach ($config as $key => $val)
        {
            if (isset($this->$key))
            {
                $method = 'set' . ucfirst(str_replace('_', '', $key));
                
                if (method_exists($this, $method))
                {
                    $this->$method($val);
                }
                else
                {
                    $this->$key = $val;
                }            
            }
        }
    }
    
    /**
     * Clear Everything
     * 
     * Clears all the properties of the class and sets the object to
     * the beginning state. Very handy if you are doing subsequent calls 
     * with different data.
     *
     * @return void
     */
    function clear()
    {
        // Set the request defaults
        $this->host         = '';
        $this->port         = 0;
        $this->path         = '';
        $this->target       = '';
        $this->method       = 'GET';
        $this->schema       = 'http';
        $this->params       = array();
        $this->headers      = array();
        $this->cookies      = array();
        $this->_cookies     = array();
        
        // Set the config details        
        $this->debug        = FALSE;
        $this->error        = '';
        $this->status       = 0;
        $this->timeout      = '25';
        $this->useCurl      = TRUE;
        $this->referrer     = '';
        $this->username     = '';
        $this->password     = '';
        $this->redirect     = TRUE;
        
        // Set the cookie and agent defaults
        $this->nextToken    = '';
        $this->useCookie    = TRUE;
        $this->saveCookie   = TRUE;
        $this->maxRedirect  = 3;
        $this->cookiePath   = 'cookie.txt';
        $this->userAgent    = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.9';
    }
    
    /**
     * Set target URL
     *
     * @param string URL of target resource
     * @return void
     */
    function setTarget($url)
    {
        if ($url)
        {
            $this->target = $url;
        }   
    }
    
    /**
     * Set http method
     *
     * @param string HTTP method to use (GET or POST)
     * @return void
     */
    function setMethod($method)
    {
        if ($method == 'GET' || $method == 'POST')
        {
            $this->method = $method;
        }   
    }
    
    /**
     * Set referrer URL
     *
     * @param string URL of referrer page
     * @return void
     */
    function setReferrer($referrer)
    {
        if ($referrer)
        {
            $this->referrer = $referrer;
        }   
    }
    
    /**
     * Set User agent string
     *
     * @param string Full user agent string
     * @return void
     */
    function setUseragent($agent)
    {
        if ($agent)
        {
            $this->userAgent = $agent;
        }   
    }
    
    /**
     * Set timeout of execution
     *
     * @param integer Timeout delay in seconds
     * @return void
     */
    function setTimeout($seconds)
    {
        if ($seconds > 0)
        {
            $this->timeout = $seconds;
        }   
    }
    
    /**
     * Set cookie path (cURL only)
     *
     * @param string File location of cookiejar
     * @return void
     */
    function setCookiepath($path)
    {
        if ($path)
        {
            $this->cookiePath = $path;
        }   
    }
    
    /**
     * Set request parameters
     *
     * @param array All the parameters for GET or POST
     * @return void
     */
    function setParams($dataArray)
    {
        if (is_array($dataArray))
        {
            $this->params = array_merge($this->params, $dataArray);
        }   
    }
    
    /**
     * Set basic http authentication realm
     *
     * @param string Username for authentication
     * @param string Password for authentication
     * @return void
     */
    function setAuth($username, $password)
    {
        if (!empty($username) && !empty($password))
        {
            $this->username = $username;
            $this->password = $password;
        }
    }
    
    /**
     * Set maximum number of redirection to follow
     *
     * @param integer Maximum number of redirects
     * @return void
     */
    function setMaxredirect($value)
    {
        if (!empty($value))
        {
            $this->maxRedirect = $value;
        }
    }
    
    /**
     * Add request parameters
     *
     * @param string Name of the parameter
     * @param string Value of the parameter
     * @return void
     */
    function addParam($name, $value)
    {
        if (!empty($name) && !empty($value))
        {
            $this->params[$name] = $value;
        }   
    }
    
    /**
     * Add a cookie to the request
     *
     * @param string Name of cookie
     * @param string Value of cookie
     * @return void
     */
    function addCookie($name, $value)
    {
        if (!empty($name) && !empty($value))
        {
            $this->cookies[$name] = $value;
        }   
    }
    
    /**
     * Whether to use cURL or not
     *
     * @param boolean Whether to use cURL or not
     * @return void
     */
    function useCurl($value = TRUE)
    {
        if (is_bool($value))
        {
            $this->useCurl = $value;
        }   
    }
    
    /**
     * Whether to use cookies or not
     *
     * @param boolean Whether to use cookies or not
     * @return void
     */
    function useCookie($value = TRUE)
    {
        if (is_bool($value))
        {
            $this->useCookie = $value;
        }   
    }
    
    /**
     * Whether to save persistent cookies in subsequent calls
     *
     * @param boolean Whether to save persistent cookies or not
     * @return void
     */
    function saveCookie($value = TRUE)
    {
        if (is_bool($value))
        {
            $this->saveCookie = $value;
        }   
    }
    
    /**
     * Whether to follow HTTP redirects
     *
     * @param boolean Whether to follow HTTP redirects or not
     * @return void
     */
    function followRedirects($value = TRUE)
    {
        if (is_bool($value))
        {
            $this->redirect = $value;
        }   
    }
    
    /**
     * Get execution result body
     *
     * @return string output of execution
     */
    function getResult()
    {
        return $this->result;
    }
    
    /**
     * Get execution result headers
     *
     * @return array last headers of execution
     */
    function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get execution status code
     *
     * @return integer last http status code
     */
    function getStatus()
    {
        return $this->status;
    }
        
    /**
     * Get last execution error
     *
     * @return string last error message (if any)
     */
    function getError()
    {
        return $this->error;
    }

    /**
     * Execute a HTTP request
     * 
     * Executes the http fetch using all the set properties. Intellegently
     * switch to fsockopen if cURL is not present. And be smart to follow
     * redirects (if asked so).
     * 
     * @param string URL of the target page (optional)
     * @param string URL of the referrer page (optional)
     * @param string The http method (GET or POST) (optional)
     * @param array Parameter array for GET or POST (optional)
     * @return string Response body of the target page
     */    
    function execute($target = '', $referrer = '', $method = '', $data = array())
    {
        // Populate the properties
        $this->target = ($target) ? $target : $this->target;
        $this->method = ($method) ? $method : $this->method;
        
        $this->referrer = ($referrer) ? $referrer : $this->referrer;
        
        // Add the new params
        if (is_array($data) && count($data) > 0) 
        {
            $this->params = array_merge($this->params, $data);
        }
        
        // Process data, if presented
        if(is_array($this->params) && count($this->params) > 0)
        {
            // Get a blank slate
            $tempString = array();
            
            // Convert data array into a query string (ie animal=dog&sport=baseball)
            foreach ($this->params as $key => $value) 
            {
                if(strlen(trim($value))>0)
                {
                    $tempString[] = $key . "=" . urlencode($value);
                }
            }
            
            $queryString = join('&', $tempString);
        }
        
        // If cURL is not installed, we'll force fscokopen
        $this->useCurl = $this->useCurl && in_array('curl', get_loaded_extensions());
        
        // GET method configuration
        if($this->method == 'GET')
        {
            if(isset($queryString))
            {
                $this->target = $this->target . "?" . $queryString;
            }
        }
        
        // Parse target URL
        $urlParsed = parse_url($this->target);
        
        // Handle SSL connection request
        if ($urlParsed['scheme'] == 'https')
        {
            $this->host = 'ssl://' . $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 443;
        }
        else
        {
            $this->host = $urlParsed['host'];
            $this->port = ($this->port != 0) ? $this->port : 80;
        }
        
        // Finalize the target path
        $this->path   = (isset($urlParsed['path']) ? $urlParsed['path'] : '/') . (isset($urlParsed['query']) ? '?' . $urlParsed['query'] : '');
        $this->schema = $urlParsed['scheme'];
        
        // Pass the requred cookies
        $this->_passCookies();
        
        // Process cookies, if requested
        if(is_array($this->cookies) && count($this->cookies) > 0)
        {
            // Get a blank slate
            $tempString   = array();
            
            // Convert cookiesa array into a query string (ie animal=dog&sport=baseball)
            foreach ($this->cookies as $key => $value) 
            {
                if(strlen(trim($value)) > 0)
                {
                    $tempString[] = $key . "=" . urlencode($value);
                }
            }
            
            $cookieString = join('&', $tempString);
        }
        
        // Do we need to use cURL
        if ($this->useCurl)
        {
            // Initialize PHP cURL handle
            $ch = curl_init();
    
            // GET method configuration
            if($this->method == 'GET')
            {
                curl_setopt ($ch, CURLOPT_HTTPGET, TRUE); 
                curl_setopt ($ch, CURLOPT_POST, FALSE); 
            }
            // POST method configuration
            else
            {
                if(isset($queryString))
                {
                    curl_setopt ($ch, CURLOPT_POSTFIELDS, $queryString);
                }
                
                curl_setopt ($ch, CURLOPT_POST, TRUE); 
                curl_setopt ($ch, CURLOPT_HTTPGET, FALSE); 
            }
            
            // Basic Authentication configuration
            if ($this->username && $this->password)
            {
                curl_setopt($ch, CURLOPT_USERPWD, $this->username . ':' . $this->password);
            }
            
            // Custom cookie configuration
            if($this->useCookie && isset($cookieString))
            {
                curl_setopt ($ch, CURLOPT_COOKIE, $cookieString);
            }
            
            curl_setopt($ch, CURLOPT_HEADER,         TRUE);                 // No need of headers
            curl_setopt($ch, CURLOPT_NOBODY,         FALSE);                // Return body
                
            curl_setopt($ch, CURLOPT_COOKIEJAR,      $this->cookiePath);    // Cookie management.
            curl_setopt($ch, CURLOPT_TIMEOUT,        $this->timeout);       // Timeout
            curl_setopt($ch, CURLOPT_USERAGENT,      $this->userAgent);     // Webbot name
            curl_setopt($ch, CURLOPT_URL,            $this->target);        // Target site
            curl_setopt($ch, CURLOPT_REFERER,        $this->referrer);      // Referer value
            
            curl_setopt($ch, CURLOPT_VERBOSE,        FALSE);                // Minimize logs
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);                // No certificate
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->redirect);      // Follow redirects
            curl_setopt($ch, CURLOPT_MAXREDIRS,      $this->maxRedirect);   // Limit redirections to four
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);                 // Return in string
            
            // Get the target contents
            $content = curl_exec($ch);
            $contentArray = explode("\r\n\r\n", $content);
            
            // Get the request info 
            $status  = curl_getinfo($ch);
			
			// Get the headers
			$resHeader = array_shift($contentArray);

			// Store the contents
			$this->result = implode($contentArray, "\r\n\r\n");

			// Parse the headers
			$this->_parseHeaders($resHeader);
            
            // Store the error (is any)
            $this->_setError(curl_error($ch));
            
            // Close PHP cURL handle
            curl_close($ch);
        }
        else
        {
            // Get a file pointer
            $filePointer = fsockopen($this->host, $this->port, $errorNumber, $errorString, $this->timeout);
       
            // We have an error if pointer is not there
            if (!$filePointer)
            {
                $this->_setError('Failed opening http socket connection: ' . $errorString . ' (' . $errorNumber . ')');
                return FALSE;
            }

            // Set http headers with host, user-agent and content type
            $requestHeader  = $this->method . " " . $this->path . "  HTTP/1.1\r\n";
            $requestHeader .= "Host: " . $urlParsed['host'] . "\r\n";
            $requestHeader .= "User-Agent: " . $this->userAgent . "\r\n";
            $requestHeader .= "Content-Type: application/x-www-form-urlencoded\r\n";
            
            // Specify the custom cookies
            if ($this->useCookie && $cookieString != '')
            {
                $requestHeader.= "Cookie: " . $cookieString . "\r\n";
            }

            // POST method configuration
            if ($this->method == "POST")
            {
                $requestHeader.= "Content-Length: " . strlen($queryString) . "\r\n";
            }
            
            // Specify the referrer
            if ($this->referrer != '')
            {
                $requestHeader.= "Referer: " . $this->referrer . "\r\n";
            }
            
            // Specify http authentication (basic)
            if ($this->username && $this->password)
            {
                $requestHeader.= "Authorization: Basic " . base64_encode($this->username . ':' . $this->password) . "\r\n";
            }
       
            $requestHeader.= "Connection: close\r\n\r\n";
       
            // POST method configuration
            if ($this->method == "POST")
            {
                $requestHeader .= $queryString;
            }           

            // We're ready to launch
            fwrite($filePointer, $requestHeader);
       
            // Clean the slate
            $responseHeader = '';
            $responseContent = '';

            // 3...2...1...Launch !
            do
            {
                $responseHeader .= fread($filePointer, 1);
            }
            while (!preg_match('/\\r\\n\\r\\n$/', $responseHeader));
            
            // Parse the headers
            $this->_parseHeaders($responseHeader);
            
            // Do we have a 302 redirect ?
            if ($this->status == '302' && $this->redirect == TRUE)
            {
                if ($this->curRedirect < $this->maxRedirect)
                {
                    // Let's find out the new redirect URL
                    $newUrlParsed = parse_url($this->headers['location']);
                    
                    if ($newUrlParsed['host'])
                    {
                        $newTarget = $this->headers['location'];    
                    }
                    else
                    {
                        $newTarget = $this->schema . '://' . $this->host . '/' . $this->headers['location'];
                    }
                    
                    // Reset some of the properties
                    $this->port   = 0;
                    $this->status = 0;
                    $this->params = array();
                    $this->method = 'GET';
                    $this->referrer = $this->target;
                    
                    // Increase the redirect counter
                    $this->curRedirect++;
                    
                    // Let's go, go, go !
                    $this->result = $this->execute($newTarget);
                }
                else
                {
                    $this->_setError('Too many redirects.');
                    return FALSE;
                }
            }
            else
            {
                // Nope...so lets get the rest of the contents (non-chunked)
                if ($this->headers['transfer-encoding'] != 'chunked')
                {
                    while (!feof($filePointer))
                    {
                        $responseContent .= fgets($filePointer, 128);
                    }
                }
                else
                {
                    // Get the contents (chunked)
                    while ($chunkLength = hexdec(fgets($filePointer)))
                    {
                        $responseContentChunk = '';
                        $readLength = 0;
                       
                        while ($readLength < $chunkLength)
                        {
                            $responseContentChunk .= fread($filePointer, $chunkLength - $readLength);
                            $readLength = strlen($responseContentChunk);
                        }

                        $responseContent .= $responseContentChunk;
                        fgets($filePointer);  
                    }
                }
                
                // Store the target contents
                $this->result = chop($responseContent);
            }
        }
        
        // There it is! We have it!! Return to base !!!
        return $this->result;
    }
    
    /**
     * Parse Headers (internal)
     * 
     * Parse the response headers and store them for finding the resposne 
     * status, redirection location, cookies, etc. 
     *
     * @param string Raw header response
     * @return void
     * @access private
     */
    function _parseHeaders($responseHeader)
    {
        // Break up the headers
        $headers = explode("\r\n", $responseHeader);
        
        // Clear the header array
        $this->_clearHeaders();
        
        // Get resposne status
        if($this->status == 0)
        {
            // Oooops !
            if(!eregi($match = "^http/[0-9]+\\.[0-9]+[ \t]+([0-9]+)[ \t]*(.*)\$", $headers[0], $matches))
            {
                $this->_setError('Unexpected HTTP response status');
                return FALSE;
            }
            
            // Gotcha!
            $this->status = $matches[1];
            array_shift($headers);
        }
        
        // Prepare all the other headers
        foreach ($headers as $header)
        {
            // Get name and value
            $headerName  = strtolower($this->_tokenize($header, ':'));
            $headerValue = trim(chop($this->_tokenize("\r\n")));
            
            // If its already there, then add as an array. Otherwise, just keep there
            if(isset($this->headers[$headerName]))
            {
                if(gettype($this->headers[$headerName]) == "string")
                {
                    $this->headers[$headerName] = array($this->headers[$headerName]);
                }
                    
                $this->headers[$headerName][] = $headerValue;
            }
            else
            {
                $this->headers[$headerName] = $headerValue;
            }
        }
            
        // Save cookies if asked 
        if ($this->saveCookie && isset($this->headers['set-cookie']))
        {
            $this->_parseCookie();
        }
    }
    
    /**
     * Clear the headers array (internal)
     *
     * @return void
     * @access private
     */
    function _clearHeaders()
    {
        $this->headers = array();
    }
    
    /**
     * Parse Cookies (internal)
     * 
     * Parse the set-cookie headers from response and add them for inclusion.
     *
     * @return void
     * @access private
     */
    function _parseCookie()
    {
        // Get the cookie header as array
        if(gettype($this->headers['set-cookie']) == "array")
        {
            $cookieHeaders = $this->headers['set-cookie'];
        }
        else
        {
            $cookieHeaders = array($this->headers['set-cookie']);
        }

        // Loop through the cookies
        for ($cookie = 0; $cookie < count($cookieHeaders); $cookie++)
        {
            $cookieName  = trim($this->_tokenize($cookieHeaders[$cookie], "="));
            $cookieValue = $this->_tokenize(";");
            
            $urlParsed   = parse_url($this->target);
            
            $domain      = $urlParsed['host'];
            $secure      = '0';
            
            $path        = "/";
            $expires     = "";
            
            while(($name = trim(urldecode($this->_tokenize("=")))) != "")
            {
                $value = urldecode($this->_tokenize(";"));
                
                switch($name)
                {
                    case "path"     : $path     = $value; break;
                    case "domain"   : $domain   = $value; break;
                    case "secure"   : $secure   = ($value != '') ? '1' : '0'; break;
                }
            }
            
            $this->_setCookie($cookieName, $cookieValue, $expires, $path , $domain, $secure);
        }
    }
    
    /**
     * Set cookie (internal)
     * 
     * Populate the internal _cookies array for future inclusion in 
     * subsequent requests. This actually validates and then populates 
     * the object properties with a dimensional entry for cookie.
     *
     * @param string Cookie name
     * @param string Cookie value
     * @param string Cookie expire date
     * @param string Cookie path
     * @param string Cookie domain
     * @param string Cookie security (0 = non-secure, 1 = secure)
     * @return void
     * @access private
     */
    function _setCookie($name, $value, $expires = "" , $path = "/" , $domain = "" , $secure = 0)
    {
        if(strlen($name) == 0)
        {
            return($this->_setError("No valid cookie name was specified."));
        }

        if(strlen($path) == 0 || strcmp($path[0], "/"))
        {
            return($this->_setError("$path is not a valid path for setting cookie $name."));
        }
            
        if($domain == "" || !strpos($domain, ".", $domain[0] == "." ? 1 : 0))
        {
            return($this->_setError("$domain is not a valid domain for setting cookie $name."));
        }
        
        $domain = strtolower($domain);
        
        if(!strcmp($domain[0], "."))
        {
            $domain = substr($domain, 1);
        }
            
        $name  = $this->_encodeCookie($name, true);
        $value = $this->_encodeCookie($value, false);
        
        $secure = intval($secure);
        
        $this->_cookies[] = array( "name"      =>  $name,
                                   "value"     =>  $value,
                                   "domain"    =>  $domain,
                                   "path"      =>  $path,
                                   "expires"   =>  $expires,
                                   "secure"    =>  $secure
                                 );
    }
    
    /**
     * Encode cookie name/value (internal)
     *
     * @param string Value of cookie to encode
     * @param string Name of cookie to encode
     * @return string encoded string
     * @access private
     */
    function _encodeCookie($value, $name)
    {
        return($name ? str_replace("=", "%25", $value) : str_replace(";", "%3B", $value));
    }
    
    /**
     * Pass Cookies (internal)
     * 
     * Get the cookies which are valid for the current request. Checks 
     * domain and path to decide the return.
     *
     * @return void
     * @access private
     */
    function _passCookies()
    {
        if (is_array($this->_cookies) && count($this->_cookies) > 0)
        {
            $urlParsed = parse_url($this->target);
            $tempCookies = array();
            
            foreach($this->_cookies as $cookie)
            {
                if ($this->_domainMatch($urlParsed['host'], $cookie['domain']) && (0 === strpos($urlParsed['path'], $cookie['path']))
                    && (empty($cookie['secure']) || $urlParsed['protocol'] == 'https')) 
                {
                    $tempCookies[$cookie['name']][strlen($cookie['path'])] = $cookie['value'];
                }
            }
            
            // cookies with longer paths go first
            foreach ($tempCookies as $name => $values) 
            {
                krsort($values);
                foreach ($values as $value) 
                {
                    $this->addCookie($name, $value);
                }
            }
        }
    }
    
    /**
    * Checks if cookie domain matches a request host (internal)
    * 
    * Cookie domain can begin with a dot, it also must contain at least
    * two dots.
    * 
    * @param string Request host
    * @param string Cookie domain
    * @return bool Match success
     * @access private
    */
    function _domainMatch($requestHost, $cookieDomain)
    {
        if ('.' != $cookieDomain{0}) 
        {
            return $requestHost == $cookieDomain;
        } 
        elseif (substr_count($cookieDomain, '.') < 2) 
        {
            return false;
        } 
        else 
        {
            return substr('.'. $requestHost, - strlen($cookieDomain)) == $cookieDomain;
        }
    }
    
    /**
     * Tokenize String (internal)
     * 
     * Tokenize string for various internal usage. Omit the second parameter 
     * to tokenize the previous string that was provided in the prior call to 
     * the function.
     *
     * @param string The string to tokenize
     * @param string The seperator to use
     * @return string Tokenized string
     * @access private
     */
    function _tokenize($string, $separator = '')
    {
        if(!strcmp($separator, ''))
        {
            $separator = $string;
            $string = $this->nextToken;
        }
        
        for($character = 0; $character < strlen($separator); $character++)
        {
            if(gettype($position = strpos($string, $separator[$character])) == "integer")
            {
                $found = (isset($found) ? min($found, $position) : $position);
            }
        }
        
        if(isset($found))
        {
            $this->nextToken = substr($string, $found + 1);
            return(substr($string, 0, $found));
        }
        else
        {
            $this->nextToken = '';
            return($string);
        }
    }
    
    /**
     * Set error message (internal)
     *
     * @param string Error message
     * @return string Error message
     * @access private
     */
    function _setError($error)
    {
        if ($error != '')
        {
            $this->error = $error;
            return $error;
        }
    }
}






?>