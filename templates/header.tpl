<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{L('locale')}" xml:lang="{L('locale')}">
  <head>
    <title>{$this->_title}</title>

    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <link rel="icon" type="image/png" href="{$this->get_image('favicon')}" />
    <?php foreach ($project_list as $project): ?>
    <link rel="section" type="text/html" href="{$baseurl}?project={$project[0]}" />
    <?php endforeach; ?>
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
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/lang/calendar-{substr(L('locale'), 0, 2)}.js"></script>
    <!--[if IE 6]>
    <script type="text/javascript" src="{$baseurl}javascript/ie_hover.js"></script>
    <![endif]-->
    <?php foreach(TextFormatter::get_javascript() as $file): ?>
        <script type="text/javascript" src="{$baseurl}plugins/{$file}"></script>
    <?php endforeach; ?>
  </head>
  <body onload="perms = new Perms('permissions')">
  <div id="container">
    <!-- Remove this to remove the logo -->
    <h1 id="title"><a href="{$baseurl}">{$proj->prefs['project_title']}</a></h1>
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
            <?php $check = array('area', 'id');
                  if ($do == 'reports') {
                    $check = array_merge($check, array('open', 'close', 'edit', 'assign', 'repdate', 'comments', 'attachments',
                                    'related', 'notifications', 'reminders', 'within', 'duein', 'fromdate', 'todate'));
                  }
                  foreach ($check as $key):
                  if (Get::has($key)): ?>
            <input type="hidden" name="{$key}" value="{Get::val($key)}" />
            <?php endif;
                  endforeach; ?>
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
      
      <div class="clear"></div>

      <?php if ($proj->prefs['intro_message'] && in_array($do, array('details', 'index', 'newtask', 'reports', 'depends'))): ?>
      <div id="intromessage">{!TextFormatter::render($proj->prefs['intro_message'], false, 'msg', $proj->id,
                               ($proj->prefs['last_updated'] < $proj->prefs['cache_update']) ? $proj->prefs['pm_instructions'] : '')}</div>
      <?php endif; ?>
