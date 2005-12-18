<div id="toolbox">
  <h3>{$admin_text['admintoolbox']} :: {$newproject_text['createnewproject']}</h3>
  <fieldset class="admin">
    <legend>{$admin_text['newproject']}</legend>
    <form action="{$baseurl}" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="newproject" />
      </div>
      <table class="admin">
        <tr>
          <td><label for="projecttitle">{$newproject_text['projecttitle']}</label></td>
          <td><input id="projecttitle" name="project_title" type="text" size="40" maxlength="100" /></td>
        </tr>
        <tr>
          <td><label for="themestyle">{$newproject_text['themestyle']}</label></td>
          <td>
            <select id="themestyle" name="theme_style">
              {!tpl_options($fs->listThemes(), $proj->prefs['theme_style'], true)}
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
          <td><label for="intro_message">{$newproject_text['intromessage']}</label></td>
          <td><textarea id="intro_message" name="intro_message" rows="10" cols="50"></textarea></td>
        </tr>
        <tr>
          <td><label for="othersview">{$newproject_text['othersview']}</label></td>
          <td><input id="othersview" type="checkbox" name="others_view" value="1" checked="checked" /></td>
        </tr>
        <tr>
          <td><label for="anonopen">{$newproject_text['allowanonopentask']}</label></td>
          <td><input id="anonopen" type="checkbox" name="anon_open" value="1" /></td>
        </tr>
        <tr>
          <td class="buttons" colspan="2"><input class="adminbutton" type="submit" value="{$newproject_text['createthisproject']}" /></td>
        </tr>
      </table>
    </form>
  </fieldset>
</div>
