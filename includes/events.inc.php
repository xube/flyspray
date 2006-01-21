<?php

function event_description($history) {
    $return = '';
    global $fs, $baseurl;
    $fs->get_language_pack('details');
    $fs->get_language_pack('severity');
    $fs->get_language_pack('priority');
    global $details_text, $severity_list, $priority_list;
    
    $translate = array('item_summary' => 'summary', 'attached_to_project' => 'attachedtoproject',
                       'task_type' => 'tasktype', 'product_category' => 'category', 'item_status' => 'status',
                       'task_priority' => 'priority', 'operating_system' => 'operatingsystem', 'task_severity' => 'severity',
                       'product_version' => 'reportedversion');

    $new_value = $history['new_value'];
    $old_value = $history['old_value'];

    switch($history['event_type']) {
    case '0':  //Field changed
            $field = $history['field_changed'];
            switch ($field) {
                case 'item_summary':
                case 'attached_to_project':
                case 'task_type':
                case 'product_category':
                case 'item_status':
                case 'task_priority':
                case 'operating_system':
                case 'task_severity':
                case 'product_version':
                    if($field == 'task_priority') {
                        $old_value = $priority_list[$old_value];
                        $new_value = $priority_list[$new_value];
                    } elseif($field == 'task_severity') {
                        $old_value = $severity_list[$old_value];
                        $new_value = $severity_list[$new_value];
                    } elseif($field != 'item_summary') {                        
                        $old_value = $history[$field . '1'];
                        $new_value = $history[$field . '2'];
                    }
                    $field = $details_text[$translate[$field]];
                    break;
                case 'closedby_version':
                    $field = $details_text['dueinversion'];
                    $old_value = ($old_value == '0') ? $details_text['undecided'] : $history['product_version1'];
                    $new_value = ($new_value == '0') ? $details_text['undecided'] : $history['product_version2'];
                    break;
                 case 'due_date':
                    $field = $details_text['duedate'];
                    $old_value = formatDate($old_value, false, $details_text['undecided']);
                    $new_value = formatDate($new_value, false, $details_text['undecided']);
                    break;
                case 'percent_complete':
                    $field = $details_text['percentcomplete'];
                    $old_value .= '%';
                    $new_value .= '%';
                    break;
                case 'detailed_desc':
                    $field = "<a href=\"{$baseurl}index.php?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}&history=yep#history\">{$details_text['details']}</a>";
                    if (!empty($details)) {
                        $details_previous = tpl_formatText($old_value);
                        $details_new =  tpl_formatText($old_value);
                    }
                    $old_value = '';
                    $new_value = '';
                    break;
            }
            $return .= "{$details_text['fieldchanged']}: {$field}";
            if ($old_value || $new_value) {
                 $return .= " ({$old_value} &nbsp;&nbsp;&rarr; {$new_value})";
            }
            break;
    case '1':      //Task opened
            $return .= $details_text['taskopened'];
            break;
    case '2':      //Task closed
            $return .= $details_text['taskclosed'];
            $return .= " ({$history['resolution_name']}";
            if (!empty($old_value)) {
                 $return .= ': ' . tpl_formatText($old_value, true);
            }
            $return .= ')';
            break;
    case '3':      //Task edited
            $return .= $details_text['taskedited'];
            break;
    case '4':      //Comment added
            $return .= '<a href="#comments">' . $details_text['commentadded'] . '</a>';
            break;
    case '5':      //Comment edited
            $return .= "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentedited']}</a>";
            if ($history['c_date_added']) {
                 $return .= " ({$details_text['commentby']} " . tpl_userlink($history['c_user_id']) . " - " . formatDate($history['c_date_added'], true) . ")";
            }
            if ($details) {
                 $details_previous = tpl_formatText($old_value);
                 $details_new      = tpl_formatText($new_value);
            }
            break;
    case '6':     //Comment deleted
            $return .= "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentdeleted']}</a>";
            if ($new_value != '' && $history['field_changed'] != '') {
                 $return .= " ({$details_text['commentby']} " . tpl_userlink($new_value) . " - " . formatDate($history['field_changed'], true) . ")";
            }
            if (!empty($details)) {
                 $details_previous = tpl_formatText($old_value);
                 $details_new = '';
            }
            break;
    case '7':    //Attachment added
            $return .= $details_text['attachmentadded'];
            if ($history['orig_name']) {
                 $return .= ": <a href=\"{$baseurl}?getfile={$new_value}\">{$history['orig_name']}</a>";
                 if ($history['file_desc'] != '') {
                      $return .= " ({$history['file_desc']})";
                 }
            }
            break;
    case '8':    //Attachment deleted
            $return .= "{$details_text['attachmentdeleted']}: {$new_value}";
            break;
    case '9':    //Notification added
            $return .= "{$details_text['notificationadded']}: " . tpl_userlink($new_value);
            break;
    case '10':  //Notification deleted
            $return .= "{$details_text['notificationdeleted']}: " . tpl_userlink($new_value);
            break;
    case '11':  //Related task added
            $return .= "{$details_text['relatedadded']}: ". tpl_tasklink($new_value);
            break;
    case '12':          //Related task deleted
            $return .= "{$details_text['relateddeleted']}: ". tpl_tasklink($new_value);
            break;
    case '13':  //Task reopened
            $return .= $details_text['taskreopened'];
            break;
    case '14':  //Task assigned
            if (empty($old_value)) {
                $users = explode(' ', trim($new_value));
                $users = array_map ('tpl_userlink', $users);
                $return .= "{$details_text['taskassigned']} ";
                $return .= implode(', ', $users);
            } elseif (empty($new_value)) {
                 $return .= $details_text['assignmentremoved'];
            } else {
                 $users = explode(' ', trim($new_value));
                 $users = array_map ('tpl_userlink', $users);
                 $return .= "{$details_text['taskreassigned']} ";
                 $return .= implode(', ', $users);
            }
            break;
    case '15': //Task added to related list of another task
            $return .= "{$details_text['addedasrelated']} " . tpl_tasklink($new_value);
            break;
    case '16': //Task deleted from related list of another task
            $return .= "{$details_text['deletedasrelated']} " . tpl_tasklink($new_value);
            break;
    case '17': //Reminder added
            $return .= "{$details_text['reminderadded']}: " . tpl_userlink($new_value);
            break;
    case '18': //Reminder deleted
            $return .= "{$details_text['reminderdeleted']}: " . tpl_userlink($new_value);
            break;
    case '19': //User took ownership
            $return .= "{$details_text['ownershiptaken']}: " . tpl_userlink($new_value);
            break;
    case '20': //User requested task closure
            $return .= $details_text['closerequestmade'] . ' - ' . $new_value;
            break;
    case '21': //User requested task
            $return .= $details_text['reopenrequestmade'] . ' - ' . $new_value;
            break;
    case '22': // Dependency added
            $return .= "{$details_text['depadded']} ".tpl_tasklink($new_value);
            break;
    case '23': // Dependency added to other task
            $return .= "{$details_text['depaddedother']} ".tpl_tasklink($new_value);
            break;
    case '24': // Dependency removed
            $return .= "{$details_text['depremoved']} ".tpl_tasklink($new_value);
            break;
    case '25': // Dependency removed from other task
            $return .= "{$details_text['depremovedother']} ".tpl_tasklink($new_value);
            break;
    case '26': // Task marked private
            $return .= $details_text['taskmadeprivate'];
            break;
    case '27': // Task privacy removed - task made public
            $return .= $details_text['taskmadepublic'];
            break;
    case '28': // PM request denied
            $return .= $details_text['pmreqdenied'] . ' - ' . $new_value;
            break;
    case '29': // User added to assignees list
            $return .= $details_text['addedtoassignees'];
            break;
    }
    return $return;    
}

?>
