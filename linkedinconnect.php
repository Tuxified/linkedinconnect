<?php
/*
Plugin Name: LinkedIn Connect
Author:  Tonći Galić
Author URI: http://tuxified.com/
Plugin URI: http://tuxified.com/linkedin_for_wordpress
Description: Integrate LinkedIn and Wordpress.  Provides single-signon using oAuth and displays LinkedIn avatars.
Acknowledgments:  
	Shannon Whitley( http://voiceoftech.com/swhitley/) - Twit Connect Plugin
	Brooks Bennett (http://www.brooksskybennett.com/) - oAuth Popup

Version: 0.2
************************************************************************************
M O D I F I C A T I O N S
1. 28/12/2009 Tonći Galić - Initial Release
2. 16/03/2010 Tonći Galić - almost done for first release
************************************************************************************
************************************************************************************
I N S T R U C T I O N S

There are two ways to display the button:

1) Add the following code to your comment page where you want the button to appear:

    <!-- Begin LinkedIn Connect -->
    <?php if(function_exists('linkedin_connect')){linkedin_connect();} ?>
    <!-- End LinkedIn Connect -->

2) Or, simply allow this plugin to render the template where the code below 
   exists (usually already present but below the form):
 
    <?php do_action('comment_form', $post->ID); ?>

************************************************************************************
*/

if(!version_compare(PHP_VERSION, '5.0.0', '<'))
{
    include dirname(__FILE__).'/linkedinOAuth.php';
}

$lic_btn_images = array(WP_PLUGIN_URL.'/linkedinconnect/images/linkedin_signin.png'
                    , WP_PLUGIN_URL.'/linkedinconnect/images/linkedin_button.gif'
                    , WP_PLUGIN_URL.'/linkedinconnect/images/linkedinconnect.png'
                    );

$lic_user_login_suffix = get_option("lic_user_login_suffix");

//************************************************************************************
//* Actions and Filters
//************************************************************************************
add_action('init', 'lic_init');
add_filter("get_avatar", "lic_get_avatar",11,4);
add_action('comment_form', 'lic_show_linkedin_connect_button');
add_action("admin_menu", "lic_config_page");
add_action("wp_head", "lic_wp_head");
add_action('wp_print_styles', 'lic_stylesheet_add');
add_action('wp_admin_css','lic_stylesheet_add');

if (session_id() == "") {
    session_start();
}

if(get_option('lic_add_to_login_page') == 'Y')
{
    add_action('login_form', 'lic_login_form');
}

$lic_share_this = get_option('lic_share_this');
if($lic_share_this == 'Y')
{
    add_action('comment_post', 'lic_comment_post');
}


$lic_loaded = false;

//************************************************************************************
//* lic_init
//************************************************************************************
function lic_init()
{
    if(!is_user_logged_in())
    {
        if(isset($_GET['lic_oauth_start']))
        {
            //echo "lic_oAuth_Start<br/>\n";
            lic_oAuth_Start();
        }
        /*
        if(isset($_GET['oauth_verifier']))
        {
            //echo "lic_LinkedInInfoGet<br/>\n";
            lic_LinkedInInfoGet($_GET['oauth_verifier']);
        }

         */
        if(isset($_GET['oauth_verifier']))
        {
            //echo "lic_oAuth_Confirm";
            lic_oAuth_Confirm();
        }
    }
}

//************************************************************************************
//* linkedin_connect
//************************************************************************************
function linkedin_connect()
{
    global $lic_loaded;
    if($lic_loaded)
    {
        return;
    }
    lic_show_linkedin_connect_button();
    $lic_loaded = true;
}

//************************************************************************************
//* lic_login_form
//************************************************************************************
function lic_login_form()
{
    lic_show_linkedin_connect_button(0,'login');
}

//************************************************************************************
//* lic_wp_head
//************************************************************************************
function lic_wp_head()
{
    if(is_user_logged_in())
    {
        echo '<script type="text/javascript">'."\r\n";
        echo '<!--'."\r\n";
        echo 'if(window.opener){if(window.opener.document.getElementById("lic_connect")){window.opener.lic_bookmark("");window.close();}}'."\r\n";
        echo '//-->'."\r\n";
        echo '</script>';
    }
}

