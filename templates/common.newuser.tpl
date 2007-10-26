<fieldset class="box">
<legend>{L('registernewuser')}</legend>

<form action="<?php if ($do == 'admin' || $do == 'pm'): ?>{$this->url(array($do, 'proj' . $proj->id, 'newuser'))}<?php else: ?>{$_SERVER['SCRIPT_NAME']}<?php endif; ?>" method="post" id="registernewuser">
  <table class="box">
    <tr>
      <td>
        <?php if ($do == 'admin' || $do == 'pm'): ?>
        <input type="hidden" name="area" value="newuser" />
        <?php endif; ?>
        <input type="hidden" name="action" value="newuser" />
        <input type="hidden" name="do" value="{$do}" />
        <label for="username">{L('username')}</label>
      </td>
      <td><input id="username" name="user_name" value="{Post::val('user_name')}" class="required text" type="text" size="32" maxlength="20" onblur="checkname(this.value);" />
      <br /><span id="errormessage"></span></td>
    </tr>
    <tr>
      <td><label for="userpass">{L('password')}</label></td>
      <td><input id="userpass" class="password" name="user_pass" value="{Post::val('user_pass')}" type="password" size="20" maxlength="100" /> <em>{L('minpwsize')}</em></td>
    </tr>
    <tr>
      <td><label for="userpass2">{L('confirmpass')}</label></td>
      <td>
        <input id="userpass2" class="password" name="user_pass2" value="{Post::val('user_pass2')}" type="password" size="20" maxlength="100" /><br />
        {L('leaveemptyauto')}
      </td>
    </tr>
    <tr>
      <td><label for="realname">{L('realname')}</label></td>
      <td><input id="realname" name="real_name" class="required text" value="{Post::val('real_name')}" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="emailaddress">{L('emailaddress')}</label></td>
      <td><input id="emailaddress" name="email_address" class="text required" value="{Post::val('email_address')}" type="text" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="jabberid">{L('jabberid')}</label></td>
      <td><input id="jabberid" name="jabber_id" class="text" type="text" value="{Post::val('jabber_id')}" size="20" maxlength="100" /></td>
    </tr>
    <tr>
      <td><label for="notify_type">{L('notifications')}</label></td>
      <td>
        <select id="notify_type" name="notify_type">
          {!tpl_options(array(L('none'), L('email'), L('jabber'), L('both')), Post::val('notify_type'))}
        </select>
      </td>
    </tr>
    <tr>
      <td><label for="time_zone">{L('timezone')}</label></td>
      <td>
        <select id="time_zone" name="time_zone">
          {!tpl_options(tpl_TimeZones(), Post::val('time_zone', 0))}
        </select>
      </td>
    </tr>
    <?php if (isset($groups)): ?>
    <tr>
      <td><label for="groupin">{L('globalgroup')}</label></td>
      <td>
        <select id="groupin" class="adminlist" name="group_in">
          {!tpl_options($groups, Post::val('group_in'))}
        </select>
      </td>
    </tr>
    <?php endif; ?>
  </table>
  <p><button type="submit" id="buSubmit">{L('registeraccount')}</button></p>
</form>
</fieldset>
