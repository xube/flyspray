<?php

/*
   This is the main script that everything else is included
   in.  Mostly what it does is check the user permissions
   to see what they have access to.
*/

include('header.php');

session_start();

// Get the translation for the wrapper page (this page)
$lang = $flyspray_prefs['lang_code'];
get_language_pack($lang, 'main');

// Set the page to include
if (isset($_REQUEST['do'])) {
  $do = $_REQUEST['do'];
} else {
  // Default page is the task list
  $do = "index";
}

// Check that the requested project actually exists
$check_proj_exists = $fs->dbQuery("SELECT * FROM flyspray_projects
                                   WHERE project_id = ?",
                                   array($project_id)
                                 );
if (!$fs->dbCountRows($check_proj_exists)) {
  die("<meta http-equiv=\"refresh\" content=\"0; URL=index.php?project={$flyspray_prefs['default_project']}\">");
};

// If a file was requested, deliver it
if ($_GET['getfile']) {

  list($orig_name, $file_name, $file_type) = $fs->dbFetchArray(
                                $fs->dbQuery("SELECT orig_name,
                                              file_name,
                                              file_type
                                              FROM flyspray_attachments
                                              WHERE attachment_id = '{$_GET['getfile']}'
                                      ")
                                 );

  if (file_exists("attachments/$file_name")) {

    $path = "attachments/$file_name";

    header("Pragma: public");
    header("Content-type: $file_type");
    header("Content-Disposition: filename=$orig_name");
    header("Content-transfer-encoding: binary\n");
    header("Content-length: " . filesize($path) . "\n");

    readfile("$path");
  } else {
    echo $language['filenotexist'];
  };

// If no file was requested, show the page as per normal
} else {
  // Send this header for i18n support
  // Note that server admins can override this, breaking Flyspray.
  header("Content-type: text/html; charset=utf-8");

  // Start Output Buffering and gzip encoding if setting is present.
  // This functionality provided Mariano D'Arcangelo
  if ($conf_array['general']['output_buffering']=='gzip') include 'includes/gzip_compress.php';
  elseif ($conf_array['general']['output_buffering']=='on') ob_start();
  // ob_end_flush() isn't needed in MOST cases because it is called automatically
  // at the end of script execution by PHP itself when output buffering is turned on
  // either in the php.ini or by calling ob_start().
  
  // If the user has used the search box, store their search for later on
  if (isset($_GET['perpage']) || isset($_GET['tasks']) || isset($_GET['order'])) {
    $_SESSION['lastindexfilter'] = "index.php?tasks={$_GET['tasks']}&amp;project={$_GET['project']}&amp;string={$_GET['string']}&amp;type={$_GET['type']}&amp;sev={$_GET['sev']}&amp;due={$_GET['due']}&amp;dev={$_GET['dev']}&amp;cat={$_GET['cat']}&amp;status={$_GET['status']}&amp;perpage={$_GET['perpage']}&amp;pagenum={$_GET['pagenum']}&amp;order={$_GET['order']}&amp;order2=" . $_GET['order2'] . "&amp;sort={$_GET['sort']}&amp;sort2=" . $_GET['sort2'];
  }
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<title>Flyspray::&nbsp;&nbsp;<?php echo stripslashes($project_prefs['project_title']) . ':&nbsp;&nbsp;' ;?></title>
  <link rel="icon" href="./favicon.ico" type="image/png" />
  <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
  <link href="themes/<?php echo $project_prefs['theme_style'];?>/theme.css" rel="stylesheet" type="text/css" />
  <script type="text/javascript" src="includes/functions.js"></script>
  <script type="text/javascript" src="includes/styleswitcher.js"></script>
  <style type="text/css">@import url(includes/jscalendar/calendar-win2k-1.css);</style>
  <script type="text/javascript" src="includes/jscalendar/calendar_stripped.js"></script>
  <script type="text/javascript" src="includes/jscalendar/lang/calendar-en.js"></script>
  <script type="text/javascript" src="includes/jscalendar/calendar-setup.js"></script>
  
  <?php
      // open the themes directory
      if ($handle = opendir('themes/')) {
      $theme_array = array();
       while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && file_exists("themes/$file/theme.css")) {
          array_push($theme_array, $file);
        }
      }
      closedir($handle);
    }

    // Sort the array alphabetically
    sort($theme_array);
    // Then display them as alternate themes for browsers that support such features, like Mozilla!
    while (list($key, $val) = each($theme_array)) {
      echo "<link href=\"themes/$val/theme.css\" title=\"$val\" rel=\"alternate stylesheet\" type=\"text/css\" />\n";
    };
    ?>
