<?php
#get_language_pack($lang, 'main');
require_once("lang/$lang/severity.php");
require_once("lang/$lang/reports.php");

function summary_report()
{
	echo "<div class=\"tabentries\">";
	echo "<p><em>Summary Report</em></p>";
	echo "<div class=\"tabentry\">";
	echo "aoeu";
	echo "</div>";
	echo "</div>";
}

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
		$startdate = date("d")."-".date("M")."-".(date("Y") - 1);
	}

	if(isset($_REQUEST['enddate']))
	{
		$enddate   = $_REQUEST['enddate'];
	} else {
		$enddate = date("d-M-Y");
	}
	?>

	<form name="changelog_form" action="?do=reports&report=changelog" method="POST">
	
	<?php echo $reports_text['listfrom']?> <input type="text" size="12" name="startdate" value="<?php echo $startdate?>">
	
	<a href="javascript: void(0);" onmouseover="if (timeoutId) clearTimeout(timeoutId);window.status='Show Calendar';return true;" onmouseout="if (timeoutDelay) calendarTimeout();window.status='';" onclick="g_Calendar.show(event,'changelog_form.startdate',false); return false;"><img src="themes/<?php echo $flyspray_prefs['theme_style']?>/calendar.png" name="imgCalendar" border="0" alt=""></a>
	
	<?php echo $reports_text['to']?> <input type="text" size="12" name="enddate" value="<?php echo $enddate?>">
	
	<a href="javascript: void(0);" onmouseover="if (timeoutId) clearTimeout(timeoutId);window.status='Show Calendar';return true;" onmouseout="if (timeoutDelay) calendarTimeout();window.status='';" onclick="g_Calendar.show(event,'changelog_form.enddate',false); return false;"><img src="themes/<?php echo $flyspray_prefs['theme_style']?>/calendar.png" name="imgCalendar" border="0" alt=""></a>
	
	<select name="sort">
		<option value="asc" <?php if($_REQUEST['sort'] == 'asc') { echo "SELECTED";};?>><?php echo $reports_text['oldestfirst'];?></option>
		<option value="desc" <?php if($_REQUEST['sort'] == 'desc') { echo "SELECTED";};?>><?php echo $reports_text['recentfirst'];?></option>
	</select>
	
	<input type="submit" class="mainbutton" name="submit" value="<?=$reports_text['show']?>">
	
	</form>
	
	<?php
	$ustartdate = strtotime("$startdate");
	$uenddate   = strtotime("$enddate + 1 day");

	$sort = $_POST['sort'];
	
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
//require_once("jpgraph/jpgraph-1.16/src/jpgraph.php");
//require_once("jpgraph/jpgraph-1.16/src/jpgraph_pie.php");
	global $fs;
	global $severity_list;
	global $reports_text;
	$get_severity_count = $fs->dbQuery("SELECT task_type,task_severity,COUNT(*) AS severity_count FROM flyspray_tasks WHERE attached_to_project = ? AND !is_closed GROUP BY task_severity ORDER BY task_severity DESC", array($_COOKIE['flyspray_project']));
	$count = $fs->dbCountRows($get_severity_count);
	
	echo "<div class=\"tabentries\">";
	echo "<p><em>$reports_text[severityrep]</em></p>";
	echo "<div class=\"tabentry\">";
	//echo "aoeu: $count";
	$counttotal = 0;
	
	while ($row = $fs->dbFetchArray($get_severity_count))
	{
		$sev_severity = $row['task_severity'];
		$sev_severity = $severity_list[$sev_severity];
		$sev_count = $row['severity_count'];
		$sevarray[$sev_severity] = $sev_count;
		$counttotal += $sev_count;
	}
	
	echo "$reports_text[totalopen]: $counttotal<br>";
	foreach ($sevarray as $severity=>$count)
	{
		echo "$severity: $count<br>";
	}
	
	echo "</div>";
	echo "</div>";
}

function age_report()
{
	echo "<div class=\"tabentries\">";
	echo "<p><em>Age Report</em></p>";
	echo "<div class=\"tabentry\">";
	echo "aoeu";
	echo "</div>";
	echo "</div>";
}

?>

<!-- <h1><?php echo $language['reports'];?></h1> -->

<!--
<table border="0">
<tr>
<td>
  <h2>Bug Types</h2>
  <img border=3 src="/scripts/reports-graph-type.php">
</td>
<td>
  <h2>Bug Severities</h2>
  <img border=3 src="/scripts/reports-graph-severity.php">
</td>
</tr>
</table>
-->

<?php
if(!isset($_REQUEST['report']))
{
	$report = "summary";
} else {
	$report = $_REQUEST['report'];
}
?>

<p id="tabs">
    <?php
    if ($report == 'summary') {
      echo "<a class=\"tabactive\" href=\"?do=reports&report=summary\">Summary</a><small> | </small>";
    } else {
      echo "<a class=\"tabnotactive\" href=\"?do=reports&report=summary\">Summary</a><small> | </small>";
    };

    if ($report == 'changelog') {
      echo "<a class=\"tabactive\" href=\"?do=reports&report=changelog\">Changelog</a><small> | </small>";
    } else {
      echo "<a class=\"tabnotactive\" href=\"?do=reports&report=changelog\">Changelog</a><small> | </small>";
    };

    if ($report == 'severity') {
      echo "<a class=\"tabactive\" href=\"?do=reports&report=severity\">Severity</a><small> | </small>";
    } else {
      echo "<a class=\"tabnotactive\" href=\"?do=reports&report=severity\">Severity</a><small> | </small>";
    };

    if ($report == 'age') {
      echo "<a class=\"tabactive\" href=\"?do=reports&report=age\">Age</a><small> | </small>";
    } else {
      echo "<a class=\"tabnotactive\" href=\"?do=reports&report=age\">Age</a><small> | </small>";
    };

switch ($report)
{
	case "changelog":
		changelog_report();
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
		summary_report();
		break;
}
?>