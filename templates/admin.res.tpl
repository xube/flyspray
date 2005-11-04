<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$admin_text['resed']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['resolutions']}</legend>
    <?php
    $this->assign('list_type', 'resolution');
    $this->assign('rows', $proj->listResolutions());
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
