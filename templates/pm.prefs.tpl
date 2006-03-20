<div id="toolbox">
  <h3>{L('pmtoolbox')} ::  {$proj->prefs['project_title']} : {L('preferences')}</h3>

  <form action="{$baseurl}" method="post">
    <fieldset class="admin">
      <legend>{L('general')}</legend>

      <table class="admin">
        <tr>
          <td><label for="projecttitle">{L('projecttitle')}</label></td>
          <td>
            <input id="projecttitle" name="project_title" class="text" type="text" size="40" maxlength="100"
              value="{$proj->prefs['project_title']}" />
          </td>
        </tr>

        <tr>
          <td><label for="defaultcatowner">{L('defaultcatowner')}</label></td>
          <td>
            <select id="defaultcatowner" name="default_cat_owner">
              <option value="0">{L('noone')}</option>
              {!tpl_options($proj->UserList(), $proj->prefs['default_cat_owner'])}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="langcode">{L('language')}</label></td>
          <td>
            <select id="langcode" name="lang_code">
              {!tpl_options($fs->listLangs(), $proj->prefs['lang_code'], true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="intromesg">{L('intromessage')}</label></td>
          <td>
            <textarea id="intromesg" name="intro_message" rows="12" cols="70">{$proj->prefs['intro_message']}</textarea>
          </td>
        </tr>
        <tr>
          <td><label for="isactive">{L('isactive')}</label></td>
          <td>{!tpl_checkbox('project_is_active', $proj->prefs['project_is_active'], 'isactive')}</td>
        </tr>
        <tr>
          <td><label for="othersview">{L('othersview')}</label></td>
          <td>{!tpl_checkbox('others_view', $proj->prefs['others_view'], 'othersview')}</td>
        </tr>
        <tr>
          <td><label for="anonopen">{L('allowanonopentask')}</label></td>
          <td>{!tpl_checkbox('anon_open', $proj->prefs['anon_open'], 'anonopen')}</td>
        </tr>
        <tr>
          <td><label for="comment_closed">{L('allowclosedcomments')}</label></td>
          <td>{!tpl_checkbox('comment_closed', $proj->prefs['comment_closed'], 'comment_closed')}</td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{L('lookandfeel')}</legend>

      <table class="admin">
        <tr>
          <td><label for="themestyle">{L('themestyle')}</label></td>
          <td>
            <select id="themestyle" name="theme_style">
              {!tpl_options($fs->listThemes(), $proj->prefs['theme_style'], true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label>{L('visiblecolumns')}</label></td>
          <td class="text">
            <?php // Set the selectable column names
            $columnnames = array('id', 'tasktype', 'category', 'severity',
            'priority', 'summary', 'dateopened', 'status', 'openedby',
            'assignedto', 'lastedit', 'reportedin', 'dueversion', 'duedate',
            'comments', 'attachments', 'progress', 'dateclosed', 'os', 'votes');
            $selectedcolumns = explode(" ", $proj->prefs['visible_columns']);
            ?>
            {!tpl_double_select('visible_columns', $columnnames, $selectedcolumns, true)}
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{L('notifications')}</legend>

      <table class="admin">
        <tr>
          <td><label for="notify_subject">{L('notifysubject')}</label></td>
          <td>
            <input id="notify_subject" class="text" name="notify_subject" type="text" size="40" value="{$proj->prefs['notify_subject']}" />
            {L('notifysubjectinfo')}
          </td>
        </tr>
        <tr>
          <td><label for="emailaddress">{L('emailaddress')}</label></td>
          <td>
            <input id="emailaddress" name="notify_email" class="text" type="text" value="{$proj->prefs['notify_email']}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberid">{L('jabberid')}</label></td>
          <td>
            <input id="jabberid" class="text" name="notify_jabber" type="text" value="{$proj->prefs['notify_jabber']}" />
          </td>
        </tr>
      </table>
    </fieldset>

    <fieldset class="admin">
      <legend>{L('feeds')}</legend>

      <table class="admin">
        <tr>
          <td><label for="feed_description">{L('feeddescription')}</label></td>
          <td>
            <input id="feed_description" class="text" name="feed_description" type="text" value="{$proj->prefs['feed_description']}" />
          </td>
        </tr>
        <tr>
          <td><label for="feed_img_url">{L('feedimgurl')}</label></td>
          <td>
            <input id="feed_img_url" class="text" name="feed_img_url" type="text" value="{$proj->prefs['feed_img_url']}" />
          </td>
        </tr>
      </table>
    </fieldset>
    
    <table>
      <tr>
        <td class="buttons">
          <input type="hidden" name="do" value="modify" />
          <input type="hidden" name="action" value="updateproject" />
          <input type="hidden" name="project_id" value="{$proj->id}" />
          <button type="submit">{L('saveoptions')}</button>
        </td>
        <td class="buttons">
          <button type="reset">{L('resetoptions')}</button>
        </td>
      </tr>
    </table>
  </form>

</div>
