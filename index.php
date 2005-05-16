<?php

/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/

include_once('header.php');

// Set a generic "blank" variable so that we don't get notices
$novar = '';

// Send this header for i18n support
// Note that server admins can override this, breaking Flyspray.
header("Content-type: text/html; charset=utf-8");

// Check that we're using 0.9.6, and start the upgrade script if we're not.
if (isset($flyspray_prefs['dateformat']) && !isset($flyspray_prefs['fs_ver']))
{
   Header("Location: sql/upgrade_0.9.6_to_0.9.7.php");
}

// This generates an URL so that action scripts can take us back to the previous page
$this_page = sprintf("%s",$_SERVER["REQUEST_URI"]);
$this_page = str_replace('&', '&amp;', $this_page);

// Background daemon that does scheduled reminders
if ($flyspray_prefs['reminder_daemon'] == '1')
   $fs->startReminderDaemon();

// Get the translation for the wrapper page (this page)
$lang = $flyspray_prefs['lang_code'];
$fs->get_language_pack($lang, 'main');

// Get user permissions
$permissions = array();
if (isset($_COOKIE['flyspray_userid']) && isset($_COOKIE['flyspray_passhash']))
{
   // Check to see if the user has been trying to hack their cookies to perform sql-injection
   if (!preg_match ("/^\d*$/", $_COOKIE['flyspray_userid']) OR (!preg_match ("/^\d*$/", @$_COOKIE['flyspray_project'])))
   {
      die("Stop hacking your cookies, you naughty fellow!");
   }

   // Fetch info on the current user
   $current_user = $fs->getUserDetails($_COOKIE['flyspray_userid']);

   // Fetch the permissions array for the current user
   $permissions = $fs->getPermissions($current_user['user_id'], $project_id);
// End of getting user permissions
}

// Set the theme
if (isset($_GET['project']) && $_GET['project'] == '0')
{
   $themestyle = $flyspray_prefs['global_theme'];
} else
{
   $themestyle = $project_prefs['theme_style'];
}

// Set the page to include
if (isset($_REQUEST['do']))
{
   $do = $_REQUEST['do'];
} else
{
   // Default page is the task list
   $do = "index";
}

// Check that the requested project actually exists
$check_proj_exists = $db->Query("SELECT * FROM {$dbprefix}_projects
                                   WHERE project_id = ?",
                                   array($project_id)
                                 );

if (!$db->CountRows($check_proj_exists))
{
   $fs->redirect("index.php?project=" . $flyspray_prefs['default_project']);
}

