<?php
class dokuwiki_TextFormatter
{    
    function render($text, $onyfs = false, $type = null, $id = null, $instructions = null)
    {
        global $conf, $baseurl, $db;
        
        // Unfortunately dokuwiki also uses $conf
        $fs_conf = $conf;
        $conf = array();

        // Dokuwiki generates some notices
        error_reporting(E_ALL ^ E_NOTICE);
        if (!$instructions) {
            include_once(BASEDIR . '/plugins/dokuwiki/inc/parser/parser.php');
        }
        require_once(BASEDIR . '/plugins/dokuwiki/inc/common.php');
        require_once(BASEDIR . '/plugins/dokuwiki/inc/parser/xhtml.php');

        // Create a renderer
        $Renderer = & new Doku_Renderer_XHTML();

        if (!$instructions) {
            $modes = p_get_parsermodes();
            
            $Parser = & new Doku_Parser();
            
            // Add the Handler
            $Parser->Handler = & new Doku_Handler();
            
            // Add modes to parser
            foreach($modes as $mode){
                $Parser->addMode($mode['mode'], $mode['obj']);
            }
            $instructions = $Parser->parse($text);
            for ($i = 0; $i < count($instructions); ++$i) {
                if ($instructions[$i][0] == 'code' && isset($instructions[$i][1][1])) {
                    $instructions[$i][1] = call_user_func_array(array(&$Renderer, $instructions[$i][0]), $instructions[$i][1]);
                    $instructions[$i][0] = 'geshi_cached';
                }
            }
            
            // Cache the parsed text
            if (!is_null($type) && !is_null($id)) {
                $fields = array('content'=> serialize($instructions), 'type'=> $type , 'topic'=> $id,
                                'last_updated'=> time());

                $keys = array('type','topic');
                //autoquote is always true on db class
                $db->Replace('{cache}', $fields, $keys);
            }
        } else {
            $instructions = unserialize($instructions);
        }

        $Renderer->smileys = getSmileys();
        $Renderer->entities = getEntities();
        $Renderer->acronyms = getAcronyms();
        $Renderer->interwiki = getInterwiki();

        $conf = $fs_conf;

        // Loop through the instructions
        foreach ($instructions as $instruction) {
            // Execute the callback against the Renderer
            call_user_func_array(array(&$Renderer, $instruction[0]), $instruction[1]);
        }

        $return = $Renderer->doc;

        // Display the output
        if (Get::val('histring')) {
            $words = explode(' ', Get::val('histring'));
            foreach($words as $word) {
                $return = html_hilight($return, $word);
            }
        }
        
        return $return;
    }
}
?>