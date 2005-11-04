<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$admin_text['oslisted']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['operatingsystems']}</legend>
    <?php
    $this->assign('list_type', 'os');
    $this->assign('rows', $proj->listOs(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
