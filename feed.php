<?php
// We can't include this script as part of index.php?do= etc,
// as that would introduce html code into it.  HTML != Valid XML
// So, include the headerfile to set up database access etc

define('IN_FS', true);

require dirname(__FILE__).'/header.php';

if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user = new User(Cookie::val('flyspray_userid'));
    $user->check_account_ok();
} elseif (Get::val('user_id') && Get::val('auth')) {
    $user = new User(Get::val('user_id'));
    if (Get::val('auth') != md5($user->infos['user_pass'] . $user->infos['register_date'])) {
        $user =& new User;
    }
} else {
    $user =& new User;
}

$page =& new FSTpl();

// Set up the basic XML head
header ('Content-type: text/html; charset=utf-8');

$max_items  = (Get::num('num', 10) == 10) ? 10 : 20;
$sql_project = '';
if ($proj->id) {
    $sql_project = sprintf(' t.project_id = %d', $proj->id);
}

$feed_type  = Get::enum('feed_type', array('rss1', 'rss2', 'atom'), 'rss2');

switch (Get::val('topic')) {
    case 'clo': $orderby = 'date_closed'; $closed = 't.is_closed = 1 AND';
                $title   = 'Recently closed tasks';
    break;

    case 'edit':$orderby = 'last_edited_time'; $closed = '';
                $title   = 'Recently edited tasks';
    break;

    default:    $orderby = 'date_opened'; $closed = '';
                $title   = 'Recently opened tasks';
    break;
}

$filename = md5(sprintf('%s-%s-%d-%d', $feed_type, $orderby, $proj->id, $max_items) . $conf['general']['cookiesalt']);
$cachefile = sprintf('%s/%s', FS_CACHE_DIR, $filename);

// Get the time when a task has been changed last
$db->setLimit($max_items);
$sql = $db->query("SELECT  t.date_opened, t.date_closed, t.last_edited_time
                     FROM  {tasks}    t
                    WHERE  $closed $sql_project
                 ORDER BY  $orderby DESC");
$most_recent = 0;
while ($row = $sql->fetchRow()) {
    $most_recent = max($most_recent, $row['date_opened'], $row['date_closed'], $row['last_edited_time']);
}

if ($fs->prefs['cache_feeds']) {
    if ($fs->prefs['cache_feeds'] == '1') {
        if (!is_link($cachefile) && is_file($cachefile) && $most_recent <= filemtime($cachefile)) {
            readfile($cachefile);
            exit;
        }
    }
    else {
        $content = $db->x->GetOne("SELECT  content
                                     FROM  {cache} t
                                    WHERE  type = ? AND topic = ? $sql_project
                                           AND max_items = ?  AND last_updated >= ?", null,
                                   array($feed_type, $orderby . $user->id, $max_items, $most_recent));
        if ($content) {
            echo $content;
            exit;
        }
    }
}

/* build a new feed if cache didn't work */
$db->setLimit($max_items);
$task_details = $db->x->getAll(
                        "SELECT  t.task_id, t.item_summary, t.detailed_desc, t.date_opened, t.date_closed,
                                 t.last_edited_time, t.opened_by, u.real_name, u.email_address, u.show_contact, t.*, p.project_prefix
                           FROM  {tasks}    t
                     INNER JOIN  {users}    u ON t.opened_by = u.user_id
                     INNER JOIN  {projects} p ON t.project_id = p.project_id
                          WHERE  $closed $sql_project
                       ORDER BY  $orderby DESC");

$task_details     = array_filter($task_details, array($user, 'can_view_task'));
$feed_description = $proj->prefs['feed_description'] ? $proj->prefs['feed_description'] : $fs->prefs['page_title'] . $proj->prefs['project_title'].': '.$title;
$feed_image       = false;
if ($proj->prefs['feed_img_url']
        && !strncmp($proj->prefs['feed_img_url'], 'http://', 7))
{
    $feed_image   = $proj->prefs['feed_img_url'];
}

$page->uses('most_recent', 'feed_description', 'feed_image', 'task_details');
$content = $page->fetch('feed.'.$feed_type.'.tpl');

// cache feed
if ($fs->prefs['cache_feeds'])
{
    if ($fs->prefs['cache_feeds'] == '1') {
            file_put_contents($cachefile, $content, LOCK_EX);
    }
    else {
       /**
        * " Try to update a record, and if the record is not found,
        *   an insert statement is generated and executed "
        */

        $fields = array('content'=> array('value' => $content),
                        'type'=> array('value' => $feed_type, 'key' => true),
                        'topic'=> array('value' => $orderby . $user->id, 'key' => true),
                        'project_id'=> array('value' => $proj->id, 'key' => true),
                        'max_items'=> array('value' => $max_items, 'key' => true),
                        'last_updated'=> array('value' => time()));

        $db->Replace('{cache}', $fields);
    }
}

header('Content-Type: application/xml; charset=utf-8');
echo $content;
?>
