<?php 


/*
 * Helper class to open the media gallery, an enumerate all pages.
 */

class webtoappMedia
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
        return <<<END

<script>
jQuery(document).ready(function($){
    $('{$selectorButton}').click(function(e) {
        e.preventDefault();
        var custom_media_frame = wp.media.frames.custom_media_frame = wp.media({
            title: 'Choose or Upload Media',
            button: {
                text: 'Use this media'
            },
        });
        custom_media_frame.on('select', function() {
            var attachment = custom_media_frame.state().get('selection').first().toJSON();
            $('{$selectorInput}').val(attachment.url);
        });
        custom_media_frame.open();
    });
});
</script>

END;
    }
    
    public static function dropdownPages($idDropdown, $defaultText, $idCopyToInput = null)
    {        
        $out = <<<END
<select id="{$idDropdown}" class="btn dropdown-toggle" style="width:1em">
    
END;

        foreach ( get_pages( array('post_status' => 'publish') ) as $page )
        {
            $val = esc_attr( get_permalink($page) );
            $title = esc_html($page->post_title);
            
            $out .= <<<END
<option value="{$val}">{$title}</option>
END;
        }
    
        if( $idCopyToInput != null )
        {
            $out .= <<<END
<script>
jQuery(document).ready(function($){
    $('#{$idDropdown}').change(function(e)
    {
        $('#{$idCopyToInput}').val( this.value );
    });
});
</script>
END;
        }
        
$out .= <<<END
</select>
END;
        
        return $out;
              
    }
}

?>