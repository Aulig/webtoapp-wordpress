<?php 

if ( ! defined( 'ABSPATH' ) ) exit;

/*
 * Helper class to open the media gallery, and enumerate all pages.
 */

class WtadMedia
{
    function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts') );
    }
    
    function admin_enqueue_scripts()
    {
        wp_enqueue_media();
    }
    
    function getNecessaryJs($selectorInput, $selectorButton)
    {
        $selectorButton = esc_js($selectorButton);
        $selectorInput  = esc_js($selectorInput);
        
        $out = "";
        $out .= "<script>";
        $out .= "jQuery(document).ready(function($){";
        $out .= "$('{$selectorButton}').click(function(e) {";
        $out .= "e.preventDefault();";
        $out .= "var custom_media_frame = wp.media.frames.custom_media_frame = wp.media({";
        $out .= "title: '" . esc_js(__('Choose or Upload Media', 'webtoapp-design')) . "',";
        $out .= "button: {";
        $out .= "text: '" . esc_js(__('Use this media', 'webtoapp-design')) . "'";
        $out .= "},";
        $out .= "});";
        $out .= "custom_media_frame.on('select', function() {";
        $out .= "var attachment = custom_media_frame.state().get('selection').first().toJSON();";
        $out .= "$('{$selectorInput}').val(attachment.url);";
        $out .= "});";
        $out .= "custom_media_frame.open();";
        $out .= "});";
        $out .= "});";
        $out .= "</script>";

        return $out;
    }
    
    public static function dropdownPages($idDropdown, $defaultText, $idCopyToInput = null)
    {
        $idDropdownAtt = esc_attr($idDropdown);
        $label = esc_html($defaultText);
        $out = "<label class='sr-only' for='{$idDropdownAtt}'>{$label}</label>";
        $out .= "<select id='{$idDropdownAtt}' style='max-width:12em'>";
        $out .= "<option value=''>{$label}</option>";

        foreach (get_pages(array("post_status" => "publish")) as $page) {
            $val = esc_url(get_permalink($page));
            $title = esc_html($page->post_title);
            $out .= "<option value='{$val}'>{$title}</option>";
        }
        $out .= "</select>";

        if ($idCopyToInput !== null) {
            $idDropdownStr = esc_js($idDropdown);
            $idCopyToInput = esc_js($idCopyToInput);
            $out .= "<script>";
            $out .= "jQuery(document).ready(function($){";
            $out .= "$('#{$idDropdownStr}').on('change', function() {";
            $out .= "if (this.value !== '') {";
            $out .= "$('#{$idCopyToInput}').val(this.value);";
            $out .= "this.selectedIndex = 0;";
            $out .= "}";
            $out .= "});";
            $out .= "});";
            $out .= "</script>";
        }

        return $out;
    }
}

?>