// If a file was requested, deliver it
if (isset($_GET['getfile']) && !empty($_GET['getfile']))
{
   list($task_id, $orig_name, $file_name, $file_type)
      = $db->FetchArray($db->Query("SELECT task_id,
                                    orig_name,
                                    file_name,
                                    file_type
                                    FROM {$dbprefix}_attachments
                                    WHERE attachment_id = ?",
                                    array($_GET['getfile'])
                                 )
                        );

   // Retrieve permissions!
   $task_details = $fs->GetTaskDetails($task_id);
   $proj_prefs = $fs->GetProjectPrefs($task_details['attached_to_project']);
   $user_permissions = @$fs->getPermissions($current_user['user_id'], $task_details['attached_to_project']);

   // Check if file exists, and user permission to access it!
   if (file_exists("attachments/$file_name")
      && ($project_prefs['others_view'] == '1' OR $user_permissions['view_attachments'] == '1'))
   {
      $path = $basedir ."attachments/$file_name";

      header("Pragma: public");
      header("Content-type: $file_type");
      header("Content-Disposition: filename=$orig_name");
      header("Content-transfer-encoding: binary\n");
      header("Content-length: " . filesize($path) . "\n");

      readfile("$path");
   } else
   {
      echo $language['filenotexist'];
   }

// If no file was requested, show the page as per normal
} else
{
   // Start Output Buffering and gzip encoding if setting is present.
   // This functionality provided Mariano D'Arcangelo
   if ($conf_array['general']['output_buffering']=='gzip')
   {
      include_once( 'includes/gzip_compress.php' );

   } elseif ($conf_array['general']['output_buffering']=='on')
   {
      ob_start();
   }
   // ob_end_flush() isn't needed in MOST cases because it is called automatically
   // at the end of script execution by PHP itself when output buffering is turned on
   // either in the php.ini or by calling ob_start().

   // When viewing the task list, take down each value that the search form may have passed
   if ($do == 'index')
   {
      $extraurl = '&amp;string=' . $_GET['string'] . '&amp;type=' . $_GET['type'] . '&amp;sev=' . $_GET['sev'] . '&amp;dev=' . $_GET['dev']
                  . '&amp;due=' . $_GET['due'] . '&amp;cat=' . $_GET['cat'] . '&amp;status=' . $_GET['status']
                  . '&amp;order2=' . $_GET['order2'] . '&amp;sort=' . $_GET['sort']
                  . '&amp;sort2=' . $_GET['sort2'] . '&amp;perpage=' . $_GET['perpage']
                  . '&amp;date=' . $_GET['date'] . '&amp;project=' . @$_GET['project'];

      $_SESSION['lastindexfilter'] = $flyspray_prefs['base_url'] . 'index.php?tasks=' . $_GET['tasks']
                                     . '&amp;pagenum=' . $_GET['pagenum'] . $extraurl;

      if (isset($_GET['order']))
         $_SESSION['lastindexfilter'] .= '&amp;order=' . $_GET['order'];
   }
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
   <title>Flyspray::&nbsp;&nbsp;<?php echo stripslashes($project_prefs['project_title']) . ':&nbsp;&nbsp;' ;?></title>
   <link rel="icon" type="image/png"
   <?php
   if (file_exists("themes/$themestyle/favicon.ico"))
   {
      echo "href=\"{$flyspray_prefs['base_url']}themes/$themestyle/favicon.ico\"";
   } else
   {
      echo "href=\"{$flyspray_prefs['base_url']}favicon.ico\"";
   }
   ?>
   />
   <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
   <?php
   echo '<link href="' . $flyspray_prefs['base_url'] . 'themes/' . $themestyle . '/theme.css" rel="stylesheet" type="text/css" />' . "\n";
   echo '<link rel="alternate" type="text/xml" title="Flyspray RSS Feed" href="' . $flyspray_prefs['base_url'] . 'scripts/rss.php?proj=' . $project_id . '" />' . "\n";
   echo '<script type="text/javascript" src="' . $flyspray_prefs['base_url'] . 'includes/styleswitcher.js"></script>' . "\n";
   echo '<script type="text/javascript" src="' . $flyspray_prefs['base_url'] . 'includes/tabs.js"></script>' . "\n";
   echo '<script type="text/javascript" src="' . $flyspray_prefs['base_url'] . 'includes/functions.js"></script>' . "\n";

   // This allows theme authors to include other code/javascript/dhtml to make their theme funky
   if (file_exists($flyspray_prefs['base_url'] . 'themes/' . $themestyle . '/header.php'))
      include($flyspray_prefs['base_url'] . 'themes/' . $themestyle . '/header.php');

   echo '<!--[if IE 6]>';
   echo '<script type="text/javascript" src="' . $flyspray_prefs['base_url'] . 'includes/ie_hover.js"></script>';
   echo '<![endif]-->';

   echo '<style type="text/css">@import url(' . $flyspray_prefs['base_url'] . 'includes/jscalendar/calendar-win2k-1.css);</style>';
   echo '<script type="text/javascript" src="' . $flyspray_prefs['base_url'] . 'includes/jscalendar/calendar_stripped.js"></script>';
   echo '<script type="text/javascript" src="' . $flyspray_prefs['base_url'] . 'includes/jscalendar/lang/calendar-en.js"></script>';
   echo '<script type="text/javascript" src="' . $flyspray_prefs['base_url'] . 'includes/jscalendar/calendar-setup.js"></script>';
    ?>

</head>
<body>

<?php
// People might like to define their own header files for their theme
$headerfile = "$basedir/themes/".$project_prefs['theme_style']."/header.inc.php";
if(file_exists("$headerfile"))
   include("$headerfile");

// If the admin wanted the Flyspray logo shown at the top of the page...
if ($project_prefs['show_logo'] == '1')
   echo '<h1 id="title"><span>' . $project_prefs['project_title'] . '</span></h1>';

// if no-one's logged in, show the login box
if(!isset($_COOKIE['flyspray_userid']))
   require('scripts/loginbox.php');


// If we have allowed anonymous logging of new tasks
// Show the link to the Add Task form
if ($project_prefs['anon_open'] == '1' && !isset($_COOKIE['flyspray_userid']))
   echo '<div id="anonopen"><a href="?do=newtask&amp;project=' . $project_id . '">' . $language['opentaskanon'] . '</a></div>';



////////////////////////////////////////////////
// OK, now we start the new permissions stuff //
////////////////////////////////////////////////

// If the user has the right name cookies
if (isset($_COOKIE['flyspray_userid']) && isset($_COOKIE['flyspray_passhash']))
{
   // Check that the user hasn't spoofed the cookie contents somehow
   if ($_COOKIE['flyspray_passhash'] == crypt($current_user['user_pass'], "$cookiesalt")
      // And that their account is enabled
      && $permissions['account_enabled'] == '1'
      // and that their group is enabled
      && $permissions['group_open'] == '1')
   {

   ////////////////////////
   // Show the user menu //
   ////////////////////////

   echo '<p id="menu">' . "\n";

   // Display "Logged in as - username"
   echo '<em>' . $current_user['real_name'] . ' (' . $current_user['user_name'] . ')</em>';

   // Display Add New Task link
   if ($permissions['open_new_tasks'] == '1')
   {
      echo '<small> | </small>';
      echo '<a id="newtasklink" href="' . $fs->CreateURL('newtask', $project_id) . '" accesskey="a">' . $language['addnewtask'] . "</a>\n";
   }

   // Reports link
   if ($permissions['view_reports'] == '1' && $project_id != '0')
   {
      echo '<small> | </small>';
      echo '<a id="reportslink" href="' . $fs->CreateURL('reports', null) . '" accesskey="r">' . $language['reports'] . "</a>\n";
   }

   // Edit My Details link
   echo '<small> | </small>';
   echo '<a id="editmydetailslink" href="' . $fs->CreateURL('myprofile', null) . '" accesskey="e">' . $language['editmydetails'] . "</a>\n";


   // If the user has conducted a search, then show a link to the most recent task list filter
   echo '<small> | </small>';
   if(isset($_SESSION['lastindexfilter']))
   {
      echo '<a id="lastsearchlink" href="' . $_SESSION['lastindexfilter'] . '" accesskey="m">' . $language['lastsearch'] . "</a>\n";
   } else
   {
      echo '<a id="lastsearchlink" href="index.php">' . $language['lastsearch'] . "</a>\n";
   }

   // Administrator's Toolbox link
   if ($permissions['is_admin'] == '1')
   {
      echo '<small> | </small>';
      echo '<a id="optionslink" href="?do=admin&amp;area=prefs">' . $language['admintoolbox'] . "</a>\n";
   }

   // Project Manager's Toolbox link
   if ($permissions['manage_project'] == '1')
   {
      echo '<small> | </small>';
      echo '<a id="projectslink" href="' . $fs->CreateURL('pm', 'prefs', $project_id) . '">' . $language['manageproject'] . "</a>\n";
   }

   // Logout link
   echo '<small> | </small>';
   echo '<a id="logoutlink" href="' . $fs->CreateURL('logout', null)  . '" accesskey="l">' . $language['logout'] . "</a>\n";

   // End of mainmenu area


      //////////////////////////////////
     // Show the pending PM requests //
    //////////////////////////////////

    if ($permissions['manage_project'] == '1')
    {
      // Find out if there are any PM requests wanting attention
      $get_req = $db->Query("SELECT * FROM {$dbprefix}_admin_requests
                             WHERE project_id = ? AND resolved_by = '0'",
                             array($project_id));

      $num_req = $db->CountRows($get_req);

      // Show the amount of admin requests waiting
      if ($db->CountRows($get_req))
      {
         echo '<small> | </small>';
         echo '<a id="pendingreq" class="attention" href="' . $fs->CreateURL('pm', 'pendingreq', $project_id) . '">' . $num_req . ' ' . $language['pendingreq'] . '</a>';
      }

    // End of checking if the pending PM requests should be displayed
   }


   echo "</p>";

    // If the user's account is closed
   } else {
      echo "<br />{$language['disabledaccount']}";
      Header("Location: ?do=authenticate&action=logout");
   // End of checking if the user's account is open
   }

// End of checking if the user has the right cookies
}

// ERROR status bar
if (isset($_SESSION['ERROR']))
{
   echo '<div id="errorbar" onClick="document.getElementById(\'errorbar\').style.display = \'none\'">' . $_SESSION['ERROR'] . '</div>';
   unset($_SESSION['ERROR']);
}

// SUCCESS status bar
if (isset($_SESSION['SUCCESS']))
{
   echo '<div id="successbar" onClick="document.getElementById(\'successbar\').style.display = \'none\'">' . $_SESSION['SUCCESS'] . '</div>';
   unset($_SESSION['SUCCESS']);
}
?>

<div id="content">

<div id="projectselector">
<form action="<?php echo $flyspray_prefs['base_url'];?>index.php" method="get">
      <p>
      <select name="tasks">
        <option value="all"><?php echo $language['tasksall'];?></option>
      <?php
      if (isset($_COOKIE['flyspray_userid']))
      {
      ?>
         <option value="assigned" <?php if(isset($_GET['tasks']) && $_GET['tasks'] == 'assigned') echo 'selected="selected"'; ?>><?php echo $language['tasksassigned']; ?></option>
         <option value="reported" <?php if(isset($_GET['tasks']) && $_GET['tasks'] == 'reported') echo 'selected="selected"'; ?>><?php echo $language['tasksreported']; ?></option>
         <option value="watched" <?php if(isset($_GET['tasks']) && $_GET['tasks'] == 'watched') echo 'selected="selected"'; ?>><?php echo $language['taskswatched']; ?></option>
      <?php
      }
      ?>
      </select>
      <?php echo $language['selectproject'];?>
      <select name="project">
      <option value="0"<?php if (isset($_GET['project']) && $_GET['project'] == '0') echo ' selected="selected"';?>><?php echo $language['allprojects'];?></option>
      <?php

      // If the user has permission to view all projects
      if (isset($permissions['global_view']) && $permissions['global_view'] == '1')
      {
         $get_projects = $db->Query("SELECT * FROM {$dbprefix}_projects
                                     ORDER BY project_title");

      // or, if the user is logged in
      } elseif (isset($_COOKIE['flyspray_userid']))
      {

         $get_projects = $db->Query("SELECT DISTINCT p.*
                                       FROM {$dbprefix}_projects p
                                       LEFT JOIN {$dbprefix}_groups g ON p.project_id = g.belongs_to_project
                                       LEFT JOIN {$dbprefix}_users_in_groups uig ON g.group_id = uig.group_id
                                       WHERE uig.user_id = ?
                                       AND g.view_tasks = '1'
                                       OR p.others_view = '1'
                                       AND p.project_is_active = '1'",
                                       array($current_user['user_id'])
                                     );
      // Anonymous users
      } else
      {
         $get_projects = $db->Query("SELECT * FROM {$dbprefix}_projects
                                     WHERE project_is_active = '1'
                                     AND others_view = '1'
                                     ORDER BY project_title");
      }

      // Cycle through the results from whichever query above
      while ($row = $db->FetchArray($get_projects))
      {
         // Ensure that the selected project matches the one we are currently looking at
         if ( $project_id == $row['project_id']
         && (!isset($_GET['project'])
         OR ( isset($_GET['project']) && !empty($_GET['project']) ) ) )
         {
            echo '<option value="' . $row['project_id'] . '" selected="selected">' . stripslashes($row['project_title']) . '</option>';
            $project_list[] = $row['project_id'];
         } else
         {
            echo '<option value="' . $row['project_id'] . '">' . stripslashes($row['project_title']) . '</option>';
            $project_list[] = $row['project_id'];
         }

      }
      ?>
      </select>
      <input class="mainbutton" type="submit" value="<?php echo $language['show'];?>" />
      </p>
</form>
</div>

<!-- We somehow need to make this work with the new Funky URLs -->
<form action="<?php echo $flyspray_prefs['base_url'];?>index.php" method="get">
    <p id="showtask">
      <label><?php echo $language['showtask'];?> #
      <input id="taskid" name="id" type="text" size="10" maxlength="10" accesskey="t" /></label>
      <input type="hidden" name="do" value="details" />
      <input class="mainbutton" type="submit" value="<?php echo $language['go'];?>" />
    </p>
</form>

<?php

// Show the project blurb if the project manager defined one
if ($project_prefs['project_is_active'] == '1'
    && ($project_prefs['others_view'] == '1' OR @$permissions['view_tasks'] == '1')
    && !empty($project_prefs['intro_message'])
    && ($do == 'details' OR $do == 'index' OR $do == 'newtask' OR $do == 'reports')
    OR (isset($_GET['project']) && $_GET['project'] == '0'))
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
require("scripts/$do.php");

// Show the user's permissions
if (isset($_COOKIE['flyspray_userid']))
{
   echo '<div id="permslink">';
   echo '<a href="#" onclick="showhidestuff(\'permissions\');">' . $language['permissions'] . '</a>';
//    echo $language['permissions'];
//    echo '<a href="#" onclick="showstuff(\'permissions\');">' . $language['show'] . '</a>&nbsp;/&nbsp;';
//    echo '<a href="#" onclick="hidestuff(\'permissions\');">' . $language['hide'] . '</a>&nbsp;';
   echo '</div>';

   echo '<div id="permissions">';
   echo '<table border="1">';

   $perm_fields = array('is_admin',
                        'manage_project',
                        'view_tasks',
                        'open_new_tasks',
                        'modify_own_tasks',
                        'modify_all_tasks',
                        'view_comments',
                        'add_comments',
                        'edit_comments',
                        'delete_comments',
                        'view_attachments',
                        'create_attachments',
                        'delete_attachments',
                        'view_history',
                        'close_own_tasks',
                        'close_other_tasks',
                        'assign_to_self',
                        'assign_others_to_self',
                        'view_reports',
                        'global_view');

   foreach ($permissions as $key => $val)
   {
      if (in_array($key, $perm_fields))
      {
         echo '<tr><td>';
         $key = str_replace('_', ' ', $key);
         echo $key;
         echo '</td>';
         $val = str_replace('0', '<td style="color: red;">No</td>', $val);
         $val = str_replace('1', '<td style="color: green;">Yes</td>', $val);
         echo $val;
         echo "</tr>\n";
      }
   }

   echo '</table></div>';
}

?>

</div>
<p id="footer">
   <!-- Please don't remove this line - it helps promote Flyspray -->
   <a href="http://flyspray.rocks.cc/" class="offsite"><?php printf("%s %s", $language['poweredby'], $fs->version);?></a>
</p>


<?php
$footerfile = "$basedir/themes/".$project_prefs['theme_style']."/footer.inc.php";
if(file_exists("$footerfile"))
   include_once("$footerfile");

?>

</body>
</html>

<?php
// End of file delivery / showing the page
}
?>
