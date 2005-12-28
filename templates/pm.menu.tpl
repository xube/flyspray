<div id="toolboxmenu">
  <a id="projprefslink" href="{$fs->CreateURL('pm', 'prefs',      $proj->id)}">{$admin_text['preferences']}</a>
  <a id="projuglink"    href="{$fs->CreateURL('pm', 'groups',     $proj->id)}">{$pm_text['usergroups']}</a>
  <a id="projttlink"    href="{$fs->CreateURL('pm', 'tt',         $proj->id)}">{$admin_text['tasktypes']}</a>
  <a id="projstatuslink" href="{$fs->CreateURL('pm', 'status',     $proj->id)}">{$admin_text['taskstatuses']}</a>
  <a id="projreslink"   href="{$fs->CreateURL('pm', 'res',        $proj->id)}">{$admin_text['resolutions']}</a>
  <a id="projcatlink"   href="{$fs->CreateURL('pm', 'cat',        $proj->id)}">{$admin_text['categories']}</a>
  <a id="projoslink"    href="{$fs->CreateURL('pm', 'os',         $proj->id)}">{$admin_text['operatingsystems']}</a>
  <a id="projverlink"   href="{$fs->CreateURL('pm', 'ver',        $proj->id)}">{$admin_text['versions']}</a>
  <a id="projreqlink"   href="{$fs->CreateURL('pm', 'pendingreq', $proj->id)}">{$pm_text['pendingreq']}</a>
</div>
