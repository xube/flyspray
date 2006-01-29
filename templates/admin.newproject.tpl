<div id="toolbox">
  <h3>{$language['admintoolboxlong']} :: {$language['createnewproject']}</h3>
  <fieldset class="admin">
    <legend>{$language['newproject']}</legend>
    <form action="{$baseurl}" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="newproject" />
      </div>
      <table class="admin">
        <tr>
          <td><label for="projecttitle">{$language['projecttitle']}</label></td>
          <td><input id="projecttitle" name="project_title" type="text" class="text" size="40" maxlength="100" /></td>
        </tr>
        <tr>
          <td><label for="themestyle">{$language['themestyle']}</label></td>
          <td>
            <select id="themestyle" name="theme_style">
              {!tpl_options($fs->listThemes(), $proj->prefs['theme_style'], true)}
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
          <td><label for="intro_message">{$language['intromessage']}</label></td>
          <td><textarea id="intro_message" name="intro_message" rows="10" cols="50"></textarea></td>
        </tr>
        <tr>
          <td><label for="othersview">{$language['othersview']}</label></td>
          <td><input id="othersview" type="checkbox" name="others_view" value="1" checked="checked" /></td>
        </tr>
        <tr>
          <td><label for="anonopen">{$language['allowanonopentask']}</label></td>
          <td><input id="anonopen" type="checkbox" name="anon_open" value="1" /></td>
        </tr>
        <tr>
          <td class="buttons" colspan="2"><button type="submit">{$language['createthisproject']}</button></td>
        </tr>
      </table>
    </form>
  </fieldset>
</div>
