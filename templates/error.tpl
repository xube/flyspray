<fieldset>
  <legend>{L('errorocurred')}</legend>

  <p>
  <?php if ($type == ERROR_INPUT): ?>
  {L('inputerror')}
  <?php elseif ($type == ERROR_PERMS): ?>
  {L('permserror')}
  <?php elseif ($type == ERROR_INTERNAL): ?>
  {L('internalerror')}
  <?php endif; ?>
  </p>

  <?php if (isset($file) && $line): ?>
  <p>{L('location')}: {$line}@{$file}</p>
  <?php endif; ?>
  <?php if ($message): ?>
  <p><strong>{$message}</strong></p>
  <?php endif; ?>
</fieldset>
