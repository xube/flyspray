<div id="notify" class="tab">
  <p><em>{L('theseusersnotify')}</em></p>
  <?php foreach ($notifications as $row): ?>
  <p>
    {!tpl_userlink($row['user_id'])} &mdash;
    <a href="{$baseurl}?do=modify&amp;action=remove_notification&amp;ids={Get::num('id')}&amp;user_id={$row['user_id']}">{L('remove')}</a>
  </p>
  <?php endforeach; ?>

  <?php if ($user->perms['manage_project']): ?>
  <form action="{$baseurl}" method="get">
    <p>
        <label class="default multisel" for="notif_user_id">{L('addusertolist')}</label>
        <input class="users text" size="30" type="text" name="user_id" id="notif_user_id" /><button type="submit">{L('addtolist')}</button>
        <div class="autocomplete" id="notif_complete"></div>
        <script type="text/javascript">
            new Ajax.Autocompleter('notif_user_id', 'notif_complete', '{$baseurl}/javascript/callbacks/usersearch.php', {})
        </script>
        
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="ids" value="{Get::num('id')}" />
      <input type="hidden" name="action" value="add_notification" />
    </p>
  </form>
  <?php endif; ?>
</div>

