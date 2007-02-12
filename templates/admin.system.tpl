<div id="toolbox">
  <h3>{L('admintoolboxlong')} :: {L('system')}</h3>

  <fieldset>
  <legend>{L('system')}</legend>
  <table>
    <tr>
      <th>{L('phpversion')}</th>
      <td>{phpversion()} / {php_sapi_name()}</td>
    </tr>
    <tr>
      <th>{L('system')}</th>
      <td>{php_uname()}</td>
    </tr>
    <tr>
      <th>{L('fsversion')} ({L('files')} / {L('database')})</th>
      <td>{$fs->version} / {$db_version}</td>
    </tr>
    <tr>
      <th colspan="2">{L('fileaccess')}</th>
    </tr>
    <?php foreach (array('/flyspray.conf.php', '/cache', '/attachments') as $file): ?>
    <tr>
      <th>{$file}</th>
      <td>
        <?php if (is_writable(BASEDIR . $file)): ?>
        <span class="good">{L('writable')}</span>
        <?php else: ?>
        <span class="bad">{L('notwritable')}</span> ({L('affects:' . $file)})
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <th colspan="2">{L('extensions')}</th>
    </tr>
    <?php foreach (array('openssl', 'xml') as $ext): ?>
    <tr>
      <th>{$ext}</th>
      <td>
        <?php if (extension_loaded($ext)): ?>
        <span class="good">{L('available')}</span>
        <?php else: ?>
        <span class="bad">{L('notavailable')}</span> ({L('affects:' . $ext)})
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr>
      <th colspan="2">{L('functions')}</th>
    </tr>
    <?php foreach (array('shell_exec', 'stream_socket_enable_crypto', 'dns_get_record') as $func): ?>
    <tr>
      <th>{$func}()</th>
      <td>
        <?php if (function_exists($func) && !Flyspray::function_disabled($func)): ?>
        <span class="good">{L('available')}</span>
        <?php else: ?>
        <span class="bad">{L('notavailable')}</span> ({L('affects:' . $func)})
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
  </fieldset>
</div>
