<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$proj->prefs['project_title']} : {$pm_text['tasktypeed']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['tasktypes']}</legend>
    <?php
    $this->assign('list_type', 'tasktype');
    $this->assign('rows', $proj->listTaskTypes(false));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
