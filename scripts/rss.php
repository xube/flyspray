<?php
// Include the header to set up database access etc
include_once('../header.php');

// If the user gives us a limit, use it.  default is five results
if (isset($_REQUEST['num']) && is_numeric($_REQUEST['num']))
{
   $limit = $_REQUEST['num'];
} else
{
   $limit = '10';
}

// If the user requested tasks from a specific project, select it. default is global default project
if (isset($_REQUEST['proj']) && is_numeric($_REQUEST['proj']) && !empty($_REQUEST['proj']))
{
   $proj = $_REQUEST['proj'];
} else
{
   $proj = $flyspray_prefs['default_project'];
}

switch ($_REQUEST['type'])
{
   case 'new': $orderby = 'date_opened';
               $title = 'Recently opened tasks';
   break;
   case 'clo': $orderby = 'date_closed';
               $title = 'Recently closed tasks';
   break;
   case 'sev': $orderby = 'task_severity';
               $title = 'Most severe tasks';
   break;
   case 'pri': $orderby = 'task_priority';
               $title = 'Priority tasks';
   break;
   default: $orderby = 'date_opened';
            $title = 'Recently opened tasks';
   break;
}

$project_prefs = $fs->getProjectPrefs($proj);

// Set up the basic XML head
header ("Content-type: text/xml");
echo '<?xml version="1.0"?>' . "\n";
echo '<rss version="2.0">';
//echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/">' . "\n";
//echo '<channel rdf:about="http://www.zend.com/news.rss">' . "\n";
echo '<channel>';
echo '<title>Flyspray</title>' . "\n";
echo '<description>Flyspray:: ' . $project_prefs['project_title'] . ': ' . $title . '</description>' . "\n";
echo '<link>http://flyspray.rocks.cc/</link>' . "\n";
//echo '<image rdf:resource="" />' . "\n";

// Query the database
$task_details = $db->Query("SELECT task_id, item_summary, detailed_desc
                            FROM {$dbprefix}tasks t
                            LEFT JOIN {$dbprefix}projects p ON t.attached_to_project = p.project_id
                            WHERE t.is_closed <> '1'
                            AND p.project_id = ?
                            AND p.project_is_active = '1'
                            AND t.mark_private <> '1'
                            ORDER BY $orderby DESC",
                            array($proj), $limit);


// Now, let's loop the results
while ($row = $db->FetchArray($task_details))
{
   $item_summary = htmlspecialchars($row['item_summary']);
   $detailed_desc = htmlspecialchars($row['detailed_desc']);

   if (!get_magic_quotes_gpc())
   {
      $item_summary = str_replace("\\", "&#92;", $item_summary);
      $detailed_desc = str_replace("\\", "&#92;", $detailed_desc);
   }

   $item_summary = stripslashes($item_summary);
   $detailed_desc = stripslashes($detailed_desc);

   echo '<item>' . "\n";
   echo '<title>' . $item_summary . '</title>' . "\n";
   echo '<description>' . $fs->FormatText($detailed_desc) . '</description>' . "\n";
   echo '<link>' . $fs->CreateURL('details', $row['task_id']) . '</link>' . "\n";
   echo '</item>';
}

echo '</channel>' . "\n";
//echo '</rdf:RDF>' . "\n";
echo '</rss>';