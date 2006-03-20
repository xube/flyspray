<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{L('locale')}" xml:lang="{L('locale')}">
  <head>
    <title>{$this->_title}</title>

    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="icon" type="image/png" href="{$this->themeUrl()}favicon.ico" />
    <link media="screen" href="{$this->themeUrl()}theme.css" rel="stylesheet" type="text/css" />
    <link media="print"  href="{$this->themeUrl()}theme_print.css" rel="stylesheet" type="text/css" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 1.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray RSS 2.0 Feed"
          href="{$baseurl}feed.php?feed_type=rss2&amp;project={$proj->id}" />
	<link rel="alternate" type="application/atom+xml" title="Flyspray Atom 0.3 Feed"
	      href="{$baseurl}feed.php?feed_type=atom&amp;project={$proj->id}" />
	      
    <script type="text/javascript" src="{$baseurl}javascript/prototype/prototype.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/script.aculo.us/scriptaculous.js"></script>
<?php if ('index' == $do || 'details' == $do): ?>
    <script type="text/javascript" src="{$baseurl}javascript/{$do}.js"></script>
<?php endif; ?>    
    <script type="text/javascript" src="{$baseurl}javascript/tabs.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/functions.js"></script>
    <script type="text/javascript" src='{$baseurl}javascript/perms.js'></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/lang/calendar-{substr(L('locale'), 0, 2)}.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar-setup_stripped.js"></script>
    <!--[if IE 6]>
    <script type="text/javascript" src="{$baseurl}javascript/ie_hover.js"></script>
    <![endif]-->
  </head>
  <body onload="perms = new Perms('permissions')">
  <div id="container">
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
        <form id="projectselectorform" action="{$baseurl}index.php" method="get">
           <div>
            <button type="submit" value="1" name="switch">{L('switchto')}</button>
            <select name="project">
              {!tpl_options(array_merge(array(0 => L('allprojects')), $project_list), $proj->id)}
            </select>
            &nbsp;            
            <select name="tasks">
              <option value="all">{L('tasksall')}</option>
              <?php
              if (!$user->isAnon()) {
                  echo tpl_options(array(
                              'assigned' => L('tasksassigned'),
                              'reported' => L('tasksreported'),
                              'watched'  => L('taskswatched'),
                              'last'     => L('lastsearch')), Get::val('tasks'));
              }
              ?>
            </select>
            <input type="hidden" name="do" value="{$do}" />
            <?php foreach($_GET as $key => $value): ?>
            <?php if(!in_array($key, array('area', 'id'))) continue; ?>
            <input type="hidden" name="{$key}" value="{$value}" />
            <?php endforeach; ?>
            <button accesskey="u" value="1" name="show" type="submit">{L('show')}</button>
          </div>
        </form>
      </div>

      <div id="showtask">
        <form action="{$baseurl}index.php" method="get">
          <div>
            <button type="submit">{L('showtask')} #</button>
            <input id="taskid" name="show_task" class="text" type="text" size="10" maxlength="10" accesskey="t" />
          </div>
        </form>
      </div>

      <div <?php if (!@$intro_message): ?>style="height:0"<?php endif; ?> id="intromessage">{!@$intro_message} &nbsp;</div>
