<div id="toolbox">
  <h3>{L('admintoolboxlong')} :: {L('usersandgroups')}</h3>
  <fieldset class="admin">
    <legend>{L('usersandgroups')}</legend>
    <p>
      <a href="{CreateURL('newuser', $proj->id)}">{L('newuser')}</a> |
      <a href="{CreateURL('newgroup', $proj->id)}">{L('newgroup')}</a>
    </p>
    <?php
    $this->display('common.groups.tpl');
    ?>
  </fieldset>
</div>
