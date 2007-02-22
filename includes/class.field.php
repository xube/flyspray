<?php
/**
 * Flyspray
 *
 * Field class
 *
 * This script contains all the functions we use often with fields, to
 * not have all the logic in templates.
 *
 * @license http://opensource.org/licenses/lgpl-license.php Lesser GNU Public License
 * @package flyspray
 * @author Florian Schmitz
 */

class Field
{
    var $prefs = array();
    var $id = 0;

    /**
     * Initialise a field, uses an array based on SQL query
     * @access public
     * @param array $field_array
     */
    function field($field_array)
    {
        if (!is_array($field_array) || !count($field_array)) {
            trigger_error('Invalid initialisation data for Fields()!', E_USER_ERROR);
        }

        $this->prefs = $field_array;
        $this->id    = intval($field_array['field_id']);
    }

    /**
     * Returns (safe) HTML which displays a field
     * @access public
     * @param array $task task data
     * @param array $parents parents for a category item
     * @return string
     */
    function view($task = array(), $parents = array())
    {
        if (!isset($task['f' . $this->id]) || !$task['f' . $this->id]) {
            return '<span class="fade">' . eL('notspecified') . '</span>';
        }

        $html = '';
        if ($task['f' . $this->id . '_name']) {
            if ($this->prefs['list_type'] == LIST_CATEGORY) {
                foreach ($parents[$this->id] as $cat) {
                    $html .= htmlspecialchars($cat, ENT_QUOTES, 'utf-8') . '&#8594;';
                }
            }
            $html .= htmlspecialchars($task['f' . $this->id . '_name'], ENT_QUOTES, 'utf-8');
        } elseif ($this->prefs['field_type'] == FIELD_DATE) {
            $html .= formatDate($task['f' . $this->id]);
        }
        return $html;
    }

    /**
     * Returns (safe) HTML which displays a field to edit a value
     * @access public
     * @param array $task task data
     * @return string
     */
    function edit($use_default = true, $lock = false, $task = array(), $add_options = array(), $attrs = array())
    {
        global $user, $proj;

        if ($use_default) {
            $task['f' . $this->id] = $this->prefs['default_value'];
        } else if (!isset($task['f' . $this->id])) {
            $task['f' . $this->id] = '';
        }

        // determine whether or not to lock inputs
        $lock = $lock && $this->prefs['force_default'] &&
                         (count($task) > 3 && !$user->can_edit_task($task) || !$user->perms('modify_all_tasks'));

        $html = '';
        if ($this->prefs['list_id'] && $this->prefs['field_type'] == FIELD_LIST) {
            $html .= '<select id="f' . $this->id . '" name="field' . $this->id . (isset($attrs['multiple']) ? '[]' : '') . '" ' . join_attrs($attrs);
            $html .= tpl_disableif($lock) . '>';
            $html .= tpl_options(array_merge($add_options, $proj->get_list($this->prefs, $task['f' . $this->id])),
                                 Req::val('field' . $this->id, $task['f' . $this->id]));
            $html .= '</select>';
        } elseif ($this->prefs['field_type'] == FIELD_DATE) {
            $attrs = array();
            if ($lock) {
                $attrs = array('readonly' => 'readonly');
            }

            $html .= tpl_datepicker('field' . $this->id, '', Req::val('field' . $this->id, $task['f' . $this->id]), $attrs);
        }

        return $html;
    }
}
?>