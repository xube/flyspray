<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{L('locale')}" xml:lang="{L('locale')}">
  <head>
    <title>{$this->_title}</title>

    <meta name="description" content="Flyspray, a Bug Tracking System written in PHP." />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="Content-Script-Type" content="text/javascript" />
    <meta http-equiv="Content-Style-Type" content="text/css" />

    <link rel="icon" type="image/png" href="{$this->get_image('favicon')}" />
    <link rel="index" id="indexlink" type="text/html" href="{$baseurl}" />
    <?php foreach ($fs->projects as $project): ?>
    <link rel="section" type="text/html" href="{$baseurl}?project={$project['project_id']}" />
    <?php endforeach; ?>
    <link media="screen" href="{$this->themeUrl()}theme.css" rel="stylesheet" type="text/css" />
    <link media="print"  href="{$this->themeUrl()}theme_print.css" rel="stylesheet" type="text/css" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray Feed - {L('taskopened')}" href="{$baseurl}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray Feed - {L('taskclosed')}" href="{$baseurl}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link rel="alternate" type="application/rss+xml" title="Flyspray Feed - {L('taskedited')}" href="{$baseurl}feed.php?feed_type=rss1&amp;project={$proj->id}" />
    <link title="{$proj->prefs['project_title']} - Flyspray" type="application/opensearchdescription+xml" rel="search" href="{$baseurl}index.php?opensearch=1&amp;project_id={$proj->id}" />

    <script type="text/javascript" src="{$baseurl}javascript/prototype/prototype.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/script.aculo.us/builder.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/script.aculo.us/effects.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/script.aculo.us/controls.js"></script>
    <?php if ('index' == $do || 'details' == $do): ?>
        <script type="text/javascript" src="{$baseurl}javascript/{$do}.js"></script>
    <?php endif; ?>
    <?php if ( $do == 'pm' || $do == 'admin'): ?>
        <script type="text/javascript" src="{$baseurl}javascript/tablecontrol.js"></script>
    <?php endif; ?>
    <script type="text/javascript" src="{$baseurl}javascript/tabs.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/functions.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar_stripped.js"></script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/calendar-setup_stripped.js"> </script>
    <script type="text/javascript" src="{$baseurl}javascript/jscalendar/lang/calendar-{substr(L('locale'), 0, 2)}.js"></script>
    <!--[if IE]>
    <link media="screen" href="{$this->themeUrl()}ie.css" rel="stylesheet" type="text/css" />
    <![endif]-->
    <?php foreach(TextFormatter::get_javascript() as $file): ?>
        <script type="text/javascript" src="{$baseurl}plugins/{$file}"></script>
    <?php endforeach; ?>
  </head>
  <body onload="perms = new Perms('permissions');<?php
        if (isset($_SESSION['SUCCESS'])):
        ?>window.setTimeout('Effect.Fade(\'successbar\', &lbrace;duration:.3&rbrace;)', 8000);<?php
        elseif (isset($_SESSION['ERROR'])):
        ?>window.setTimeout('Effect.Fade(\'errorbar\', &lbrace;duration:.3&rbrace;)', 8000);<?php endif ?>">

  <div id="container">
