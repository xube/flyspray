<div id="history" class="tab">
  <?php if ($details): ?>
  <b>{$details_text['selectedhistory']}</b>
  &mdash;
  <a href="{$fs->createUrl('details', Get::val('id'))}#history">
    {$details_text['showallhistory']}</a>
  <?php endif; ?>
  <table class="history">
    <tr>
      <th>{$details_text['eventdate']}</th>
      <th>{$details_text['user']}</th>
      <th>{$details_text['event']}</th>
    </tr>

    <?php if (!count($histories)): ?>
    <tr><td colspan="3">{$details_text['nohistory']}</td></tr>
    <?php else: ?>
    <?php foreach ($histories as $history): ?>
    <tr>
      <td>{$fs->formatDate($history['event_date'], true)}</td>
      <td>{!tpl_userlink($history['user_id'])}</td>
      <td>
        <?php
        /*
         * FIXME TODO
         * This code is horribly unsecure, and has to be reworked
         */
        $new_value = $history['new_value'];
        $old_value = $history['old_value'];
        //Create an event description
        if ($history['event_type'] == 0) {            //Field changed
            $field = $history['field_changed'];
            switch ($field) {
                case 'item_summary':
                    $field = $details_text['summary'];
                    break;
                case 'attached_to_project':
                    $field = $details_text['attachedtoproject'];
                    $result = $db->Query("SELECT project_title FROM {projects} WHERE project_id = ?", array($old_value));
                    $old_value = "<a href=\"?project={$old_value}\">".$db->FetchOne($result)."</a>";
                    $result = $db->Query("SELECT project_title FROM {projects} WHERE project_id = ?", array($new_value));
                    $new_value = "<a href=\"?project={$new_value}\">".$db->FetchOne($result)."</a>";
                    break;
                case 'task_type':
                    $field = $details_text['tasktype'];
                    $result = $db->Query("SELECT tasktype_name FROM {list_tasktype} WHERE tasktype_id = ?", array($old_value));
                    $old_value = $db->fetchOne($result);
                    $result = $db->Query("SELECT tasktype_name FROM {list_tasktype} WHERE tasktype_id = ?", array($new_value));
                    $new_value = $db->fetchOne($result);
                    break;
                case 'product_category':
                    $field = $details_text['category'];
                    $result = $db->Query("SELECT category_name FROM {list_category} WHERE category_id = ?", array($old_value));
                    $old_value = $db->fetchOne($result);
                    $result = $db->Query("SELECT category_name FROM {list_category} WHERE category_id = ?", array($new_value));
                    $new_value = $db->fetchOne($result);
                    break;
                case 'item_status':
                    $field = $details_text['status'];
                    $old_value = $status_list[$old_value];
                    $new_value = $status_list[$new_value];
                    break;
                case 'task_priority':
                    $field = $details_text['priority'];
                    $old_value = $priority_list[$old_value];
                    $new_value = $priority_list[$new_value];
                    break;
                case 'operating_system':
                    $field = $details_text['operatingsystem'];
                    $result = $db->Query("SELECT os_name FROM {list_os} WHERE os_id = ?", array($old_value));
                    $old_value = $db->fetchOne($result);
                    $result = $db->Query("SELECT os_name FROM {list_os} WHERE os_id = ?", array($new_value));
                    $new_value = $db->fetchOne($result);
                    break;
                case 'task_severity':
                    $field = $details_text['severity'];
                    $old_value = $severity_list[$old_value];
                    $new_value = $severity_list[$new_value];
                    break;
                case 'product_version':
                    $field = $details_text['reportedversion'];
                    $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($old_value));
                    $old_value = $db->fetchOne($result);
                    $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($new_value));
                    $new_value = $db->fetchOne($result);
                    break;
                case 'closedby_version':
                    $field = $details_text['dueinversion'];
                    if ($old_value == '0') {
                        $old_value = $details_text['undecided'];
                    } else {
                        $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array(intval($old_value)));
                        $old_value = $db->fetchOne($result);
                    }
                    if ($new_value == '0') {
                        $new_value = $details_text['undecided'];
                    } else {
                        $result = $db->Query("SELECT version_name
                                FROM {list_version}
                                WHERE version_id = ?", array(intval($new_value)));
                        $new_value = $db->fetchOne($result);
                    }
                    break;
                 case 'due_date':
                    $field = $details_text['duedate'];
                    $old_value = $fs->FormatDate($old_value, false, $details_text['undecided']);
                    $new_value = $fs->FormatDate($new_value, false, $details_text['undecided']);
                    break;
                case 'percent_complete':
                    $field = $details_text['percentcomplete'];
                    $old_value .= '%';
                    $new_value .= '%';
                    break;
                case 'detailed_desc':
                    $field = "<a href=\"index.php?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['details']}</a>";
                    if (!empty($details)) {
                        $details_previous = tpl_formatText($old_value);
                        $details_new =  tpl_formatText($old_value);
                    }
                    $old_value = '';
                    $new_value = '';
                    break;
            }
            echo "{$details_text['fieldchanged']}: {$field}";
            if ($old_value != '' || $new_value != '') {
                echo " ({$old_value} &nbsp;&nbsp;&rarr; {$new_value})";
            }
        } elseif ($history['event_type'] == '1') {      //Task opened
            echo $details_text['taskopened'];
        } elseif ($history['event_type'] == '2') {      //Task closed
            echo $details_text['taskclosed'];
            $result = $db->Query("SELECT resolution_name FROM {list_resolution} WHERE resolution_id = ?", array($new_value));
            $res_name = $db->FetchOne($result);
            echo " ({$res_name}";
            if (!empty($old_value)) {
                echo ': ' . tpl_formatText($old_value);
            }
            echo ')';
        } elseif ($history['event_type'] == '3') {      //Task edited
            echo $details_text['taskedited'];
        } elseif ($history['event_type'] == '4') {      //Comment added
            echo '<a href="#comments">' . $details_text['commentadded'] . '</a>';
        } elseif ($history['event_type'] == '5') {      //Comment edited
            echo "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentedited']}</a>";
            $comment = $db->Query("SELECT user_id, date_added FROM {comments} WHERE comment_id = ?", array($history['field_changed']));
            if ($db->CountRows($comment) != 0) {
                $comment = $db->FetchRow($comment);
                echo " ({$details_text['commentby']} " . tpl_userlink($comment['user_id']) . " - " . $fs->formatDate($comment['date_added'], true) . ")";
            }
            if ($details != '') {
                $details_previous = tpl_formatText($old_value);
                $details_new      = tpl_formatText($new_value);
            }
        } elseif ($history['event_type'] == '6') {     //Comment deleted
            echo "<a href=\"?do=details&amp;id={$history['task_id']}&amp;details={$history['history_id']}#history\">{$details_text['commentdeleted']}</a>";
            if ($new_value != '' && $history['field_changed'] != '') {
                echo " ({$details_text['commentby']} " . tpl_userlink($new_value) . " - " . $fs->formatDate($history['field_changed'], true) . ")";
            }
            if (!empty($details)) {
                $details_previous = tpl_formatText($old_value);
                $details_new = '';
            }
        } elseif ($history['event_type'] == '7') {    //Attachment added
            echo $details_text['attachmentadded'];
            $attachment = $db->Query("SELECT orig_name, file_desc FROM {attachments} WHERE attachment_id = ?", array($new_value));
            if ($db->CountRows($attachment) != 0) {
                $attachment = $db->FetchRow($attachment);
                echo ": <a href=\"{$baseurl}?getfile={$new_value}\">{$attachment['orig_name']}</a>";
                if ($attachment['file_desc'] != '') {
                    echo " ({$attachment['file_desc']})";
                }
            }
        } elseif ($history['event_type'] == '8') {    //Attachment deleted
           echo "{$details_text['attachmentdeleted']}: {$new_value}";
        } elseif ($history['event_type'] == '9') {    //Notification added
           echo "{$details_text['notificationadded']}: " . tpl_userlink($new_value);
        } elseif ($history['event_type'] == '10') {  //Notification deleted
           echo "{$details_text['notificationdeleted']}: " . tpl_userlink($new_value);
        } elseif ($history['event_type'] == '11') {  //Related task added
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $related = $db->fetchOne($result);
            echo "{$details_text['relatedadded']}: <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$related}</a>";
        } elseif ($history['event_type'] == '12') {  //Related task deleted
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $related = $db->fetchOne($result);
            echo "{$details_text['relateddeleted']}: <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$related}</a>";
        } elseif ($history['event_type'] == '13') {  //Task reopened
            echo $details_text['taskreopened'];
        } elseif ($history['event_type'] == '14') {  //Task assigned
            if ($old_value == '0') {
                echo "{$details_text['taskassigned']} " . tpl_userlink($new_value);
            } elseif ($new_value == '0') {
                echo $details_text['assignmentremoved'];
            } else {
                echo "{$details_text['taskreassigned']} " . tpl_userlink($new_value);
            }
        } elseif ($history['event_type'] == '15') { //Task added to related list of another task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $related = $db->fetchOne($result);
            echo "{$details_text['addedasrelated']} <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$related}</a>";
        } elseif ($history['event_type'] == '16') { //Task deleted from related list of another task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $related = $db->fetchOne($result);
            echo "{$details_text['deletedasrelated']} <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$related}</a>";
        } elseif ($history['event_type'] == '17') { //Reminder added
            echo "{$details_text['reminderadded']}: " . tpl_userlink($new_value);
        } elseif ($history['event_type'] == '18') { //Reminder deleted
            echo "{$details_text['reminderdeleted']}: " . tpl_userlink($new_value);
        } elseif ($history['event_type'] == '19') { //User took ownership
            echo "{$details_text['ownershiptaken']}: " . tpl_userlink($new_value);
        } elseif ($history['event_type'] == '20') { //User requested task closure
            echo $details_text['closerequestmade'] . ' - ' . $new_value;
        } elseif ($history['event_type'] == '21') { //User requested task
            echo $details_text['reopenrequestmade'] . ' - ' . $new_value;
        } elseif ($history['event_type'] == '22') { // Dependency added
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $dependency = $db->fetchOne($result);
            echo "{$details_text['depadded']} <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$dependency}</a>";
        } elseif ($history['event_type'] == '23') { // Dependency added to other task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $dependency = $db->fetchOne($result);
            echo "{$details_text['depaddedother']} <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$dependency}</a>";
        } elseif ($history['event_type'] == '24') { // Dependency removed
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $dependency = $db->fetchOne($result);
            echo "{$details_text['depremoved']} <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$dependency}</a>";
        } elseif ($history['event_type'] == '25') { // Dependency removed from other task
            $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($new_value));
            $dependency = $db->fetchOne($result);
            echo "{$details_text['depremovedother']} <a href=\"" . $fs->CreateURL('details', $new_value) . "\">FS#{$new_value} &mdash; {$dependency}</a>";
        } elseif ($history['event_type'] == '26') { // Task marked private
            echo $details_text['taskmadeprivate'];
        } elseif ($history['event_type'] == '27') { // Task privacy removed - task made public
            echo $details_text['taskmadepublic'];
        } elseif ($history['event_type'] == '28') { // PM request denied
            echo $details_text['pmreqdenied'] . ' - ' . $new_value;
        }
        ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
  </table>

  <?php if ($details && isset($details_previous) && isset($details_new)): ?>
  <table class="history">
    <tr>
      <th>{$details_text['previousvalue']}</th>
      <th>{$details_text['newvalue']}</th>
    </tr>
    <tr>
      <td>{$details_previous}</td>
      <td>{$details_new}</td>
    </tr>
  </table>
  <?php endif; ?>
</div>
