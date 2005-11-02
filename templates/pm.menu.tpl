<div id="toolboxmenu">
  <a id="projprefslink" href="{$fs->CreateURL('pm', 'prefs',      $project_id)}">{$admin_text['preferences']}</a>
  <a id="projuglink"    href="{$fs->CreateURL('pm', 'groups',     $project_id)}">{$pm_text['usergroups']}</a>
  <a id="projttlink"    href="{$fs->CreateURL('pm', 'tt',         $project_id)}">{$admin_text['tasktypes']}</a>
  <a id="projreslink"   href="{$fs->CreateURL('pm', 'res',        $project_id)}">{$admin_text['resolutions']}</a>
  <a id="projcatlink"   href="{$fs->CreateURL('pm', 'cat',        $project_id)}">{$admin_text['categories']}</a>
  <a id="projoslink"    href="{$fs->CreateURL('pm', 'os',         $project_id)}">{$admin_text['operatingsystems']}</a>
  <a id="projverlink"   href="{$fs->CreateURL('pm', 'ver',        $project_id)}">{$admin_text['versions']}</a>
  <a id="projreqlink"   href="{$fs->CreateURL('pm', 'pendingreq', $project_id)}">{$pm_text['pendingreq']}</a>
</div>
