<?php

/*
   This script is used to check/correct all user-inputted data We don't want Flyspray to end
   up on BugTraq!
*/

$check['getfile']       = $check['project'] = $check['page'] = $check['perpage'] = $check['pagenum']
                        = $check['notify_type'] = $check['sev'] = $check['type'] = $check['cat']
                        = $check['due'] = $check['id'] = 'num';
$check['action']        = array('logout', 'newtask', 'update', 'close', 'reopen', 'addcomment', 'chpass', 'registeruser',
                                'newuser', 'newgroup', 'globaloptions', 'newproject', 'updateproject', 'addattachment',
                                'edituser', 'editgroup', 'update_list', 'add_to_list', 'update_category', 'add_category',
                                'add_related', 'remove_related', 'add_notification', 'remove_notification', 'editcomment',
                                'deletecomment', 'deleteattachment', 'addreminder', 'deletereminder', 'update_version_list',
                                'add_to_version_list', 'addtogroup', 'movetogroup', 'requestreopen', 'takeownership',
                                'requestclose', 'newdep', 'removedep', 'sendmagic', 'sendcode', 'makeprivate', 'makepublic',
                                'denypmreq', 'massaddnotify', 'massremovenotify', 'masstakeownership', 'addtoassignees');
$check['area']          = array('comments', 'editcomment', 'attachments', 'related', 'notify', 'status', 'users', 'tt', 'res',
                                'groups', 'remind', 'system', 'history', 'pendingreq', 'prefs', 'cat', 'os', 'ver', 'editgroup',
                                'newproject');
$check['do']            = array('index', 'admin', 'pm', 'reports', 'authenticate', 'chpass', 'roadmap', 'details', 'depends',
                                'loginbox', 'modify', 'newgroup', 'newproject', 'newtask', 'newuser', 'changelog', 'register',
                                'report', 'myprofile', 'lostpw', 'editcomment', 'error');
$check['order']         = array('id', 'proj', 'type', 'date', 'sev', 'cat', 'os', 'status', 'due', 'dateclosed', 'event_date', 'pri',
                                'openedby', 'reportedin', 'assignedto', 'prog', 'duedate');
$check['sort']          = array('asc', 'desc');
$check['report']        = array('summary', 'changelog', 'events', 'severity', 'age');
$check['tasks']         = array('all', 'assigned', 'reported', 'watched');
$check['email_address'] = "/^[a-z0-9._\-']+(?:\+[a-z0-9._-]+)?@([a-z0-9.-]+\.)+[a-z]{2,4}+$/i";
$check['magic']         = '/^[a-zA-Z0-9_-]+$/';
$check[$_SESSION['SESSNAME']] = '/^[a-zA-Z0-9]+$/';
$check['status']        = '/^(\d+|open)$/';
$check['order2']        = $check['order'];
$check['sort2']         = $check['sort'];
$check['jabber_id']     = $check['email_address'];

function check_value(&$value, $allowed) {
    global $fs;
    if (is_array($allowed)) {
        if (!in_array($value, $allowed)) {
            $value = $allowed[0];
        }
    } else if ($allowed == 'num') {
        $value = intval($value);
    } else if($value && !preg_match($allowed, $value)) {
        $fs->Redirect(CreateURL('error', null));
    }
}

foreach($check as $key => $allowed) {
    if (Get::has($key)) {
        if (is_array(Get::val($key))) {
            foreach (Get::val($key) as $num => $value) {
                check_value($_GET[$key][$num], $allowed);
            }
        } else {
            check_value($_GET[$key], $allowed);
        }        
    }

    if (Post::has($key)) {
        if (is_array(Post::val($key))) {
            foreach (Post::val($key) as $num => $value) {
                check_value($_POST[$key][$num], $allowed);
            }
        } else {
            check_value($_POST[$key], $allowed);
        }        
    }
}

?>
