<div id="toolbox">
  <h3>{$pm_text['pmtoolbox']} ::  {$proj->prefs['project_title']} : {$admin_text['preferences']}</h3>

  <form action="{$baseurl}" method="post">
    <fieldset class="admin">
      <legend>{$admin_text['general']}</legend>

      <input type="hidden" name="do" value="modify" />
      <input type="hidden" name="action" value="updateproject" />
      <input type="hidden" name="project_id" value="{$proj->id}" />
      <table class="admin">
        <tr>
          <td><label for="projecttitle">{$admin_text['projecttitle']}</label></td>
          <td>
            <input id="projecttitle" name="project_title" type="text" size="40" maxlength="100"
              value="{$proj->prefs['project_title']}" />
          </td>
        </tr>

        <tr>
          <td><label for="defaultcatowner">{$admin_text['defaultcatowner']}</label></td>
          <td>
            <select id="defaultcatowner" name="default_cat_owner">
              <option value="">{$admin_text['noone']}</option>
              {!$fs->listUsers($proj->id, $proj->prefs['default_cat_owner'])}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="intromessage">{$admin_text['intromessage']}</label></td>
          <td>
            <textarea id="intromessage" name="intro_message" rows="12" cols="70">{$proj->prefs['intro_message']}</textarea>
          </td>
        </tr>
        <tr>
          <td><label for="isactive">{$admin_text['isactive']}</label></td>
          <td>{!tpl_checkbox('project_is_active', $proj->prefs['project_is_active'], 'isactive')}</td>
        </tr>
        <tr>
          <td><label for="othersview">{$admin_text['othersview']}</label></td>
          <td>{!tpl_checkbox('others_view', $proj->prefs['others_view'], 'othersview')}</td>
        </tr>
        <tr>
          <td><label for="anonopen">{$admin_text['allowanonopentask']}</label></td>
          <td>{!tpl_checkbox('anon_open', $proj->prefs['anon_open'], 'anonopen')}</td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$admin_text['lookandfeel']}</legend>

      <table class="admin">
        <tr>
          <td><label for="themestyle">{$admin_text['themestyle']}</label></td>
          <td>
            <select id="themestyle" name="theme_style">
              {!tpl_options($fs->listThemes(), $proj->prefs['theme_style'], true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="showlogo">{$admin_text['showlogo']}</label></td>
          <td>{!tpl_checkbox('show_logo', $proj->prefs['show_logo'], 'showlogo')}</td>
        </tr>
        <tr>
          <td><label>{$admin_text['visiblecolumns']}</label></td>
          <td class="admintext">
            <?php // Set the selectable column names
            $columnnames = array('id', 'tasktype', 'category', 'severity',
            'priority', 'summary', 'dateopened', 'status', 'openedby',
            'assignedto', 'lastedit', 'reportedin', 'dueversion', 'duedate',
            'comments', 'attachments', 'progress');
            foreach ($columnnames as $column):
            ?>
            {!tpl_checkbox("visible_columns[$column]",
            strstr($proj->prefs['visible_columns'], $column) !== false)}
            {$index_text[$column]}<br />
            <?php endforeach; ?>
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{$pm_text['notifications']}</legend>

      <table class="admin">
        <tr>
          <td><label for="emailaddress">{$pm_text['emailaddress']}</label></td>
          <td>
            <input id="emailaddress" name="notify_email" type="text" value="{$proj->prefs['notify_email']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberid">{$pm_text['jabberid']}</label></td>
          <td>
            <input id="jabberid" name="notify_jabber" type="text" value="{$proj->prefs['notify_jabber']}" />
          </td>
        </tr>
      </table>
    </fieldset>

    <table>
      <tr>
        <td class="buttons"><input class="adminbutton" type="submit" value="{$admin_text['saveoptions']}" /></td>
        <td class="buttons"><input class="adminbutton" type="reset" value="{$admin_text['resetoptions']}" /></td>
      </tr>
    </table>
  </form>

</div>
