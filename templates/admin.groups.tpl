<div id="toolbox">
  <h3>{$language['admintoolboxlong']} :: {$language['usersandgroups']}</h3>
  <fieldset class="admin">
    <legend>{$language['usersandgroups']}</legend>
    <p>
      <a href="{CreateURL('newuser', $proj->id)}">{$language['newuser']}</a> |
      <a href="{CreateURL('newgroup', $proj->id)}">{$language['newgroup']}</a>
    </p>
    <?php
    $this->display('common.groups.tpl');
    ?>
  </fieldset>
</div>
