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
        switch ($this->prefs['field_type'])
        {
            case FIELD_LIST:
                if ($this->prefs['list_type'] == LIST_CATEGORY) {
                    foreach ($parents[$this->id] as $cat) {
                        $html .= Filters::noXSS($cat) . '&#8594;';
                    }
                }
                $html .= Filters::noXSS($task['f' . $this->id . '_name']);
                break;

            case FIELD_DATE:
                $html .= formatDate($task['f' . $this->id]);
                break;

            case FIELD_TEXT:
                $html .= Filters::noXSS($task['f' . $this->id]);
                break;
        }
        return $html;
    }

    /**
     * Returns (safe) HTML which displays a field to edit a value
     * @access public
     * @param bool $use_default use default field value or not
     * @param bool $lock lock the field depending on the users perms ornot
     * @param array $task task data
     * @param array $add_options add options to the select?
     * @param array $attrs add attributes to the select
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
        switch ($this->prefs['field_type'])
        {
            case FIELD_LIST:
                if (!$this->prefs['list_id']) {
                    return '';
                }

                $html .= '<select id="f' . $this->id . '" name="field' . $this->id . (isset($attrs['multiple']) ? '[]' : '') . '" ' . join_attrs($attrs);
                $html .= tpl_disableif($lock) . '>';
                $html .= tpl_options(array_merge($add_options, $proj->get_list($this->prefs, $task['f' . $this->id])),
                                     Req::val('field' . $this->id, $task['f' . $this->id]));
                $html .= '</select>';
                break;

            case FIELD_DATE:
                $attrs = array();
                if ($lock) {
                    $attrs = array('readonly' => 'readonly');
                }

                $html .= tpl_datepicker('field' . $this->id, '', Req::val('field' . $this->id, $task['f' . $this->id]), $attrs);
                break;

            case FIELD_TEXT:
                $html .= '<input type="text" class="text" id="field' . $this->id . '" name="field' . $this->id . '" value="' .
                          Filters::noXSS($task['f' . $this->id]) . '" />';
                break;
        }

        return $html;
    }

    /**
     * Returns a correct value for the field based on user input
     * @access public
     * @param string $input
     * @return string
     */
    function read($input)
    {
        global $user, $db;

        switch ($this->prefs['field_type'])
        {
            case FIELD_DATE:
                $value = Flyspray::strtotime($input);
                break;

            case FIELD_TEXT:
                $value = (string) $input;
                break;

            case FIELD_LIST:
                if ($this->prefs['list_type'] == LIST_CATEGORY) {
                    $check = $db->GetOne('SELECT count(*)
                                            FROM {list_category}
                                           WHERE list_id = ? AND category_id = ?',
                                           array($this->prefs['list_id'], $input));
                } else {
                    $check = $db->GetOne('SELECT count(*)
                                            FROM {list_items}
                                           WHERE list_id = ? AND list_item_id = ?',
                                           array($this->prefs['list_id'], $input));
                }
                $value = ($check) ? $input : 0;
                break;
        }

        if (!$value || $this->prefs['force_default'] && !$user->perms('modify_all_tasks')) {
            $value = $this->prefs['default_value'];
        }

        return $value;
    }
}
?>