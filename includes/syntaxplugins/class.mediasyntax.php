<?php

class MediaSyntax extends SyntaxPlugin
{
    function getOrder() {
        return 3;
    }

    function beforeCache(&$input, $plugins) {
        // youtube videos...for the unlikely case that...
        $input = preg_replace('|{{youtube:([a-zA-Z0-9]+)}}|uUi',
                              '<object width="425" height="350">
                                <param name="movie" value="http://www.youtube.com/v/\1"></param>
                                <param name="wmode" value="transparent"></param>
                                <embed src="http://www.youtube.com/v/\1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed>
                               </object>', $input);
        
        // external images
        $input = preg_replace('|{{image:([^<>"]+)}}|uUi',
                              '<img src="\1" alt="" />', $input);
        // image attachments
        $input = preg_replace_callback('|{{([[:alnum:]\.\-_]{4,})}}|uUi', array(&$this, 'imageAttachment'), $input);
    }
    
    function imageAttachment($matches) {
        global $db, $baseurl, $user;
        
        // we'll not blindly make images out of all attachments
        $ext = substr($matches[1], -3);
        if (!in_array($ext, array('png', 'jpg', 'gif'))) {
            return $matches[0];
        }
        
        $att = $db->x->getRow('SELECT * FROM {attachments} WHERE orig_name = ?', null, $matches[1]);
        $task = Flyspray::GetTaskDetails($att['task_id']);
        
        if ($att && $user->can_view_task($task)) {
            return sprintf('<img src="%s" alt="%s" />',
                           Filters::noXSS($baseurl . '?getfile=' . $att['attachment_id']), Filters::noXSS($att['orig_name']));
        } else {
            return $matches[0];
        }
    }
}

?>