<fieldset class="box"><legend>{L('mynotifications')}</legend>

<form action="{CreateUrl('myprofile', 'notifs')}" method="get">
<div>
  <label for="events_since">{L('showeventssince')}</label>
  <input type="text" class="text" name="events_since" value="{Get::val('events_since', '-1 week')}" />
  <button type="submit">{L('go')}</button>
</div>
</form>

<?php foreach ($tasks as $task): ?>
<div class="myprofile-notifs">{!tpl_tasklink($task)}
  <table>
  <?php foreach ($task_events[$task['task_id']] as $event): ?>
    <tr <?php if ($user->id == $event['user_id']): ?>class="fade"<?php endif; ?>>
      <td>{formatDate($event['event_date'])}</td>
      <td>{!tpl_userlink($event['user_id'])}</td>
      <td>{!event_description($event)}</td>
    </tr>
  <?php endforeach; ?>
  </table>
</div>
<?php endforeach; ?>

</fieldset>