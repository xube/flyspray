<?php
// This script generates all different types of reports.
// The majority of work was done by Jonathan Oxer - http://www.ivt.com.au/
//  Thanks, Jon!

get_language_pack($lang, 'reports');
get_language_pack($lang, 'details');

function summary_report()
{
	echo "<div class=\"tabentries\">";
	echo "<p><em>Summary Report</em></p>";
	echo "<div class=\"tabentry\">";
	echo "aoeu";
	echo "</div>";
	echo "</div>";
}

/***
 * changelog_report
 * Display a list of closed tasks between specified dates. This can be useful as a starting
 * point for writing a version changelog.
 */
function changelog_report()
{
	global $fs;
	global $flyspray_prefs;
	global $reports_text;

	echo "<div class=\"tabentries\">";
	echo "<p><em>$reports_text[changeloggen]</em></p>";
	echo "<div class=\"tabentry\">";
	if(isset($_REQUEST['startdate']))
	{
		$startdate = $_REQUEST['startdate'];
	} else {
		$startdate = date("d-M-Y", strtotime("-1 month"));
	}

	if(isset($_REQUEST['enddate']))
	{
		$enddate   = $_REQUEST['enddate'];
	} else {
		$enddate = date("d-M-Y");
	}
	
	if ($_REQUEST['sort']) {
	  $sort = $_REQUEST['sort'];  
	} else {
	  $sort = 'desc';
	};
	?>

	<form name="changelog_form" action="?do=reports&report=changelog" method="POST">
	
	<?php echo $reports_text['listfrom']?> 
	
        <input id="startdate" type="text" name="startdate" size="10" value="<?=$startdate?>">
	<button id="triggerstartdate">...</button>
        <script type="text/javascript">
          Calendar.setup(
            {
              inputField  : "startdate",         // ID of the input field
              ifFormat    : "%d-%b-%Y",    // the date format
              button      : "triggerstartdate"       // ID of the button
            }
          );
        </script>
	
	<?php echo $reports_text['to']?> <input id="enddate" type="text" name="enddate" size="10" value="<?php echo $enddate?>">
        <button id="triggerenddate">...</button>
        <script type="text/javascript">
          Calendar.setup(
            {
              inputField  : "enddate",         // ID of the input field
              ifFormat    : "%d-%b-%Y",    // the date format
              button      : "triggerenddate"       // ID of the button
            }
          );
        </script>

	
	<select name="sort">
		<option value="asc" <?php if($sort == 'asc') { echo "SELECTED";};?>><?php echo $reports_text['oldestfirst'];?></option>
		<option value="desc" <?php if($sort == 'desc') { echo "SELECTED";};?>><?php echo $reports_text['recentfirst'];?></option>
	</select>
	
	<input type="submit" class="mainbutton" name="submit" value="<?=$reports_text['show']?>">
	
	</form>
	
	<?php
	$ustartdate = strtotime("$startdate");
	$uenddate   = strtotime("$enddate + 1 day");
	
	$get_changes = $fs->dbQuery("SELECT t.*, u.*, r.*
		FROM flyspray_tasks t
		LEFT JOIN flyspray_users u on t.closed_by = u.user_id
		LEFT JOIN flyspray_list_resolution r on t.resolution_reason = r.resolution_id
		WHERE t.is_closed = 1
		AND t.attached_to_project = ?
		AND t.date_closed >= ?
		AND t.date_closed <= ?
		ORDER BY t.date_closed $sort", array($_COOKIE['flyspray_project'],$ustartdate,$uenddate));

	echo "<table border=\"1\" cellpadding=\"2\" cellspacing=\"0\">";
	while ($row = $fs->dbFetchArray($get_changes))
	{
		$task_id         = $row['task_id'];
		$task_resolution = $row['resolution_name'];
		$item_summary    = $row['item_summary'];
		$closure_comment = $row['closure_comment'];
		$real_name       = $row['real_name'];
		$event_date      = $fs->formatDate($row['date_closed'], false);
		if (!get_magic_quotes_gpc()) {
			$item_summary = str_replace("\\", "&#92;", $item_summary);
			$closure_comment = str_replace("\\", "&#92;", $closure_comment);
		}
		
		$item_summary = stripslashes($item_summary);
		$closure_comment = stripslashes($closure_comment);
		
		if($closure_comment)
		{
			#echo "<tr><td>$item_summary</td><td>$closure_comment</td> [$real_name]<br>";
			#echo "<tr><td>$item_summary</td><td>$closure_comment</td><td>$real_name</td></tr>";
			echo "<tr><td>$event_date: <a href=\"?do=details&amp;id=$task_id\">$item_summary</a>. <b>$task_resolution :</b> $closure_comment</td><td>$real_name</td></tr>";
		}
		
	}
	echo "</table>";
	echo "</div>";
	echo "</div>";
};

/***
 * severity_report
 * Display a list of item severities with a count of open items in each severity
 */
function severity_report()
{
	global $fs;
	global $severity_list;
	global $reports_text;

	$severity_colours = array('','ffe9b4','efca80','edb98a','ffb2ac','f3a29b');
	
	$get_severity_count = $fs->dbQuery("SELECT task_type,task_severity,COUNT(*) AS severity_count 
		FROM flyspray_tasks WHERE attached_to_project = ? 
		AND !is_closed 
		GROUP BY task_severity 
		ORDER BY task_severity DESC", array($_COOKIE['flyspray_project']));
	$count = $fs->dbCountRows($get_severity_count);
	
	echo "<div class=\"tabentries\">\n";
	echo "<p><em>$reports_text[severityrep]</em></p>\n";
	echo "<div class=\"tabentry\">\n";
	//echo "aoeu: $count";
	$counttotal = 0;
	
	$colcount = 9;
	while ($row = $fs->dbFetchArray($get_severity_count))
	{
		$sev_severity = $row['task_severity'];
		$sev_title = $severity_list[$sev_severity];
		$sev_count = $row['severity_count'];
		$sevarray[$sev_severity] = $sev_count;
		$counttotal += $sev_count;

		$sev_color = $severity_colours[$sev_severity];

		$dataelements[] .= $sev_count.'_'.$sev_title.'_'.$sev_color;
		$colcount--;
		$colcount--;
	}

	$data = implode("|",$dataelements);
	
	#echo "d: $data<br>\n";
	$data = urlencode($data);
	#echo "d: $data<br>\n";

	echo "$reports_text[totalopen]: $counttotal<br>\n";
	foreach ($sevarray as $severity=>$count)
	{
	#	echo "$severity: $count<br>\n";
	}
	
	$title = urlencode("Tasks by severity");
	
	#echo "<img src=\"scripts/reports-graph-pie.php?title=$title&data=$sevarray\" />";
	echo "<img src=\"scripts/reports-graph-pie.php?title=$title&data=$data\" />\n";
	
	echo "</div>\n";
	echo "</div>\n";
}


/***
 * age_report
 * Display report of bug counts by age
 */
function age_report()
{
	global $fs;
	global $flyspray_prefs;
	global $reports_text;
        
	echo "<div class=\"tabentries\">\n";
	echo "<p><em>Age Report</em></p>\n";
	echo "<div class=\"tabentry\">\n";
	echo "<p>This age report is a work in progress. At present it only shows a list of ages in days, with a count of open tasks in each age. Ultimately this should show a bar graph with ages in weeks.\n<p>\n";

	$today = time();
	$bracket_count = 1;
	
	$age_list = $fs->dbQuery("SELECT task_severity, date_opened
	        FROM flyspray_tasks
	        WHERE attached_to_project = ? AND is_closed = 0
	        ORDER BY date_opened", array($_COOKIE['flyspray_project']));
        while ($row = $fs->dbFetchArray($age_list)) {
		$date_opened = $row['date_opened'];
		$task_age = $today - $date_opened;
		$task_age = round($task_age / 86400);
		if($task_age == $last_age)
		{
			$bracket_count++;
		}
		else
		{
			$bracket_count = 1;
		}
        	#echo "$bracket_count : $today : $date_opened : $task_age<br>";
		$agecount_array["$task_age"] = $bracket_count;
		$last_age = $task_age;
	}

	#echo "<pre>\n";
	#print_r($agecount_array);
	#echo "</pre>\n";
	
	foreach($agecount_array as $age => $count)
	{
		echo "$age days:$count<br />\n";
	}

	echo "</div>\n";
	echo "</div>\n";
}


/***
 * events_report
 * Allow user to select types of events to view, and display a list of those events
 */
function events_report()
{
	global $fs;
	global $flyspray_prefs;
	global $reports_text;
	global $details_text;

	echo "<div class=\"tabentries\">";
	echo "<p><em>{$reports_text['eventsrep']}</em></p>";
	echo "<div class=\"tabentry\">";

switch ($_REQUEST['sort']) {
    case "asc":
        $sort = "ASC";
        break;
    case "desc":
        $sort = "DESC";
        break;
    default:
        $sort = "DESC";
    };

    switch ($_REQUEST['order']) {
    case "id":
        $orderby = "h.task_id {$sort}, h.event_date {$sort}";
        break;
    case "type":
        $orderby = "h.event_type {$sort}, h.event_date {$sort}";
        break;
    case "date":
        $orderby = "h.event_date {$sort}, h.event_type {$sort}";
        break;
    case "user":
        $orderby = "u.real_name {$sort}, h.event_date {$sort}";
        break;
    default:
        $orderby = "h.event_date {$sort}, h.event_type {$sort}";
    };

    $wheredate = '';
    switch ($_REQUEST['date']) {
    case 'within':
        $date = 'within';
        if ($_REQUEST['within'] != 'all') {
            $wheredate = 24 * 60 * 60;
            if ($_REQUEST['within'] == 'week') {
                $wheredate *= 7;
            } elseif ($_REQUEST['within'] == 'month') {
                $wheredate *= 30;
            } elseif ($_REQUEST['within'] == 'year') {
                $wheredate *= 365;
            };
            $wheredate = date('U') - $wheredate;
            $wheredate = "AND h.event_date > {$wheredate}";
        };
        break;
    case 'from':
        $date = 'from';
        $fromdate = $_REQUEST['fromdate'];
        $todate = $_REQUEST['todate'];

        $ufromdate = strtotime($fromdate);
        $utodate = strtotime($todate);

        $wheredate = "AND h.event_date > {$ufromdate} AND h.event_date < {$utodate}";
        break;
    case 'duein':
        $date = 'duein';
        if (is_numeric($_REQUEST['duein']) && $_REQUEST['duein'] != '') {
            $wheredate = "AND t.closedby_version = {$_REQUEST['duein']}";
        };
        break;
    };


    $type = array();
    $get_array = array();
    if (isset($_REQUEST['open'])) {          $search = 1; array_push($type, 1, 13);          $get_array[] = 'open';}
    if (isset($_REQUEST['close'])) {         $search = 1; array_push($type, 2);              $get_array[] = 'close';}
    if (isset($_REQUEST['edit'])) {          $search = 1; array_push($type, 0, 3);           $get_array[] = 'edit';}
    if (isset($_REQUEST['assign'])) {        $search = 1; array_push($type, 14);             $get_array[] = 'assign';}
    if (isset($_REQUEST['comments'])) {      $search = 1; array_push($type, 4, 5, 6);        $get_array[] = 'comments';}
    if (isset($_REQUEST['attachments'])) {   $search = 1; array_push($type, 7, 8);           $get_array[] = 'attachments';}
    if (isset($_REQUEST['related'])) {       $search = 1; array_push($type, 11, 12, 15, 16); $get_array[] = 'related';}
    if (isset($_REQUEST['notifications'])) { $search = 1; array_push($type, 9, 10);          $get_array[] = 'notifications';}
    if (isset($_REQUEST['reminders'])) {     $search = 1; array_push($type, 17, 18);         $get_array[] = 'reminders';}

    $type = implode($type, ', ');
    if ($type != '') {
        $type = "AND h.event_type IN ({$type})";
    };
    foreach ($get_array as $value) {
        $get = "&amp;{$value}";
    }
?>

    <div class="admin">
        <form action="?do=reports&report=events" method="post" name="events_form">
        <!-- <input type="hidden" name="do" value="reports">
        <input type="hidden" name="report" value="events"> -->
        <table>
        <tr>
            <td><b><?php echo $reports_text['events'];?></b><br>
                <table>
                <tr><td><?php echo $reports_text['tasks'];?></td>
                    <td><label class="inline"><input type="checkbox" name="open" <?php if (isset($_REQUEST['open'])) echo 'checked';?>>
                    <?php echo $reports_text['opened'];?></label></td>
                    <td><label class="inline"><input type="checkbox" name="close" <?php if (isset($_REQUEST['close'])) echo 'checked';?>>
                    <?php echo $reports_text['closed'];?></label></td>
                    <td><label class="inline"><input type="checkbox" name="edit" <?php if (isset($_REQUEST['edit'])) echo 'checked';?>>
                    <?php echo $reports_text['edited'];?></label></td>
                </td></tr>
                <tr><td></td>
                    <td><label class="inline"><input type="checkbox" name="assign" <?php if (isset($_REQUEST['assign'])) echo 'checked';?>>
                    <?php echo $reports_text['assigned'];?></label></td>
                    <td><label class="inline"><input type="checkbox" name="comments" <?php if (isset($_REQUEST['comments'])) echo 'checked';?>>
                    <?php echo $reports_text['comments'];?></label></td>
                    <td><label class="inline"><input type="checkbox" name="attachments" <?php if (isset($_REQUEST['attachments'])) echo 'checked';?>>
                    <?php echo $reports_text['attachments'];?></label></td>
                </td></tr>
                <tr><td></td>
                    <td><label class="inline"><input type="checkbox" name="related" <?php if (isset($_REQUEST['related'])) echo 'checked';?>>
                    <?php echo $reports_text['relatedtasks'];?></label></td>
                    <td><label class="inline"><input type="checkbox" name="notifications" <?php if (isset($_REQUEST['notifications'])) echo 'checked';?>>
                    <?php echo $reports_text['notifications'];?></label></td>
                    <td><label class="inline"><input type="checkbox" name="reminders" <?php if (isset($_REQUEST['reminders'])) echo 'checked';?>>
                    <?php echo $reports_text['reminders'];?></label></td>
                </td></tr>
                </table>
            </td>
            <td><b>Date:</b><br>
                <table border="0">
                <?php if(!isset($_REQUEST['within'])) { $_REQUEST['within'] = 'year'; } ?>
                <tr>
                    <td><label class="inline"><input type="radio" name="date" value="within" <?php if($date == 'within') echo 'checked';?>>
		    <?php echo $reports_text['within'];?></label></td>
                        <td colspan="6"><select name="within">
                            <option value="day" <?php if ($_REQUEST['within'] == 'day') echo 'selected';?>><?php echo $reports_text['pastday'];?></option>
                            <option value="week" <?php if ($_REQUEST['within'] == 'week') echo 'selected';?>><?php echo $reports_text['pastweek'];?></option>
                            <option value="month" <?php if ($_REQUEST['within'] == 'month') echo 'selected';?>><?php echo $reports_text['pastmonth'];?></option>
                            <option value="year" <?php if ($_REQUEST['within'] == 'year') echo 'selected';?>><?php echo $reports_text['pastyear'];?></option>
                            <option value="all" <?php if ($_REQUEST['within'] == 'all') echo 'selected';?>><?php echo $reports_text['nolimit'];?></option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td><label class="inline"><input type="radio" name="date" value="from" <?php if($date == 'from') echo 'checked';?>>
                    <?php echo $reports_text['from'];?></label></td>
                    <td>
                    <input id="fromdate" type="text" name="fromdate" size="10" value="<?php echo $fromdate?>">
                    <button id="triggerfromdate">...</button>
                    <script type="text/javascript">
                     Calendar.setup(
                       {
                         inputField  : "fromdate",         // ID of the input field
                         ifFormat    : "%d-%b-%Y",    // the date format
                         button      : "triggerfromdate"       // ID of the button
                       }
                     );
                   </script>
		   &mdash;
		   <input id="todate" type="text" name="todate" size="10" value="<?php echo $todate?>">
	           <button id="triggertodate">...</button>
                   <script type="text/javascript">
                     Calendar.setup(
                       {
                         inputField  : "todate",         // ID of the input field
                         ifFormat    : "%d-%b-%Y",    // the date format
                         button      : "triggertodate"       // ID of the button
                       }
                     );
                  </script>
                  </td>
                <tr>
                    <td><label class="inline"><input type="radio" name="date" value="duein" <?php if($date == 'duein') echo 'checked';?>>
                    <?php echo $reports_text['duein'];?></label></td>
                    <td colspan="6">
                        <select name="duein">
                            <?php
                            $ver_list = $fs->dbQuery("SELECT version_id, version_name
                                                        FROM flyspray_list_version
                                                        WHERE project_id=? AND show_in_list=?
                                                        ORDER BY list_position", array($_COOKIE['flyspray_project'], '1'));
                            while ($row = $fs->dbFetchArray($ver_list)) {
                                if ($_REQUEST['duein'] == $row['version_id']) {
                                echo "<option value=\"{$row['version_id']}\" selected=\"selected\">{$row['version_name']}</option>";
                                } else {
                                echo "<option value=\"{$row['version_id']}\">{$row['version_name']}</option>";
                                };
                            };
                            ?>
                        </select>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        <tr><td><input type="submit" class="mainbutton" name="submit" value="<?=$reports_text['show']?>"></td></tr>
        </table>
        </form>
    </div>

     <?php
        $query_history = $fs->dbQuery("SELECT h.*, u.user_name, u.real_name, t.item_summary, t.task_severity
                                         FROM flyspray_history h
                                         LEFT JOIN flyspray_users u ON h.user_id = u.user_id
                                         LEFT JOIN flyspray_tasks t ON h.task_id = t.task_id
                                         WHERE t.attached_to_project = ? {$type} {$wheredate}
                                         ORDER BY {$orderby}", array($_COOKIE['flyspray_project']));
	
        if ($fs->dbCountRows($query_history) == 0)
	{
            echo "$details_text[nohistory]\n";
        }
	elseif($search)
	{
	   ?>
           <table id="tasklist">
           <tr>
              <th><a href="?do=history&amp;order=date&amp;sort=<?php echo ($_REQUEST['order'] == 'date' && $_REQUEST['sort'] == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $details_text['eventdate'];?></a></th>
              <th><a href="?do=history&amp;order=user&amp;sort=<?php echo ($_REQUEST['order'] == 'user' && $_REQUEST['sort'] == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $details_text['user'];?></a></th>
              <th class="taskid"><a href="?do=history&amp;order=id&amp;sort=<?php echo ($_REQUEST['order'] == 'id' && $_REQUEST['sort'] == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $index_text['id'];?></a></th>
              <th><?php echo $details_text['summary'];?></th>
              <th><a href="?do=history&amp;order=type&amp;sort=<?php echo ($_REQUEST['order'] == 'type' && $_REQUEST['sort'] == 'desc' ? 'asc' : 'desc') . $get;?>"><?php echo $details_text['event'];?></a></th>
           </tr>
           <?php

            while ($history = $fs->dbFetchRow($query_history))
	    {
                ?>
                <tr class="severity<?php echo $history['task_severity'];?>">
                <td><?php echo $fs->formatDate($history['event_date'], true);?></td>
                <td><?php if ($history['user_id'] == 0) {
                            echo $details_text['anonymous'];
                        } else {
                            echo "<a href=\"?do=admin&amp;area=users&amp;id={$history['user_id']}\"> {$history['real_name']} ({$history['user_name']})</a>";
                        }?></td>
                <?php echo "<td><a href=\"?do=details&id={$history['task_id']}\">{$history['task_id']}</a></td>";?>
                <?php echo "<td><a href=\"?do=details&id={$history['task_id']}&area=history#tabs\">" . htmlspecialchars(stripslashes($history['item_summary'])) . "</a></td>";?>
                <td><?php echo EventDescription($history);?></td>
                </tr>
                <?php
            }
        }

    	echo "</table>\n";
    
	echo "</div>\n";
	echo "</div>\n";
}


/***
 * eventdescription
 * Display details of a particular event. Called by events_report when displaying a
 * list of results.
 */

function EventDescription($history)
{   
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
                $oldvalue = htmlspecialchars(stripslashes($oldvalue));
                $newvalue = htmlspecialchars(stripslashes($newvalue));
                break;
            case 'attached_to_project':
                $field = $details_text['attachedtoproject'];
                list($oldprojecttitle) = $fs->dbFetchRow($fs->dbQuery("SELECT project_title FROM flyspray_projects WHERE project_id = ?", array($oldvalue)));
                list($newprojecttitle) = $fs->dbFetchRow($fs->dbQuery("SELECT project_title FROM flyspray_projects WHERE project_id = ?", array($newvalue)));
                $oldvalue = "<a href=\"?project={$oldvalue}\">{$oldprojecttitle}</a>";
                $newvalue = "<a href=\"?project={$newvalue}\">{$newprojecttitle}</a>";
                break;
            case 'task_type':
                $field = $details_text['tasktype'];
                list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT tasktype_name FROM flyspray_list_tasktype WHERE tasktype_id = ?", array($oldvalue)));
                list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT tasktype_name FROM flyspray_list_tasktype WHERE tasktype_id = ?", array($newvalue)));
                break;
            case 'product_category':
                $field = $details_text['category'];
                list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT category_name FROM flyspray_list_category WHERE category_id = ?", array($oldvalue)));
                list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT category_name FROM flyspray_list_category WHERE category_id = ?", array($newvalue)));
                break;
            case 'item_status':
                $field = $details_text['status'];
                $oldvalue = $status_list[$oldvalue];
                $newvalue = $status_list[$newvalue];
                break;
            case 'operating_system':
                $field = $details_text['operatingsystem'];
                list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT os_name FROM flyspray_list_os WHERE os_id = ?", array($oldvalue)));
                list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT os_name FROM flyspray_list_os WHERE os_id = ?", array($newvalue)));
                break;
            case 'task_severity':
                $field = $details_text['severity'];
                $oldvalue = $severity_list[$oldvalue];
                $newvalue = $severity_list[$newvalue];
                break;
            case 'product_version':
                $field = $details_text['reportedversion'];
                list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($oldvalue)));
                list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($newvalue)));
                break;
            case 'closedby_version':
                $field = $details_text['dueinversion'];
                if ($oldvalue == '0') {
                    $oldvalue = $details_text['undecided'];
                } else {
                    list($oldvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($oldvalue)));
                };
                if ($newvalue == '0') {
                    $newvalue = $details_text['undecided'];
                } else {
                    list($newvalue) = $fs->dbFetchRow($fs->dbQuery("SELECT version_name FROM flyspray_list_version WHERE version_id = ?", array($newvalue)));
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
            $res_name = $fs->dbFetchRow($fs->dbQuery("SELECT resolution_name FROM flyspray_list_resolution WHERE resolution_id = ?", array($newvalue)));
            $description .= " ({$res_name['resolution_name']})";

        } elseif ($history['event_type'] == 3) {      //Task edited
            $description = $details_text['taskedited'];

        } elseif ($history['event_type'] == 4) {      //Comment added
            $description = "<a href=\"?do=details&amp;id={$history['task_id']}&amp;area=comments#{$newvalue}\">{$details_text['commentadded']}</a>";

        } elseif ($history['event_type'] == 5) {      //Comment edited
            $description = "<a href=\"?do=details&amp;id={$history['task_id']}&amp;area=comments#{$newvalue}\">{$details_text['commentedited']}</a>";
            $comment = $fs->dbQuery("SELECT user_id, date_added FROM flyspray_comments WHERE comment_id = ?", array($newvalue));
            if ($fs->dbCountRows($comment) != 0) {
                $comment = $fs->dbFetchRow($comment);
                $description .= " ({$details_text['commentby']} " . $fs->LinkedUsername($comment['user_id']) . " - " . $fs->formatDate($comment['date_added'], true) . ")";
            };

        } elseif ($history['event_type'] == 6) {      //Comment deleted
            $description = $details_text['commentdeleted'];
            if ($newvalue != '' && $oldvalue != '') {
                $description .= " ({$details_text['commentby']} " . $fs->LinkedUsername($newvalue) . " - " . $fs->formatDate($oldvalue, true) . ")";    
            };

        } elseif ($history['event_type'] == 7) {      //Attachment added
            $description = $details_text['attachmentadded'];
            $attachment = $fs->dbQuery("SELECT orig_name, file_desc FROM flyspray_attachments WHERE attachment_id = ?", array($newvalue));
            if ($fs->dbCountRows($attachment) != 0) {
                $attachment = $fs->dbFetchRow($attachment);
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
            list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
            $description = "{$details_text['relatedadded']}: {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";      

        } elseif ($history['event_type'] == 12) {      //Related task deleted
            list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
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
            list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
            $related = htmlspecialchars(stripslashes($related));
            $description = "{$details_text['addedasrelated']} {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

        } elseif ($history['event_type'] == 16) {      //Task deleted from related list of another task
            list($related) = $fs->dbFetchRow($fs->dbQuery("SELECT item_summary FROM flyspray_tasks WHERE task_id = ?", array($newvalue)));
            $related = htmlspecialchars(stripslashes($related));
            $description = "{$details_text['deletedasrelated']} {$details_text['task']} #{$newvalue} &mdash; <a href=\"?do=details&amp;id={$newvalue}\">{$related}</a>";

        } elseif ($history['event_type'] == 17) {      //Reminder added
            $description = "{$details_text['reminderadded']}: " . $fs->LinkedUsername($newvalue);

        } elseif ($history['event_type'] == 18) {      //Reminder deleted
            $description = "{$details_text['reminderdeleted']}: " . $fs->LinkedUsername($newvalue);
        };
        
       return $description;
}






####################################################################################
/* Main page logic to determine report to display */
if(!isset($_REQUEST['report']))
{
	$report = "changelog";
} else {
	$report = $_REQUEST['report'];
}
?>

<p id="tabs">
    <?php
    //if ($report == 'summary') {
    //  echo "<a class=\"tabactive\" href=\"?do=reports&report=summary\">Summary</a><small> | </small>";
    //} else {
    //  echo "<a class=\"tabnotactive\" href=\"?do=reports&report=summary\">Summary</a><small> | </small>";
    //};

    if ($report == 'changelog') {
      echo "<a class=\"tabactive\" href=\"?do=reports&report=changelog\">{$reports_text['changelog']}</a><small> | </small>";
    } else {
      echo "<a class=\"tabnotactive\" href=\"?do=reports&report=changelog\">{$reports_text['changelog']}</a><small> | </small>";
    };
    
    if ($report == 'events') {
      echo "<a class=\"tabactive\" href=\"?do=reports&report=events\">{$reports_text['events']}</a><small> | </small>";
    } else {
      echo "<a class=\"tabnotactive\" href=\"?do=reports&report=events\">{$reports_text['events']}</a><small> | </small>";
    };

    //if ($report == 'severity') {
    //  echo "<a class=\"tabactive\" href=\"?do=reports&report=severity\">Severity</a><small> | </small>";
    //} else {
    //  echo "<a class=\"tabnotactive\" href=\"?do=reports&report=severity\">Severity</a><small> | </small>";
    //};

    //if ($report == 'age') {
    //  echo "<a class=\"tabactive\" href=\"?do=reports&report=age\">Age</a><small> | </small>";
    //} else {
    //  echo "<a class=\"tabnotactive\" href=\"?do=reports&report=age\">Age</a><small> | </small>";
    //};

switch ($report)
{
	case "changelog":
		changelog_report();
		break;
	case "events":
		events_report();
		break;
	case "severity":
		severity_report();
		break;
	case "age":
		age_report();
		break;
	case "summary":
		summary_report();
		break;
	default:
		changelog_report();
		break;
}
?>
