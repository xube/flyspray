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
    $args['assigned_to'] = implode(';', $task['assigned_to']);
}

switch (Post::val('field')) {
    case 'summary':
        $args['item_summary'] = Post::val('value');
        break;
    
    case 'project':
        $args['project_id'] = Post::num('value');
        break;
    
    case 'progress':
        $args['percent_complete'] = Post::num('value');
        break;
        
    default:
        // now all the custom fields
        $field = new Field(substr(5, Post::val('field')));
        if ($field->id) {
            $args[Post::val('field')] = Post::val('value');
        }
}

// Let our backend function do the rest
Backend::edit_task($task, $args);

// let's get the updated value
echo tpl_draw_cell(Flyspray::GetTaskDetails(Post::val('task_id')), Post::val('field'), '<span class="%s %s">%s</span>');
?>