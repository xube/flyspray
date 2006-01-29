<div id="toolbox">
  <h3>{$language['pmtoolbox']} :: {$proj->prefs['project_title']} : {$language['tasktypeed']}</h3>

  <fieldset class="admin">
    <legend>{$language['tasktypes']}</legend>
    <?php
    $this->assign('list_type', 'tasktype');
    $this->assign('rows', $proj->listTaskTypes(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
