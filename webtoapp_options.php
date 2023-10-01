<?php

require_once( dirname( __FILE__ ) . '/media.php' );

/*
 * Handles only the webtoapp options page.
 */

class WebToAppOptions
{
    private $webToApp;
    
    private $default_options = array();

    private $options_page_hook;
    
    private $options_name = "webtoapp";
    
    private $options_group;
    
    private $media;
    
    function __construct($webToApp)
    {
        $this->webToApp = $webToApp;
        
        $this->media = new webtoappMedia();
        
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

        wp_enqueue_style( 'webtoapp_admin_fontawesome_css', plugins_url('back/webtoapp.design_static_library_fontawesome-5.15.2_css_fontawesome.min.css', __FILE__), array(), $this->webToApp->version);
        wp_enqueue_style( 'webtoapp_admin_solid_css',       plugins_url('back/webtoapp.design_static_library_fontawesome-5.15.2_css_solid.min.css', __FILE__),       array(), $this->webToApp->version);
        wp_enqueue_style( 'webtoapp_admin_general_css',     plugins_url('back/webtoapp.css', __FILE__),                                                              array(), $this->webToApp->version);
            
        wp_enqueue_script('webtoapp_admin_js',              plugins_url('back/webtoapp.js',  __FILE__), array(), $this->webToApp->version, true);
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
        
        $resp = $responseText === null? "" : "<div class='alert alert-{$context}' role='alert'>" . $responseText . "</div>";
        
        $logo = plugins_url('back/logo.svg', __FILE__);
        
return <<<END
<nav class="navbar navbar-expand-md navbar-light bg-light fixed-top shadow-sm" id="navigation-bar">
<a class="navbar-brand" href="https://webtoapp.design/?utm_source=wordpress&utm_medium=plugin&utm_campaign=header">
<img decoding="async" src="{$logo}" style="height:2rem; width: auto" height="144" width="144" class="d-inline-block align-top" alt="Website to App Converter webtoapp.design Logo">
webtoapp.design
</a>

<div class="collapse navbar-collapse" id="navbarResponsive">

</div>
</nav>

{$resp}
END;
    }
    
    function auto($on, $lastAutoResponse)
    {
        $turnOn  = $on? "btn btn-primary active" : "btn btn-secondary";
        $turnOff = $on? "btn btn-secondary" : "btn btn-primary active";
        
        $text = $on? "An automatic notification will be sent when a new page is made public. The notification will contain the post's title and, if available, it's featured image."
                    :"No automatic notification will be sent when a new page is made public."; 
        
        $last = $lastAutoResponse == null? "" : "<p>" . "  Last notification: " . $lastAutoResponse . "</p>";
                           
        $out = <<<END
<div class="card mt-3">
  <div class="card-header">
    <h3 class="title">New Page Publish Notification</h3>
  </div>
  <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
        <div class="btn-group btn-group-toggle" data-toggle="buttons" >
            <input style="color:white" class="{$turnOn}"  type="submit" name="webapp-auto-on"  autocomplete="off" value="On" />
            <input style="color:white" class="{$turnOff}" type="submit" name="webapp-auto-off" autocomplete="off" value="Off"/>
        </div>
        <br/><br/>
        <p>{$text}</p>
        <p>{$last}</p>
    </form>
    </div>
</div>
END;
        
    return $out;
    }
    
