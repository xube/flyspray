<?php

/*
   This script generates all different types of reports.
   The majority of work was done by Jonathan Oxer - http://www.ivt.com.au/
   Thanks, Jon!
*/


$fs->get_language_pack('reports');
$fs->get_language_pack('details');
$fs->get_language_pack('admin');
$fs->get_language_pack('index');

// Only allow those with permission to view this page
if ($permissions['view_reports'] != '1') {
    echo $admin_text['nopermission'];
    exit;
    $fs->Redirect( $fs->CreateURL('error', null) );
}

function summary_report() { ?>
    <div class="tabentries">
      <p><em>Summary Report</em></p>
      <div class="tabentry">aoeu</div>
    </div>
<?php }

/***
 * changelog_report
 * Display a list of closed tasks between specified dates. This can be useful as a starting
 * point for writing a version changelog.
 */
function changelog_report()
{
    global $db; 
    global $fs;
    global $reports_text;
    global $proj;

    $startdate  = Req::val('startdate', date('d-M-Y', strtotime('-1 month')));
    $enddate    = Req::val('enddate', date('d-M-Y'));
    $sort       = Req::val('sort', 'desc');

    $ustartdate = strtotime($startdate);
    $uenddate   = strtotime("$enddate + 1 day");

    $get_changes = $db->Query("SELECT  t.*, u.*, r.*
                                 FROM  {tasks} t
                            LEFT JOIN  {users} u on t.closed_by = u.user_id
                            LEFT JOIN  {list_resolution} r on t.resolution_reason = r.resolution_id
                                WHERE  t.is_closed = 1 AND t.attached_to_project = ?
                                       AND t.date_closed >= ?  AND t.date_closed <= ?
                             ORDER BY  t.date_closed $sort", array($proj->id,$ustartdate,$uenddate));
?>

<div class="tabentries">
  <p><em>$reports_text[changeloggen]</em></p>
    <div class="tabentry">
  
    <form name="changelog_form" action="index.php?do=reports&report=changelog" method="post">
      <?php echo $reports_text['listfrom']?>
  
      <input id="startdate" type="text" name="startdate" size="10" value="<?php echo $startdate?>" />
      <button id="triggerstartdate">...</button>
      <script type="text/javascript">
          Calendar.setup({
              inputField  : "startdate",         // ID of the input field
              ifFormat    : "%d-%b-%Y",          // the date format
              button      : "triggerstartdate"   // ID of the button
          });
      </script>
  
      <?php echo $reports_text['to']?> <input id="enddate" type="text" name="enddate" size="10" value="<?php echo $enddate?>" />
      <button id="triggerenddate">...</button>
      <script type="text/javascript">
          Calendar.setup({
            inputField  : "enddate",         // ID of the input field
            ifFormat    : "%d-%b-%Y",        // the date format
            button      : "triggerenddate"   // ID of the button
          });
      </script>
  
      <select name="sort">
        <option value="asc"  <?php if ($sort == 'asc')  echo 'selected="selected"'; ?>><?php echo $reports_text['oldestfirst'];?></option>
        <option value="desc" <?php if ($sort == 'desc') echo 'selected="selected"'; ?>><?php echo $reports_text['recentfirst'];?></option>
      </select>
  
      <input type="submit" class="mainbutton" name="submit" value="<?php echo $reports_text['show']?>" />
    </form>
    <table border='1' cellpadding='2' cellspacing='0'>
    <?php
    while ($row = $db->FetchArray($get_changes)) {
        $task_id         = $row['task_id'];
        $task_resolution = $row['resolution_name'];
        $item_summary    = $row['item_summary'];
        $closure_comment = $row['closure_comment'];
        $real_name       = $row['real_name'];
        $event_date      = $fs->formatDate($row['date_closed'], false);

        if($closure_comment) {
            echo "<tr><td>$event_date: <a href=\"" . $fs->CreateURL('details', $task_id) . "\">$item_summary</a>. <b>$task_resolution :</b> $closure_comment</td><td>$real_name</td></tr>";
        }
    }
    ?>
    </table>
  </div>
</div>
<?php
};

/***
 * severity_report
 * Display a list of item severities with a count of open items in each severity
 */
function severity_report()
{
    global $db;
    global $fs;
    global $severity_list;
    global $reports_text;
    global $proj;

    $severity_colours = array('','ffe9b4','efca80','edb98a','ffb2ac','f3a29b');

    $get_severity_count = $db->Query("SELECT  task_type,task_severity,COUNT(*) AS severity_count
                                        FROM  {tasks}
                                       WHERE  attached_to_project = ? AND !is_closed
                                    GROUP BY  task_severity
                                    ORDER BY  task_severity DESC", array($proj->id));
    $count      = $db->CountRows($get_severity_count);
    $counttotal = 0;

    while ($row = $db->FetchArray($get_severity_count)) {
        $sev_severity = $row['task_severity'];
        $sev_title    = $severity_list[$sev_severity];
        $sev_count    = $row['severity_count'];
        $sevarray[$sev_severity] = $sev_count;
        $counttotal  += $sev_count;
        $sev_color    = $severity_colours[$sev_severity];

        $dataelements[] .= $sev_count.'_'.$sev_title.'_'.$sev_color;
    }

    $data  = urlencode(implode('|',$dataelements));
    $title = urlencode("Tasks by severity");
?>
<div class="tabentries">
  <p><em>$reports_text[severityrep]</em></p>
    <div class="tabentry">
    <?php echo "$reports_text[totalopen]: $counttotal"; ?><br />
    <img src="scripts/reports-graph-pie.php?title=<?php echo $title?>&data=<?php echo $data?>" />\n";
  </div>
</div>
<?php
}


/***
 * age_report
 * Display report of bug counts by age
 */
function age_report()
{
    global $db;
    global $fs;
    global $reports_text;
    global $proj;
   
    $today = time();
    $bracket_count = 1;
   
    $age_list = $db->Query("SELECT  task_severity, date_opened
                              FROM  {tasks}
                             WHERE  attached_to_project = ? AND is_closed = 0
                          ORDER BY  date_opened", array($proj->id));
   
    while ($row = $db->FetchArray($age_list)) {
        $date_opened = $row['date_opened'];
        $task_age    = round( ($today - $date_opened) / 86400 );
        if ($task_age == $last_age) {
            $bracket_count++;
        } else {
            $bracket_count = 1;
        }
        $agecount_array[$task_age] = $bracket_count;
        $last_age = $task_age;
    }
?>
<div class="tabentries">
  <p><em>Age Report</em></p>
  <div class="tabentry">
    <p>
    This age report is a work in progress. At present it only shows a list of
    ages in days, with a count of open tasks in each age. Ultimately this should
    show a bar graph with ages in weeks.
    <p>
<?php
    foreach($agecount_array as $age => $count) {
        echo "$age days:$count<br />\n";
    }
?>
  </div>
</div>
<?php
}


/***
 * events_report
 * Allow user to select types of events to view, and display a list of those events
 */
function events_report()
{
    global $db;
    global $fs;
    global $conf;
    global $reports_text;
    global $details_text;
    global $proj;
    global $index_text;

    strtoupper(Req::val('sort', 'desc'));

    switch (Req::val('order')) {
        case 'id':
            $orderby = "h.task_id {$sort}, h.event_date {$sort}";
            break;
        case 'type':
            $orderby = "h.event_type {$sort}, h.event_date {$sort}";
            break;
        case 'date':
            $orderby = "h.event_date {$sort}, h.event_type {$sort}";
            break;
        case 'user':
            $orderby = "u.real_name {$sort}, h.event_date {$sort}";
            break;
        default:
            $orderby = "h.event_date {$sort}, h.event_type {$sort}";
    }

    $date = $wheredate = $within = '';
    switch (Req::val('date')) {
        case 'within':
            $date   = 'within';
            $within = Req::val('within');
            if ($within != 'all') {
                $wheredate = 24 * 60 * 60;
                if ($within == 'week') {
                    $wheredate *= 7;
                } elseif ($within == 'month') {
                    $wheredate *= 30;
                } elseif ($within == 'year') {
                    $wheredate *= 365;
                };
                $wheredate = date('U') - $wheredate;
                $wheredate = "AND h.event_date > {$wheredate}";
            };
            break;

        case 'from':
            $date      = 'from';
            $fromdate  = Req::val('fromdate', date("d-M-Y"));
            $todate    = Req::val('todate', date("d-M-Y"));

            $ufromdate = strtotime($fromdate);
            // Add 24 hours to the end to make it include that date
            $utodate   = strtotime($todate) + 86400;

            $wheredate = "AND h.event_date > {$ufromdate} AND h.event_date < {$utodate}";
            break;

        case 'duein':
            if (is_numeric($duein = Req::val('duein'))) {
                $date      = 'duein';
                $wheredate = "AND t.closedby_version = $duein";
            };
            break;
    }
    if (!$within) { $within = 'year'; }

    $search = $get = '';
    $type = $get_array = array();
    if (Req::has('open'))          { $search = 1; array_push($type, 1, 13);          $get_array[] = 'open';          }
    if (Req::has('close'))         { $search = 1; array_push($type, 2);              $get_array[] = 'close';         }
    if (Req::has('edit'))          { $search = 1; array_push($type, 0, 3);           $get_array[] = 'edit';          }
    if (Req::has('assign'))        { $search = 1; array_push($type, 14);             $get_array[] = 'assign';        }
    if (Req::has('comments'))      { $search = 1; array_push($type, 4, 5, 6);        $get_array[] = 'comments';      }
    if (Req::has('attachments'))   { $search = 1; array_push($type, 7, 8);           $get_array[] = 'attachments';   }
    if (Req::has('related'))       { $search = 1; array_push($type, 11, 12, 15, 16); $get_array[] = 'related';       }
    if (Req::has('notifications')) { $search = 1; array_push($type, 9, 10);          $get_array[] = 'notifications'; }
    if (Req::has('reminders'))     { $search = 1; array_push($type, 17, 18);         $get_array[] = 'reminders';     }

    if ($type = implode($type, ', ')) {
        $type = "AND h.event_type IN ({$type})";
        $get = '&amp;' . join('&amp;', $get_array);
    }
?>

    <div id="events" class="tab">
      <form action="<?php echo $conf['general']['baseurl'];?>index.php?do=reports&amp;report=events" method="post">
        <table>
        <tr>
          <td><b><?php echo $reports_text['events'];?></b><br />
            <table>
              <tr>
                <td><?php echo $reports_text['tasks'];?></td>
                <td><label class="inline"><input type="checkbox" name="open" <?php if (Req::has('open')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['opened'];?></label></td>
                <td><label class="inline"><input type="checkbox" name="close" <?php if (Req::has('close')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['closed'];?></label></td>
                <td><label class="inline"><input type="checkbox" name="edit" <?php if (Req::has('edit')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['edited'];?></label></td>
              </tr>
              <tr>
                <td></td>
                <td><label class="inline"><input type="checkbox" name="assign" <?php if (Req::has('assign')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['assigned'];?></label></td>
                <td><label class="inline"><input type="checkbox" name="comments" <?php if (Req::has('comments')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['comments'];?></label></td>
                <td><label class="inline"><input type="checkbox" name="attachments" <?php if (Req::has('attachments')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['attachments'];?></label></td>
              </tr>
              <tr>
                <td></td>
                <td><label class="inline"><input type="checkbox" name="related" <?php if (Req::has('related')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['relatedtasks'];?></label></td>
                <td><label class="inline"><input type="checkbox" name="notifications" <?php if (Req::has('notifications')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['notifications'];?></label></td>
                <td><label class="inline"><input type="checkbox" name="reminders" <?php if (Req::has('reminders')) echo 'checked="checked"';?> />
                    <?php echo $reports_text['reminders'];?></label></td>
              </tr>
            </table>
          </td>
          <td><b><?php echo $reports_text['date'];?></b><br />
            <table border="0">
              <tr>
                <td><label class="inline"><input type="radio" name="date" value="within" <?php if ($date == 'within') echo 'checked="checked"';?> />
                    <?php echo $reports_text['within'];?></label></td>
                <td colspan="6">
                  <select name="within">
                    <option value="day"   <?php if ($within == 'day')   echo 'selected="selected"';?>><?php echo $reports_text['pastday'];?></option>
                    <option value="week"  <?php if ($within == 'week')  echo 'selected="selected"';?>><?php echo $reports_text['pastweek'];?></option>
                    <option value="month" <?php if ($within == 'month') echo 'selected="selected"';?>><?php echo $reports_text['pastmonth'];?></option>
                    <option value="year"  <?php if ($within == 'year')  echo 'selected="selected"';?>><?php echo $reports_text['pastyear'];?></option>
                    <option value="all"   <?php if ($within == 'all')   echo 'selected="selected"';?>><?php echo $reports_text['nolimit'];?></option>
                  </select>
                </td>
              </tr>

              <tr>
                <td><label class="inline"><input type="radio" name="date" value="from" <?php if($date == 'from') echo 'checked="checked"';?> />
                    <?php echo $reports_text['from'];?></label></td>
                <td>
                  <input id="fromdate" type="text" name="fromdate" size="10" value="<?php if(isset($fromdate)) echo $fromdate?>" />
                  <button id="triggerfromdate">...</button>
                  <script type="text/javascript">
                    Calendar.setup({
                        inputField  : "fromdate",       // ID of the input field
                        ifFormat    : "%d-%b-%Y",       // the date format
                        button      : "triggerfromdate" // ID of the button
                    });
                  </script>
                  &mdash;
                  <input id="todate" type="text" name="todate" size="10" value="<?php if(isset($todate)) echo $todate;?>" />
                  <button id="triggertodate">...</button>
                  <script type="text/javascript">
                    Calendar.setup({
                        inputField  : "todate",         // ID of the input field
                        ifFormat    : "%d-%b-%Y",       // the date format
                        button      : "triggertodate"   // ID of the button
                    });
                  </script>
                </td>
              </tr>
              <tr>
                <td><label class="inline"><input type="radio" name="date" value="duein" <?php if($date == 'duein') echo 'checked="checked"';?> />
                    <?php echo $reports_text['duein'];?></label></td>
                <td colspan="6">
                  <select name="duein">
                  <?php
                  $ver_list = $db->Query("SELECT  version_id, version_name
                                            FROM  {list_version}
                                           WHERE  project_id = ?  AND show_in_list = '1' AND version_tense = '3'
                                        ORDER BY  list_position", array($proj->id));

                  while ($row = $db->FetchArray($ver_list)) {
                      if (Req::val('duein') == $row['version_id']) {
                          echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>";
                      } else {
                          echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>";
                      }
                  }
                  ?>
                  </select>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr><td><input type="submit" class="mainbutton" name="submit" value="<?php echo $reports_text['show'];?>" /></td></tr>
        </table>
      </form>

      <?php
      $query_history = $db->Query("SELECT  h.*, u.user_name, u.real_name, t.item_summary, t.task_severity
                                      FROM  {history} h
                                 LEFT JOIN  {users} u ON h.user_id = u.user_id
                                 LEFT JOIN  {tasks} t ON h.task_id = t.task_id
                                     WHERE  t.attached_to_project = ? {$type} {$wheredate}
                                  ORDER BY  {$orderby}", array($proj->id));

      if (!$db->CountRows($query_history)):
          echo "$details_text[nohistory]\n";
      elseif($search):
      ?>
      <table id="tasklist">
        <tr>
          <th class="taskid"><a href="?do=history&amp;order=id&amp;sort=<?php echo (Req::val('order') == 'id' && $sort == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $index_text['id'];?></a></th>
          <th><?php echo $details_text['summary'];?></th>
          <th><a href="?do=history&amp;order=date&amp;sort=<?php echo (Req::val('order') == 'date' && $sort == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $details_text['eventdate'];?></a></th>
          <th><a href="?do=history&amp;order=user&amp;sort=<?php echo (Req::val('order') == 'user' && $sort == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $details_text['user'];?></a></th>
          <th><a href="?do=history&amp;order=type&amp;sort=<?php echo (Req::val('order') == 'type' && $sort == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $details_text['event'];?></a></th>
        </tr>
        <?php while ($history = $db->FetchRow($query_history)): ?>
        <tr class="severity<?php echo $history['task_severity'];?>" onclick="openTask('<?php echo $fs->CreateURL('details', $history['task_id']);?>')">
          <?php echo '<td><a href="' . $fs->CreateURL('details', $history['task_id']) . "\">FS#{$history['task_id']}</a></td>";?>
          <?php echo '<td><a href="' . $fs->CreateURL('details', $history['task_id']) . '">' . htmlspecialchars($history['item_summary']) . '</a></td>';?>
          <td><?php echo $fs->formatDate($history['event_date'], true);?></td>
          <td><?php echo $history['user_id'] ? $fs->LinkedUserName($history['user_id']) : $details_text['anonymous']; ?></td>
          <td><?php echo EventDescription($history);?></td>
        </tr>
        <?php endwhile; ?>
      </table>
      <?php endif; ?>
    </div>
<?php
}


/***
 * eventdescription
 * Display details of a particular event. Called by events_report when displaying a
 * list of results.
 */
function EventDescription($history)
{
    global $db;
    global $fs;
    global $details_text;

    $description = '';
    $newvalue = $history['new_value'];
    $oldvalue = $history['old_value'];

    //Create an event description
    if ($history['event_type'] == 0) {            //Field changed

        $field = $history['field_changed'];

        switch ($field) {
        case 'item_summary':
            $field = $details_text['summary'];
            $oldvalue = htmlspecialchars($oldvalue);
            $newvalue = htmlspecialchars($newvalue);
            break;
        case 'attached_to_project':
            $field = $details_text['attachedtoproject'];
            $result = $db->Query("SELECT project_title FROM {projects} WHERE project_id = ?", array($oldvalue));
            list($oldprojecttitle) = $db->FetchRow($result);
            $result = $db->Query("SELECT project_title FROM {projects} WHERE project_id = ?", array($newvalue));
            list($newprojecttitle) = $db->FetchRow($result);
            $oldvalue = "<a href=\"?project={$oldvalue}\">{$oldprojecttitle}</a>";
            $newvalue = "<a href=\"?project={$newvalue}\">{$newprojecttitle}</a>";
            break;
        case 'task_type':
            $field = $details_text['tasktype'];
            $result = $db->Query("SELECT tasktype_name FROM {list_tasktype} WHERE tasktype_id = ?", array($oldvalue));
            list($oldvalue) = $db->FetchRow($result);
            $result = $db->Query("SELECT tasktype_name FROM {list_tasktype} WHERE tasktype_id = ?", array($newvalue));
            list($newvalue) = $db->FetchRow($result);
            break;
        case 'product_category':
            $field = $details_text['category'];
            $result = $db->Query("SELECT category_name FROM {list_category} WHERE category_id = ?", array($oldvalue));
            list($oldvalue) = $db->FetchRow($result);
            $result = $db->Query("SELECT category_name FROM {list_category} WHERE category_id = ?", array($newvalue));
            list($newvalue) = $db->FetchRow($result);
            break;
        case 'item_status':
            $field = $details_text['status'];
            $oldvalue = $status_list[$oldvalue];
            $newvalue = $status_list[$newvalue];
            break;
        case 'operating_system':
            $field = $details_text['operatingsystem'];
            $result = $db->Query("SELECT os_name FROM {list_os} WHERE os_id = ?", array($oldvalue));
            list($oldvalue) = $db->FetchRow($result);
            $result = $db->Query("SELECT os_name FROM {list_os} WHERE os_id = ?", array($newvalue));
            list($newvalue) = $db->FetchRow($result);
            break;
        case 'task_severity':
            $field = $details_text['severity'];
            $oldvalue = $severity_list[$oldvalue];
            $newvalue = $severity_list[$newvalue];
            break;
        case 'product_version':
            $field = $details_text['reportedversion'];
            $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($oldvalue));
            list($oldvalue) = $db->FetchRow($result);
            $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($newvalue));
            list($newvalue) = $db->FetchRow($result);
            break;
        case 'closedby_version':
            $field = $details_text['dueinversion'];
            if (empty($oldvalue)) {
                $oldvalue = $details_text['undecided'];
            } else {
                $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($oldvalue));
                list($oldvalue) = $db->FetchRow($result);
            };
            if (empty($newvalue)) {
                $newvalue = $details_text['undecided'];
            } else {
                $result = $db->Query("SELECT version_name FROM {list_version} WHERE version_id = ?", array($newvalue));
                list($newvalue) = $db->FetchRow($result);
            };
            break;
        case 'percent_complete':
            $field = $details_text['percentcomplete'];
            $oldvalue .= '%';
            $newvalue .= '%';
            break;
        case 'detailed_desc':
            $field = $details_text['details'];
            $oldvalue = '';
            $newvalue = '';
            break;
        };

        $description = "{$details_text['fieldchanged']}: {$field}";
        if ($oldvalue != '' || $newvalue != '') {
            $description .= " ({$oldvalue} &rarr; {$newvalue})";
        };

    } elseif ($history['event_type'] == 1) {      //Task opened
        $description =  $details_text['taskopened'];

    } elseif ($history['event_type'] == 2) {      //Task closed
        $description = $details_text['taskclosed'];
        $result = $db->Query("SELECT resolution_name FROM {list_resolution} WHERE resolution_id = ?", array($newvalue));
        $res_name = $db->FetchRow($result);
        $description .= " ({$res_name['resolution_name']})";

    } elseif ($history['event_type'] == 3) {      //Task edited
        $description = $details_text['taskedited'];

    } elseif ($history['event_type'] == 4) {      //Comment added
        $description = "<a href=\"?do=details&amp;id={$history['task_id']}&amp;area=comments#{$newvalue}\">{$details_text['commentadded']}</a>";

    } elseif ($history['event_type'] == 5) {      //Comment edited
        $commentid = $history['field_changed'];
        $description = "<a href=\"?do=details&amp;id={$history['task_id']}&amp;area=comments#{$commentid}\">{$details_text['commentedited']}</a>";
        $comment = $db->Query("SELECT user_id, date_added FROM {comments} WHERE comment_id = ?", array($commentid));
        if ($db->CountRows($comment) != 0) {
            $comment = $db->FetchRow($comment);
            $description .= " ({$details_text['commentby']} " . $fs->LinkedUsername($comment['user_id']) . " - " . $fs->formatDate($comment['date_added'], true) . ")";
        };

    } elseif ($history['event_type'] == 6) {      //Comment deleted
        $description = $details_text['commentdeleted'];
        if ($newvalue != '' && $oldvalue != '') {
            $description .= " ({$details_text['commentby']} " . $fs->LinkedUsername($newvalue) . " - " . $fs->formatDate($oldvalue, true) . ")";
        };

    } elseif ($history['event_type'] == 7) {      //Attachment added
        $description = $details_text['attachmentadded'];
        $attachment = $db->Query("SELECT orig_name, file_desc FROM {attachments} WHERE attachment_id = ?", array($newvalue));
        if ($db->CountRows($attachment) != 0) {
            $attachment = $db->FetchRow($attachment);
            $description .= ": <a href=\"?getfile={$newvalue}\">{$attachment['orig_name']}</a>";
            if ($attachment['file_desc'] != '') {
                $description .= " ({$attachment['file_desc']})";
            };
        };

    } elseif ($history['event_type'] == 8) {      //Attachment deleted
        $description = "{$details_text['attachmentdeleted']}: {$newvalue}";

    } elseif ($history['event_type'] == 9) {      //Notification added
        $description = "{$details_text['notificationadded']}: " . $fs->LinkedUsername($newvalue);

    } elseif ($history['event_type'] == 10) {      //Notification deleted
        $description = "{$details_text['notificationdeleted']}: " . $fs->LinkedUsername($newvalue);

    } elseif ($history['event_type'] == 11) {      //Related task added
        $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
        list($related) = $db->FetchRow($result);
        $description = "{$details_text['relatedadded']}: {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

    } elseif ($history['event_type'] == 12) {      //Related task deleted
        $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
        list($related) = $db->FetchRow($result);
        $description = "{$details_text['relateddeleted']}: {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

    } elseif ($history['event_type'] == 13) {      //Task reopened
        $description = $details_text['taskreopened'];

    } elseif ($history['event_type'] == 14) {      //Task assigned
        if ($history['old_value'] == '0') {
            $description = "{$details_text['taskassigned']} " . $fs->LinkedUsername($newvalue);
        } elseif ($history['new_value'] == '0') {
            $description = $details_text['assignmentremoved'];
        } else {
            $description = "{$details_text['taskreassigned']} " . $fs->LinkedUsername($newvalue);
        };
    } elseif ($history['event_type'] == 15) {      //Task added to related list of another task
        $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
        list($related) = $db->FetchRow($result);
        $related = htmlspecialchars($related);
        $description = "{$details_text['addedasrelated']} {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

    } elseif ($history['event_type'] == 16) {      //Task deleted from related list of another task
        $result = $db->Query("SELECT item_summary FROM {tasks} WHERE task_id = ?", array($newvalue));
        list($related) = $db->FetchRow($result);
        $related = htmlspecialchars($related);
        $description = "{$details_text['deletedasrelated']} {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

    } elseif ($history['event_type'] == 17) {      //Reminder added
        $description = "{$details_text['reminderadded']}: " . $fs->LinkedUsername($newvalue);

    } elseif ($history['event_type'] == 18) {      //Reminder deleted
        $description = "{$details_text['reminderdeleted']}: " . $fs->LinkedUsername($newvalue);
    };

   return $description;
}
?>

<ul id="submenu">
  <li><a href="#events"><?php echo $reports_text['events'];?></a></li>
</ul>

<?php
$report = Req::val('report', 'events');
if ($report && function_exists($report.'_report')) {
    call_user_func($report.'_report');
} else {
    events_report();
}
?>
