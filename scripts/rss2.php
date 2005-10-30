<?php
// We can't include this script as part of index.php?do= etc,
// as that would introduce html code into it.  HTML != Valid XML
// So, include the headerfile to set up database access etc
require_once(dirname(dirname(__FILE__)).'/header.php');

$limit = intval(Req::val('num', 10));
$proj  = intval(Req::val('proj', $flyspray_prefs['default_project']));

$project_prefs = $fs->GetProjectPrefs($projectid);

$args = array('0',                     // We're not passing a user id
               $project_id,
               Req::val('tasks', '0'),
               '0',                    // No search string, thanks
               Req::val('type', '0'),
               Req::val('sev', '0'),
               Req::val('dev', '0'),
               Req::val('cat', '0'),
               Req::val('status', '0'),
               Req::val('due', '0'),
               Req::val('date', '0'),
               $limit
         );


// Pass the search arguments to the backend function
$tasklist = $be->GenerateTaskList($args);

//Set up the basic XML head
header ("Content-type: text/xml");
echo '<?xml version="1.0"?>'."\n";
?>
<rss version="2.0">
  <channel>
    <title>Flyspray</title>
    <description>Flyspray:: <?php echo $project_prefs['project_title'] . ': ' .  $title ?></description>
    <link>http://flyspray.rocks.cc/</link>
<?php
        foreach ($tasklist AS $key => $val):
            $item_summary = htmlspecialchars($val['item_summary']);
            $detailed_desc = htmlspecialchars($val['detailed_desc']);

            if (get_magic_quotes_gpc()) {
                $item_summary  = stripslashes($item_summary);
                $detailed_desc = stripslashes($detailed_desc);
            }
?>
    <item>
      <title><?php echo $item_summary ?></title>
      <description><?php echo $fs->FormatText($detailed_desc) ?></description>
      <link><?php echo $fs->CreateURL('details', $row['task_id']) ?></link>
    </item>';

<?php   endforeach; ?>
  </channel>
</rss>
