<?php
/*
 * Here goes all the functions that are used to enforce permissions policies
 */

function tpl_draw_perms($perms)
{
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
?>
    <div id="permslink">
    <a href="#" onclick="showhidestuff('permissions');"><?php echo $language['permissions'] ?></a>
    </div>
    <div id="permissions">
    <table border="1">
<?php
    foreach ($permissions as $key => $val) {
        if (in_array($key, $perm_fields)) {
            echo '<tr><td>' . str_replace('_', ' ', $key) . '</td>';
            if ($val == '0') {
                echo '<td style="color: red;">No</td></tr>';
            } else {
                echo '<td style="color: green;">Yes</td></tr>';
            }
        }
    }
?>
    </table>
    </div>
<?php
}

// A policy function takes up to 4 arguments :
//  $user     the current user
//  $perms    the current permissions
//  $project  the current project prefs
//  $task     the current task details (if needed)

function can_view_task($user, $perms, $project, $task)
{
    // permissions to view a task (or its dependencies)
    return ( $task['project_is_active'] == '1'
            && ($project['others_view'] == '1' || @$perms['view_tasks'] == '1')
            && (
                ($task['mark_private'] == '1' && $task['assigned_to'] == $user['user_id'])
                || @$perms['manage_project'] == '1'
                || $task['mark_private'] != '1')
           );
}

?>
