<fieldset>
  <legend>{L('errorocurred')}</legend>

  <p>
  <?php if ($type == ERROR_INPUT): ?>
  {L('inputerror')}
  <?php elseif ($type == ERROR_PERMS): ?>
  {L('permserror')}
  <?php elseif ($type == ERROR_DB): ?>
  {L('dberror')}
  <?php endif; ?>
  </p>

  <?php if ($message): ?>
  <p>{$message}</p>
  <?php endif; ?>
</fieldset>