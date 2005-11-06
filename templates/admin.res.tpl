<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$admin_text['resolutionlist']}</h3>

  <fieldset class="admin">
    <legend>{$admin_text['resolutions']}</legend>
    <?php
    $this->assign('list_type', 'resolution');
    $this->assign('rows', $proj->listResolutions(true));
    $this->display('common.list.tpl');
    ?>
  </fieldset>
</div>
