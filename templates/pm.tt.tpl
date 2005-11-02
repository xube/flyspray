<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$project_prefs['project_title']} : {$pm_text['tasktypeed']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['tasktypes']}</legend>
    <?php
    $this->assign('list_type', 'tasktype');
    $this->assign('rows', $fs->listTaskTypesIn($project_id));
    $this->display('pm._list.tpl');
    ?>
  </fieldset>
</div>
