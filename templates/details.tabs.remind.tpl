<?php if (!$task_details['is_closed']): ?>
  <div id="remind" class="tab">
  <?php foreach ($reminders as $row): ?>
  <form action="{$baseurl}" method="post">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="deletereminder" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />
      <input type="hidden" name="reminder_id" value="{$row['reminder_id']}" />
      <button type="submit">{$language['remove']}</button>
    </div>
  </form>
  <em>{$language['remindthisuser']}:</em>
  <a href="?do=admin&amp;area=users&amp;id={$row['to_user_id']}">
    {$row['real_name']} ({$row['user_name']})</a>
  <br />
  <?php
      // Work out the unit of time to display
      if ($row['how_often'] < 86400) {
          $how_often = $row['how_often'] / 3600 . " " . $language['hours'];
      } elseif ($row['how_often'] < 604800) {
          $how_often = $row['how_often'] / 86400 . " " . $language['days'];
      } else {
          $how_often = $row['how_often'] / 604800 . " " . $language['weeks'];
      }
  ?>

  <em>{$language['thisoften']}:</em> {$how_often}
  <br />
  <em>{$language['message']}:</em> {!tpl_formatText($row['reminder_message'])}
  <br /><br />
  <?php endforeach; ?>

  <fieldset><legend>{$language['addreminder']}</legend>
  <form action="{$baseurl}" method="post" id="formaddreminder">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addreminder" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />

      <em>{$language['remindthisuser']}</em>
      <select class="adminlist" name="to_user_id">
        {!tpl_options($proj->UserList())}
      </select>

      <br />

      <em>{$language['thisoften']}</em>
      <input class="text" type="text" name="timeamount1" size="3" maxlength="3" />
      <select class="adminlist" name="timetype1">
        <option value="3600">{$language['hours']}</option>
        <option value="86400">{$language['days']}</option>
        <option value="604800">{$language['weeks']}</option>
      </select>

      <br />

      <em>{$language['startafter']}</em>
      <input class="text" type="text" name="timeamount2" size="3" maxlength="3" />
      <select class="adminlist" name="timetype2">
        <option value="3600">{$language['hours']}</option>
        <option value="86400">{$language['days']}</option>
        <option value="604800">{$language['weeks']}</option>
      </select>

      <br />
      <textarea class="text" name="reminder_message"
        rows="10" cols="72">{$language['defaultreminder']}

{CreateURL('details', Get::val('id'))}</textarea>
      <br />
      <button type="submit">{$language['addreminder']}</button>
    </div>
  </form>
  </fieldset>
</div>
<?php endif; ?>