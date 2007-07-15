<?php

define('IN_FS', true);

require_once('../../header.php');
require_once('../../scripts/index.php');
$baseurl = dirname(dirname($baseurl)) .'/' ;

// first, find out about the field we are going to edit
$classnames = explode(' ', Post::val('classname'));
$field = '';
foreach ($classnames as $name) {
    if (substr($name, 0, 5) == 'task_') {
        $field = Filters::noXSS(substr($name, 5));
    }
}

// spare unnecessary queries
if (!$field) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$task = Flyspray::GetTaskDetails(Post::val('task_id'));

// we better not forget this one ;)
if (!$user->can_edit_task($task)) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

// pre build some HTML
$prev = Filters::noXSS(str_replace("'", "\'", tpl_draw_cell($task, $field, '<span class="%s %s">%s</span>')));
$id = sprintf('id="%s" name="%s"', $field, $field);

switch ($field)
{
    case 'summary':
        echo '<input type="text" class="text" '. $id . ' value="'. Filters::noXSS($task['item_summary']) .'" />';
        break;
        
    case 'project':
        echo '<select '. $id . '>'
			    . tpl_options($fs->projects, $task['project_id']) . '
		      </select>';
            break;
            
    case 'progress':
        $arr = array(); for ($i = 0; $i<=100; $i+=10) $arr[$i] = $i.'%';
        echo '<select '. $id . '>'
                . tpl_options($arr, $task['percent_complete']) . '
              </select>';
        break;
    
    default:
        // consider custom fields
        $field_id = substr($field, 5);
        $f = new Field($field_id);
        if ($f->id) {
            echo $f->edit(!USE_DEFAULT, !LOCK_FIELD, $task, array(), array(), 'qe');
            $field = 'qe' . $field;
        } else {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }
        break; 
}

$args = sprintf("%s, '%s'", $task['task_id'], $field);
echo '<button type="button" onclick="savequickedit(' . $args . ')">'.eL('OK').'</button>
      <button type="button" onclick="this.parentNode.update(\''. $prev .'\')">X</button>';
echo "<script type='text/javascript'>$('{$field}').focus();</script>";
?>