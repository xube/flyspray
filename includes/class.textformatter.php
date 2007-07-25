<?php

// {{{ Text formatting
class TextFormatter
{
    /*
     * Contains all allowed Syntax classes, loaded in constructor
     */
    var $classes = array();
    /*
     * Contains all available class names
     */
    var $classnames = array();

    /*
     * Holds HTML which is added before and after the text area
     * for preview features or a toolbar.
     */
    var $htmlbefore = '';
    var $htmlafter = '';

    function TextFormatter()
    {
        global $proj;

        require_once 'class.syntaxplugin.php';
        $plugins = glob_compat(dirname(__FILE__) . '/syntaxplugins/class.*');

        foreach ($plugins as $plugin) {
            require_once($plugin);
            $class = substr(basename($plugin), 6, -4);
            $this->classnames[] = $class;
            // if wanted, exclude some plugins
            if ($proj->prefs['syntax_plugins'] && strpos($proj->prefs['syntax_plugins'], $class) === false) {
                continue;
            }
            $class = new $class;
            $this->classes[$class->getOrder()] = $class;
            $this->htmlbefore .= $class->getHtmlBefore();
            $this->htmlafter  .= $class->getHtmlAfter();
        }
        ksort($this->classes);
    }

    /*
     * Renders a given $text and returns it
     *
     * @param string $text any input text
     * @param bool $onlyfs specify whether or not to use only the most basic syntax, for closure comments etc
     * @param string $type needed when saving to cache (rota, comm, task etc)
     * @param int $id needed when saving to cache (a task or comment id for example)
     * @param string $instructions data from cache if available
     * @return string
     */
    function render($text, $onlyfs = false, $type = null, $id = null, $instructions = null)
    {
        global $conf, $fs, $db, $proj;

        // get from cache or save
        if (is_string($instructions) && strlen($instructions) > 0) {
            $text = $instructions;
        } else {
            // parse...
            foreach ($this->classes as $class) {
                if (!$onlyfs || $class->isBasic()) {
                    $class->beforeCache($text);
                }
            }
            // ...and save
            if (!is_null($type) && !is_null($id)) {
                $classnames = array_map('get_class', $this->classes);
                $fields = array('content'=> array('value' => $text),
                                'project_id'=> array('value' => $proj->id),
                                'type'=> array('value' => $type, 'key' => true) ,
                                'topic'=> array('value' => $id, 'key' => true),
                                'syntax_plugins'=> array('value' => strtolower(implode(' ', $classnames))),
                                'last_updated'=> array('value' => time()));

                $db->Replace('{cache}', $fields);
            }
        }

        // after cache:
        foreach ($this->classes as $class) {
            if (!$onlyfs || $class->isBasic()) {
                $class->afterCache($text);
            }
        }

        return $text;
    }

    function textarea($name, $rows, $cols, $attrs = null, $content = null)
    {
        global $conf;

        $name = Filters::noXSS($name);

        $return = sprintf('<textarea name="%s" id="%s" cols="%s" rows="%s" ', $name, $name, intval($cols), intval($rows));
        if (is_array($attrs) && count($attrs)) {
            $return .= join_attrs($attrs);
        }
        $return .= '>';
        if (is_string($content) && strlen($content)) {
            $return .= Filters::noXSS($content);
        }
        $return .= '</textarea>';
        return str_replace('%id', $name, $this->htmlbefore) . $return . str_replace('%id', $name, $this->htmlafter);
    }
}
// }}}