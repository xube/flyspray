<?php if (!$task_details['is_closed']): ?>
  <div id="remind" class="tab">
  
  <form method="post" action="{$baseurl}" >
    <table id="reminders" class="userlist">
    <tr>
      <th>
        <a href="javascript:ToggleSelected('reminders')">
          <img title="{L('toggleselected')}" alt="{L('toggleselected')}" src="{$this->get_image('kaboodleloop')}" width="16" height="16" />
        </a>
      </th>
      <th>{L('user')}</th>
      <th>{L('frequency')}</th>
      <th>{L('message')}</th>
    </tr>

    <?php foreach ($reminders as $row): ?>
    <tr>
      <td class="ttcolumn">
        <input type="checkbox" name="reminder_id[]" {!tpl_disableif(!$user->can_edit_task($task_details))} value="{$row['reminder_id']}" />
      </td>
      
     <td>{!tpl_userlink($row['user_id'])}</td>
     <?php
      // Work out the unit of time to display
      if ($row['how_often'] < 86400) {
          $how_often = $row['how_often'] / 3600 . ' ' . L('hours');
      } elseif ($row['how_often'] < 604800) {
          $how_often = $row['how_often'] / 86400 . ' ' . L('days');
      } else {
          $how_often = $row['how_often'] / 604800 . ' ' . L('weeks');
      }
     ?>
     <td>{$how_often}</td>
     <td>{!TextFormatter::render($row['reminder_message'], true)}</td>
  </tr>
    <?php endforeach; ?>
    <tr><td colspan="4"><input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="deletereminder" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />
      <button type="submit">{L('remove')}</button></td></tr>
    </table>
  </form>

  <fieldset><legend>{L('addreminder')}</legend>
  <form action="{$baseurl}" method="post" id="formaddreminder">
    <div>
      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="addreminder" />
      <input type="hidden" name="task_id" value="{Get::val('id')}" />

        <label class="default multisel" for="to_user_id">{L('remindthisuser')}</label>
        <input class="users text" size="30" type="text" name="to_user_id" id="to_user_id" />
        <div class="autocomplete" id="to_user_id_complete"></div>
        <script type="text/javascript">
            new Ajax.Autocompleter('to_user_id', 'to_user_id_complete', '{$baseurl}/javascript/callbacks/usersearch.php', {})
        </script>

      <br />

      <em>{L('thisoften')}</em>
      <input class="text" type="text" name="timeamount1" size="3" maxlength="3" />
      <select class="adminlist" name="timetype1">
        <option value="3600">{L('hours')}</option>
        <option value="86400">{L('days')}</option>
        <option value="604800">{L('weeks')}</option>
      </select>

      <br />

      <em>{L('startafter')}</em>
      <input class="text" type="text" name="timeamount2" size="3" maxlength="3" />
      <select class="adminlist" name="timetype2">
        <option value="3600">{L('hours')}</option>
        <option value="86400">{L('days')}</option>
        <option value="604800">{L('weeks')}</option>
      </select>

      <br />
      <textarea class="text" name="reminder_message"
        rows="10" cols="72">{L('defaultreminder')}

{CreateURL('details', Get::val('id'))}</textarea>
      <br />
      <button type="submit">{L('addreminder')}</button>
    </div>
  </form>
  </fieldset>
</div>
<?php endif; ?>
