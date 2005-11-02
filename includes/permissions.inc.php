<?php
/*
 * Here goes all the functions that are used to enforce permissions policies
 */

function tpl_draw_perms($perms)
{
    global $language;
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
    $yesno = array(
            '<td style="color: red;">No</td>',
            '<td style="color: green;">Yes</td>');

    $html = '<table border="1">';
    
    foreach ($perms as $key => $val) {
        if (in_array($key, $perm_fields)) {
            $html .= '<tr><td>' . str_replace('_', ' ', $key) . '</td>';
            $html .= $yesno[(bool)$val].'</tr>';
        }
    }
    return $html . '</table>';
}

function can_view_task($task)
{
    global $user, $proj;
    // permissions to view a task (or its dependencies)
    return $task['project_is_active']
        && ($proj->prefs['others_view'] || $user->perms['view_tasks'])
        && ( ($task['mark_private'] && $task['assigned_to'] == $user->id)
                || $user->perms['manage_project'] || !$task['mark_private']);
}

function can_modify_task($task)
{
    global $user;
    return $user->perms['modify_all_tasks'] ||
        ($user->perms['modify_own_tasks'] && $task['assigned_to'] == $user->id);
}

function can_take_ownership($task)
{
    global $user;
    return ($user->perms['assign_to_self'] && empty($task['assigned_to']))
        || ($user->perms['assign_others_to_self'] && $task['assigned_to'] != $user->id);
}

function can_close_task($task)
{
    global $user;
    return ($user->perms['close_own_tasks'] && $task['assigned_to'] == $user->id)
        || $user->perms['close_other_tasks'];
}

function can_create_user() {
    // Make sure that only admins are using this page, unless
    // The application preferences allow anonymous signups
    global $user;
    return $user->perms['is_admin']
        || ( !$fs->prefs['spam_proof'] && $fs->prefs['anon_reg'] && $user->isAnon() );
}

function can_create_group() {
    global $user;
    return $user->perms['is_admin'] == '1'
        || ($user->perms['manage_project'] == '1' && !Get::val('project'));
}

?>
