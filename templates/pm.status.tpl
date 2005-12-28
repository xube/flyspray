<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} :: {$proj->prefs['project_title']} : {$pm_text['taskstatuses']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['taskstatuses']}</legend>
    <?php
    $this->assign('list_type', 'status');
    $this->assign('rows', $proj->listTaskStatuses(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
