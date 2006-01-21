<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$admin_text['usergroups']}</h3>
  <fieldset class="admin">
    <legend>{$admin_text['usergroups']}</legend>
    <p>
      <a href="{CreateURL('newuser', $proj->id)}">{$admin_text['newuser']}</a> |
      <a href="{CreateURL('newgroup', $proj->id)}">{$admin_text['newgroup']}</a>
    </p>
    <?php
    $this->display('common.groups.tpl');
    ?>
  </fieldset>
</div>
