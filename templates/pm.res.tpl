<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$project_prefs['project_title']} : {$pm_text['resed']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['resolutions']}</legend>
    <?php
    $this->assign('list_type', 'resolution');
    $this->assign('rows', $fs->listResolutionsIn($project_id));
    $this->display('pm._list.tpl');
    ?>
  </fieldset>
</div>
