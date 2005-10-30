<?php

/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/

include_once(dirname(__FILE__).'/header.php');

$this_page = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES);

// Background daemon that does scheduled reminders
if ($conf['general']['reminder_daemon'] == '1') {
    $fs->startReminderDaemon();
}

// Get the translation for the wrapper page (this page)
$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'main');
setlocale(LC_ALL, str_replace('-','_',$language['locale']));

// Get user permissions
if (Cookie::has('flyspray_userid') && Cookie::has('flyspray_passhash')) {
    $user_id = Cookie::val('flyspray_userid');

    // Check to see if the user has been trying to hack their cookies to perform sql-injection
    if (!is_numeric($user_id) || !is_numeric(Cookie::val('flyspray_project'))) {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }

    // Only logged in users get to use the 'last search' functionality
    foreach (array('string','type','sev','due','dev','cat','status') as $key) {
        if (Get::has($key)) {
            $db->Query("UPDATE  {$dbprefix}users
                           SET  last_search = ?
                         WHERE  user_id = ?",
                    array($this_page, $user_id)
            );
        }
    }

    $current_user = $fs->getUserDetails($user_id);
    $permissions  = $fs->getPermissions($current_user['user_id'], $project_id);
} else {
    $permissions  = array();
}

// Set the theme
if (Req::val('project') == '0') {
   $themestyle = $flyspray_prefs['global_theme'];
} else {
   $themestyle = $project_prefs['theme_style'];
}

