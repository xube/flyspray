<?php

function event_description($history) {
    $return = '';
    global $fs, $baseurl, $language;
    
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
                    $field = $language[$translate[$field]];
                    break;
                case 'closedby_version':
                    $field = $language['dueinversion'];
                    $old_value = ($old_value == '0') ? $language['undecided'] : $history['product_version1'];
                    $new_value = ($new_value == '0') ? $language['undecided'] : $history['product_version2'];
                    break;
                 case 'due_date':
                    $field = $language['duedate'];
                    $old_value = formatDate($old_value, false, $language['undecided']);
                    $new_value = formatDate($new_value, false, $language['undecided']);
                    break;
                case 'percent_complete':
                    $field = $language['percentcomplete'];
                    $old_value .= '%';
                    $new_value .= '%';
                    break;
                case 'detailed_desc':
                    $field = "<a href=\"{$baseurl}index.php?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}&history=yep#history\">{$language['details']}</a>";
                    if (!empty($details)) {
                        $details_previous = tpl_formatText($old_value);
                        $details_new =  tpl_formatText($old_value);
                    }
                    $old_value = '';
                    $new_value = '';
                    break;
            }
            $return .= "{$language['fieldchanged']}: {$field}";
            if ($old_value || $new_value) {
                 $return .= " ({$old_value} &nbsp;&nbsp;&rarr; {$new_value})";
            }
            break;
    case '1':      //Task opened
            $return .= $language['taskopened'];
            break;
    case '2':      //Task closed
            $return .= $language['taskclosed'];
            $return .= " ({$history['resolution_name']}";
            if (!empty($old_value)) {
                 $return .= ': ' . tpl_formatText($old_value, true);
            }
            $return .= ')';
            break;
    case '3':      //Task edited
            $return .= $language['taskedited'];
            break;
    case '4':      //Comment added
            $return .= '<a href="#comments">' . $language['commentadded'] . '</a>';
            break;
    case '5':      //Comment edited
            $return .= "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$language['commentedited']}</a>";
            if ($history['c_date_added']) {
                 $return .= " ({$language['commentby']} " . tpl_userlink($history['c_user_id']) . " - " . formatDate($history['c_date_added'], true) . ")";
            }
            if ($details) {
                 $details_previous = tpl_formatText($old_value);
                 $details_new      = tpl_formatText($new_value);
            }
            break;
    case '6':     //Comment deleted
            $return .= "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$language['commentdeleted']}</a>";
            if ($new_value != '' && $history['field_changed'] != '') {
                 $return .= " ({$language['commentby']} " . tpl_userlink($new_value) . " - " . formatDate($history['field_changed'], true) . ")";
            }
            if (!empty($details)) {
                 $details_previous = tpl_formatText($old_value);
                 $details_new = '';
            }
            break;
    case '7':    //Attachment added
            $return .= $language['attachmentadded'];
            if ($history['orig_name']) {
                 $return .= ": <a href=\"{$baseurl}?getfile={$new_value}\">{$history['orig_name']}</a>";
                 if ($history['file_desc'] != '') {
                      $return .= " ({$history['file_desc']})";
                 }
            }
            break;
    case '8':    //Attachment deleted
            $return .= "{$language['attachmentdeleted']}: {$new_value}";
            break;
    case '9':    //Notification added
            $return .= "{$language['notificationadded']}: " . tpl_userlink($new_value);
            break;
    case '10':  //Notification deleted
            $return .= "{$language['notificationdeleted']}: " . tpl_userlink($new_value);
            break;
    case '11':  //Related task added
            $return .= "{$language['relatedadded']}: ". tpl_tasklink($new_value);
            break;
    case '12':          //Related task deleted
            $return .= "{$language['relateddeleted']}: ". tpl_tasklink($new_value);
            break;
    case '13':  //Task reopened
            $return .= $language['taskreopened'];
            break;
    case '14':  //Task assigned
            if (empty($old_value)) {
                $users = explode(' ', trim($new_value));
                $users = array_map ('tpl_userlink', $users);
                $return .= "{$language['taskassigned']} ";
                $return .= implode(', ', $users);
            } elseif (empty($new_value)) {
                 $return .= $language['assignmentremoved'];
            } else {
                 $users = explode(' ', trim($new_value));
                 $users = array_map ('tpl_userlink', $users);
                 $return .= "{$language['taskreassigned']} ";
                 $return .= implode(', ', $users);
            }
            break;
    case '15': //Task added to related list of another task
            $return .= "{$language['addedasrelated']} " . tpl_tasklink($new_value);
            break;
    case '16': //Task deleted from related list of another task
            $return .= "{$language['deletedasrelated']} " . tpl_tasklink($new_value);
            break;
    case '17': //Reminder added
            $return .= "{$language['reminderadded']}: " . tpl_userlink($new_value);
            break;
    case '18': //Reminder deleted
            $return .= "{$language['reminderdeleted']}: " . tpl_userlink($new_value);
            break;
    case '19': //User took ownership
            $return .= "{$language['ownershiptaken']}: " . tpl_userlink($new_value);
            break;
    case '20': //User requested task closure
            $return .= $language['closerequestmade'] . ' - ' . $new_value;
            break;
    case '21': //User requested task
            $return .= $language['reopenrequestmade'] . ' - ' . $new_value;
            break;
    case '22': // Dependency added
            $return .= "{$language['depadded']} ".tpl_tasklink($new_value);
            break;
    case '23': // Dependency added to other task
            $return .= "{$language['depaddedother']} ".tpl_tasklink($new_value);
            break;
    case '24': // Dependency removed
            $return .= "{$language['depremoved']} ".tpl_tasklink($new_value);
            break;
    case '25': // Dependency removed from other task
            $return .= "{$language['depremovedother']} ".tpl_tasklink($new_value);
            break;
    case '26': // Task marked private
            $return .= $language['taskmadeprivate'];
            break;
    case '27': // Task privacy removed - task made public
            $return .= $language['taskmadepublic'];
            break;
    case '28': // PM request denied
            $return .= $language['pmreqdenied'] . ' - ' . $new_value;
            break;
    case '29': // User added to assignees list
            $return .= $language['addedtoassignees'];
            break;
    }
    return $return;    
}

?>
