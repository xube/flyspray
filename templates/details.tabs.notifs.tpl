<div id="notify" class="tab">
  <p><em>{$language['theseusersnotify']}</em></p>
  <?php foreach ($notifications as $row): ?>
  <p>
    {!tpl_userlink($row['user_id'])} &mdash;
    <a href="{$baseurl}?do=modify&amp;action=remove_notification&amp;ids={Get::val('id')}&amp;user_id={$row['user_id']}">{$language['remove']}</a>
  </p>
  <?php endforeach; ?>

  <?php if ($user->perms['manage_project']): ?>
  <form action="{$baseurl}" method="get">
    <p class="admin">
      {$language['addusertolist']}
      <select class="adminlist" name="user_id">
        {!tpl_options($proj->UserList())}
      </select>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="ids" value="{Get::val('id')}" />
      <input type="hidden" name="action" value="add_notification" />
      <button type="submit">{$language['addtolist']}</button>
    </p>
  </form>
  <?php endif; ?>
</div>

