<?php

class SmileySyntax extends SyntaxPlugin
{
    function getOrder() {
        return 56;
    }

    function beforeCache(&$input) {
        global $baseurl;

        $smileys = array(
            '8-O' => 'omg.png',
            '8-o' => 'omg.png',
            ':-(' => 'cry.png',
            ':-)' => 'smile.png',
            ':-/' => 'confused.png',
            ':-\\' => 'confused.png',
            ':-?' => 'confused.png',
            ':-D' => 'lol.png',
            ':-P' => 'tongue.png',
            ':-o' => 'omg.png',
            ':-O' => 'omg.png',
            ':-|' => 'neutral.png',
            ';-)' => 'wink.png',
            '^_^' => 'happy.png',
            'LOL' => 'lol.png');

        // generate HTML
        foreach ($smileys as $key => $smiley) {
            $smileys[$key] = sprintf('<img src="%sincludes/%s/smilieys/%s" height="15" width="15" alt="%s" />',
                                     $baseurl, basename(dirname(__FILE__)), $smiley, $key);
        }

        $input = str_replace(array_keys($smileys), array_values($smileys), $input);
    }
}

?>