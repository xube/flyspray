<?php
// We can't include this script as part of index.php?do= etc,
// as that would introduce html code into it.  HTML != Valid XML
// So, include the headerfile to set up database access etc
include_once('../header.php');

// Ensure that any variables we don't receive are set properly
$criteria = array('tasks', 'type', 'sev', 'dev', 'cat', 'status', 'due', 'date');
foreach ($criteria AS $key => $val)
{
   if ( !isset($_REQUEST[$val]) OR empty($_REQUEST[$val]) )
      $_REQUEST[$val] = '0';
}

if ( isset($_REQUEST['num']) )
{
   $limit = $_REQUEST['num'];
} else
{
   $limit = '10';
}

// If the user requested tasks from a specific project, select it. default is global default project
if (!empty($_REQUEST['proj']))
{
   $projectid = $_REQUEST['proj'];
} else
{
   $projectid = $flyspray_prefs['default_project'];
}

$project_prefs = $fs->GetProjectPrefs($projectid);

$args = array('0',                     // We're not passing a user id
               $projectid,
               $_REQUEST['tasks'],
               '0',                    // No search string, thanks
               $_REQUEST['type'],
               $_REQUEST['sev'],
               $_REQUEST['dev'],
               $_REQUEST['cat'],
               $_REQUEST['status'],
               $_REQUEST['due'],
               $_REQUEST['date'],
               $limit
             );


// Pass the search arguments to the backend function
$tasklist = $be->GenerateTaskList($args);

// This for debugging
if (!is_array($tasklist))
   die( 'ERROR: ' . $tasklist );

//Set up the basic XML head
header ("Content-type: text/xml");
echo '<rss version="2.0">'. "\n";
echo '<channel>'. "\n";
echo '<title>Flyspray</title>' . "\n";
echo '<description>Flyspray:: ' . $project_prefs['project_title'] . '</description>' . "\n";
echo '<link>http://flyspray.rocks.cc/</link>' . "\n";

// Loop through the task list
foreach ( $tasklist AS $key => $val )
{
/*
   // This is for testing/debug purposes
   if (is_array($val))
   {
      foreach ($val AS $key2 => $val2)
         echo $key2 . ' => ' . $val2 . "<br />\n";
   } else
   {
      echo $val . "<br />\n";
   }
*/
    $item_summary = htmlspecialchars($val['item_summary']);
    $detailed_desc = htmlspecialchars($val['detailed_desc']);

    if (!get_magic_quotes_gpc())
    {
       $item_summary = str_replace("\\", "&#92;", $item_summary);
       $detailed_desc = str_replace("\\", "&#92;", $detailed_desc);
    }

    $item_summary = stripslashes($item_summary);
    $detailed_desc = stripslashes($detailed_desc);

    echo '<item>' . "\n";
    echo '<title>' . $item_summary . '</title>' . "\n";
    echo '<description>' . $detailed_desc . '</description>' . "\n";
    echo '<link>' . $fs->CreateURL('details', $val['task_id']) . '</link>' . "\n";
    echo '</item>';

// End of looping through task list
}

// Finish off the channel and feed
echo '</channel>' . "\n";
echo '</rss>';