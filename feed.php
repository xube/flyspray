<?php
// We can't include this script as part of index.php?do= etc,
// as that would introduce html code into it.  HTML != Valid XML
// So, include the headerfile to set up database access etc
require_once(dirname(__FILE__).'/header.php');
require_once(dirname(__FILE__).'/includes/class.tpl.php');
$page = new FSTpl();

// Set up the basic XML head
header ('Content-type: text/xml; charset=utf-8');

$limit      = (intval(Req::val('num', 10)) == 10) ? 10 : 20;
$feed_type  = Req::val('feed_type', 'rss2');
if($feed_type != 'rss1' && $feed_type != 'rss2')
{
	$feed_type = 'atom';
}
$project    = intval(Req::val('proj', $fs->prefs['default_project']));

switch (Req::val('topic')) {
   case 'clo': $orderby = 'date_closed'; $closed = 0;
               $title   = 'Recently closed tasks';
   break;

   case 'edit':$orderby = 'last_edited_time'; $closed = 1;
               $title   = 'Recently edited tasks';
   break;
      
   default:    $orderby = 'date_opened'; $closed = 1;
               $title   = 'Recently opened tasks';
   break;
}

$task_details = $db->Query("SELECT  t.task_id, t.item_summary, t.detailed_desc, t.date_opened, t.date_closed,
                                    t.last_edited_time, t.opened_by, u.real_name
                              FROM  {tasks} t, {users} u
                         LEFT JOIN  {projects} p ON t.attached_to_project = p.project_id
                             WHERE  t.is_closed <> '$closed' AND p.project_id = ? AND t.opened_by = u.user_id
                                    AND p.project_is_active = '1' AND t.mark_private <> '1'
                          ORDER BY  $orderby DESC", array($project), $limit);

// Get the time when a task has been changed last
$most_recent = 0;
while ($row = $db->FetchArray($task_details))
{
	if($row['date_closed'] > $most_recent || $row['last_edited_time'] > $most_recent)
	{
		$most_recent = max($row['last_edited_time'],$row['date_closed']);
	}
}
$task_details->MoveFirst();

if($fs->prefs['cache_feeds'] != '0')
{
	if($fs->prefs['cache_feeds'] == '1')
	{
		$filename = $feed_type.'-'.$orderby.'-'.$project.'-'.$limit;
		if(file_exists('cache/'.$filename) && $most_recent <= filemtime('cache/'.$filename))
		{
			readfile('cache/'.$feed_type.'-'.$orderby.'-'.$project.'-'.$limit);
			exit;
		}
	}
	else
	{
		$content = $db->Query("SELECT content,last_updated FROM {cache} WHERE  type = ? AND topic = ? AND project = ? AND `limit` = ?",
									array($feed_type,$orderby,$project,$limit));
		$content = $db->FetchArray($content);

		if($content['content'] != '' && $most_recent <= $content['last_updated'])
		{
			echo $content['content'];
			exit;
		}
	}
}

// Feed description...
$feed_description = ($proj->prefs['feed_description'] != '') ? $proj->prefs['feed_description'] : 'Flyspray:: '.$proj->prefs['project_title'].': '.$title;
// and feed image
$feed_image = false;
if($proj->prefs['feed_img_url'] != '' && substr($proj->prefs['feed_img_url'],0,7) == 'http://')
{
	$feed_image  = $proj->prefs['feed_img_url'];
}

$task_details = $db->fetchAllArray($task_details);
$page->uses('most_recent','feed_description','feed_image','task_details');
$content = $page->fetch('feed.'.$feed_type.'.tpl');

// cache feed
if($fs->prefs['cache_feeds'] != '0')
{
	if($fs->prefs['cache_feeds'] == '1')
	{
		if(!is_writeable('cache') && !@chmod('cache',0777))
		{
			 die('Error when caching the feed: cache/ is not writeable.');
		}
	
		// Remove old cached files
		$filename = $feed_type.'-'.$orderby.'-'.$project.'-'.$limit;
		if(file_exists('cache/'.$filename))
		{
			unlink('cache/'.$filename);
		}
		
		// Write new file
		$handle = fopen('cache/'.$feed_type.'-'.$orderby.'-'.$project.'-'.$limit,'w');
		fwrite($handle,$content);
		fclose($handle);
	}
	else
	{ 
		$db->Query("UPDATE {cache} SET content = ?,last_updated = ? WHERE  type = ? AND topic = ? AND project = ? AND `limit` = ?",
						array($content,time(),$feed_type,$orderby,$project,$limit));
						
		if($db->Affected_Rows() == 0)
		{
			$db->Query("INSERT INTO {cache} (content,type,topic,project,`limit`,last_updated) VALUES(?,?,?,?,?,?)",
						array($content,$feed_type,$orderby,$project,$limit,time()));
		}
	}
}

echo $content;
?>