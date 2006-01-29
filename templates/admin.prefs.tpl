<div id="toolbox">
  <h3>{$language['admintoolboxlong']} :: {$language['preferences']}</h3>

  <form action="{$baseurl}" method="post">
    <fieldset class="admin">
      <legend>{$language['general']}</legend>
      <table class="admin">
        <tr>
          <td><label for="defaultproject">{$language['defaultproject']}</label></td>
          <td>
            <select id="defaultproject" name="default_project">
              {!tpl_options(array_merge(array(0 => $language['allprojects']), $fs->listProjects()), $fs->prefs['default_project'])}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="langcode">{$language['language']}</label></td>
          <td>
            <select id="langcode" name="lang_code">
              {!tpl_options($fs->listLangs(), $fs->prefs['lang_code'], true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="dateformat">{$language['dateformat']}</label></td>
          <td>
            <input id="dateformat" name="dateformat" type="text" class="text" size="40" maxlength="30" value="{$fs->prefs['dateformat']}" />
          </td>
        </tr>
        <tr>
          <td><label for="dateformat_extended">{$language['dateformat_extended']}</label></td>
          <td>
            <input id="dateformat_extended" name="dateformat_extended" class="text" type="text" size="40" maxlength="30" value="{$fs->prefs['dateformat_extended']}" />
          </td>
        </tr>
        <tr>
          <td><label for="cache_feeds">{$language['cache_feeds']}</label></td>
          <td>
            <select id="cache_feeds" name="cache_feeds">
            {!tpl_options(array('0' => $language['no_cache'], '1' => $language['cache_disk'], '2' => $language['cache_db']), $fs->prefs['cache_feeds'])}
            </select>
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$language['userregistration']}</legend>
      <table class="admin">
        <tr>
          <td><label for="allowusersignups">{$language['anonreg']}</label></td>
          <td>{!tpl_checkbox('anon_reg', $fs->prefs['anon_reg'], 'allowusersignups')}</td>
        </tr>
        <tr>
          <td><label for="spamproof">{$language['spamproof']}</label></td>
          <td>{!tpl_checkbox('spam_proof', $fs->prefs['spam_proof'], 'spamproof')}</td>
        </tr>
        <tr>
          <td><label for="defaultglobalgroup">{$language['defaultglobalgroup']}</label></td>
          <td>
            <select id="defaultglobalgroup" name="anon_group">
              {!tpl_options($fs->listGroups(), $fs->prefs['anon_group'])}
            </select>
          </td>
        </tr>
        <tr>
          <td><label id="groupsassignedlabel">{$language['groupassigned']}</label></td>
          <td class="text">
            <?php foreach($fs->listGroups() as $group): ?>
            {!tpl_checkbox('assigned_groups['.$group['group_id'].']',
            strstr($fs->prefs['assigned_groups'], $group['group_id']) !== false)}
            {$group['group_name']}<br />
            <?php endforeach; ?>
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$language['notifications']}</legend>
      <table class="admin">
        <tr>
          <td><label for="usernotify">{$language['forcenotify']}</label></td>
          <td>
            <select id="usernotify" name="user_notify">
              {!tpl_options(array($language['neversend'], $language['userchoose'], $language['email'], $language['jabber']), $fs->prefs['user_notify'])}
            </select>
          </td>
        </tr>
        <tr>
          <th colspan="2"><hr />
            {$language['emailnotify']}
          </th>
        </tr>
        <tr>
          <td><label for="adminemail">{$language['fromaddress']}</label></td>
          <td>
            <input id="adminemail" name="admin_email" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['admin_email']}" />
          </td>
        </tr>
        <tr>
          <td><label for="smtpserv">{$language['smtpserver']}</label></td>
          <td>
            <input id="smtpserv" name="smtp_server" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_server']}" />
          </td>
        </tr>
        <tr>
          <td><label for="smtpuser">{$language['smtpuser']}</label></td>
          <td>
            <input id="smtpuser" name="smtp_user" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_user']}" />
          </td>
        </tr>
        <tr>
          <td><label for="smtppass">{$language['smtppass']}</label></td>
          <td>
            <input id="smtppass" name="smtp_pass" class="text" type="text" size="40" maxlength="100" value="{$fs->prefs['smtp_pass']}" />
          </td>
        </tr>
        <tr>
          <th colspan="2"><hr />
            {$language['jabbernotify']}
          </th>
        </tr>
        <tr>
          <td><label for="jabberserver">{$language['jabberserver']}</label></td>
          <td>
            <input id="jabberserver" class="text" type="text" name="jabber_server" size="40" maxlength="100" value="{$fs->prefs['jabber_server']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberport">{$language['jabberport']}</label></td>
          <td>
            <input id="jabberport" class="text" type="text" name="jabber_port" size="40" maxlength="100" value="{$fs->prefs['jabber_port']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberusername">{$language['jabberuser']}</label></td>
          <td>
            <input id="jabberusername" class="text" type="text" name="jabber_username" size="40" maxlength="100" value="{$fs->prefs['jabber_username']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberpassword">{$language['jabberpass']}</label></td>
          <td>
            <input id="jabberpassword" name="jabber_password" class="password" type="password" size="40" maxlength="100" value="{$fs->prefs['jabber_password']}" />
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$language['lookandfeel']}</legend>
      <table class="admin">
        <tr>
          <td><label for="globaltheme">{$language['globaltheme']}</label></td>
          <td>
            <select id="globaltheme" name="global_theme">
              {!tpl_options($fs->listThemes(), $fs->prefs['global_theme'], true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label id="viscollabel">{$language['visiblecolumns']}</label></td>
          <td class="text">
            <?php // Set the selectable column names
            $columnnames = array('id', 'project', 'tasktype', 'category', 'severity',
            'priority', 'summary', 'dateopened', 'status', 'openedby',
            'assignedto', 'lastedit', 'reportedin', 'dueversion', 'duedate',
            'comments', 'attachments', 'progress', 'dateclosed', 'os', 'votes');
            $selectedcolumns = explode(" ", $fs->prefs['visible_columns']);
            ?>
            {!tpl_double_select('visible_columns', $columnnames, $selectedcolumns, true)}
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
          <button type="submit">{$language['saveoptions']}</button>
        </td>
        <td class="buttons">
          <button type="reset">{$language['resetoptions']}</button>
        </td>
      </tr>
    </table>
  </form>

</div>