</head>
<body>

<?php
// People might like to define their own header files for their theme
$headerfile = "$basedir/themes/".$project_prefs['theme_style']."/header.inc.php"; 
if(file_exists("$headerfile")) { 
 include("$headerfile"); 
};


// If the admin wanted the Flyspray logo shown at the top of the page...
if ($project_prefs['show_logo'] == '1') {
  echo "<h1 id=\"title\"><span>{$project_prefs['project_title']}</span></h1>";
};

////////////////////////////////////////////////
// OK, now we start the new permissions stuff //
////////////////////////////////////////////////

// If the user has the right name cookies
if ($_COOKIE['flyspray_userid'] && $_COOKIE['flyspray_passhash']) {

    // Check to see if the user has been trying to hack their cookies to perform sql-injection
    if (!preg_match ("/^\d*$/", $_COOKIE['flyspray_userid']) OR (!preg_match ("/^\d*$/", $_COOKIE['flyspray_project']))) {
      die("Stop hacking your cookies, you naughty fellow!");
    };
   
   // Fetch info on the current user
   $current_user = $fs->getUserDetails($_COOKIE['flyspray_userid']);
   
   // Fetch the permissions array for the current user
   $permissions = $fs->checkPermissions($current_user['user_id'], $_COOKIE['flyspray_project']);


  // Check that the user hasn't spoofed the cookie contents somehow
  if ($_COOKIE['flyspray_passhash'] == crypt($current_user['user_pass'], "$cookiesalt")
      // And that their account is enabled
      && $permissions['account_enabled'] == '1'
      // and that their group is enabled
      && $permissions['group_open'] == '1'
     ){

    ////////////////////////
    // Show the user menu //
    ////////////////////////

    echo '<p id="menu">' . "\n";
    echo '<span id="mainmenu">' . "\n";

   // Display logged in username
   echo '<em>' . $language['loggedinas'] . ' - ' . $current_user['user_name'] . '</em>';
    
   // Display Add New Task link
   if ($permissions['open_new_tasks'] == '1') {
      echo '<small> | </small>';
      echo '<a id="newtasklink" href="?do=newtask&amp;project=' . $project_id . '" accesskey="a">' . $language['addnewtask'] . "</a>\n";
    };
    
   // Display Reports link
   if ($permissions['view_reports'] == '1') {
      echo '<small> | </small>';
      echo '<a id="reportslink" href="index.php?do=reports" accesskey="r">' . $language['reports'] . "</a>\n";
    };
    
   // Display Edit My Details link
   echo '<small> | </small>';
   echo '<a id="editmydetailslink" href="?do=admin&amp;area=users&amp;id=' . $current_user['user_id'] . '" accesskey="e">' . $language['editmydetails'] . "</a>\n";


   // If the user has conducted a search, then show a link to the most recent task list filter
   echo '<small> | </small>';
   if(isset($_SESSION['lastindexfilter'])) {
      echo '<a id="lastsearchlink" href="' . $_SESSION['lastindexfilter'] . '" accesskey="m">' . $language['lastsearch'] . "</a>\n";
   } else {
      echo '<a id="lastsearchlink">' . $language['lastsearch'] . "</a>\n";
   };
   
   // Display Logout link   
   echo '<small> | </small>';
   echo '<a id="logoutlink" href="index.php?do=authenticate&amp;action=logout" accesskey="l">' . $language['logout'] . "</a>\n";
   
   // End of mainmenu area
   echo "</span>\n";

      /////////////////////////
     // Show the Admin menu //
    /////////////////////////

    if ($permissions['is_admin'] == "1" OR $permissions['manage_project'] == '1') {

    // Find out if there are any PM requests wanting attention
    $get_req = $fs->dbQuery("SELECT * FROM flyspray_admin_requests
                             WHERE project_id = ? AND resolved_by = '0'",
                             array($project_id));
    $num_req = $fs->dbCountRows($get_req);

    // Check for admin requests too
    if ($permissions['is_admin'] == '1') {
      $get_admin_req = $fs->dbQuery("SELECT * FROM flyspray_admin_requests
                                     WHERE project_id = '0' AND resolved_by = '0'");
      $num_req = $num_req + $fs->dbCountRows($get_admin_req);
    };


      echo '<span id="adminmenu">';

      echo '<em>' . $language['adminmenu'] . '</em>';

      // Global Options link
      if ($permissions['is_admin'] == '1') {
         echo '<small> | </small>';
         echo '<a id="optionslink" href="?do=admin&amp;area=options">' . $language['options'] . "</a>\n";
       };
      
      // Project options link
      if ($permissions['manage_project'] == '1') {
         echo '<small> | </small>';
         echo '<a id="projectslink" href="?do=admin&amp;area=projects&amp;show=prefs&amp;project=' . $project_id . '">' . $language['projects'] . "</a>\n";

        // Get a list of projects so that we can cycle through them for the submenu
        /*$get_projects = $fs->dbQuery("SELECT * FROM flyspray_projects");
        echo '<ul>';
        while ($this_project = $fs->dbFetchArray($get_projects)) {
           echo '<li>';
           echo '<a href="?do=admin&amp;area=projects&amp;show=prefs&amp;project=' . $this_project['project_id'] . '">' . stripslashes($this_project['project_title']) . "</a>\n";
           echo '</li>';
        };
        echo '</ul>';*/

      };
      
      // Manage users link
      if ($permissions['is_admin'] == '1') {
         echo '<small> | </small>';
         echo '<a id="usersandgroupslink" href="?do=admin&amp;area=users">' . $language['usersandgroups'] . "</a>\n";
      };
     
      if ($permissions['is_admin'] == '1') {
         echo '<small> | </small>';
         echo '<a id="listslink" href="?do=admin&amp;area=tasktype">' . $language['tasktypes'] . "</a>\n";
         echo '<small> | </small>';
         echo '<a id="listslink" href="?do=admin&amp;area=resolution">' . $language['resolutions'] . "</a>\n";
      };

      // Show the amount of admin requests waiting
      if (($permissions['manage_project'] == '1'
          OR $permissions['is_admin'] == '1')
          && $num_req > '0') {
         echo '<small> | </small>';
         echo '<a id="attention" href="?do=admin&amp;area=pendingreq">' . $num_req . ' ' . $language['adminreqwaiting'] . '</a>';

      };

      // End of admin menu span
      echo "</span>\n";

    // End of checking if the admin menu should be displayed
    };

    echo "</p>";

    // If the user's account is closed
    } else {
      echo "<br />{$language['disabledaccount']}";
      Header("Location: ?do=authenticate&action=logout");
    // End of checking if the user's account is open
    };

// End of checking if the user has the right cookies
};

// ERROR status bar
if (isset($_SESSION['ERROR'])) {
   echo '<div id="errorbar" onClick="document.getElementById(\'errorbar\').style.display = \'none\'">' . $_SESSION['ERROR'] . '</div>';
   unset($_SESSION['ERROR']);
};

// SUCCESS status bar
if (isset($_SESSION['SUCCESS'])) {
   echo '<div id="successbar" onClick="document.getElementById(\'successbar\').style.display = \'none\'">' . $_SESSION['SUCCESS'] . '</div>';
   unset($_SESSION['SUCCESS']);
};

?>


<div id="content">

<form action="index.php" method="get">
      <p>
      <select name="tasks">
        <option value="all"><?php echo $language['tasksall'];?></option>
      <?php if ($_COOKIE['flyspray_userid']) { ?>
        <option value="assigned" <?php if($_GET['tasks'] == 'assigned') echo 'selected="selected"'; ?>><?php echo $language['tasksassigned']; ?></option>
        <option value="reported" <?php if($_GET['tasks'] == 'reported') echo 'selected="selected"'; ?>><?php echo $language['tasksreported']; ?></option>
        <option value="watched" <?php if($_GET['tasks'] == 'watched') echo 'selected="selected"'; ?>><?php echo $language['taskswatched']; ?></option>
      <?php }; ?> 
      </select>
      <?php echo $language['selectproject'];?>
      <select name="project">
      <option value="0"<?php if ($_GET['project'] == '0') echo ' selected="selected"';?>><?php echo $language['allprojects'];?></option>
      <?php
      $get_projects = $fs->dbQuery("SELECT * FROM flyspray_projects WHERE project_is_active = ? ORDER BY project_title", array('1'));
      while ($row = $fs->dbFetchArray($get_projects)) {
        if ($project_id == $row['project_id'] && $_GET['project'] != '0') {
          echo '<option value="' . $row['project_id'] . '" selected="selected">' . stripslashes($row['project_title']) . '</option>';
        } else {
          echo '<option value="' . $row['project_id'] . '">' . stripslashes($row['project_title']) . '</option>';
        };
      };
      ?>
      </select>
      <input class="mainbutton" type="submit" value="<?php echo $language['show'];?>" />
      </p>
</form>

<form action="index.php" method="get">
    <p id="showtask">
      <label><?php echo $language['showtask'];?> #
      <input name="id" type="text" size="10" maxlength="10" accesskey="t" /></label>
      <input type="hidden" name="do" value="details" />
      <input class="mainbutton" type="submit" value="<?php echo $language['go'];?>" />
    </p>
</form>

<?php

// Show the project blurb if the project manager defined one
if ($project_prefs['intro_message'] != ''  && $do != 'admin' && $do != 'modify') {
  $intro_message = Markdown(stripslashes($project_prefs['intro_message'])); 
  echo "<p class=\"intromessage\">$intro_message</p>";
};

// If we have allowed anonymous logging of new tasks
// Show the link to the Add Task form
if ($project_prefs['anon_open'] == '1' && !$_COOKIE['flyspray_userid']) {
  echo "<p class=\"unregistered\"><a href=\"?do=newtask&amp;project=$project_id\">{$language['opentaskanon']}</a></p><br />";
};

// Check that this page isn't being submitted twice
if (requestDuplicated()) {
  printf('<meta http-equiv="refresh" content="2; URL=?id=%s">', $project_id);
  printf('<div class="redirectmessage"><p><em>%s</em></p></div>', $language['duplicated']);
  echo '</body></html>';
  exit;
};

// Show the page the user wanted
require("scripts/$do.php");


// if no-one's logged in, show the login box
if(!$_COOKIE['flyspray_userid']) {
  require('scripts/loginbox.php');
};
?>

</div>      
<p id="footer">
<!-- Please don't remove this line - it helps promote Flyspray -->
<a href="http://flyspray.rocks.cc/" class="offsite"><?php printf("%s %s", $language['poweredby'], $fs->version);?></a>
</p>


<?php 
$footerfile = "$basedir/themes/".$project_prefs['theme_style']."/footer.inc.php"; 
if(file_exists("$footerfile")) { 
 include("$footerfile"); 
} 

// Print out permissions stuff for debugging
/*if (isset($_COOKIE['flyspray_userid'])) {
  echo '<br /><h3>Debugging for the new permissions system</h3>';
  echo '<table border="1">';
  while (list($key, $val) = each($permissions)) {
     echo '<tr><td>';
     $key = str_replace('_', ' ', $key);
     echo $key;
     echo '</td>';
     $val = str_replace('0', '<td style="color: red;">No</td>', $val);
     $val = str_replace('1', '<td style="color: green;">Yes</td>', $val);
     echo $val;
     echo '</tr>';
  };
  echo '</table>';
};*/
?> 

</body>
</html>

<?php
// End of file delivery / showing the page
};
?>
