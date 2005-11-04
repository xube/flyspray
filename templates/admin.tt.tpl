<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$admin_text['tasktypes']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['tasktypes']}</legend>
    <?php
    $this->assign('list_type', 'tasktype');
    $this->assign('rows', $proj->listTaskTypes(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
