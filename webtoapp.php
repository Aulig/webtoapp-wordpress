<?php
/*
 * Plugin Name:       webtoapp.design
 * Plugin URI:        https://webtoapp.design/?utm_source=wordpress&utm_medium=plugin&utm_campaign=homepage
 * Description:       Turn your website into an app and send push notifications to your users.
 * Version:           1.0.5
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * License:           GPLv2
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       webtoapp-design
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require_once( dirname( __FILE__ ) . '/webtoapp_options.php' );

class WtadMain {
    public $version = "1.0.5";

    private $options;
    private $publishedPostIds = array();

    function __construct()
    {
        $this->options = new WtadOptions($this);
        add_action("transition_post_status", array($this, "transition_post_status"), 10, 3);
    }

    function transition_post_status($new_status, $old_status, $post)
    {
        if ($new_status !== "publish" || $old_status === "publish"
                || !in_array($post->post_type, array("post", "page"), true)
                || $post->post_password !== "") {
            return;
        }

        if ($this->publishedPostIds === array()) {
            // Wait for the editor to save metadata such as the featured image.
            add_action("shutdown", array($this, "shutdown"), 1000);
        }

        $this->publishedPostIds[$post->ID] = $post->ID;
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
        $post_ids = $this->publishedPostIds;
        $this->publishedPostIds = array();

        foreach ($post_ids as $post_id) {
            // Recheck the saved post in case it changed again during this request.
            $post = get_post($post_id);
            if ($post === null || $post->post_status !== "publish" || $post->post_password !== ""
                    || !in_array($post->post_type, array("post", "page"), true)
                    || !is_post_publicly_viewable($post)) {
                continue;
            }

            $options = $this->options->GetOptions();
            if (($options["key"] ?? "") === "" || ($options["autopublish"] ?? false) !== true) {
                return;
            }

            $title = get_the_title($post);
            $image_url = $this->selectPostImage($post_id);
            $response = $this->pushNotification($options["key"], $title, "", get_permalink($post), $image_url);

            $last_response = $title . " (" . $this->getResponseText($response) . ")";
            if ($image_url !== null) {
                $last_response .= " [" . $image_url . "]";
            }

            // Saving a delivery result must not overwrite settings changed while the request was in flight.
            update_option(WtadOptions::LAST_AUTO_RESPONSE, $last_response, false);
        }
    }

    public function getResponseOK($response)
    {
        return $response === self::$RESPONSE_OK;
    }

    public function getResponseText($response)
    {
        switch($response)
        {
            case self::$RESPONSE_OK:
                return __("Success: Notification Sent", "webtoapp-design");
            case self::$RESPONSE_TRANSMISSION_FAILURE:
                return __("Could not send the notification. Please check the connection and try again.", "webtoapp-design");
            case self::$RESPONSE_NO_KEY:
                return __("API Key Error", "webtoapp-design");
            case self::$RESPONSE_INVALID_BODY:
                return __("The notification service rejected the request.", "webtoapp-design");
            case self::$RESPONSE_INVALID_INPUT:
                return __("Enter a notification title and valid URLs for any links you include.", "webtoapp-design");
            case self::$RESPONSE_INVALID_KEY:
                return __("Enter a valid API key.", "webtoapp-design");
            default:
                return __("The notification service returned an unexpected response.", "webtoapp-design");
        }
    }

    public static $RESPONSE_OK = 0;
    public static $RESPONSE_TRANSMISSION_FAILURE = -1;
    public static $RESPONSE_NO_KEY = -2;
    public static $RESPONSE_INVALID_BODY = -3;
    public static $RESPONSE_UNEXPECTED = -4;
    public static $RESPONSE_INVALID_INPUT = -5;
    public static $RESPONSE_INVALID_KEY = -6;

    function pushNotification($key, $title, $message, $url_to_open, $image_url)
    {
        $url = add_query_arg("key", rawurlencode($key), "https://webtoapp.design/api/global_push_notifications");
        $data = array("title" => $title, "message" => $message);
        if (is_string($url_to_open) && $url_to_open !== "") {
            $data["url_to_open"] = $url_to_open;
        }
        if (is_string($image_url) && $image_url !== "") {
            $data["image_url"] = $image_url;
        }

        $response = wp_remote_post($url, array(
            "body" => wp_json_encode($data),
            "headers" => array(
                "Accept" => "application/json",
                "Content-Type" => "application/json",
            ),
            "redirection" => 0,
        ));

        if (is_wp_error($response)) {
            return self::$RESPONSE_TRANSMISSION_FAILURE;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code === 401) {
            return self::$RESPONSE_NO_KEY;
        }
        if ($code >= 500) {
            return self::$RESPONSE_TRANSMISSION_FAILURE;
        }
        if ($code >= 400) {
            return self::$RESPONSE_INVALID_BODY;
        }
        if ($code !== 200) {
            return self::$RESPONSE_UNEXPECTED;
        }

        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        return ($response_body["success"] ?? false) === true ? self::$RESPONSE_OK : self::$RESPONSE_UNEXPECTED;
    }
}

$wtadMain = new WtadMain();
