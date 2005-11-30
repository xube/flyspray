<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language['locale']}" xml:lang="{$language['locale']}">
  <head>
    <title>{$this->_title}</title>

    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS Feed"
      href="{$baseurl.'scripts/rss.php?proj='.$proj->id}" />
    <link rel="icon" type="image/png" href="{$this->themeUrl()}/favicon.ico" />
    <link media="screen" href="{$this->themeUrl()}/theme.css" rel="stylesheet" type="text/css" />
    <link media="print"  href="{$this->themeUrl()}/theme_print.css" rel="stylesheet" type="text/css" />
    <style type="text/css">@import url({$baseurl}includes/jscalendar/calendar-win2k-1.css);</style>
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 1.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 2.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss2&amp;project={$proj->id}" />
	<link rel="alternate" type="application/atom+xml" title="Flyspray Atom 0.3 Feed"
	      href="{$baseurl}feed.php?feed_type=atom&amp;project={$proj->id}" />
	      
    <script type="text/javascript" src="{$baseurl}javascript/prototype/prototype.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/tasklist.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/styleswitcher.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/tabs.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/functions.js"></script>
    <script type="text/javascript" src='{$baseurl}javascript/perms.js'></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/lang/calendar-en.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar-setup.js"></script>
    <!--[if IE 6]>
    <script type="text/javascript" src="{$baseurl}javascript/ie_hover.js"></script>
    <![endif]-->
  </head>
  <body onload="perms = new Perms('permissions')">
    <!-- Remove this to remove the logo -->
    <h1 id="title">{$proj->prefs['project_title']}</h1>
    <?php
    if ($user->isAnon()):
        $this->display('loginbox.tpl');
    else:
        $this->display('links.tpl');
    endif; ?>

    <?php if (!empty($_SESSION['ERROR'])): ?>
    <div id="errorbar" onclick="this.style.display='none'">{$_SESSION['ERROR']}</div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['SUCCESS'])): ?>
    <div id="successbar" onclick="this.style.display='none'">{$_SESSION['SUCCESS']}</div>
    <?php endif; ?>

    <div id="content">
      <div id="projectselector">
        <form action="{$baseurl}index.php" method="get">
          <p>
            <select name="tasks">
              <option value="all">{$language['tasksall']}</option>
              <?php
              if (!$user->isAnon()) {
                  echo tpl_options(array(
                              'assigned' => $language['tasksassigned'],
                              'reported' => $language['tasksreported'],
                              'watched'  => $language['taskswatched']), Get::val('tasks'));
              }
              ?>
            </select>
            {$language['selectproject']}
            <select name="project">
              <option value="0">{$language['allprojects']}</option>
              {!tpl_options($project_list, Get::val('project') !== '0' ?  $proj->id : -1)}
            </select>
            <input accesskey="u" class="mainbutton" type="submit" value="{$language['show']}" />
          </p>
        </form>
      </div>

      <div id="showtask">
        <form action="{$baseurl}index.php" method="get">
          <div>
            <label for="taskid">{$language['showtask']} #</label>
            <input id="taskid" name="show_task" type="text" size="10" maxlength="10" accesskey="t" />
            <input class="mainbutton" type="submit" value="{$language['go']}" />
          </div>
        </form>
      </div>

      <div id="intromessage">{!@$intro_message}</div>
