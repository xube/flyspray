<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$project_prefs['project_title']} : {$pm_text['verlisted']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['versions']}</legend>
    <?php
    $this->assign('list_type', 'version');
    $this->assign('rows', $fs->listVersionsIn($project_id));
    $this->display('pm._list.tpl');
    ?>
  </fieldset>
</div>
