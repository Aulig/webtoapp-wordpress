<?php

if ( ! defined( 'ABSPATH' ) ) exit;

require_once( dirname( __FILE__ ) . '/media.php' );

/*
 * Handles only the webtoapp options page.
 */

class WtadOptions
{
    private $wtadMain;
    
    private $default_options = array();

    private $options_page_hook;
    
    private $options_name = "webtoapp";
    
    private $options_group;
    
    private $media;
    
    function __construct($wtadMain)
    {
        $this->wtadMain = $wtadMain;
        
        $this->media = new WtadMedia();
        
        $this->options_group = $this->options_name."_group";
        
        add_action('admin_menu',            array($this, 'callback_admin_menu') );
        
        add_action('admin_init',            array($this, 'callback_admin_init') );
        
        add_action('admin_enqueue_scripts', array($this, 'callback_admin_enqueue_scripts') );
    }

    function callback_admin_menu()
    {
        $page_title = "webtoapp.design";
        
        $menu_title = "webtoapp.design";          /* as it appears in menu */
        
        $unique_menu_slug = "webtoapp_options_page";
        
        $this->options_page_hook = add_options_page(
            $page_title,
            $menu_title,
            'manage_options',
            $unique_menu_slug,                    /*also forms slug of url for options page*/
            array($this, 'options_page_echo') );
    }

    function callback_admin_init()
    {
        register_setting($this->options_group, $this->options_name, array($this, 'callback_sanitize') ); // group name, then name passed to get_option
    }

    function callback_admin_enqueue_scripts($hook)
    {
        if($hook != $this->options_page_hook)
            return;

        wp_enqueue_style( 'webtoapp_admin_fontawesome_css', plugins_url('back/webtoapp.design_static_library_fontawesome-5.15.2_css_fontawesome.min.css', __FILE__), array(), $this->wtadMain->version);
        wp_enqueue_style( 'webtoapp_admin_solid_css',       plugins_url('back/webtoapp.design_static_library_fontawesome-5.15.2_css_solid.min.css', __FILE__),       array(), $this->wtadMain->version);
        wp_enqueue_style( 'webtoapp_admin_general_css',     plugins_url('back/webtoapp.css', __FILE__),                                                              array(), $this->wtadMain->version);
            
        wp_enqueue_script('webtoapp_admin_js',              plugins_url('back/webtoapp.js',  __FILE__), array(), $this->wtadMain->version, true);
    }
    
    function callback_sanitize($options)
    {
        return $options;
    }
    
    public function SetOptions($options)
    {
        if( ! update_option($this->options_name, $options) )
        {
            $a = 42; //debugging
        }
    }
    
    public function GetOptions()
    {
        return wp_parse_args( get_option( $this->options_name, $this->default_options ), $this->default_options );
    }
   
    function header($responseText, $responseOK)
    {
        $context = $responseOK? "success" : "danger";
        
        $resp = $responseText === null? "" : "<div class='alert alert-" . esc_attr($context). "' role='alert'>" . esc_html($responseText) . "</div>";
        
        $logo =  esc_url( plugins_url('back/logo.svg', __FILE__) );
        
        $out = "";
        $out .= "<nav class='navbar navbar-expand-md navbar-light bg-light fixed-top shadow-sm' id='navigation-bar'>";
        $out .= "<a class='navbar-brand' href='https://webtoapp.design/?utm_source=wordpress&utm_medium=plugin&utm_campaign=header'>";
        $out .= "<img decoding='async' src='{$logo}' style='height:2rem; width: auto' height='144' width='144' class='d-inline-block align-top' alt='Website to App Converter webtoapp.design Logo'>";
        $out .= "webtoapp.design";
        $out .= "</a>";

        $out .= "<div class='collapse navbar-collapse' id='navbarResponsive'>";

        $out .= "</div>";
        $out .= "</nav>";

        $out .=  wp_kses_post($resp);
        
        return $out;
    }
    
    function auto($on, $lastAutoResponse)
    {
        $turnOn  = $on? "btn btn-primary active" : "btn btn-secondary";
        $turnOff = $on? "btn btn-secondary" : "btn btn-primary active";
        
        $text = $on? "An automatic notification will be sent when a new page is made public. The notification will contain the post's title and, if available, it's featured image."
                    :"No automatic notification will be sent when a new page is made public."; 
        
        $text = esc_html($text);
                    
        $last = $lastAutoResponse == null? "" : "Last notification: " . esc_html($lastAutoResponse);
                           
        $out = "";
        $out .= "<div class='card mt-3'>";
        $out .= "  <div class='card-header'>";
        $out .= "<h3 class='title'>New Page Publish Notification</h3>";
        $out .= "</div>";
        $out .= "<div class='card-body'>";
        $out .= "<form action='' method='post' enctype='multipart/form-data'>";
        $out .= "<div class='btn-group btn-group-toggle' data-toggle='buttons' >";
        $out .= "<input style='color:white' class='" . esc_attr($turnOn) . "'  type='submit' name='webapp-auto-on'  autocomplete='off' value='On' />";
        $out .= "<input style='color:white' class='" . esc_attr($turnOff)."' type='submit' name='webapp-auto-off' autocomplete='off' value='Off'/>";
        $out .= "</div>";
        $out .= "<br/><br/>";
        $out .= "<p>" . wp_kses_post($text) . "</p>";
        $out .= "<p>" . wp_kses_post($last) . "</p>";
        $out .= "</form>";
        $out .= "</div>";
        $out .= "</div>";
        
        return $out;
    }
    