    function push()
    {
        $dd = webtoappMedia::dropdownPages("dd_url", "Select a page", "url_to_open");
        
        $out = <<<END
<div class="card mt-3">
  <div class="card-header">
    <h3 class="title">Push Notifications</h3>
  </div>
  <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
      <p>The title or main message of your push notification.</p>
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">
              <i class="fas fa-sticky-note" style="width: 1rem;" aria-hidden="true"></i>
            </span>
          </div>
          <input name="title" class="form-control" placeholder="Title" id="title" required="" type="text" >
          <label for="title" class="sr-only">Title</label>
          <p class="text-danger" id="regex-error-title" hidden=""></p>
        </div>
      </div>
      <p>An optional, longer message that is shown below the title.</p>
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">
              <i class="fas fa-envelope" style="width: 1rem;" aria-hidden="true"></i>
            </span>
          </div>
          <input name="message" class="form-control" placeholder="Message (optional)" id="message" type="text" >
          <label for="message" class="sr-only">Message (optional)</label>
          <p class="text-danger" id="regex-error-message" hidden=""></p>
        </div>
      </div>
      <p> This link will be opened inside your app when the notification is clicked. <a href="https://webtoapp.design/blog/send-push-notification#tracking-notification-clicks"> Here's how you can track how many users are opening your notifications. </a>
      </p>
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">
              <i class="fas fa-link" style="width: 1rem;" aria-hidden="true"></i>
            </span>
          </div>
          <input name="url_to_open" class="form-control" placeholder="Link to Open on Notification Click (optional)" id="url_to_open" type="url" >
          <label for="url_to_open" class="sr-only">Link to Open on Notification Click (optional)</label>

          <p class="text-danger" id="regex-error-url_to_open" hidden=""></p>
            

            <div class="input-group-append">{$dd}</div>
         
        </div>

      </div>
      <p>A link to an image that will be attached to your push notification. Requirements:</p>
      <ul>
        <li>Image in PNG or JPG format</li>
        <li>Image size smaller than 300KB</li>
        
      </ul>
      <div class="form-group">
        <div class="input-group">
          <div class="input-group-prepend">
            <span class="input-group-text">
              <i class="fas fa-image" style="width: 1rem;" aria-hidden="true"></i>
            </span>
          </div>
          <input name="image_url" class="form-control" placeholder="Image Link (optional)" id="image_url" type="url" >
          <label for="image_url" class="sr-only">Image Link (optional)</label>
          <p class="text-danger" id="regex-error-image_url" hidden=""></p>
           <div class="input-group-append">
            <button class="btn btn-primary" type="button" id="image_media_gallery" >Media Gallery</button>
          </div>
        </div>
      </div>
      <input id="csrf_token" name="csrf_token" type="hidden" value="IjFjZDEyYmFkMmI0YjJmMzNmZWMwYWNkZGFiNTcwMTkyYjI1MzlkOWIi.ZP9wDw.KRs8sJse6_nAyrl0rAlrT0Smvf8">
      <button type="submit" class="btn btn-primary mt-2 btn-block" name="send_notification">Send Notification</button>
    </form>
    <p class="mt-3"> Having difficulties? <a href="https://webtoapp.design/blog/send-push-notification">Here's our guide to sending push notifications</a> including a section about <a href="https://webtoapp.design/blog/send-push-notification#not-receiving-notifications">why users might not be receiving notifications.</a>
    </p>
  </div>
</div>
END;
        
        $out .= $this->media->getNecessaryJs("#image_url", "#image_media_gallery");
        
        
        
        return $out;
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
            $options["key"] = $_POST['webtoapp-key'];
            
            update_option($this->options_name, $options);
        }
        
        if( isset($_POST['send_notification']) )
        {
            $r = $this->webToApp->pushNotification($options["key"], $_POST['title'], $_POST['message'], $_POST['url_to_open'], $_POST['image_url']);
            
            $responseOK   = $this->webToApp->getResponseOK($r);
            $responseText = $this->webToApp->getResponseText($r); 
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
            
            <?php  echo $this->header($responseText, $responseOK) ?> 
            
            <?php  if( ! isset($options["key"]) ) { ?>
            
            <form id="webtoapp-set-key" method="post" enctype="multipart/form-data" action="" class="webtoapp-dashboard" >  
            	
    			<h1>Enter your API key:</h1>

    			<div class="input-group">
                    <input name="webtoapp-key" class="form-control" placeholder="abcdef123456" id="webtoapp-key" type="text" minlength="43" maxlength="43">
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
    		
           <?php echo $this->push() ?>
           
           <?php echo $this->auto( isset($options["autopublish"]) && $options["autopublish"] == true,
                                   isset($options["last_auto_response"])? $options["last_auto_response"] : null ) ?>
           
           <form id="webtoapp-set-key" method="post" enctype="multipart/form-data" action="" class="webtoapp-dashboard" >
    			<br/>
    			
    			<p>
    			<input class="btn btn-danger url-submit-button" type="submit" value="Delete API Key" name="webtoapp-key-delete" />
    			<?php echo "&nbsp;(" . $options["key"] .")"  ?>
    			</p>
           </form>
              
           <?php } ?> 
               
        </div> 
    	<?php 
    }
    
}
?>