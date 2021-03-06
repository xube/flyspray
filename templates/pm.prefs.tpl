<div id="toolbox">
  <h3>{L('pmtoolbox')} ::  {$proj->prefs['project_title']} : {L('preferences')}</h3>

  <form style="clear:both;" action="{$this->url(array('pm', 'proj' . $proj->id, 'prefs'))}" method="post">
  <ul id="submenu">
   <li><a href="#general">{L('general')}</a></li>
   <li><a href="#lookandfeel">{L('lookandfeel')}</a></li>
   <li><a href="#notifications">{L('notifications')}</a></li>
   <li><a href="#feeds">{L('feedsandsvn')}</a></li>
  </ul>

  <div id="general" class="tab">
      <table class="box">
        <tr>
          <td><label for="projecttitle">{L('projecttitle')}</label></td>
          <td>
            <input id="projecttitle" name="project_title" class="text" type="text" size="40" maxlength="100"
              value="{Post::val('project_title', $proj->prefs['project_title'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="project_prefix">{L('projectprefix')}</label></td>
          <td>
            <input id="project_prefix" name="project_prefix" class="text" type="text" size="20" maxlength="20"
              value="{Post::val('project_prefix', $proj->prefs['project_prefix'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="defaultcatowner">{L('defaultcatowner')}</label></td>
          <td>
            {!tpl_userselect('default_cat_owner', Post::val('default_cat_owner', $proj->prefs['default_cat_owner']), 'defaultcatowner')}
          </td>
        </tr>
        <tr>
          <td><label for="langcode">{L('language')}</label></td>
          <td>
            <select id="langcode" name="lang_code">
              {!tpl_options(Flyspray::listLangs(), Post::val('lang_code', $proj->prefs['lang_code']), true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="override_user_lang">{L('overrideuserlang')}</label></td>
          <td>{!tpl_checkbox('override_user_lang', Post::val('override_user_lang', $proj->prefs['override_user_lang']), 'override_user_lang')}</td>
        </tr>
        <tr>
          <td><label for="intro_message">{L('intromessage')}</label></td>
          <td>
            {!$this->text->textarea('intro_message', 8, 70, null, Post::val('intro_message', $proj->prefs['intro_message']))}
          </td>
        </tr>
        <tr>
          <td><label for="default_task">{L('defaulttask')}</label></td>
          <td>
            {!$this->text->textarea('default_task', 8, 70, null, Post::val('default_task', $proj->prefs['default_task']))}
          </td>
        </tr>
        <tr>
          <td><label for="othersview">{L('othersview')}</label></td>
          <td>{!tpl_checkbox('others_view', Post::val('others_view', $proj->prefs['others_view']), 'othersview')}</td>
        </tr>
        <tr>
          <td><label for="anon_view_tasks">{L('anonviewtasks')}</label></td>
          <td>{!tpl_checkbox('anon_view_tasks', Post::val('anon_view_tasks', $proj->prefs['anon_view_tasks']), 'anon_view_tasks')}</td>
        </tr>
        <tr>
          <td><label for="anon_open">{L('allowanonopentask')}</label></td>
          <td>{!tpl_checkbox('anon_open', Post::val('anon_open', $proj->prefs['anon_open']), 'anon_open')}</td>
        </tr>
        <tr>
          <td><label for="comment_closed">{L('allowclosedcomments')}</label></td>
          <td>{!tpl_checkbox('comment_closed', Post::val('comment_closed', $proj->prefs['comment_closed']), 'comment_closed')}</td>
        </tr>
        <tr>
          <td><label for="auto_assign">{L('autoassign')}</label></td>
          <td>{!tpl_checkbox('auto_assign', Post::val('auto_assign', $proj->prefs['auto_assign']), 'auto_assign')}</td>
        </tr>
        <tr>
          <td><label for="roadmap_field">{L('roadmapfield')}</label></td>
          <td>
            <select name="roadmap_field" id="roadmap_field">
            {!tpl_options($roadmap_options, Post::val('roadmap_field', $proj->prefs['roadmap_field']))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="changelog_reso">{L('changelogreso')}</label></td>
          <td>
            <select name="changelog_reso[]" id="changelog_reso" multiple="multiple" size="4">
            {!tpl_options($proj->get_list(array('list_id' => $fs->prefs['resolution_list'])), explode(' ', $proj->prefs['changelog_reso']), false)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="anon_group">{L('anongroup')}</label></td>
          <td>
            <select id="anon_group" name="anon_group">
              <option value="0">{L('none')}</option>
              {!tpl_options(Flyspray::listGroups($proj->id), Post::val('anon_group', $proj->prefs['anon_group']))}
            </select>
          </td>
        </tr>
        <tr>
          <td>
              <script type="text/javascript">
              // <!--
                function confirm_move_to(sel) {
                    var project_name = sel.options[sel.selectedIndex].text;
                    if (confirm("{L('deleteproject')} "+project_name+"?")) {
                        var chbox = sel.form.delete_project;
                        chbox.checked = true;
                        sel.form.submit();
                    } else {
                        sel.selectedIndex = 0;
                    }
                }
                function confirm_delete(chbox) {
                    var sel = chbox.form.move_to;
                    var project_name = sel.options[sel.selectedIndex].text;
                    if (confirm("{L('deleteproject')} "+project_name+"?")) {
                        chbox.form.submit();
                    } else {
                        chbox.checked = false;
                    }
                }
              // -->
              </script>
              <label>{!tpl_checkbox('delete_project', null, null, 1, array('onclick' => 'confirm_delete(this)'))} {L('deleteproject')}</label>
          </td>
          <td>
              <select name="move_to" onchange="confirm_move_to(this)">{!tpl_options(array_merge(array(0 => L('none')), Flyspray::listProjects()), null, false, null, (string) $proj->id)}</select>
          </td>
        </tr>
      </table>
    </div>

    <div id="lookandfeel" class="tab">
      <table class="box">
        <tr>
          <td><label for="themestyle">{L('themestyle')}</label></td>
          <td>
            <select id="themestyle" name="theme_style">
              {!tpl_options(Flyspray::listThemes(), Post::val('theme_style', $proj->prefs['theme_style']), true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="default_entry">{L('defaultentry')}</label></td>
          <td>
            <select id="default_entry" name="default_entry">
              {!tpl_options(array('index' => L('tasklist'), 'toplevel' => L('toplevel'), 'roadmap' => L('roadmap')), Post::val('default_entry', $proj->prefs['default_entry']))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="syntax_plugins">{L('syntaxplugins')}</label></td>
          <td>
            <select id="syntax_plugins" name="syntax_plugins[]" multiple="multiple" size="4">
              {!tpl_options($this->text->allclasses, explode(' ', $proj->prefs['syntax_plugins']), true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label>{L('visiblecolumns')}</label></td>
          <td class="text">
            <?php // Set the selectable column names
            $selectedcolumns = explode(' ', Post::val('visible_columns', $proj->prefs['visible_columns']));
            ?>
            {!tpl_double_select('visible_columns', $proj->columns, $selectedcolumns)}
          </td>
        </tr>
      </table>
    </div>

    <div id="notifications" class="tab">
      <table class="box">
        <tr>
          <td><label for="notify_subject">{L('notifysubject')}</label></td>
          <td>
            <input id="notify_subject" class="text" name="notify_subject" type="text" size="40" value="{Post::val('notify_subject', $proj->prefs['notify_subject'])}" />
            {L('notifysubjectinfo')}
          </td>
        </tr>
        <tr>
          <td><label for="emailaddress">{L('emailaddress')}</label></td>
          <td>
            <input id="emailaddress" name="notify_email" class="text" type="text" value="{Post::val('notify_email', $proj->prefs['notify_email'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="jabberid">{L('jabberid')}</label></td>
          <td>
            <input id="jabberid" class="text" name="notify_jabber" type="text" value="{Post::val('notify_jabber', $proj->prefs['notify_jabber'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="notify_reply">{L('replyto')}</label></td>
          <td>
            <input id="notify_reply" name="notify_reply" class="text" type="text" value="{Post::val('notify_reply', $proj->prefs['notify_reply'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="notify_types">{L('notifytypes')}</label></td>
          <td>
            <select id="notify_types" size="10" multiple="multiple" name="notify_types[]">
            {!tpl_options(array(0 => L('none'),
                                NOTIFY_TASK_OPENED     => L('taskopened'),
                                NOTIFY_TASK_CHANGED    => L('pm.taskchanged'),
                                NOTIFY_TASK_CLOSED     => L('taskclosed'),
                                NOTIFY_TASK_REOPENED   => L('pm.taskreopened'),
                                NOTIFY_DEP_ADDED       => L('pm.depadded'),
                                NOTIFY_DEP_REMOVED     => L('pm.depremoved'),
                                NOTIFY_COMMENT_ADDED   => L('commentadded'),
                                NOTIFY_REL_ADDED       => L('relatedadded'),
                                NOTIFY_OWNERSHIP       => L('ownershiptaken'),
                                NOTIFY_PM_REQUEST      => L('pmrequest'),
                                NOTIFY_PM_DENY_REQUEST => L('pmrequestdenied'),
                                NOTIFY_NEW_ASSIGNEE    => L('newassignee'),
                                NOTIFY_REV_DEP         => L('revdepadded'),
                                NOTIFY_REV_DEP_REMOVED => L('revdepaddedremoved'),
                                NOTIFY_ADDED_ASSIGNEES => L('assigneeadded')),
                                Post::val('notify_types', explode(' ', $proj->prefs['notify_types'])))}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="send_digest">{L('senddigest')}</label></td>
          <td>
            {!tpl_checkbox('send_digest', Post::val('send_digest', $proj->prefs['send_digest']), 'send_digest')}
          </td>
        </tr>
        <tr>
          <td><label for="mail_headers">{L('mailheaders')}</label></td>
          <td>
            <textarea rows="5" name="mail_headers" id="mail_headers" cols="10">{Post::val('mail_headers', $proj->prefs['mail_headers'])}</textarea>
          </td>
        </tr>
      </table>
    </div>

    <div id="feeds" class="tab">
      <table class="box">
        <tr>
          <td><label for="feed_description">{L('feeddescription')}</label></td>
          <td>
            <input id="feed_description" class="text" name="feed_description" type="text" value="{Post::val('feed_description', $proj->prefs['feed_description'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="feed_img_url">{L('feedimgurl')}</label></td>
          <td>
            <input id="feed_img_url" class="text" name="feed_img_url" type="text" value="{Post::val('feed_img_url', $proj->prefs['feed_img_url'])}" />
          </td>
        </tr>
        <tr><td colspan="2"><hr /></td></tr>
        <tr>
          <td><label for="svn_url">{L('svnurl')}</label></td>
          <td>
            <input id="svn_url" name="svn_url" class="text" size="50" type="text" value="{Post::val('svn_url', $proj->prefs['svn_url'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="svn_user">{L('svnuser')}</label></td>
          <td>
            <input id="svn_user" name="svn_user" class="text" type="text" value="{Post::val('svn_user', $proj->prefs['svn_user'])}" />
          </td>
        </tr>
        <tr>
          <td><label for="svn_password">{L('svnpassword')}</label></td>
          <td>
            <input id="svn_password" name="svn_password" class="text" type="text" value="{Post::val('svn_password', $proj->prefs['svn_password'])}" />
          </td>
        </tr>
      </table>
    </div>

    <div class="tbuttons">
      <input type="hidden" name="action" value="updateproject" />
      <input type="hidden" name="project_id" value="{$proj->id}" />
      <button type="submit">{L('saveoptions')}</button>

      <button type="reset">{L('resetoptions')}</button>
    </div>
  </form>

</div>