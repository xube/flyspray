<div id="toolboxmenu">
  <a id="projprefslink" href="{CreateURL('pm', 'prefs',      $proj->id)}">{$admin_text['preferences']}</a>
  <a id="projuglink"    href="{CreateURL('pm', 'groups',     $proj->id)}">{$pm_text['usergroups']}</a>
  <a id="projttlink"    href="{CreateURL('pm', 'tt',         $proj->id)}">{$admin_text['tasktypes']}</a>
  <a id="projstatuslink" href="{CreateURL('pm', 'status',     $proj->id)}">{$admin_text['taskstatuses']}</a>
  <a id="projreslink"   href="{CreateURL('pm', 'res',        $proj->id)}">{$admin_text['resolutions']}</a>
  <a id="projcatlink"   href="{CreateURL('pm', 'cat',        $proj->id)}">{$admin_text['categories']}</a>
  <a id="projoslink"    href="{CreateURL('pm', 'os',         $proj->id)}">{$admin_text['operatingsystems']}</a>
  <a id="projverlink"   href="{CreateURL('pm', 'ver',        $proj->id)}">{$admin_text['versions']}</a>
  <a id="projreqlink"   href="{CreateURL('pm', 'pendingreq', $proj->id)}">{$pm_text['pendingreq']}</a>
</div>
