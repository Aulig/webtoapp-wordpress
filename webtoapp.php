<?php
/*
 Plugin Name: webtoapp.design
 Plugin URI:
 Description: webtoapp.design
 Author: webtoapp.design
 Text Domain: webtoapp
 License: Copyright 2023 webtoapp.design
 Version:                               0.0.30 */      require_once( dirname( __FILE__ ) . '/webtoapp_options.php' );
class WebToApp { public $version =     "0.0.30";

    private $options;

    function __construct()
    {
        add_filter( 'wp_fatal_error_handler_enabled', '__return_false' );
        
        $this->options = new WebToAppOptions($this);
        
        add_action('transition_post_status', array($this, 'transition_post_status'), 10, 3 );
    }
    
    private $postPublishId = 0;
    
    function transition_post_status($new_status, $old_status, $post)    //detect when a new post is first published...
    {
        if ( $new_status == 'publish' && $old_status != 'publish' )
        {
            $this->postPublishId = $post->ID;
            
            add_action('shutdown', array($this, 'shutdown'), 1000, 0);  //but its metadata like featured image is not set yet.
        }
    }
    
    function selectPostImage($post_id)
    {
        $attachment_id = get_post_thumbnail_id($post_id);
        
        if( $attachment_id === 0 || $attachment_id === false )  //0 or false;
            return null;
        
        $out = wp_get_attachment_image_src($attachment_id, array(300, 300) );
            
        if( $out !== false )
            return $out[0];
            
        return null;
    }
    
    function shutdown()
    {
        if( $this->postPublishId != 0 )
        {
            $options = $this->options->GetOptions();
            
            if( isset($options["key"]) && isset($options["autopublish"]) && $options["autopublish"] == true )
            {
                $key = $options["key"];
                
                $title = get_the_title($this->postPublishId);
                
                $post_url = get_permalink($this->postPublishId);
                
                $message = "";
                
                $image_url = $this->selectPostImage($this->postPublishId);
                
                $this->postPublishId = 0;
                
                $r = $this->pushNotification($key, $title, $message, $post_url, $image_url);
                
                $img = $image_url? " [" . $image_url .  "]" : "";
                
                $slug = $title . " (" . $this->getResponseText($r) . ")" . $img;    //purely for explanatory text on the options page.
                
                $options["last_auto_response"] = $slug;
                
                $this->options->SetOptions($options);
            }
            
        }
    }
    
    public function getResponseOK($response)
    {
        switch($response)
        {
            case WebToApp::$RESPONSE_OK:                    return true;
            case WebToApp::$RESPONSE_TRANSMISSION_FAILURE:  return false; 
            case WebToApp::$RESPONSE_NO_KEY:                return false; 
            default:                                        return false;
        }
    }
    
    public function getResponseText($response)
    {
        switch($response)
        {
            case WebToApp::$RESPONSE_OK:                    return "Success: Notification Sent";
            case WebToApp::$RESPONSE_TRANSMISSION_FAILURE:  return "Server Error: Notification not sent";
            case WebToApp::$RESPONSE_NO_KEY:                return "API Key Error";
            default:                                        return "Unknown Error";
        }
    }
    
    public static $RESPONSE_OK                   =  0;
    public static $RESPONSE_TRANSMISSION_FAILURE = -1;
    public static $RESPONSE_NO_KEY               = -2;
    public static $RESPONSE_INVALID_BODY         = -3;
    public static $RESPONSE_UNEXPECTED           = -4;
  
    function pushNotification($key, $title, $message, $url_to_open, $image_url)
    {
        $url = "https://webtoapp.design/api/global_push_notifications?key={$key}";

        $data = array(
             'title'        => $title
            ,'message'      => $message
            ,'url_to_open'  => $url_to_open
            ,'image_url'    => $image_url
        );

        $args = array(
            'body' => json_encode($data),
            'headers' => array(
                'accept' => 'application/json'
                ,'Content-Type' => 'application/json',
            ),
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response))
        {
            //$err = $response->get_error_message();    //only for debugging
            
            return WebToApp::$RESPONSE_TRANSMISSION_FAILURE;
        }
        
        else
        {
            $code = wp_remote_retrieve_response_code($response);
            
            if( $code == 401 )
                return WebToApp::$RESPONSE_NO_KEY;
            
            else if( $code != 200 ) 
                return WebToApp::$RESPONSE_INVALID_BODY;
            
            $response_body = json_decode( wp_remote_retrieve_body($response), true);
            
            return isset($response_body["success"]) && $response_body["success"] == true? WebToApp::$RESPONSE_OK 
                                                                                        : WebToApp::$RESPONSE_UNEXPECTED;
        }
    }
    
} 

$webtoapp = new WebToApp();

?>