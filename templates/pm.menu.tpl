<div id="toolboxmenu">
  <a id="projprefslink" href="{$this->url(array('pm', 'proj' . $proj->id, 'prefs'))}">{L('preferences')}</a>
  <a id="projgroupslink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'groups'))}">{L('groups')}</a>
  <a id="projuserslink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'users'))}">{L('users')}</a>
  <a id="projfieldslink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'fields'))}">{L('fields')}</a>
  <a id="projlistslink"    href="{$this->url(array('pm', 'proj' . $proj->id, 'lists'))}">{L('lists')}</a>
  <a id="projreqlink"   href="{$this->url(array('pm', 'proj' . $proj->id, 'pendingreq'))}">{L('pendingrequests')}</a>
</div>