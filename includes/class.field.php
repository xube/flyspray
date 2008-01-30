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
     * @param mixed $field_array int for field ID, array for "ready-to-use" field
     */
    function field($field_array)
    {
        if (!is_array($field_array) || !count($field_array)) {
            global $db;
            $field_array = $db->x->GetRow('SELECT * FROM {fields} f
                                        LEFT JOIN {lists} l ON f.list_id = l.list_id
                                            WHERE field_id = ?', null, array($field_array));
        }

        if (!is_array($field_array) || !count($field_array)) {
            return;
        }

        $this->prefs = $field_array;
        $this->id    = intval($field_array['field_id']);
    }

    /**
     * Returns (safe) HTML which displays a field
     * @access public
     * @param array $task task data
     * @param array $parents parents for a category item
     * @param bool $plain this is **not** meant to be safe, but basically to display content where HTML tags are disturbing
     * @return string
     */
    function view($task = array(), $parents = array(), $plain = false)
    {
        if (!isset($task['field' . $this->id]) || !$task['field' . $this->id]) {
            $html = sprintf('<span class="fade">%s</span>', eL('notspecified'));
        } else {

            $html = '';
            switch ($this->prefs['field_type'])
            {
                case FIELD_LIST:
                    if ($this->prefs['list_type'] == LIST_CATEGORY && isset($parents[$this->id])) {
                        foreach ($parents[$this->id] as $cat) {
                            $html .= Filters::noXSS($cat) . '&#8594;';
                        }
                    }
                    $html .= Filters::noXSS($task['field' . $this->id . '_name']);
                    break;

                case FIELD_DATE:
                    $html .= formatDate($task['field' . $this->id]);
                    break;

                case FIELD_TEXT:
                    $html .= Filters::noXSS($task['field' . $this->id]);
                    break;

                case FIELD_USER:
                    $html .= Filters::noXSS($task['field' . $this->id . '_name']);
                    break;
            }
        }
        return ($plain ? strip_tags(htmlspecialchars_decode($html, ENT_QUOTES)) : $html);
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
    function edit($use_default = true, $lock = false, $task = array(), $add_options = array(), $attrs = array(), $prefix = '')
    {
        global $user, $proj;

        if ($use_default) {
            $task['field' . $this->id] = $this->prefs['default_value'];
        } else if (!isset($task['field' . $this->id])) {
            $task['field' . $this->id] = '';
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

                $html .= sprintf('<select id="%sfield%d" name="%sfield%d%s" %s ',
                                 $prefix, $this->id, $prefix, $this->id, (isset($attrs['multiple']) ? '[]' : ''), join_attrs($attrs));
                $html .= tpl_disableif($lock) . '>';
                $html .= tpl_options(array_merge($add_options, $proj->get_list($this->prefs, $task['field' . $this->id])),
                                     Req::val('field' . $this->id, $task['field' . $this->id]));
                $html .= '</select>';
                break;

            case FIELD_DATE:
                $attrs = array();
                if ($lock) {
                    $attrs = array('readonly' => 'readonly');
                }

                $html .= tpl_datepicker($prefix . 'field' . $this->id, '', Req::val('field' . $this->id, $task['field' . $this->id]), $attrs);
                break;

            case FIELD_TEXT:
                $html .= sprintf('<input type="text" class="text" id="%sfield%d" name="%sfield%d" value="%s"/>',
                                  $prefix, $this->id, $prefix, $this->id, Filters::noXSS(Req::val('field' . $this->id, $task['field' . $this->id]))) ;
                break;

            case FIELD_USER:
                $html .= tpl_userselect($prefix . 'field' . $this->id, Req::val('field' . $this->id, $task['field' . $this->id]));
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
                $value = $input ? Flyspray::strtotime($input) : '';
                // this would be a unix timestamp
                if (is_numeric($input)) {
                    $value = $input;
                }
                break;

            case FIELD_TEXT:
                $value = (string) $input;
                break;

            case FIELD_LIST:
                if ($this->prefs['list_type'] == LIST_CATEGORY) {
                    $check = $db->x->GetOne('SELECT count(*)
                                            FROM {list_category}
                                           WHERE list_id = ? AND category_id = ?',
                                           null, array($this->prefs['list_id'], $input));
                } else {
                    $check = $db->x->GetOne('SELECT count(*)
                                            FROM {list_items}
                                           WHERE list_id = ? AND list_item_id = ?',
                                           null, array($this->prefs['list_id'], $input));
                }
                $value = ($check) ? $input : 0;
                break;

            case FIELD_USER:
                // try to determine a valid user ID if necessary
                $value = Flyspray::UserNameOrId($input);
                break;
        }

        if (!$value || $this->prefs['force_default'] && !$user->perms('modify_all_tasks')) {
            $value = $this->prefs['default_value'];
        }

        return $value;
    }
}
?>
