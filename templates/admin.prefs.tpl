<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$admin_text['preferences']}</h3>

  <form action="{$baseurl}" method="post">
    <fieldset class="admin">
      <legend>{$admin_text['general']}</legend>
      <table class="admin">
        <tr>
          <td><label for="defaultproject">{$admin_text['defaultproject']}</label></td>
          <td>
            <select id="defaultproject" name="default_project">
              {!tpl_options($fs->listProjects(), $fs->prefs['default_project'])}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="langcode">{$admin_text['language']}</label></td>
          <td>
            <select id="langcode" name="lang_code">
              {!tpl_options($fs->listLangs(), $fs->prefs['lang_code'], true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="dateformat">{$admin_text['dateformat']}</label></td>
          <td>
            <input id="dateformat" name="dateformat" type="text" size="40" maxlength="30" value="{$fs->prefs['dateformat']}" />
          </td>
        </tr>
        <tr>
          <td><label for="dateformat_extended">{$admin_text['dateformat_extended']}</label></td>
          <td>
            <input id="dateformat_extended" name="dateformat_extended" type="text" size="40" maxlength="30" value="{$fs->prefs['dateformat_extended']}" />
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$admin_text['userregistration']}</legend>
      <table class="admin">
        <tr>
          <td><label for="allowusersignups">{$admin_text['anonreg']}</label></td>
          <td>{!tpl_checkbox('anon_reg', $fs->prefs['anon_reg'], 'allowusersignups')}</td>
        </tr>
        <tr>
          <td><label for="spamproof">{$admin_text['spamproof']}</label></td>
          <td>{!tpl_checkbox('spam_proof', $fs->prefs['spam_proof'], 'spamproof')}</td>
        </tr>
        <tr>
          <td><label for="defaultglobalgroup">{$admin_text['defaultglobalgroup']}</label></td>
          <td>
            <select id="defaultglobalgroup" name="anon_group">
              {!tpl_options($proj->listGroups(), $fs->prefs['anon_group'])}
            </select>
          </td>
        </tr>
        <tr>
          <td><label id="groupsassignedlabel">{$admin_text['groupassigned']}</label></td>
          <td class="admintext">
            <?php foreach($proj->listGroups() as $group): ?>
            {!tpl_checkbox('assigned_groups['.$group['group_id'].']',
            strstr($fs->prefs['assigned_groups'], $group['group_id']) !== false)}
            {$group['group_name']}<br />
            <?php endforeach; ?>
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$admin_text['notifications']}</legend>
      <table class="admin">
        <tr>
          <td><label for="usernotify">{$admin_text['forcenotify']}</label></td>
          <td>
            <select id="usernotify" name="user_notify">
              {!tpl_options(array($admin_text['none'], $admin_text['userchoose'], $admin_text['email'], $admin_text['jabber']), $fs->prefs['user_notify'])}
            </select>
          </td>
        </tr>
        <tr>
          <th colspan="2"><hr />
            {$admin_text['emailnotify']}
          </th>
        </tr>
        <tr>
          <td><label for="adminemail">{$admin_text['fromaddress']}</label></td>
          <td>
            <input id="adminemail" name="admin_email" type="text" size="40" maxlength="100" value="{$fs->prefs['admin_email']}" />
          </td>
        </tr>
        <tr>
          <td><label for="smtpserv">{$admin_text['smtpserver']}</label></td>
          <td>
            <input id="smtpserv" name="smtp_server" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_server']}" />
          </td>
        </tr>
        <tr>
          <td><label for="smtpuser">{$admin_text['smtpuser']}</label></td>
          <td>
            <input id="smtpuser" name="smtp_user" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_user']}" />
          </td>
        </tr>
        <tr>
          <td><label for="smtppass">{$admin_text['smtppass']}</label></td>
          <td>
            <input id="smtppass" name="smtp_pass" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_pass']}" />
          </td>
        </tr>
        <tr>
          <th colspan="2"><hr />
            {$admin_text['jabbernotify']}
          </th>
        </tr>
        <tr>
          <td><label for="jabberserver">{$admin_text['jabberserver']}</label></td>
          <td>
            <input id="jabberserver" name="jabber_server" size="40" maxlength="100" value="{$fs->prefs['jabber_server']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberport">{$admin_text['jabberport']}</label></td>
          <td>
            <input id="jabberport" name="jabber_port" size="40" maxlength="100" value="{$fs->prefs['jabber_port']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberusername">{$admin_text['jabberuser']}</label></td>
          <td>
            <input id="jabberusername" name="jabber_username" size="40" maxlength="100" value="{$fs->prefs['jabber_username']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberpassword">{$admin_text['jabberpass']}</label></td>
          <td>
            <input id="jabberpassword" name="jabber_password" type="password" size="40" maxlength="100" value="{$fs->prefs['jabber_password']}" />
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$admin_text['lookandfeel']}</legend>
      <table class="admin">
        <tr>
          <td><label for="globaltheme">{$admin_text['globaltheme']}</label></td>
          <td>
            <select id="globaltheme" name="global_theme">
              {!tpl_options($fs->listThemes(), $fs->prefs['global_theme'], true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label id="viscollabel">{$admin_text['visiblecolumns']}</label></td>
          <td class="admintext">
            <?php // Set the selectable column names
            $columnnames = array('id', 'project', 'tasktype', 'category', 'severity',
            'priority', 'summary', 'dateopened', 'status', 'openedby',
            'assignedto', 'lastedit', 'reportedin', 'dueversion', 'duedate',
            'comments', 'attachments', 'progress');
            $selectedcolumns = explode(" ", $fs->prefs['visible_columns']);
            ?>
            <select id="visiblecolumns" name="visible_columns[]" multiple="multiple">
            {!tpl_options($columnnames, $selectedcolumns, true)}
            </select>
          </td>
        </tr>
      </table>
    </fieldset>

    <table>
      <tr>
        <td class="buttons">
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="globaloptions" />
          <input type="hidden" name="prev_page" value="{$_SERVER['REQUEST_URI']}" />
          <input class="adminbutton" type="submit" value="{$admin_text['saveoptions']}" />
        </td>
        <td class="buttons">
          <input class="adminbutton" type="reset" value="{$admin_text['resetoptions']}" />
        </td>
      </tr>
    </table>
  </form>

</div>
