<?php 
// Include the header to set up database access etc
include('../header.php');

// Set up the basic XML head
echo '<?xml version="1.0"?>' . "\n";
echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/">' . "\n";
echo '<channel rdf:about="http://www.zend.com/news.rss">' . "\n";
echo '<title>Flyspray Bugs</title>' . "\n";
echo '<link>http://flyspray.rocks.cc/</link>' . "\n";
echo '<description>This is a list of Flyspray tasks</description>' . "\n";
//echo '<image rdf:resource="" />' . "\n";
echo '</channel>' . "\n";

// If the user gives us a limit, use it
if (isset($_REQUEST['num'])) {
  $limit = $_REQUEST['num'];
} else {
  $limit = '5';
};

// Determine which type the user wants
if ($_REQUEST['type'] == 'new') {

  $task_details = $fs->dbQuery("SELECT task_id, item_summary, detailed_desc
                                 FROM flyspray_tasks
                                 WHERE is_closed != '1' ORDER BY date_opened DESC", false, $limit);

} elseif ($_REQUEST['type'] == 'clo') {

  $task_details = $fs->dbQuery("SELECT task_id, item_summary, detailed_desc
                                 FROM flyspray_tasks
                                 WHERE is_closed = '1' ORDER BY date_closed DESC", false, $limit);
  
} elseif ($_REQUEST['type'] == 'sev') {

  $task_details = $fs->dbQuery("SELECT task_id, item_summary, detailed_desc
                                 FROM flyspray_tasks
                                 WHERE is_closed != '1' ORDER BY task_severity DESC", false, $limit);


} elseif ($_REQUEST['type'] == 'pri') {

  $task_details = $fs->dbQuery("SELECT task_id, item_summary, detailed_desc
                                 FROM flyspray_tasks
                                 WHERE is_closed != '1' ORDER BY task_priority DESC", false, $limit);

} elseif ($_REQUEST['type'] == 'due') {

  // This part to be implemented after we add a due-by-date field
  // So don't use it yet, ok?

};

// If the user actually requested a type
if (isset($_REQUEST['type'])) {
  // Now, let's loop the results
  while ($row = $fs->dbFetchArray($task_details)) {
  
    $item_summary = htmlspecialchars($row['item_summary']);
    $detailed_desc = htmlspecialchars($row['detailed_desc']);

    if (!get_magic_quotes_gpc()) {
      $item_summary = str_replace("\\", "&#92;", $item_summary);
      $detailed_desc = str_replace("\\", "&#92;", $detailed_desc);
    };

    $item_summary = stripslashes($item_summary);
    $detailed_desc = stripslashes($detailed_desc);
  
    echo '<item rdf:about="This is an item for a Flyspray RSS feed">' . "\n";
    echo '<title>' . $item_summary . '</title>' . "\n";
    echo '<link>' . $flyspray_prefs['base_url'] . '?do=details&amp;do=details&amp;id=' . $row['task_id'] . '</link>' . "\n";
    echo '<description>' . $detailed_desc . '</description>' . "\n";
    echo '</item>';
  };
};

echo '</rdf:RDF>' . "\n";