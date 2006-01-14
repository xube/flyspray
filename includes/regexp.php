<?php

/*
   This script is used to check that all user-inputted data is safe to pass to the
   rest of Flyspray, including the sql database.  We don't want Flyspray to end
   up on BugTraq!
*/

$numeric = array('getfile', 'project', 'page', 'perpage', 'pagenum', 'notify_type');

$numeric_array = array('sev', 'type', 'cat', 'due', 'id');

foreach ($numeric_array as $key) {
    if (Get::has($key)) {
        $value = Get::val($key);
        settype($value, 'array');
        foreach ($value as $val) {
            if ($val && !is_numeric($val)) {
                $fs->Redirect($fs->CreateURL('error', null));
            }
        }
    }
}

foreach ($numeric as $key) {
    if (Get::has($key) && !is_numeric(Get::val($key))) {
        $fs->Redirect($fs->CreateURL('error', null));
    }
}

$action_ok = '/^(logout|newtask|update|close|reopen|addcomment|chpass|registeruser|newuser|newgroup|globaloptions|newproject|updateproject|addattachment|edituser|editgroup|update_list|add_to_list|update_category|add_category|add_related|remove_related|add_notification|remove_notification|editcomment|deletecomment|deleteattachment|addreminder|deletereminder|update_version_list|add_to_version_list|addtogroup|movetogroup|requestreopen|takeownership|requestclose|newdep|removedep|sendmagic|sendcode|makeprivate|makepublic|denypmreq|massaddnotify|massremovenotify|masstakeownership|addtoassignees)$/';
$area_ok   = '/^(editcomment|comments|attachments|related|notify|status|users|tt|res|groups|remind|system|history|pendingreq|prefs|cat|os|ver|editgroup|newproject)$/';
$do_ok     = '/^(admin|pm|reports|authenticate|chpass|roadmap|details|depends|index|loginbox|modify|newgroup|newproject|newtask|newuser|changelog|register|report|myprofile|lostpw|editcomment|error)$/';
$email_ok  = "/^[a-z0-9._\-']+(?:\+[a-z0-9._-]+)?@([a-z0-9.-]+\.)+[a-z]{2,4}+$/i";
$order_ok  = '/^(id|proj|type|date|sev|cat|os|status|due|dateclosed|event_date|pri|openedby|reportedin|assignedto|prog|duedate)$/';
$sort_ok   = '/^(asc|desc)$/';
$sessname = $_SESSION['SESSNAME'];

$regexps   = array(
        'action'        => $action_ok,
        'area'          => $area_ok,
        'do'            => $do_ok,
        'email_address' => $email_ok,
        'jabber_id'     => $email_ok,
        'magic'         => '/^[a-zA-Z0-9_-]+$/',
        'order'         => $order_ok,
        'order2'        => $order_ok,
        'report'        => '/^(summary|changelog|events|severity|age)$/',
        'sort'          => $sort_ok,
        'sort2'         => $sort_ok,
        'tasks'         => '/^(all|assigned|reported|watched)$/',
        $sessname       => '/^[a-zA-Z0-9]+$/',
);

$regexps_array   = array(
        'status'        => '/^(\d+|open)$/',
);

foreach ($regexps_array as $key => $regexp) {
    if (Get::has($key)) {
        $value = Get::val($key);
        settype($value, 'array');
        foreach($value as $val) {
            if($val && !preg_match($regexp, $val)) {
                $fs->Redirect($fs->CreateURL('error', null));
            }
        }
    }
}

foreach ($regexps as $key => $regexp) {
    if (Post::has($key) && Post::val($key) && !preg_match($regexp, Post::val($key))) {
        $fs->Redirect($fs->CreateURL('error', null));
    }
    if (Get::has($key) && !preg_match($regexp, Get::val($key))) {
        $fs->Redirect($fs->CreateURL('error', null));
    }
}

?>