//************************************************************************************
//* lic_show_linkedin_connect_button
//************************************************************************************
function lic_show_linkedin_connect_button($id='0',$type='comment')
{
    global $user_ID, $user_email, $lic_share_this, $lic_loaded, $lic_btn_images, $lic_url, $lic_page, $lic_a;
    
    //************************************************************************************
    //* Cookie Javascript
    //************************************************************************************
    echo '<script type="text/javascript">
    <!--
        function lic_createCookie(name,value,days) {
	        if (days) {
		        var date = new Date();
		        date.setTime(date.getTime()+(days*24*60*60*1000));
		        var expires = "; expires="+date.toGMTString();
	        }
	        else var expires = "";
	        document.cookie = name+"="+value+expires+"; path=/";
        }
        function lic_readCookie(name) {
	        var nameEQ = name + "=";
	        var ca = document.cookie.split(\';\');
	        for(var i=0;i < ca.length;i++) {
		        var c = ca[i];
		        while (c.charAt(0)==\' \') c = c.substring(1,c.length);
		        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	        }
	        return null;
        }
        function lic_eraseCookie(name) {
	        lic_createCookie(name,"",-1);
        }
        function lic_updateComment(comment) { 
            if(comment){
                document.getElementById("comment").value = comment.replace(/<br\/>/g,"\n");
                lic_eraseCookie("lic_comment");
                
            }
        }
        //-->
        </script>';               
    //************************************************************************************
    //* End Cookie Javascript
    //************************************************************************************
    
    if(is_user_logged_in())
    {
        if($type == 'login')
        {
            echo '<script type="text/javascript">'."\r\n";
            echo '<!--'."\r\n";
            echo 'if(window.opener){if(window.opener.document.getElementById("lic_connect")){window.opener.lic_bookmark("");window.close();}}'."\r\n";
            echo '//-->'."\r\n";
            echo '</script>';
        }
        else
        {
            if($lic_share_this == 'Y' && get_usermeta($user_ID, 'licid'))
            {

			$post_title = strip_tags(get_the_title( $post->ID ));
    			$blog_title = get_bloginfo('name');
    
    
        		//Get the template for the update.
        		$update = get_option("lic_share_this_text");
        
        		
        		$temp_update = $update;
			$temp_update = str_replace('%%post_title%%',$post_title, $temp_update);
     		   	$temp_update = str_replace('%%blog_title%%',$blog_title, $temp_update);
     		   	$temp_update = str_replace('%%shortlink%%', 'link to here', $temp_update);
        
       		$update = $temp_update;
                echo '<p class="lic-share-this"><input type="checkbox" id="lic_share_this" name="lic_share_this" style="width:auto" /> Share This Comment [<a href="javascript:none" title="Share this comment on LinkedIn. Update will be: '.$update.'">?</a>]</p><p><input type="text" name="lic-share-this-text" max-size="140" value="" style="display:none"></p>';
            }
            //echo '<p>Update your email address: '.'<a name="licbutton" href="'.get_option('siteurl').'/wp-admin/profile.php">'.$user_email.'</a>.</p>';
            echo '<script type="text/javascript">'."\r\n";
            echo '<!--'."\r\n";
            echo 'if(!window.opener && document.getElementById("comment")){'."\r\n";
            echo '    if(document.getElementById("comment").value.length == 0)'."\r\n";
            echo '    {'."\r\n";
            echo '        lic_updateComment(lic_readCookie("lic_comment"));'."\r\n";
            echo '    }'."\r\n";
            echo '}'."\r\n";
            echo '//-->'."\r\n";
            echo '</script>'."\r\n";
            
        }
    }

    if($lic_loaded || is_user_logged_in())
    {
        return;
    }
    
    $lic_before = '';
    if($type == 'login')
    {
        $lic_login_text = get_option("lic_login_text");
        echo $lic_login_text;
        $lic_before = get_option('lic_before_login');
    }
    else
    {
    	$lic_template = get_option('lic_template');
        echo $lic_template;
        $lic_before = get_option('lic_before_comment');
    }
    
    $lic_redirect = get_option('lic_redirect');  
    $lic_btn_image = $lic_btn_images[0];
    $lic_btn_choice = get_option('lic_btn_choice');
    if(!empty($lic_btn_choice))
    {
       $lic_btn_image = $lic_btn_images[intval($lic_btn_choice) - 1];
    }
      

    //************************************************************************************
    //* Button Javascript
    //************************************************************************************
    echo '<script type="text/javascript">
    <!--
    function lic_bookmark()
    {
       var url=location.href;
       if(url.indexOf("wp-login.php") > 0)
       {
           url = "'.$lic_redirect.'";
           location.href = url;
       }
       else
       {
           var temp = url.split("#");
           url = temp[0];
           url += "#licbutton";
           location.href = url;
           location.reload();
       }
    }
    if(document.getElementById("lic_connect"))
    {
        var url = location.href;
        var button = document.createElement("button");
        button.id = "lic_button";
        button.setAttribute("class","btn");
        button.setAttribute("type","button");
        button.setAttribute("tabindex","999");
        button.onclick = function(){
            if(document.getElementById("comment"))
            {
                if(document.getElementById("comment").value.length > 0)
                {
                    var comment = document.getElementById("comment").value;
                    comment = comment.replace(/\r\n/g,"\n").replace(/\n/g,"<br/>");
                    lic_createCookie("lic_comment",comment,1);
                    var cookie = lic_readCookie("lic_comment");
                    if(cookie != comment)
                    {
                        lic_eraseCookie("lic_comment");
                        alert("The comment field must be blank before you Sign in with LinkedIn.\r\nPlease make a copy of your comment and clear the comment field.");
                        return false;
                    }
                }
            }
            window.open("'.$lic_url.'/'.$lic_page.'?a="+escape('.$lic_a.')+"&licver=2&loc="+escape(url), "licWindow","width=800,height=400,left=150,top=100,scrollbar=no,resize=no");
            return false;
        };
        button.innerHTML = "<img src=\''.$lic_btn_image.'\' alt=\'Signin with LinkedIn\' style=\'margin:0;\' />";
        document.getElementById("lic_connect").appendChild(button);';
        /* PHP */
        if(strlen($lic_before) > 0)
        {
            echo 'if(document.getElementById("'.$lic_before.'"))
                {
                    var lic_before = document.getElementById("'.$lic_before.'");
                    lic_before.parentNode.insertBefore(document.getElementById("lic_connect"),lic_before);
                }
                ';
        }
        /* END PHP */        
        echo '}
        //-->
        </script>';
    //************************************************************************************
    //* End - Button Javascript
    //************************************************************************************

    $lic_loaded = true;

}

//************************************************************************************
//* lic_stylesheet_add
//************************************************************************************
function lic_stylesheet_add() {
    $styleUrl = WP_PLUGIN_URL . '/linkedinconnect/style.css?v=1.2';
    echo '<link rel="stylesheet" type="text/css" href="'.$styleUrl.'" />';
}    


//************************************************************************************
//* lic_get_avatar
//************************************************************************************
function lic_get_avatar($avatar, $id_or_email='',$size='80') {
  global $comment, $lic_user_login_suffix;

  if(is_object($comment))
  {
      $id_or_email = $comment->user_id;
  }

  if (is_object($id_or_email)) {
     $id_or_email = $id_or_email->user_id;
  }

  if (get_usermeta($id_or_email, 'licid'))
  {
    $user_info = get_userdata($id_or_email);
    
    $avatar = "<img alt='' src='{$user_info->avatar}' class='avatar avatar-{$size}' height='{$size}' width='{$size}' />";
    return $avatar;
  } 
  else 
  {
    return $avatar;
  }
}

//************************************************************************************
//* lic_LinkedInInfoGet
//************************************************************************************
function lic_LinkedInInfoGet($req_key)
{
    global $lic_url, $lic_page;
    
    $_SESSION['lic_req_key'] = $req_key;

    if ( !class_exists('Snoopy') ) {
        include_once( ABSPATH . WPINC . '/class-snoopy.php' );
    } 

    $snoopy = new Snoopy();
    $snoopy->agent = 'LinkedIn Connect (Snoopy)';
    $snoopy->host = $_SERVER[ 'HTTP_HOST' ];
    $snoopy->read_timeout = "180";
    $url = $lic_url.'/'.$lic_page.'?lic_req_key='.urlencode($req_key);

    if(@$snoopy->fetchtext($url))
    {
        $results = $snoopy->results;
        lic_Login($results);
    } 
    else {
        $results = "Error contacting LinkedIn Connect: ".$snoopy->error."\n";
        wp_die($results);
    }
}

//************************************************************************************
//* lic_LinkedInInfoPost
//************************************************************************************
function lic_LinkedInInfoPost($req_key, $update)
{
    global $lic_url, $lic_page;

    if ( !class_exists('Snoopy') ) {
        include_once( ABSPATH . WPINC . '/class-snoopy.php' );
    } 

    $snoopy = new Snoopy();
    $snoopy->agent = 'LinkedIn Connect (Snoopy)';
    $snoopy->host = $_SERVER[ 'HTTP_HOST' ];
    $snoopy->read_timeout = "180";
    $url = $lic_url.'/'.$lic_page.'?lic_req_key='.urlencode($req_key).'&update='.urlencode($update);

    if(@$snoopy->fetchtext($url))
    {
        $results = $snoopy->results;
    } 
    else {
        $results = "Error contacting LinkedIn Connect: ".$snoopy->error."\n";
    }
    
    return $results;
   
}


//************************************************************************************
//* lic_oAuth_Start
//************************************************************************************
function lic_oAuth_Start()
{
    $lic_consumer_key = get_option('lic_consumer_key');
    $lic_consumer_secret = get_option('lic_consumer_secret');
    if(!empty($lic_consumer_key) && !empty($lic_consumer_secret))
    {
        /* Create LinkedInOAuth object with app key/secret */
        //echo "Create LinkedInOAuth object with app key/secret";
        $loc = $_GET['loc'];

        $uri = explode('#',$loc);
        //$url = $uri[0];
        $url = get_option('siteurl');
        $liclient = new LinkedInOAuth($lic_consumer_key, $lic_consumer_secret, $url);
        //$liclient->debug = true;
        /* Request tokens from linkedin */
        $liclient->getRequestToken();

        /* Save client for later */

        $_SESSION['linkedin_client'] = serialize($liclient);
        //$_SESSION['linkedin_oauth_request_token_secret'] = $tok['oauth_token_secret'];

        

        $_SESSION['linkedin_oauth_callback'] = $loc;

        echo '<script type="text/javascript">location.href = "'.$liclient->generateAuthorizeUrl().'";</script>';
        //echo $liclient->generateAuthorizeUrl();
    }
}

//************************************************************************************
//* lic_oAuth_Confirm
//************************************************************************************
function lic_oAuth_Confirm()
{
    /*
    $lic_consumer_key = get_option('lic_consumer_key');
    $lic_consumer_secret = get_option('lic_consumer_secret');
    */
    $liclient = unserialize($_SESSION['linkedin_client']);

    /* Create TwitterOAuth object with app key/secret and token key/secret from default phase */
    //$to = new TwitterOAuth($lic_consumer_key, $lic_consumer_secret, $_SESSION['linkedin_oauth_request_token'], $_SESSION['linkedin_oauth_request_token_secret']);
    /* Request access tokens from linkedin */
    //$tok = $to->getAccessToken();

    $liclient->getAccessToken($_GET['oauth_verifier']);
    
    /* Save the access tokens. Normally these would be saved in a database for future use. */
    //$_SESSION['linkedin_oauth_access_token'] = $tok['oauth_token'];
    //$_SESSION['linkedin_oauth_access_token_secret'] = $tok['oauth_token_secret'];
    $_SESSION['linkedin_client'] = serialize($liclient);
    //$to = new TwitterOAuth($lic_consumer_key, $lic_consumer_secret, $_SESSION['linkedin_oauth_access_token'], $_SESSION['linkedin_oauth_access_token_secret']);
    /* Run request on linkedin API as user. */

    $xml = $liclient->getProfile("~:full");
    $linkedinInfo = new SimpleXMLElement($xml);

    $id = $linkedinInfo->id;
    //$screen_name = $linkedinInfo->screen_name;
    $name = $linkedinInfo->{'first-name'}." ". $linkedinInfo->{'last-name'};
    $avatar = $linkedinInfo->{'picture-url'};
    $url = "";
    $url = $linkedinInfo->{'site-standard-profile-request'}->{'url'};

    lic_Login($id.'|'.$linkedinInfo->{'first-name'}.'|'.$linkedinInfo->{'last-name'}.'|'.$avatar.'|'.$url);
}

//************************************************************************************
//* lic_comment_post
//************************************************************************************
function lic_comment_post($comment_ID)
{
    global $lic_local;
    
    if(!isset($_REQUEST["lic_share_this"]))
    {
        return;
    }
    $liclient = unserialize($_SESSION['linkedin_client']);
    /*
    $lic_consumer_key = get_option('lic_consumer_key');
    $lic_consumer_secret = get_option('lic_consumer_secret');
    */
    $comment = get_comment($comment_ID); 
    $post_title = strip_tags(get_the_title( $comment->comment_post_ID ));
    $blog_title = get_bloginfo('name');
    
    $permalink = '';
    //Use the comment link if it is approved, otherwise use the post link.
    if($comment->comment_approved == 1)
    {
        $permalink = get_comment_link($comment);
    }
    else
    {
        $permalink = get_permalink($comment->comment_post_ID);
    }   

    $shortlink = '';

    if(!empty($permalink))
    {   
        $url = 'http://is.gd/api.php?longurl='.urlencode($permalink);


         
        //Shorten the link.
        if ( !class_exists('Snoopy') ) {
            include_once( ABSPATH . WPINC . '/class-snoopy.php' );
        } 

        $snoopy = new Snoopy();
        $snoopy->agent = 'LinkedIn Connect (Snoopy)';
        $snoopy->host = $_SERVER[ 'HTTP_HOST' ];
        $snoopy->read_timeout = "180";
       

        if(@$snoopy->fetchtext($url))
        {
            $shortlink = $snoopy->results;
        } 
        else {
            $results = "Your comment was submitted, but it could not be sent to LinkedIn.  There was an error shortening the url: ".$snoopy->error."\n";
            wp_die($results);
        }
        
    }
    
    if(!empty($shortlink))
    {
        //Get the template for the update.
        $update = get_option("lic_share_this_text");
        
        //Determine characters available for post and blog title.
        $temp_update = $update;
        $temp_update = str_replace('%%post_title%%', '', $temp_update);
        $temp_update = str_replace('%%blog_title%%', '', $temp_update);
        $temp_update = str_replace('%%shortlink%%', '', $temp_update);

        $update_len = strlen($temp_update);
        if(strlen($post_title) + strlen($blog_title) + strlen($shortlink) + $update_len > 140)
        {
            //Shorten the blog title.
            $ctr = strlen($blog_title) - 1;
            $shorter = false;
            while(strlen($blog_title) > 10 && 140 < strlen($post_title) + strlen($blog_title) + 3 + strlen($shortlink) + $update_len)
            {
                $blog_title = substr($blog_title,0,$ctr--);  
                $shorter = true;
            }
            if($shorter)
            {
                $blog_title.='...';
            }
            $ctr = strlen($post_title) - 1;
            $shorter = false;
            while(strlen($post_title) > 10 && 140 < strlen($post_title) + 3 + strlen($blog_title) + strlen($shortlink) + $update_len)
            {
                $post_title = substr($post_title,0,$ctr--);  
                $shorter = true;
            }
            if($shorter)
            {
                $post_title.='...';
            }
        } 
        $temp_update = $update;
        $temp_update = str_replace('%%post_title%%',$post_title, $temp_update);
        $temp_update = str_replace('%%blog_title%%',$blog_title, $temp_update);
        $temp_update = str_replace('%%shortlink%%',$shortlink, $temp_update);
        
        $update = $temp_update;
        if(strlen($update) <= 140)
        {
                $content = $liclient->setStatus($update);
                //Run request on linkedin API as user.
                //$content = $to->OAuthRequest('https://linkedin.com/statuses/update.xml', array('status' => $update), 'POST');
            if(strpos($content, 'status') === false && strpos($content, $update) === false)
            {
                wp_die('Your comment was submitted, but it could not be posted to LinkedIn.  '.$content);
            }
		
        }
    }
}

//************************************************************************************
//* lic_Login
//************************************************************************************
function lic_Login($pdvUserinfo) 
{
	global $wpdb, $lic_use_linkedin_profile, $lic_user_login_suffix;
	
	$userinfo = explode('|',$pdvUserinfo);
	if(count($userinfo) < 4)
	{
		wp_die("An error occurred while trying to contact LinkedIn Connect.");
	}

	//User login
	$user_login_n_suffix = $userinfo[1]." ".$userinfo[2].$lic_user_login_suffix;

	//Use the url from the LinkedIn profile.
	$user_url = $userinfo[4];

	$lic_use_linkedin_profile = get_option('lic_use_linkedin_profile');

	if($lic_use_linkedin_profile == 'N')
	{
		//Don use the LinkedIn profile.
		$user_url = '';
	}

	$userdata = array(
		'user_pass' => wp_generate_password(),
		'user_login' => $user_login_n_suffix,
		'display_name' => $userinfo[1]." ".$userinfo[2],
		'first_name' => $userinfo[1],
		'last_name' => $userinfo[2],
                //'avatar' => $userinfo[2],
		'user_url' => $user_url,
		'user_email' => 'onbekend@geen.nl'
	);
		
	if(!function_exists('wp_insert_user'))
	{
		include_once( ABSPATH . WPINC . '/registration.php' );
	} 

	$wpuid = lic_linkedinuser_to_wpuser($userinfo[0]);
	
	if(!$wpuid)
	{
		if (!username_exists($user_login_n_suffix))
		{
			$wpuid = wp_insert_user($userdata);
			if($wpuid)
			{
			    update_usermeta($wpuid, 'licid', "$userinfo[0]");
                      update_usermeta($wpuid, 'avatar', "$userinfo[3]");
			    update_usermeta($wpuid, 'user_url', "$user_url");
	
			}
		}
		else
		{
			wp_die('User name '.$user_login_n_suffix.' cannot be added.  It already exists.');
		}
	}
	else
	{
		$user_obj = get_userdata($wpuid);

		if($user_obj->display_name != $userinfo[1]." ".$userinfo[2] || $user_obj->user_url != $userinfo[4])
		{
			$userdata = array(
				'ID' => $wpuid,
				'display_name' => $userinfo[1]." ".$userinfo[2],
				'user_url' => $userinfo[4],
			);
			wp_update_user( $userdata );
		}
		if($user_obj->user_login != $user_login_n_suffix)
		{
			if (!username_exists($user_login_n_suffix))
			{
			    $q = sprintf( "UPDATE %s SET user_login='%s' WHERE ID=%d", 
				$wpdb->users, $user_login_n_suffix, (int) $wpuid );
				    if (false !== $wpdb->query($q))
				    {
					update_usermeta( $wpuid, 'nickname', $user_login_n_suffix );
				    }
			}
			else
			{
				wp_die('User name '.$user_login_n_suffix.' cannot be added.  It already exists.');
			}
		}
	}
	
	if($wpuid) 
	{
		wp_set_auth_cookie($wpuid, true, false);
		wp_set_current_user($wpuid);
		wp_redirect($_SESSION['linkedin_oauth_callback']);
	}
}

//************************************************************************************
//* lic_get_user_by_meta
//************************************************************************************
function lic_get_user_by_meta($meta_key, $meta_value) {
  global $wpdb;
  $sql = "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '%s' AND meta_value = '%s'";
  return $wpdb->get_var($wpdb->prepare($sql, $meta_key, $meta_value));
}

//************************************************************************************
//* lic_linkedinuser_to_wpuser
//************************************************************************************
function lic_linkedinuser_to_wpuser($licid) {
  return lic_get_user_by_meta('licid', $licid);
}

//*****************************************************************************
//* lic_config_page - WordPress admin page
//*****************************************************************************
function lic_config_page()
{
	add_submenu_page("options-general.php", "LinkedIn Connect",
		"LinkedIn Connect", 10, __FILE__, "linkedinconnect_configuration");
}

//*****************************************************************************
//* linkedinconnect_configuration - WordPress admin page processing
//*****************************************************************************
function linkedinconnect_configuration()
{
        global $lic_btn_images;
        
$lic_template_dflt = <<<KEEPME
<div id="lic_connect"><p><strong>LinkedIn Users</strong><br />Enter your personal information in the form or sign in with your LinkedIn account by clicking the button below.</p></div>
KEEPME;

$lic_login_text_dflt = <<<KEEPME2
<div id="lic_connect"><p><strong>LinkedIn Users</strong><br />Register or Login using your LinkedIn account by clicking the button below.</p></div><br/></br>
KEEPME2;

$lic_share_this_text_dflt = <<<KEEPME3
I just left a comment on %%post_title%% at %%blog_title%% - %%shortlink%%
KEEPME3;

		// Save Options
		if (isset($_POST["lic_save"])) {
			// ...the options are updated.
			update_option('lic_consumer_key', stripslashes($_POST["lic_consumer_key"]) );
			update_option('lic_consumer_secret', stripslashes($_POST["lic_consumer_secret"]) );
            update_option('lic_btn_choice', $_POST["lic_btn_choice"]);
            update_option('lic_template', stripslashes($_POST["lic_template"]));
            update_option('lic_login_text', stripslashes($_POST["lic_login_text"]));
            update_option('lic_use_linkedin_profile', $_POST["lic_use_linkedin_profile"]);
            update_option('lic_add_to_login_page', $_POST["lic_add_to_login_page"]);            
            update_option('lic_user_login_suffix', $_POST["lic_user_login_suffix"]);
            update_option('lic_redirect', $_POST["lic_redirect"]);
            update_option('lic_share_this', $_POST["lic_share_this"]);
            update_option('lic_share_this_text', stripslashes($_POST["lic_share_this_text"]));
            update_option('lic_before_comment', $_POST["lic_before_comment"]);
            update_option('lic_before_login', $_POST["lic_before_login"]);
		}
		
		// Get the Data
		$lic_consumer_key = get_option('lic_consumer_key');
		$lic_consumer_secret = get_option('lic_consumer_secret');
		$lic_template = get_option('lic_template');
		if(empty($lic_template))
		{
		    $lic_template = $lic_template_dflt;
		}
		$lic_login_text = get_option('lic_login_text');
		if(empty($lic_login_text))
		{
		    $lic_login_text = $lic_login_text_dflt;
		}
		$lic_share_this = get_option('lic_share_this');
		$lic_share_this_text = get_option('lic_share_this_text');
		if(empty($lic_share_this_text))
		{
		    $lic_share_this_text = $lic_share_this_text_dflt;
        }

		$lic_before_comment = get_option('lic_before_comment');
		$lic_before_login = get_option('lic_before_login');
        
        $lic_btn_choice = get_option('lic_btn_choice');
        $lic_user_login_suffix = get_option('lic_user_login_suffix');                                
        if(empty($lic_user_login_suffix))
        {
            $lic_user_login_suffix = '@linkedin';
        }
        
        $lic_redirect = get_option('lic_redirect');                                        
        if(empty($lic_redirect))
        {
            $lic_redirect = 'wp-admin/index.php';
            update_option('lic_redirect',$lic_redirect);
        }
        $lic_use_linkedin_profile = get_option('lic_use_linkedin_profile');
        $lic_use_linkedin_profile = $lic_use_linkedin_profile == 'Y' ?
    	"checked='true'" : "";
    	
        $lic_add_to_login_page = get_option('lic_add_to_login_page');
        $lic_add_to_login_page = $lic_add_to_login_page == 'Y' ?
    	"checked='true'" : "";

        $lic_share_this = $lic_share_this == 'Y' ?
	    "checked='true'" : "";
		
        $btn1 = $lic_btn_choice == '1' ?
            "checked='true'" : "";
        $btn2 = $lic_btn_choice == '2' ?
            "checked='true'" : "";
        $btn3 = $lic_btn_choice == '3' ?
            "checked='true'" : "";			

?>
    <h3>LinkedIn Connect Configuration</h3>
    <form action='' method='post' id='lic_conf'>
      <table cellspacing="20" width="60%">
<?php if(!version_compare(PHP_VERSION, '5.0.0', '<')) : ?>        
        <tr>
          <td width="20%" valign="top">API Key</td>
          <td>
            <input type='text' name='lic_consumer_key' value='<?php echo $lic_consumer_key ?>' size="50" />
                <br/><small>(Necessary) Your application key from LinkedIn.com. </small>
                <br/><small>For this option, you must register a new application at <a href="https://www.linkedin.com/secure/developer">LinkedIn.com</a></small>
                <br/><small>Help in filling out the registration can be found on the <a href="http://www.voiceoftech.com/swhitley/?page_id=706">LinkedIn Connect</a> page.</small>  
          </td>
        </tr>
        <tr>
          <td width="20%" valign="top">Consumer Secret</td>
          <td>
            <input type='text' name='lic_consumer_secret' value='<?php echo $lic_consumer_secret ?>' size="50" />
                <br/><small>(Necessary) Your secret key from LinkedIn.com.</small>
          </td>
        </tr>
        <tr><td colspan="2"><hr/></td></tr>
<?php endif; ?>        
        <tr>
          <td width="20%" valign="top">LinkedIn Login Suffix</td>
          <td>
            <input type='text' name='lic_user_login_suffix' value='<?php echo $lic_user_login_suffix ?>' size="20" /> [Once set, do not change.]
                 <br/><small>
                  (Recommended) Add a suffix to all LinkedIn logins to keep them separate<br/>from other logins.
                  <br/><br/>Example: Enter <strong>@linkedin</strong> into the box above.  The next LinkedIn account<br/>
                  created on your blog will be {user name}@linkedin.
                </small>
          </td>
        </tr>
        <tr>
        <td valign="top">Select a Button</td>
        <td>
        <table><tr><td width="5%">
          <input type='radio' name='lic_btn_choice' value='1' 
            <?php echo $btn1 ?>/></td><td><img src="<? echo $lic_btn_images[0] ?>" alt="" /></td></tr>
            <tr><td>
          <input type='radio' name='lic_btn_choice' value='2' 
            <?php echo $btn2 ?>/></td><td><img src="<? echo $lic_btn_images[1] ?>" alt="" /></td></tr>
            <tr><td>
          <input type='radio' name='lic_btn_choice' value='3' 
            <?php echo $btn3 ?>/></td><td> <img src="<? echo $lic_btn_images[2] ?>" alt="" /></td></tr>
            </table>
          </td>
        </tr>
        <tr>
        <td valign="top">Position<br/>(Optional)<p><small>Javascript alternative to modifying your theme.</small></p></td>
        <td>
        <table>
        <tr><td colspan="3">Locate the id of an html element on a page and enter it into the appropriate box below.  The LinkedIn Connect text and button will appear before that element.
        <p><small>Example: Enter <strong>commentform</strong> in the <strong>Comment Page</strong> box to place the button at the top of the comment section.</small></p>
        </td></tr>
        <tr valign="top">
            <td width="100">Comment Page</td><td width="10"></td><td>Login Page</td>
            </tr>
            <tr valign="top">
            <td><input type='text' name='lic_before_comment' value='<?php echo $lic_before_comment ?>' size="20" /><br/>
            </td>
            <td></td>
            <td><input type='text' name='lic_before_login' value='<?php echo $lic_before_login ?>' size="20" />
            </td>
            </tr>
            </table>
          </td>
        </tr>
        <tr><td colspan="2"><hr/></td></tr>
        <tr>
        <td valign="top">Author Link</td>
        <td>
        <table><tr valign="top"><td width="5%">
          <input type='checkbox' name='lic_use_linkedin_profile' value='Y' 
            <?php echo $lic_use_linkedin_profile ?>/></td><td>
            <small>Check this box if you would like the author link to point to the author's LinkedIn profile (http://LinkedIn.com/{username}).</small>
            </td></tr></table>
          </td>
        </tr>
   <tr>
          <td valign="top">Comment Page Text</td>
          <td>
            <textarea name='lic_template' rows="5" cols="50"><?php echo $lic_template; ?></textarea>
            <br/>
            <small>The text that appears above the LinkedIn Connect button on the comment page.  Do not remove id="lic_connect".</small>
          </td>
        </tr>
   <tr>
          <td valign="top">Add to Login Page</td>
          <td>
          <table><tr valign="top"><td width="5%">
          <input type='checkbox' name='lic_add_to_login_page' value='Y' 
            <?php echo $lic_add_to_login_page ?>/></td><td>
            <small>Check this box if you would like the LinkedIn Connect button to appear on the WordPress login page.</small>
            </td></tr></table>
          </td>
        </tr>
   <tr>
          <td valign="top">Login Page Text</td>
          <td>
            <textarea name='lic_login_text' rows="5" cols="50"><?php echo $lic_login_text; ?></textarea>
            <br/>
            <small>The text that appears above the LinkedIn Connect button on the login page.    Do not remove id="lic_connect".</small>
          </td>
        </tr>
   <tr>
          <td valign="top">Redirect After Login</td>
          <td>
            <input type='text' name='lic_redirect' value='<?php echo $lic_redirect ?>' size="50" />
            <br/>
            <small>The user will be taken to this address after a successful login.  This is only applied to the Login Page.</small>
          </td>
        </tr>
   <tr>
          <td valign="top">Share This Comment</td>
          <td>
            <input type='checkbox' name='lic_share_this' value='Y' 
            <?php echo $lic_share_this ?> />
            <input type='text' name='lic_share_this_text' value='<?php echo $lic_share_this_text ?>' size="70" />
            <br/>
            <small>Display a checkbox that allows visitors to automatically share a link when they submit a comment.  Replacement variables: %%post_title%%, %%blog_title%%, and %%shortlink%%.</small>
            <div style="border:solid 1px #CCCCCC;background-color:#F1F1F1;margin:5px;padding:5px;">The <strong>Share This Comment</strong> option requires Read/Write Access.  If you are using Self-Hosted oAuth, you must change your application configuration at LinkedIn.com.
            <p><strong>Important:</strong>  Visitors who previously granted Read Access to your application must Revoke Access before they can grant Read/Write Access.</p></div>
          </td>
        </tr>
      </table>
      <p class="submit">
        <input class="button-primary" type='submit' name='lic_save' value='Save Settings' />
      </p>
    </form>
<?php
			
}

?>