    function push()
    {
        $dd = WtadMedia::dropdownPages("dd_url", "Select a page", "url_to_open");
        
        $out = "";
        $out .= "<div class='card mt-3'>";
        $out .= "  <div class='card-header'>";
        $out .= "<h3 class='title'>Push Notifications</h3>";
        $out .= "</div>";
        $out .= "<div class='card-body'>";
        $out .= "<form action='' method='post' enctype='multipart/form-data'>";
        $out .= "<p>The title or main message of your push notification.</p>";
        $out .= "<div class='form-group'>";
        $out .= "<div class='input-group'>";
        $out .= "<div class='input-group-prepend'>";
        $out .= "<span class='input-group-text'>";
        $out .= "<i class='fas fa-sticky-note' style='width: 1rem;' aria-hidden='true'></i>";
        $out .= "</span>";
        $out .= "</div>";
        $out .= "<input name='title' class='form-control' placeholder='Title' id='title' required='' type='text' >";
        $out .= "<label for='title' class='sr-only'>Title</label>";
        $out .= "<p class='text-danger' id='regex-error-title' hidden=''></p>";
        $out .= "</div>";
        $out .= "</div>";
        $out .= "<p>An optional, longer message that is shown below the title.</p>";
        $out .= "<div class='form-group'>";
        $out .= "<div class='input-group'>";
        $out .= "<div class='input-group-prepend'>";
        $out .= "<span class='input-group-text'>";
        $out .= "<i class='fas fa-envelope' style='width: 1rem;' aria-hidden='true'></i>";
        $out .= "</span>";
        $out .= "</div>";
        $out .= "<input name='message' class='form-control' placeholder='Message (optional)' id='message' type='text' >";
        $out .= "<label for='message' class='sr-only'>Message (optional)</label>";
        $out .= "<p class='text-danger' id='regex-error-message' hidden=''></p>";
        $out .= "</div>";
        $out .= "</div>";
        $out .= "<p> This link will be opened inside your app when the notification is clicked. <a href='https://webtoapp.design/blog/send-push-notification#tracking-notification-clicks'> Here's how you can track how many users are opening your notifications. </a>";
        $out .= "</p>";
        $out .= "<div class='form-group'>";
        $out .= "<div class='input-group'>";
        $out .= "<div class='input-group-prepend'>";
        $out .= "<span class='input-group-text'>";
        $out .= "<i class='fas fa-link' style='width: 1rem;' aria-hidden='true'></i>";
        $out .= "</span>";
        $out .= "</div>";
        $out .= "<input name='url_to_open' class='form-control' placeholder='Link to Open on Notification Click (optional)' id='url_to_open' type='url' >";
        $out .= "<label for='url_to_open' class='sr-only'>Link to Open on Notification Click (optional)</label>";

        $out .= "<p class='text-danger' id='regex-error-url_to_open' hidden=''></p>";
            

        $out .= "<div class='input-group-append'>{$dd}</div>";
         
        $out .= "</div>";

        $out .= "</div>";
        $out .= "<p>A link to an image that will be attached to your push notification. Requirements:</p>";
        $out .= "<ul>";
        $out .= "<li>Image in PNG or JPG format</li>";
        $out .= "<li>Image size smaller than 300KB</li>";
        
        $out .= "</ul>";
        $out .= "<div class='form-group'>";
        $out .= "<div class='input-group'>";
        $out .= "<div class='input-group-prepend'>";
        $out .= "<span class='input-group-text'>";
        $out .= "<i class='fas fa-image' style='width: 1rem;' aria-hidden='true'></i>";
        $out .= "</span>";
        $out .= "</div>";
        $out .= "<input name='image_url' class='form-control' placeholder='Image Link (optional)' id='image_url' type='url' >";
        $out .= "<label for='image_url' class='sr-only'>Image Link (optional)</label>";
        $out .= "<p class='text-danger' id='regex-error-image_url' hidden=''></p>";
        $out .= "<div class='input-group-append'>";
        $out .= "<button class='btn btn-primary' type='button' id='image_media_gallery' >Media Gallery</button>";
        $out .= "</div>";
        $out .= "</div>";
        $out .= "</div>";
        $out .= "<input id='csrf_token' name='csrf_token' type='hidden' value='IjFjZDEyYmFkMmI0YjJmMzNmZWMwYWNkZGFiNTcwMTkyYjI1MzlkOWIi.ZP9wDw.KRs8sJse6_nAyrl0rAlrT0Smvf8'>";
        $out .= "<button type='submit' class='btn btn-primary mt-2 btn-block' name='send_notification'>Send Notification</button>";
        $out .= "</form>";
        $out .= "<p class='mt-3'> Having difficulties? <a href='https://webtoapp.design/blog/send-push-notification'>Here's our guide to sending push notifications</a> including a section about <a href='https://webtoapp.design/blog/send-push-notification#not-receiving-notifications'>why users might not be receiving notifications.</a>";
        $out .= "</p>";
        $out .= "</div>";
        $out .= "</div>";

        $out .= $this->media->getNecessaryJs("#image_url", "#image_media_gallery");
        return $out;
    }
    
