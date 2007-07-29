<div id="toolboxmenu">
  <a id="projprefslink" href="{$this->url(array('pm', 'proj' . $proj->id, 'prefs'))}">{L('preferences')}</a>
  <a id="projuglink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'groups'))}">{L('groups')}</a>
  <a id="projuglink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'users'))}">{L('users')}</a>
  <a id="projuglink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'fields'))}">{L('fields')}</a>
  <a id="projuglink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'lists'))}">{L('lists')}</a>
  <a id="projreqlink"   href="{$this->url(array('pm', 'proj' . $proj->id, 'pendingreq'))}">{L('pendingrequests')}</a>
</div>
