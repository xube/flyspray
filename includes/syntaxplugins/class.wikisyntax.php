<?php

class WikiSyntax extends SyntaxPlugin
{
    function getOrder() {
        return 0;
    }

    function code_callback($matches)
    {
        // translate some language names so that simpler names can be used
        $matches[1] = strtr($matches[1], array('html' => 'html4strict', 'php' => 'php-brief'));
        // if we get HTML for example, it's already encoded
        $geshi =& new GeSHi(htmlspecialchars_decode(trim($matches[2], " \t\n\r\0\x0b\xa0"), ENT_QUOTES), $matches[1]);
        $geshi->enable_classes();
        $geshi->set_overall_class('code');
        return $geshi->parse_code();
    }

    function beforeCache(&$input) {
        global $conf;
        // code highlighting
        require_once 'geshi/geshi.php';
        require_once 'Text/wiki.php';

        // transform it a little to save it from evil wiki parser ^^
        $input = preg_replace('#<code (\w+)>(.*)</code>#Uuism', '<code>' . "\n" . '\1' . "\n" . '\2</code>', $input) . ' ';

        // create a Wiki object with the loaded options
        $wiki = & Text_Wiki::singleton('Doku' /*$type = 'Doku'*/);
        $wiki->setRenderConf('xhtml', 'wikilink', 'new_text', '');

        if (isset($conf['general']['doku_url']) && $conf['general']['doku_url']) {
            $wiki->setRenderConf('xhtml', 'wikilink', 'new_url', $conf['general']['doku_url']);
        }
        $wiki->setFormatConf('Xhtml', 'translate', HTML_SPECIALCHARS);
        $wiki->setFormatConf('Xhtml', 'charset', 'utf-8');

        if (@$type == 'Doku') {
            $input = str_replace('\\\\', '', $input);
        }

        $input = @$wiki->transform($input, 'Xhtml');

        $input = preg_replace_callback('#<code>\n?(\w+)\n(.*)</code>#Uuism', array($this, 'code_callback'), $input);
    }

    function getHtmlAfter() {
        return '<button type="button" onclick="showPreview(\'%id\', \'%id_preview\')">' . L('preview') . '</button>';
    }

    /**
	 * Displays a toolbar for formatting text in the DokuWiki Syntax
	 * Uses Javascript. Beware: Ugly code right ahead.
	 *
	 * @param string $textareaId
	 */
	function getHtmlBefore() {
		global $baseurl;

		return '<div class="hide preview" id="%id_preview"></div><div>
        <a href="javascript:void(0);" onclick="surroundText(\'**\', \'**\', \'%id\'); return false;">
		  		<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-bold.png" align="bottom" alt="Bold" title="Bold" border="0" /></a>
			<a href="javascript:void(0);" onclick="surroundText(\'//\', \'//\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-italic.png" align="bottom" alt="Italicized" title="Italicized" border="0" /></a>
			<a href="javascript:void(0);" onclick="surroundText(\'__\', \'__\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-underline.png" align="bottom" alt="Underline" title="Underline" border="0" /></a>

			<a href="javascript:void(0);" onclick="surroundText(\'&lt;del&gt;\', \'&lt;/del&gt;\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/format-text-strikethrough.png" align="bottom" alt="Strikethrough" title="Strikethrough" border="0" /></a>

			<img src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" align="bottom" alt="|" style="margin: 0 3px 0 3px;" />

			<a href="javascript:void(0);" onclick="surroundText(\'======\', \'======\', \'%id\'); return false;">
			<img title="Level 1 Headline" src="'.$baseurl.'includes/syntaxplugins/img/h1.gif" align="bottom" width="23" height="22" alt="Heading1" border="0" /></a>

			<a href="javascript:void(0);" onclick="surroundText(\'=====\', \'=====\', \'%id\'); return false;">
			<img title="Level 2 Headline" src="'.$baseurl.'includes/syntaxplugins/img/h2.gif" align="bottom" width="23" height="22" alt="Heading2" border="0" /></a>

			<a href="javascript:void(0);" onclick="surroundText(\'====\', \'====\', \'%id\'); return false;">
			<img title="Level 3 Headline" src="'.$baseurl.'includes/syntaxplugins/img/h3.gif" align="bottom" width="23" height="22" alt="Heading3" border="0" /></a>

			<img title="Divider" src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<a href="javascript:void(0);" onclick="surroundText(\'&#123;&#123;http://\', \'&#125;&#125;\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/image-x-generic.png" align="bottom" alt="Insert Image" title="Insert Image" border="0" /></a>

			<a href="javascript:void(0);" onclick="replaceText(\'\n  * \', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/ul.gif" align="bottom" width="23" height="22" alt="Insert List" title="Insert List" border="0" /></a>
			<a href="javascript:void(0);" onclick="replaceText(\'\n  - \', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/ol.gif" align="bottom" width="23" height="22" alt="Insert List" title="Insert List" border="0" /></a>
			<a href="javascript:void(0);" onclick="replaceText(\'----\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/hr.gif" align="bottom" width="23" height="22" alt="Horizontal Rule" title="Horizontal Rule" border="0" /></a>

			<img src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<a href="javascript:void(0);" onclick="surroundText(\'[[http://example.com|External Link\', \']]\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/text-html.png" align="bottom" alt="Insert Hyperlink" title="Insert Hyperlink" border="0" /></a>
			<a href="javascript:void(0);" onclick="surroundText(\'[[\', \']]\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/email.png" align="bottom" alt="Insert Email" title="Insert Email" border="0" /></a>
			<a href="javascript:void(0);" onclick="surroundText(\'[[ftp://\', \']]\', \'%id\'); return false;">
				<img src="'.$baseurl.'includes/syntaxplugins/img/network.png" align="bottom" alt="Insert FTP Link" title="Insert FTP Link" border="0" /></a>

			<img src="'.$baseurl.'includes/syntaxplugins/img/divider.gif" alt="|" style="margin: 0 3px 0 3px;" />

			<a href="javascript:void(0);" onclick="surroundText(\'<code>\', \'</code>\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/source.png" align="bottom" alt="Insert Code" title="Insert Code" border="0" /></a>
			<a href="javascript:void(0);" onclick="surroundText(\'<code php>\', \'</code>\', \'%id\'); return false;">
			<img src="'.$baseurl.'includes/syntaxplugins/img/source_php.png" align="bottom" alt="Insert Code" title="Insert PHP Code" border="0" /></a></div>
		';
	}
}

?>