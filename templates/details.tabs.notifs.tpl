<div id="notify" class="tab">
  <p><em>{$details_text['theseusersnotify']}</em></p>
  <?php foreach ($notifications as $row): ?>
  <p>
    {!tpl_userlink($row['user_id'])} &mdash;
    <a href="{$baseurl}'?do=modify&amp;action=remove_notification&amp;ids={Get::val('id')}&amp;user_id={$row['user_id']}">{$details_text['remove']}</a>
  </p>
  <?php endforeach; ?>

  <?php if ($user->perms['manage_project']): ?>
  <form action="{$baseurl}" method="get">
    <p class="admin">
      {$details_text['addusertolist']}
      <select class="adminlist" name="user_id">
        <?php $fs->listUsers($proj->id); ?>
      </select>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="add_notification" />
      <input type="hidden" name="ids" value="{Get::val('id')}" />
      <input type="hidden" name="prev_page" value="{$this_page}" />
      <input class="adminbutton" type="submit" value="{$details_text['addtolist']}" />
    </p>
  </form>
  <?php endif; ?>
</div>

