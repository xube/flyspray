<?php
define('DOKU_PLUGIN', $conf['general']['basedir'] . 'includes/dokuwiki/lib/plugins/');
define('DOKU_CONF', $conf['general']['basedir'] . 'includes/dokuwiki/conf/');
define('DOKU_INTERNAL_LINK', $conf['general']['doku_url']);
define('DOKU_BASE', $baseurl .'includes/dokuwiki/');
define('DOKU_URL', $baseurl .'includes/dokuwiki/');

class Tpl
{
    var $_uses  = array();
    var $_vars  = array();
    var $_theme = '';
    var $_tpls  = array();
    var $_title = "";

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

    function setTitle($title)
    {
        $this->_title = $title;
    }

    function themeUrl()
    {
        global $baseurl;
        return $baseurl.'themes/'.$this->_theme;
    }

    function compile(&$item)
    {
        if (strncmp($item, '<?', 2)) {
            $item = preg_replace( '/{!([^\s&][^{}]*)}(\n?)/', '<?php echo \1; ?>\2\2', $item);
            $item = preg_replace( '/{([^\s&][^{}]*)}(\n?)/',
                    '<?php echo htmlspecialchars(\1, ENT_QUOTES, "utf-8"); ?>\2\2', $item);
        }
    }
    // {{{ Display page
    function pushTpl($_tpl)
    {
        $this->_tpls[] = $_tpl;
    }
    
    function catch_start()
    {
        ob_start();
    }
    
    function catch_end()
    {
        $this->_tpls[] = array(ob_get_contents());
        ob_end_clean();
    }
    