    function echoSafe($content)
    {
        $permit = array( "class" => array(), "style" => array(), "id" => array(), "type" => array(), "value" => array(), "placeholder" => array(), 
                            "name" => array(), "aria-hidden" =>array(), "href" => array(), "target" => array(),
                        "decoding" =>array(), "src" => array(), "width" =>array(), "height" =>array(), "alt" => array(), "action" =>array(), "method" =>array(), "enctype" =>array()
                            
            
        );
       
       // echo $content;
       // return;
        
        echo wp_kses($content, array(
            "button" => $permit,
            "div"    => $permit,
            "ul"     => $permit,
            "li"     => $permit,
            "form"   => $permit,
            "p"      => $permit,
            "input"  => $permit,
            "span"   => $permit,
            "h3"     => $permit,
            "select" => $permit,
            "option" => $permit,
            "a"      => $permit,
            "i"      => $permit,
            "label"  => $permit, 
            "img"    => $permit,
            "nav"    => $permit,
            "script" => $permit
        ));
    }
    
    function options_page_echo()
    {
        $options = $this->GetOptions();
        
        $responseText = null;
        $responseOK   = false;
        
        if( isset($_POST['webtoapp-key-delete']) )
        {
            unset($options["key"]);
            
            update_option($this->options_name, $options);
        }
        
        if( isset($_POST['webtoapp-key']) )
        {
            $options["key"] = sanitize_text_field($_POST['webtoapp-key']);
            
            update_option($this->options_name, $options);
        }
        
        if( isset($_POST['send_notification']) )
        {
            $r = $this->wtadMain->pushNotification(
                    sanitize_text_field( $options["key"]),
                    sanitize_text_field($_POST['title']),
                    sanitize_text_field($_POST['message']),
                    sanitize_url($_POST['url_to_open']),
                    sanitize_url($_POST['image_url']));
            
            $responseOK   = $this->wtadMain->getResponseOK($r);
            $responseText = $this->wtadMain->getResponseText($r);
        }
        
        if( isset($_POST['webapp-auto-on']) )
        {
            $options["autopublish"] = true;
            
            update_option($this->options_name, $options);
        }
        
        if( isset($_POST['webapp-auto-off']) )
        {
            $options["autopublish"] = false;
            
            update_option($this->options_name, $options);
        }
        
        ?>
        <div class="wrap webtoapp ">  
            <div id="icon-themes" class="icon32"></div>  
            
            <?php  $this->echoSafe( $this->header($responseText, $responseOK) )?> 
            
            <?php  if( ! isset($options["key"]) ) { ?>
            
            <form id="webtoapp-set-key" method="post" enctype="multipart/form-data" action="" class="webtoapp-dashboard" >  
            	
    			<h1>Enter your API key:</h1>

    			<div class="input-group">
                    <input name="webtoapp-key" class="form-control" placeholder="12345-abcdefghijklmnopqrstuvwxyz-0123456789" id="webtoapp-key" type="text" minlength="43" maxlength="43">
                    <label for="webtoapp-key" class="sr-only">Your API key</label>
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit" >Save</button>
                    </div>
                </div>

    			<br/><a href="https://webtoapp.design/?utm_source=wordpress&utm_medium=plugin&utm_campaign=login" target="_blank" >I have not created an app yet</a>
    			&nbsp;|&nbsp;
    			
    			<a href="https://webtoapp.design/redirector/app_id/dashboard_bp.developer_tools?utm_source=wordpress&utm_medium=plugin&utm_campaign=login" target="_blank" >Find my app's API key</a>
    			
    		</form>
    				    			
    		<?php  } else { ?>
    		
           <?php $this->echoSafe($this->push()) ?>
           
           <?php $this->echoSafe($this->auto( isset($options["autopublish"]) && $options["autopublish"] == true,
                                   isset($options["last_auto_response"])? $options["last_auto_response"] : null ) ) ?>
           
           <form id="webtoapp-set-key" method="post" enctype="multipart/form-data" action="" class="webtoapp-dashboard" >
    			<br/>
    			
    			<p>
    			<input class="btn btn-danger url-submit-button" type="submit" value="Delete API Key" name="webtoapp-key-delete" />
    			<?php echo esc_html("&nbsp;(" . $options["key"] .")")  ?>
    			</p>
           </form>
              
           <?php } ?> 
               
        </div> 
    	<?php 
    }
    
}
?>