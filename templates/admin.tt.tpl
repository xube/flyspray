<div id="toolbox">
  <h3>{$language['admintoolboxlong']} :: {$language['tasktypes']}</h3>

  <fieldset class="admin">
    <legend>{$language['tasktypes']}</legend>
    <?php
    $this->assign('list_type', 'tasktype');
    $this->assign('rows', $proj->listTaskTypes(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
