<?php

class FlyspraySyntax extends SyntaxPlugin
{
    function getOrder() {
        return 1;
    }

    function isBasic() {
        return true;
    }

    function beforeCache(&$input) {
        $input = preg_replace('|[[:space:]]+[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]|', '<a href="\0">\0</a>', $input);
        $input = preg_replace('/[a-zA-Z0-9._-]+@[a-zA-Z0-9-]+\.[a-zA-Z.]{2,5}/', '<a href="mailto:\0">\0</a>', $input);
    }

    function afterCache(&$input) {
        global $fs;

        // Change FS#123, bug 123 into hyperlinks to tasks
        $look = array('FS#', 'bug ');
        foreach ($fs->projects as $project) {
            $look[] = preg_quote($project['project_prefix'] . '#', '/');
        }

        $input = preg_replace_callback("/\b(" . implode('|', $look) . ")(\d+)\b/", 'tpl_fast_tasklink', $input);
    }
}

?>