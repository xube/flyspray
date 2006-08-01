<div id="toolbox">
  <h3>{L('admintoolboxlong')} :: {L('createnewproject')}</h3>
  <fieldset class="admin">
    <legend>{L('newproject')}</legend>
    <form action="{$baseurl}" method="post">
      <div>
        <input type="hidden" name="do" value="modify" />
        <input type="hidden" name="action" value="admin.newproject" />
        <input type="hidden" name="area" value="newproject" />
      </div>
      <table class="admin">
        <tr>
          <td><label for="projecttitle">{L('projecttitle')}</label></td>
          <td><input id="projecttitle" name="project_title" value="{Req::val('project_title')}" type="text" class="text" size="40" maxlength="100" /></td>
        </tr>
        <tr>
          <td><label for="themestyle">{L('themestyle')}</label></td>
          <td>
            <select id="themestyle" name="theme_style">
              {!tpl_options(Flyspray::listThemes(), Req::val('theme_style', $proj->prefs['theme_style']), true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="langcode">{L('language')}</label></td>
          <td>
            <select id="langcode" name="lang_code">
              {!tpl_options(Flyspray::listLangs(), Req::val('lang_code', $fs->prefs['lang_code']), true)}
            </select>
          </td>
        </tr>
        <tr>
          <td><label for="intro_message">{L('intromessage')}</label></td>
          <td><textarea id="intro_message" name="intro_message" rows="10" cols="50">{Req::val('intro_message')}</textarea></td>
        </tr>
        <tr>
          <td><label for="othersview">{L('othersview')}</label></td>
          <td>{!tpl_checkbox('others_view', Req::val('others_view', Req::val('action') != 'admin.newproject'), 'othersview')}</td>
        </tr>
        <tr>
          <td><label for="anonopen">{L('allowanonopentask')}</label></td>
          <td>{!tpl_checkbox('anon_open', Req::val('anon_open'), 'anonopen')}</td>
        </tr>
        <tr>
          <td class="buttons" colspan="2"><button type="submit">{L('createthisproject')}</button></td>
        </tr>
      </table>
    </form>
  </fieldset>
</div>
