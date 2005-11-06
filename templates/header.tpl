<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$language['locale']}" xml:lang="{$language['locale']}">
  <head>
    <title>Flyspray::&nbsp;&nbsp;{$proj->prefs['project_title']}:&nbsp;&nbsp;</title>

    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS Feed"
      href="{$baseurl.'scripts/rss.php?proj='.$proj->id}" />
    <link rel="icon" type="image/png" href="{$this->themeUrl()}/favicon.ico" />
    <link media="screen" href="{$this->themeUrl()}/theme.css" rel="stylesheet" type="text/css" />
    <link media="print"  href="{$this->themeUrl()}/theme_print.css" rel="stylesheet" type="text/css" />
    <style type="text/css">@import url({$baseurl}includes/jscalendar/calendar-win2k-1.css);</style>

    <script type="text/javascript" src="{$baseurl}includes/styleswitcher.js"></script>
    <script type="text/javascript" src="{$baseurl}includes/tabs.js"></script>
    <script type="text/javascript" src="{$baseurl}includes/functions.js"></script>
    <script type="text/javascript" src="{$baseurl}includes/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="{$baseurl}includes/jscalendar/lang/calendar-en.js"></script>
    <script type="text/javascript" src="{$baseurl}includes/jscalendar/calendar-setup.js"></script>
    <!--[if IE 6]>
    <script type="text/javascript" src="{$baseurl}includes/ie_hover.js"></script>
    <![endif]-->
  </head>
  <body>
    <?php if ($proj->prefs['show_logo']): ?>
    <h1 id="title"><span>{$proj->prefs['project_title']}</span></h1>
    <?php endif; ?>
    <?php
    if ($user->isAnon()):
        $this->display('loginbox.tpl');
    else:
        $this->display('links.tpl');
    ?>
    <div id="permslink">
      <a href="#" onclick="showhidestuff('permissions');">{$language['permissions']}</a>
    </div>
    <div id="permissions" onclick="showhidestuff('permissions');">
      {!tpl_draw_perms($user->perms)}
    </div>
    <?php endif; ?>

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
            <input class="mainbutton" type="submit" value="{$language['show']}" />
          </p>
        </form>
      </div>

      <form action="{$baseurl}index.php" method="get">
        <p id="showtask">
          <label>{$language['showtask']} #
          <input id="taskid" name="show_task" type="text" size="10" maxlength="10" accesskey="t" /></label>
          <input class="mainbutton" type="submit" value="{$language['go']}" />
        </p>
      </form>

      <div id="intromessage">{!@$intro_message}</div>
