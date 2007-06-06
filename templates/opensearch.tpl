<?php header('Content-Type: application/xml; charset=utf-8'); ?>
<?xml version="1.0" encoding="UTF-8"?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/">
<ShortName>{$proj->prefs['project_title']} - Flyspray</ShortName>
<Description>{L('searchforbugs')}</Description>
<SearchForm>{$baseurl}index.php?do=index&amp;project_id={$proj->id}</SearchForm>
<Url type="text/html" template="{$baseurl}index.php?project_id={$proj->id}&amp;string={searchTerms&rbrace;"/>
<Image width="16" height="16">{$baseurl}favicon.ico</Image>
</OpenSearchDescription>