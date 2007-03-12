<?php

// XXX  be aware: make sure you quote correctly using qstr()
// the variables used in the $where parameter, since statement is
// executed AS IS.

function get_events($task_id, $where = '', $sort = 'ASC')
{
    global $db;
    $sort = Filters::enum($sort, array('ASC', 'DESC'));
    return $db->Execute("SELECT h.*,
                      p1.project_title AS project_id1,
                      p2.project_title AS project_id2,
                      lr.item_name AS resolution_name,
                      c.date_added AS c_date_added,
                      c.user_id AS c_user_id,
                      att.orig_name,
                      lfn.item_name AS new_value_l,
                      lfn.item_name AS old_value_l,
                      lcfn.category_name AS new_value_c,
                      lcfn.category_name AS old_value_c,
                      f.field_name

                FROM  {history} h

            LEFT JOIN {list_items} lr ON lr.list_item_id = h.new_value AND h.event_type = 2
            LEFT JOIN {list_items} lfn ON lfn.list_item_id = h.new_value AND h.event_type = 3
            LEFT JOIN {list_items} lfo ON lfo.list_item_id = h.old_value AND h.event_type = 3
            LEFT JOIN {list_category} lcfn ON lcfn.category_id = h.new_value AND h.event_type = 3
            LEFT JOIN {list_category} lcfo ON lcfo.category_id = h.old_value AND h.event_type = 3
            LEFT JOIN {fields} f ON f.field_id = h.field_changed AND h.event_type = 3

            LEFT JOIN {projects} p1 ON p1.project_id = h.old_value AND h.field_changed='project_id'
            LEFT JOIN {projects} p2 ON p2.project_id = h.new_value AND h.field_changed='project_id'

            LEFT JOIN {comments} c ON c.comment_id = h.field_changed AND h.event_type = 5

            LEFT JOIN {attachments} att ON att.attachment_id = h.new_value AND h.event_type = 7

                WHERE h.task_id = ? $where
             ORDER BY event_date $sort, event_type ASC", array($task_id));
}

/**
 * XXX: A mess,remove my in 1.0
 */
function event_description($history) {
    $return = '';
    global $fs, $baseurl, $details;

    $translate = array('item_summary' => 'summary', 'project_id' => 'attachedtoproject',
                       'task_severity' => 'severity', 'mark_private' => 'visibility');
    // if soemthing gets double escaped, add it here.
    $noescape = array('new_value', 'old_value');

    foreach($history as $key=> $value) {
        if(!in_array($key, $noescape)) {
            $history[$key] = Filters::noXSS($value);
        }
    }

    $new_value = $history['new_value'];
    $old_value = $history['old_value'];

    switch($history['event_type']) {
    case '3':  //Field changed
            if (!$new_value && !$old_value) {
                $return .= eL('taskedited');
                break;
            }

            $field = $history['field_changed'];
            switch ($field) {
                case 'item_summary':
                case 'project_id':
                case 'task_severity':
                    if($field == 'task_severity') {
                        $old_value = $fs->severities[$old_value];
                        $new_value = $fs->severities[$new_value];
                    } elseif($field != 'item_summary') {
                        $old_value = $history[$field . '1'];
                        $new_value = $history[$field . '2'];
                    }
                    $field = eL($translate[$field]);
                    break;
                case 'percent_complete':
                    $field = eL('percentcomplete');
                    $old_value .= '%';
                    $new_value .= '%';
                    break;
                case 'mark_private':
                    $field = eL($translate[$field]);
                    $old_value = ($old_value) ? eL('private') : eL('public');
                    $new_value = ($new_value) ? eL('private') : eL('public');
                    break;
                case 'detailed_desc':
                    $field = sprintf("<a href=\"javascript:getHistory('%d', '%s', 'history', '%d');
                                      showTabById('history', true);\">%s</a>", 
                                    $history['task_id'], $baseurl, $history['history_id'], eL('details'));
                    if (!empty($details)) {
                        $details_previous = TextFormatter::render($old_value);
                        $details_new =  TextFormatter::render($new_value);
                    }
                    $old_value = '';
                    $new_value = '';
                    break;
            }
            if (is_numeric($field)) {
                $field = $history['field_name'];
            }
            $return .= eL('fieldchanged').": {$field}";
            if ($old_value || $new_value) {
                 $return .= " ({$old_value} &rarr; {$new_value})";
            }
            break;
    case '1':      //Task opened
            $return .= eL('taskopened');
            break;
    case '2':      //Task closed
            $return .= eL('taskclosed');
            $return .= " ({$history['resolution_name']}";
            if (!empty($old_value)) {
                 $return .= ': ' . TextFormatter::render($old_value, true);
            }
            $return .= ')';
            break;
    case '4':      //Comment added
            $return .= sprintf('<a href="%s#comment%d">%s</a>', 
                                Filters::noXSS(CreateUrl('details', $history['task_id'])), 
                                $history['new_value'], eL('commentadded'));
            break;
    case '5':      //Comment edited

            $return .= sprintf("<a href=\"javascript:getHistory('%d', '%s', 'history', '%d');\">%s</a>", 
                                $history['task_id'], $baseurl, $history['history_id'], eL('commentedited'));

            if ($history['c_date_added']) {
                 $return .= sprintf(' ("%s %s  - %s")', eL('commentby'), tpl_userlink($history['c_user_id']), formatDate($history['c_date_added'], true));
            }


            if ($details) {
                 $details_previous = TextFormatter::render($old_value);
                 $details_new      = TextFormatter::render($new_value);
            }
            break;
    case '6':     //Comment deleted
        
            $return .= sprintf("<a href=\"javascript:getHistory('%d', '%s', 'history','%d');\">%s</a>", 
                       $history['task_id'], $baseurl, $history['history_id'], eL('commentdeleted'));
            
            if (!empty($new_value)  && !empty($history['field_changed'])) {
                $return .= sprintf('(%s %s - %s)', eL('commentby'), tpl_userlink($new_value), formatDate($history['field_changed'], true));
            }
            if (!empty($details)) {
                 $details_previous = TextFormatter::render($old_value);
                 $details_new = '';
            }
            break;
    case '7':    //Attachment added
            $return .= eL('attachmentadded');
            if ($history['orig_name']) {
                $return .= sprintf(': <a href="%s?getfile=%d">%s</a>', $baseurl, $new_value, $history['orig_name']);
            }
            break;
    case '8':    //Attachment deleted
            $return .= eL('attachmentdeleted') . ":" . Filters::noXSS($new_value);
            break;
    case '9':    //Notification added
            $return .= eL('notificationadded') . ': ' . tpl_userlink($new_value);
            break;
    case '10':  //Notification deleted
            $return .= eL('notificationdeleted') . ': ' . tpl_userlink($new_value);
            break;
    case '11':  //Related task added
            $return .= eL('relatedadded') . ': ' . tpl_tasklink($new_value);
            break;
    case '12':  //Related task deleted
            $return .= eL('relateddeleted') . ': ' . tpl_tasklink($new_value);
            break;
    case '13':  //Task reopened
            $return .= eL('taskreopened');
            break;
    case '14':  //Task assigned
            if (empty($old_value)) {
                $users = explode(' ', trim($new_value));
                $users = array_map('tpl_userlink', $users);
                $return .= eL('taskassigned').' ';
                $return .= implode(', ', $users);
            } elseif (empty($new_value)) {
                 $return .= eL('assignmentremoved');
            } else {
                 $users = explode(' ', trim($new_value));
                 $users = array_map('tpl_userlink', $users);
                 $return .= eL('taskreassigned').' ';
                 $return .= implode(', ', $users);
            }
            break;
    case '17': //Reminder added
            $return .= eL('reminderadded') . ': ' . tpl_userlink($new_value);
            break;
    case '18': //Reminder deleted
            $return .= eL('reminderdeleted') . ': ' . tpl_userlink($new_value);
            break;
    case '19': //User took ownership
            $return .= eL('ownershiptaken') . ': ' . tpl_userlink($new_value);
            break;
    case '20': //User requested task closure
            $return .= eL('closerequestmade') . ' - ' . $new_value;
            break;
    case '21': //User requested task
            $return .= eL('reopenrequestmade') . ' - ' . $new_value;
            break;
    case '22': // Dependency added
            $return .= eL('depadded') . ' ' . tpl_tasklink($new_value);
            break;
    case '23': // Dependency added to other task
            $return .= eL('depaddedother') . ' ' . tpl_tasklink($new_value);
            break;
    case '24': // Dependency removed
            $return .= eL('depremoved') . ' ' . tpl_tasklink($new_value);
            break;
    case '25': // Dependency removed from other task
            $return .= eL('depremovedother') . ' ' . tpl_tasklink($new_value);
            break;
    // 26 and 27 replaced by 0 (mark_private)
    case '28': // PM request denied
            $return .= eL('pmreqdenied') . ' - ' . $new_value;
            break;
    case '29': // User added to assignees list
            $return .= eL('addedtoassignees');
            break;
    case '30': // user created
            $return .= eL('usercreated');
            break;
    case '31': // user deleted
            $return .= eL('userdeleted');
            break;
    }

    if (isset($details_previous)) $GLOBALS['details_previous'] = $details_previous;
    if (isset($details_new))      $GLOBALS['details_new'] = $details_new;

    return $return;
}

?>