    function display($_tpl, $_arg0 = null, $_arg1 = null)
    {
        // if only plain text
        if(is_array($_tpl)) {
            echo $_tpl[0];
            return;
        }
        
        // theming part
        $_basedir = dirname(dirname(__FILE__)).'/';
        if (file_exists($_basedir.$this->_theme.$_tpl)) {
            $_tpl_data = file_get_contents($_basedir.$this->_theme.$_tpl);
        } else {
            $_tpl_data = file_get_contents($_basedir.'templates/'.$_tpl);
        }

        // compilation part
        $_tpl_data = preg_split('!(<\?php.*\?>)!sU', $_tpl_data, -1,
                PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        array_walk($_tpl_data, array($this, 'compile'));
        $_tpl_data = join('', $_tpl_data);

        $_tpl_data = str_replace('&lbrace;', '{', $_tpl_data);
        $_tpl_data = str_replace('&rbrace;', '}', $_tpl_data);

        // variables part
        if (!is_null($_arg0)) {
            $this->assign($_arg0, $_arg1);
        }

        foreach ($this->_uses as $_var) {
            global $$_var;
        }
        extract($this->_vars);
        eval( '?>'.$_tpl_data );
    } // }}}

    function render()
    {
        while (count($this->_tpls)) {
            $this->display(array_shift($this->_tpls));
        }
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
    var $_uses = array('fs', 'conf', 'baseurl', 'language', 'proj', 'user');
}

// {{{ costful templating functions, TODO: optimize them

function tpl_tasklink($task, $text = null, $strict = false, $attrs = array(), $title = array('status','summary','assignedto','percent_complete'))
{
    global $fs, $details_text, $user, $db, $proj;
    $fs->get_language_pack('modify');
    global $modify_text;

    if (!is_array($task)) {
        $task = $fs->GetTaskDetails($task);
    }

    if($strict && !$user->can_view_task($task)) {
        return '';
    }

    if ($user->can_view_task($task)) {
        $summary = htmlspecialchars(substr($task['item_summary'], 0, 64), ENT_QUOTES, 'utf-8');
    } else {
        $summary = $modify_text['taskmadeprivate'];
    }
    
    $title_text = array();

    foreach($title as $info)
    {
        switch($info)
        {
            case 'status':
                if ($task['is_closed']) {
                    if (!isset($task['resolution_name'])) {
                        $task = $fs->GetTaskDetails($task['task_id']);
                    }
                    $title_text[] = $task['resolution_name'];
                    $attrs['class'] = 'closedtasklink';
                } else {
                    $title_text[] = $task['status_name'];
                }
                break;
            
            case 'summary':
                $title_text[] = $summary;
                break;
            
            case 'assignedto':
                if($task['assigned_to']) {
                    if (!isset($task['assigned_to_name'])) {
                        $task = $fs->GetTaskDetails($task['task_id']);
                    }
                    $title_text[] = implode(', ', $task['assigned_to_name']);
                }
                break;
            
            case 'percent_complete':
                if($task['percent_complete']) {
                    $title_text[] = $task['percent_complete'].'%';
                }
                break;
            
            case 'category':
                if($task['product_category']) {
                    if (!isset($task['category_name'])) {
                        $task = $fs->GetTaskDetails($task['task_id']);
                    }
                    $title_text[] = $task['category_name'];
                }
                break;
            
            // ... more options if necessary
        }
    }
    
    $title_text = implode(' | ', $title_text);

    if (is_null($text)) {
        $text = 'FS#'.$task['task_id'].' - '.$summary;
    }
    
    $url = htmlspecialchars($fs->CreateURL('details', $task['task_id'],  null, array('string' => Get::val('string'))));
    $link  = sprintf('<a href="%s" title="%s" %s>%s</a>',
            $url, $title_text, join_attrs($attrs), $text);

    if ($task['is_closed']) {
        $link = '<del>&#160;' . $link . '&#160;</del>';
    }
    return $link;
}

function tpl_userlink($uid)
{
    global $db, $fs;
    global $details_text;

    static $cache = array();

    if (empty($cache[$uid])) {
        $sql = $db->Query("SELECT user_name, real_name FROM {users} WHERE user_id = ?",
                array($uid));
        if ($db->countRows($sql)) {
            list($uname, $rname) = $db->fetchRow($sql);
            $cache[$uid] = '<a href="'.htmlspecialchars($fs->createUrl('user', $uid)).'">'
                .htmlspecialchars($rname, ENT_QUOTES, 'utf-8').' ('
                .htmlspecialchars($uname, ENT_QUOTES, 'utf-8').')</a>';
        } else {
            $cache[$uid] = $details_text['anonymous'];
        }
    }

    return $cache[$uid];
}

function tpl_fast_tasklink($arr)
{
    return tpl_tasklink($arr[1], $arr[0]);
}

// }}}
// {{{ some useful plugins

function join_attrs($attr = null) {
    if (is_array($attr)) {
        $arr = array();
        foreach ($attr as $key=>$val) {
            $arr[] = $key.'="'.htmlspecialchars($val, ENT_QUOTES, "utf-8").'"';
        }
        return ' '.join(' ', $arr);
    }
    return '';
}
// {{{ Datepicker
function tpl_datepicker($name, $novaldesc, $shortdesc, $value = null) {
    global $fs, $user;
    
    if (($date = $value) || ($date = Req::val($name . 'date'))) {
        $show_date = $shortdesc . ' ' . $date;
    } else {
        $date  = '0';
        $show_date = $novaldesc;
    }
    
    //$format_id  = $extended ? 'dateformat_extended' : 'dateformat';
    $dateformat = '';
    if(!$user->isAnon()) {
        $dateformat = $user->infos['dateformat'];
    }

    if(!$dateformat) {
        $dateformat = $fs->prefs['dateformat'];
    }

    if(!$dateformat) {
        $dateformat = $extended ? '%A, %d %B %Y, %I:%M%p' : '%Y-%m-%d';
    }
    
    $page = new Tpl;
    $page->assign('show_date', $show_date);
    $page->assign('name', $name);
    $page->assign('date', $date);
    $page->assign('fs', $fs);
    $page->assign('shortdesc', $shortdesc);
    $page->assign('novaldesc', $novaldesc);
    $page->assign('dateformat', $dateformat);
    $page->display('datepicker.tpl');
}
// }}}
// {{{ Options for a <select>
function tpl_options($options, $selected = null, $labelIsValue = false, $attr = null)
{
    $html = '';

    // force $selected to be an array.
    // this allows multi-selects to have multiple selected options.
    settype($selected, 'array');
    settype($options, 'array');

    foreach ($options as $value=>$label)
    {
        if (is_array($label)) {
            $value = $label[0];
            $label = $label[1];
        }
        $label = htmlspecialchars($label, ENT_QUOTES, "utf-8");
        $value = $labelIsValue ? $label
                               : htmlspecialchars($value, ENT_QUOTES, "utf-8");

        $html .= '<option value="'.$value.'"';
        if (in_array($value, $selected)) {
            $html .= ' selected="selected"';
        }
        $html .= join_attrs($attr).'>'.$label.'</option>';
    }
    if (!$html) {
        $html .= '<option>---</option>';
    }

    return $html;
} // }}}
// {{{ Double <select>
function tpl_double_select($name, $options, $selected = null, $labelIsValue = false, $updown = true)
{
    static $_id = 0;
    static $tpl = null;
    
    if (!$tpl) {
        // poor man's cache
        $tpl = new FSTpl();
    }

    settype($selected, 'array');
    settype($options, 'array');

    $tpl->assign('id', '_task_id_'.($_id++));
    $tpl->assign('name', $name);
    $tpl->assign('selected', $selected);
    $tpl->assign('updown', $updown);
    
    $html = $tpl->fetch('common.dualselect.tpl');

    $selectedones = array();

    $opt1 = '';
    foreach ($options as $value => $label) {
        if (is_array($label)) {
            $value = $label[0];
            $label = $label[1];
        }
        if ($labelIsValue) {
            $value = $label;
        }
        if (in_array($value, $selected)) {
            $selectedones[$value] = $label;
            continue;
        }
        $label = htmlspecialchars($label, ENT_QUOTES, "utf-8");
        $value = htmlspecialchars($value, ENT_QUOTES, "utf-8");

        $opt1 .= sprintf('<option title="%2$s" value="%1$s">%2$s</option>', $value, $label);
    }

    $opt2 = '';
    foreach ($selected as $value) {
        $label = htmlspecialchars($selectedones[$value], ENT_QUOTES, "utf-8");
        $value = htmlspecialchars($value, ENT_QUOTES, "utf-8");

        $opt2 .= sprintf('<option title="%2$s" value="%1$s">%2$s</option>', $value, $label);
    }

    return sprintf($html, $opt1, $opt2);
} // }}}
// {{{ Checkboxes
function tpl_checkbox($name, $checked = false, $id = null, $value = 1, $attr = null)
{
    $name  = htmlspecialchars($name,  ENT_QUOTES, "utf-8");
    $value = htmlspecialchars($value, ENT_QUOTES, "utf-8");
    $html  = '<input type="checkbox" name="'.$name.'" value="'.$value.'" ';
    if ($id) {
        $html .= 'id="'.htmlspecialchars($id, ENT_QUOTES, "utf-8").'" ';
    }
    if ($checked) {
        $html .= 'checked="checked" ';
    }

    return $html.join_attrs($attr).' />';
} // }}}
// {{{ Image display
function tpl_img($src, $alt)
{
    global $baseurl;
    if (file_exists(dirname(dirname(__FILE__)).'/'.$src)) {
        return '<img src="'.$baseurl
            .htmlspecialchars($src, ENT_QUOTES,'utf-8').'" alt="'
            .htmlspecialchars($alt, ENT_QUOTES,'utf-8').'" />';
    }
} // }}}
// {{{ Text formatting
function tpl_formattext($text, $onyfs = false)
{
    global $conf, $baseurl;
    if ($conf['general']['wiki_syntax'] && !$onyfs) {
        // Unfortunately dokuwiki also uses $conf
        $fs_conf = $conf;
        $conf = array();
        
        // Dokuwiki generates some notices
        error_reporting(E_ALL ^ E_NOTICE);
        
        require_once('dokuwiki/inc/parser/parser.php');
        require_once('dokuwiki/inc/common.php');
        require_once('dokuwiki/inc/parser/xhtml.php');
        
        $modes = p_get_parsermodes();
        
        $Parser = & new Doku_Parser();
        
        // Add the Handler
        $Parser->Handler = & new Doku_Handler();
        
        //add modes to parser
        foreach($modes as $mode){
            $Parser->addMode($mode['mode'], $mode['obj']);
        }
        
        // Do the parsing
        $instructions = $Parser->parse($text);
        
        // Create a renderer
        $Renderer = & new Doku_Renderer_XHTML();
        
        $Renderer->smileys = getSmileys();
        $Renderer->entities = getEntities();
        $Renderer->acronyms = getAcronyms();
        $Renderer->interwiki = getInterwiki();
        
        // Loop through the instructions
        foreach ($instructions as $instruction) {
            // Execute the callback against the Renderer
            call_user_func_array(array(&$Renderer, $instruction[0]), $instruction[1]);
        }
        
        $conf = $fs_conf;
        
        // Display the output
        if (Get::val('string')) {
            $words = explode(' ', Get::val('string'));
            $ret = $Renderer->doc;
            foreach($words as $word) {
                $ret = html_hilight($ret, $word);
            }
            return $ret;
        } else {
            return $Renderer->doc;
        }
   } else {
        $text = nl2br(htmlspecialchars($text));
        
        // Change URLs into hyperlinks
        if(!$onyfs) $text = preg_replace('|[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]|', '<a href="\0">\0</a>', $text);
        
        // Change FS#123 into hyperlinks to tasks
        return preg_replace_callback("/\b(?:FS#|bug )(\d+)\b/",
            'tpl_fast_tasklink', $text);
   }
}
// }}}
// {{{ Draw permissions table
function tpl_draw_perms($perms)
{
    global $language,$proj;

    $perm_fields = array('is_admin', 'manage_project', 'view_tasks',
            'open_new_tasks', 'modify_own_tasks', 'modify_all_tasks',
            'view_comments', 'add_comments', 'edit_comments', 'delete_comments',
            'view_attachments', 'create_attachments', 'delete_attachments',
            'view_history', 'close_own_tasks', 'close_other_tasks',
            'assign_to_self', 'assign_others_to_self', 'view_reports',
            'global_view');

    $yesno = array(
            '<td class="bad">No</td>',
            '<td class="good">Yes</td>');

    // FIXME: html belongs in a template, not in the template class
    $html = '<table border="1" onmouseover="perms.show()" onmouseout="perms.hide()">';
    $html .= '<thead><tr><th colspan="2">'.$language['permissionsforproject'].$proj->prefs['project_title'].'</th></tr></thead><tbody>';

    foreach ($perms as $key => $val) {
        if (!is_numeric($key) && in_array($key, $perm_fields)) {
            $html .= '<tr><th>' . str_replace('_', ' ', $key) . '</th>';
            $html .= $yesno[(bool)$val].'</tr>';
        }
    }
    return $html . '</tbody></table>';
} // }}}

/**
 * Highlights searchqueries in HTML code
 *
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Harry Fuecks <hfuecks@gmail.com>
 */
function html_hilight($html,$query){
  //split at common delimiters
  $queries = preg_split ('/[\s\'"\\\\`()\]\[?:!\.{};,#+*<>]+/',$query,-1,PREG_SPLIT_NO_EMPTY);
  foreach ($queries as $q){
     $q = preg_quote($q,'/');
     $html = preg_replace_callback("/((<[^>]*)|$q)/i",'html_hilight_callback',$html);
  }
  return $html;
}

/** 
 * Callback used by html_hilight()
 *
 * @author Harry Fuecks <hfuecks@gmail.com>
 */     
function html_hilight_callback($m) {
  $hlight = unslash($m[0]);
  if ( !isset($m[2])) {
    $hlight = '<span class="search_hit">'.$hlight.'</span>';
  }
  return $hlight;
}

function tpl_disableif($if)
{
    if ($if) {
        return 'disabled="disabled"';
    }
}
// }}}
?>
