<?php

define('IN_FS', true);

require_once('../../header.php');
require_once('../../scripts/index.php');

$task = Flyspray::GetTaskDetails(Post::val('task_id'));

// we better not forget this one ;)
if (!$user->can_edit_task($task)) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

// Import previous values
$args = $task;
if (is_array($args['assigned_to'])) {
    $args['assigned_to'] = implode(';', $task['assigned_to_uname']);
}
$fieldname =Post::val('field');
switch ($fieldname) {
    case 'summary':
        $args['item_summary'] = Post::val('value');
        break;
    
    case 'project':
        $args['project_id'] = Post::num('value');
        break;
    
    case 'progress':
        $args['percent_complete'] = Post::num('value');
        break;
    
    case 'assigned_to':
        $args['assigned_to'] = Post::val('value');
	$fieldname = 'assignedto';
        break;
        
    default:
        // now all the custom fields
        $field = new Field(substr($fieldname,5));
        if ($field->id) {
            $args[$fieldname] = Post::val('value');
        }
}

// Let our backend function do the rest
Backend::edit_task($task, $args);

// let's get the updated value
$task = Flyspray::GetTaskDetails(Post::val('task_id'));
$task['num_assigned'] = count($task['assigned_to']);
$task['assigned_to_name'] = reset($task['assigned_to_name']);
echo tpl_draw_cell($task, $fieldname, '<span class="%s %s">%s</span>');
?>