if (Get::has('getfile') && Get::val('getfile')) {
    // If a file was requested, deliver it
    $result = $db->Query("SELECT  task_id, orig_name, file_name, file_type
                            FROM  {$dbprefix}attachments
                           WHERE  attachment_id = ?", array($_GET['getfile']));
    list($task_id, $orig_name, $file_name, $file_type) = $db->FetchArray($result);

    // Retrieve permissions!
    $task_details = $fs->GetTaskDetails($task_id);
    $proj_prefs   = $fs->GetProjectPrefs($task_details['attached_to_project']);
    $user_permissions = @$fs->getPermissions($db->emptyToZero($current_user['user_id']),
            $task_details['attached_to_project']);

    // Check if file exists, and user permission to access it!
    if (file_exists("attachments/$file_name")
            && ($project_prefs['others_view'] == '1' OR $user_permissions['view_attachments'] == '1'))
    {
        $path = "$basedir/attachments/$file_name";

        header('Pragma: public');
        header("Content-type: $file_type");
        header("Content-Disposition: filename=$orig_name");
        header('Content-transfer-encoding: binary');
        header('Content-length: ' . filesize($path));

        readfile($path);
    }
    else {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }
    exit;
}

/*******************************************************************************/
/* Here begins the deep flyspray                                               */
/*******************************************************************************/

// Note that server admins can override this, breaking Flyspray i18n.
header('Content-type: text/html; charset=utf-8');

// see http://www.w3.org/TR/html401/present/styles.html#h-14.2.1
header('Content-Style-Type: text/css');

if ($conf['general']['output_buffering'] == 'gzip') {
    // Start Output Buffering and gzip encoding if setting is present.
    // This functionality provided Mariano D'Arcangelo
    include_once( 'includes/gzip_compress.php' );
}
else {
    // ob_end_flush() isn't needed in MOST cases because it is called automatically
    // at the end of script execution by PHP itself when output buffering is turned on
    // either in the php.ini or by calling ob_start().
    ob_start();
}

$do = Req::val('do', 'index');

if ($do == 'index') {
    // When viewing the task list, take down each value that the search form
    // may have passed
    $keys  = array('string', 'type', 'sev', 'dev', 'due', 'cat', 'status', 'date', 'project');
    $keys2 = array('tasks', 'pagenum', 'order', 'order2', 'sort', 'sort2');

    function keep($key) {
        return !is_null(Get::val($key)) ? $key."=".Get::val($key) : null;
    }

    $keys  = array_map('keep', $keys);
    $keys2 = array_map('keep', $keys2);
    $keys  = array_filter($keys,  create_function('$x', 'return !is_null($x);'));
    $keys2 = array_filter($keys2, create_function('$x', 'return !is_null($x);'));

    $extraurl  = join('&amp;', $keys);
    $extraurl2 = join('&amp;', $keys2);
    
    $_SESSION['lastindexfilter'] = $baseurl . 'index.php?' .
        join('&amp;', array($extraurl, $extraurl2));
}

if (file_exists("themes/$themestyle/favicon.ico")) {
    $ico_path = "{$baseurl}themes/$themestyle/favicon.ico";
} else {
    $ico_path = "{$baseurl}favicon.ico";
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"
  lang="<?php echo $language['locale'] ?>" xml:lang="<?php echo $language['locale'] ?>">
  <head>
    <title>Flyspray::&nbsp;&nbsp;<?php echo $project_prefs['project_title'] ?>:&nbsp;&nbsp;</title>
    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <link rel="icon" type="image/png" href="<?php echo $ico_path ?>" />
    <link href="<?php echo $baseurl . 'themes/' . $themestyle ?>/theme.css" rel="stylesheet" type="text/css" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS Feed" href="<?php echo $baseurl . 'scripts/rss.php?proj=' . $project_id ?>" />
    <script type="text/javascript" src="<?php echo $baseurl ?>includes/styleswitcher.js"></script>
    <script type="text/javascript" src="<?php echo $baseurl ?>includes/tabs.js"></script>
    <script type="text/javascript" src="<?php echo $baseurl ?>includes/functions.js"></script>
    <?php
    // This allows theme authors to include other code/javascript/dhtml to make their theme funky
    @include($baseurl . 'themes/' . $themestyle . '/header.php');
    ?>
    <!--[if IE 6]>
    <script type="text/javascript" src="<?php echo $baseurl ?>includes/ie_hover.js"></script>
    <![endif]-->

    <style type="text/css">@import url(<?php echo $baseurl ?>includes/jscalendar/calendar-win2k-1.css);</style>
    <script type="text/javascript" src="<?php echo $baseurl ?>includes/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="<?php echo $baseurl ?>includes/jscalendar/lang/calendar-en.js"></script>
    <script type="text/javascript" src="<?php echo $baseurl ?>includes/jscalendar/calendar-setup.js"></script>
  </head>
  <body>
  <?php
  // People might like to define their own header files for their theme
  @include("$basedir/themes/".$project_prefs['theme_style']."/header.inc.php");


// If the admin wanted the Flyspray logo shown at the top of the page...
if ($project_prefs['show_logo'] == '1') {
    echo '<h1 id="title"><span>' . $project_prefs['project_title'] . '</span></h1>';
}

// if no-one's logged in, show the login box
if(!Cookie::has('flyspray_userid')) {
   require('scripts/loginbox.php');

   // If we have allowed anonymous logging of new tasks
   // Show the link to the Add Task form
   if ($project_prefs['anon_open'] == '1') {
       echo '<div id="anonopen"><a href="?do=newtask&amp;project=' . $project_id . '">' . $language['opentaskanon'] . '</a></div>';
   }
}

////////////////////////////////////////////////
// OK, now we start the new permissions stuff //
////////////////////////////////////////////////

// If the user has the right name cookies
if (Cookie::val('flyspray_userid') && Cookie::val('flyspray_passhash')) {
    // Check that the user hasn't spoofed the cookie contents somehow
    // And that their account is enabled
    // and that their group is enabled
    if (Cookie::val('flyspray_passhash') != crypt($current_user['user_pass'], $cookiesalt)
            || $permissions['account_enabled'] != '1' || $permissions['group_open'] != '1')
    {
        // If the user's account is closed
        setcookie('flyspray_userid',   '', time()-60, '/');
        setcookie('flyspray_passhash', '', time()-60, '/');
        $fs->Redirect($fs->CreateURL('logout', null));
    }

    $things = array();

    // Display "Logged in as - username"
    $things[] = '<em>' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ')</em>';

    if ($permissions['open_new_tasks'] == '1') {
        // Display Add New Task link
        $things[] = '<a id="newtasklink" href="' . $fs->CreateURL('newtask', $project_id) . '" accesskey="a">' . $language['addnewtask'] . "</a>\n";
    }

    if ($permissions['view_reports'] == '1' && $project_id != '0') {
        // Reports link
        $things[] = '<a id="reportslink" href="' . $fs->CreateURL('reports', null) . '" accesskey="r">' . $language['reports'] . "</a>\n";
    }

    // Edit My Details link
    $things[] =  '<a id="editmydetailslink" href="' . $fs->CreateURL('myprofile', null) . '" accesskey="e">' . $language['editmydetails'] . "</a>\n";

    // If the user has conducted a search, then show a link to the most recent task list filter
    if ( !empty($current_user['last_search']) ) {
        $things[] =  '<a id="lastsearchlink" href="' . $current_user['last_search'] . '" accesskey="m">' . $language['lastsearch'] . "</a>\n";
    } else {
        $things[] =  '<a id="lastsearchlink" href="' . $baseurl . 'index.php">' . $language['lastsearch'] . "</a>\n";
    }

    if ($permissions['is_admin'] == '1') {
        // Administrator's Toolbox link
        $things[] =  '<a id="optionslink" href="' . $fs->CreateURL('admin', 'prefs') . '">' . $language['admintoolbox'] . "</a>\n";
    }

    if ($permissions['manage_project'] == '1') {
        // Project Manager's Toolbox link
        $things[] =  '<a id="projectslink" href="' . $fs->CreateURL('pm', 'prefs', $project_id) . '">' . $language['manageproject'] . "</a>\n";
    }

    // Logout link
    $things[] =  '<a id="logoutlink" href="' . $fs->CreateURL('logout', null)  . '" accesskey="l">' . $language['logout'] . "</a>\n";


    if ($permissions['manage_project'] == '1') {
        // Find out if there are any PM requests wanting attention
        $get_req = $db->Query("SELECT * FROM {$dbprefix}admin_requests WHERE project_id = ? AND resolved_by = '0'",
                array($project_id));

        $num_req = $db->CountRows($get_req);

        // Show the amount of admin requests waiting
        if ($db->CountRows($get_req)) {
            $things[] =  '<a id="pendingreq" class="attention" href="' . $fs->CreateURL('pm', 'pendingreq', $project_id) . '">' . $num_req . ' ' . $language['pendingreq'] . '</a>';
        }
    }
    
    echo '<p id="menu">'. join('<small> | </small>', $things) . '</p>';
}

if (isset($_SESSION['ERROR'])) {
    echo '<div id="errorbar" onClick="this.style.display = \'none\'">' . $_SESSION['ERROR'] . '</div>';
    unset($_SESSION['ERROR']);
}

if (isset($_SESSION['SUCCESS'])) {
    echo '<div id="successbar" onClick="this.style.display = \'none\'">' . $_SESSION['SUCCESS'] . '</div>';
    unset($_SESSION['SUCCESS']);
}
?>

<div id="content">
  <div id="projectselector">
    <form action="<?php echo $baseurl;?>index.php" method="get">
      <p>
        <select name="tasks">
          <option value="all"><?php echo $language['tasksall'];?></option>
          <?php if (isset($_COOKIE['flyspray_userid'])): ?>
          <option value="assigned" <?php if (Get::val('tasks') == 'assigned') echo 'selected="selected"'; ?>><?php echo $language['tasksassigned']; ?></option>
          <option value="reported" <?php if (Get::val('tasks') == 'reported') echo 'selected="selected"'; ?>><?php echo $language['tasksreported']; ?></option>
          <option value="watched"  <?php if (Get::val('tasks') == 'watched')  echo 'selected="selected"'; ?>><?php echo $language['taskswatched'];  ?></option>
          <?php endif; ?>
        </select>
        <?php echo $language['selectproject']; ?>
        <select name="project">
          <option value="0" <?php if (Get::val('project') == '0') echo 'selected="selected"';?>><?php echo $language['allprojects'];?></option>
          <?php

          if (@$permissions['global_view'] == '1') {
              // If the user has permission to view all projects
              $get_projects = $db->Query("SELECT  *
                                            FROM  {$dbprefix}projects
                                           WHERE  project_is_active = '1'
                                        ORDER BY  project_title");
          }
          elseif (isset($_COOKIE['flyspray_userid'])) {
              // or, if the user is logged in
              $get_projects = $db->Query("SELECT  DISTINCT p.*
                                            FROM  {$dbprefix}users_in_groups uig
                                       LEFT JOIN  {$dbprefix}groups g ON uig.group_id = g.group_id, {$dbprefix}projects p
                                           WHERE  ( (uig.user_id = ?  AND g.view_tasks = '1') OR p.others_view = '1')
                                                  AND p.project_is_active = '1'
                                        GROUP BY  p.project_id", array($current_user['user_id']));
          }
          else {
              // Anonymous users
              $get_projects = $db->Query("SELECT  *
                                            FROM  {$dbprefix}projects
                                           WHERE  project_is_active = '1' AND others_view = '1'
                                           ORDER  BY project_title");
          }

          // Cycle through the results from whichever query above
          while ($row = $db->FetchArray($get_projects)) {
              // Ensure that the selected project matches the one we are currently looking at
              if ( $project_id == $row['project_id'] && (!Get::has('project') || Get::val('project')) )
              {
                  echo '<option value="' . $row['project_id'] . '" selected="selected">' . stripslashes($row['project_title']) . '</option>';
              }
              else {
                  echo '<option value="' . $row['project_id'] . '">' . stripslashes($row['project_title']) . '</option>';
              }
              $project_list[] = $row['project_id'];
          }
          ?>
        </select>
        <input class="mainbutton" type="submit" value="<?php echo $language['show'];?>" />
      </p>
    </form>
  </div>

  <form action="<?php echo $baseurl;?>index.php" method="get">
    <p id="showtask">
      <label><?php echo $language['showtask'];?> #
      <input id="taskid" name="show_task" type="text" size="10" maxlength="10" accesskey="t" /></label>
      <input class="mainbutton" type="submit" value="<?php echo $language['go'];?>" />
    </p>
  </form>

<?php
// If someone used the 'show task' form above, redirect them
if ( Get::has('show_task') ) {
    $show_task = Get::val('show_task');

    if (is_numeric($show_task)) {
        $fs->Redirect( $fs->CreateURL('details', $show_task) );
    }
    else {
        $fs->Redirect( $fs->CreateURL('error', null) );
    }
}

// Show the project blurb if the project manager defined one
if ($project_prefs['project_is_active'] == '1'
    && ($project_prefs['others_view'] == '1' OR @$permissions['view_tasks'] == '1')
    && !empty($project_prefs['intro_message'])
    && in_array($do, array('details', 'index', 'newtask', 'reports', 'depends'))
    OR (Get::val('project') == '0'))
{
    $intro_message = Markdown(stripslashes($project_prefs['intro_message']));
    echo '<div id="intromessage">' . $intro_message . '</div>';
}

// Check that this page isn't being submitted twice
if ($fs->requestDuplicated())
{
    printf('<meta http-equiv="refresh" content="2; URL=?id=%s">', $project_id);
    printf('<div class="redirectmessage"><p><em>%s</em></p></div>', $language['duplicated']);
    echo '</body></html>';
    exit;
}

// Show the page the user wanted
require("$basedir/includes/permissions.inc.php");
require("$basedir/scripts/$do.php");

// Show the user's permissions
if (Cookie::has('flyspray_userid')) {
    tpl_draw_perms($permissions);
}
?>
    </div>
    <p id="footer">
       <!-- Please don't remove this line - it helps promote Flyspray -->
       <a href="http://flyspray.rocks.cc/" class="offsite"><?php printf("%s %s", $language['poweredby'], $fs->version);?></a>
    </p>
    <?php @include ("$basedir/themes/".$project_prefs['theme_style']."/footer.inc.php"); ?>
  </body>
</html>
