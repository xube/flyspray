<?php

class Tpl
{
    var $_uses  = array();
    var $_vars  = array();
    var $_theme = '';
   
    function uses()
    {
        $args = func_get_args();
        $this->_uses = array_merge($this->_uses, $args);
    }

    function assign($arg0 = null, $arg1 = null)
    {
        if (is_string($arg0)) {
            $this->_vars[$arg0] = $arg1;
        }
        if (is_array($arg0)) {
            $this->_vars += $arg0;
        }
        if (is_object($arg0)) {
            $this->_vars += get_object_vars($arg0);
        }
    }

    function setTheme($theme)
    {
        $this->_theme = str_replace('//', '/', $theme.'/');
    }

    function themeUrl()
    {
        global $baseurl;
        return $baseurl.'themes/'.$this->_theme;
    }

    function _compile($matches)
    {
        list(, $bang, $expr, $br) = $matches;
        if ($bang) {
            return '<?php echo ('.$expr.'); ?>'.$br.$br;
        } else {
            return '<?php echo htmlspecialchars('.$expr.', ENT_QUOTES, "utf-8"); ?>'.$br.$br;
        }
    }
    
    function display($_tpl, $_arg0 = null, $_arg1 = null)
    {
        // theming part
        $_basedir = dirname(dirname(__FILE__)).'/';
        if (file_exists($_basedir.$this->_theme.$_tpl)) {
            $_tpl_data = file_get_contents($_basedir.$this->_theme.$_tpl);
        } else {
            $_tpl_data = file_get_contents($_basedir.'templates/'.$_tpl);
        }

        // compilation part
        preg_match_all('!<\?php.*?\?>!s', $_tpl_data, $_php_blocks);
        $_tpl_data = preg_replace('!<\?php.*?\?>!s', '&&&php&&&', $_tpl_data);

        $_tpl_data = preg_replace_callback(
                '/{(!?)([^ &{][^{]*?)}(\n?)/',
                array($this, '_compile'), $_tpl_data);

        $_tpl_data = str_replace('&lbrace;', '{', $_tpl_data);
        $_tpl_data = str_replace('&rbrace;', '}', $_tpl_data);

        $_tpl_data = preg_replace('!&&&php&&&!e', 'array_shift($_php_blocks[0])', $_tpl_data);

        // variables part
        if (!is_null($_arg0)) {
            $this->assign($_arg0, $_arg1);
        }

        foreach ($this->_uses as $_var) {
            global $$_var;
        }
        extract($this->_vars);
        eval( '?>'.$_tpl_data );
    }
    
    function fetch($tpl, $arg0 = null, $arg1 = null)
    {
        ob_start();
        $this->display($tpl, $arg0, $arg1);
        return ob_get_clean();
    }
}

class FSTpl extends Tpl
{
    var $_uses = array('fs', 'conf', 'baseurl', 'language', 'project_prefs',
            'project_id', 'permissions', 'current_user');
}

// {{{ some useful plugins

function tpl_options($options, $selected = null, $labelIsValue = false, $attr = null)
{
    $html = '';

    // force $selected to be an array.
    // this allows multi-selects to have multiple selected options.
    settype($selected, 'array');
    settype($options, 'array');

    if (is_array($attr)) {
        $arr = array();
        foreach ($attr as $key=>$val) {
            $arr[] = $key.'="'.htmlspecialchars($val, ENT_QUOTES, "utf-8");
        }
        $html_attr = join(' ', $arr);
    }

    foreach ($options as $value=>$label)
    {
        if (is_array($label)) {
            list($value, $label) = $label;
        }
        $label = htmlspecialchars($label, ENT_QUOTES, "utf-8");
        $value = $labelIsValue ? $label
                               : htmlspecialchars($value, ENT_QUOTES, "utf-8");

        $html .= '<option value="'.$value.'"';
        if (in_array($value, $selected)) {
            $html .= ' selected="selected"';
        }
        $html .= ' '.$html_attr.'>'.$label.'</option>';
    }

    return $html;
}

function tpl_checkbox($name, $checked = false, $id = null, $value = 1, $attr = null)
{
    if (is_array($attr)) {
        $arr = array();
        foreach ($attr as $key=>$val) {
            $arr[] = $key.'="'.htmlspecialchars($val, ENT_QUOTES, "utf-8");
        }
        $html_attr = join(' ', $arr);
    }

    $name  = htmlspecialchars($name,  ENT_QUOTES, "utf-8");
    $value = htmlspecialchars($value, ENT_QUOTES, "utf-8");
    $html  = '<input type="checkbox" name="'.$name.'" value="'.$value.'" ';
    if ($id) {
        $html .= 'id='.htmlspecialchars($id, ENT_QUOTES, "utf-8").' ';
    }
    if ($checked) {
        $html .= 'checked="checked" ';
    }

    return $html.$html_attr.' />';
}

// }}}

?>
