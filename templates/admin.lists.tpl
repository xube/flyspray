<div id="toolbox">
  <h3>{L('admintoolboxlong')} :: {L('lists')}</h3>

    <fieldset class="box">
    <legend>{L('lists')}</legend>
    <?php $this->display('common.lists.tpl'); ?>

    <hr />

    <form action="{$this->url(array($do, 'proj' . $proj->id, 'lists'))}" method="post">
    <p>{L('moveallof')}

        <select name="list_delete">
          <?php foreach ($mergelists as $project => $plists): ?>
          <optgroup label="{$project}">
            {!tpl_options($plists)}
          </optgroup>
          <?php endforeach; ?>
        </select>

        {L('movelistto')}

        <select name="list_target">
          <?php foreach ($mergelists as $project => $plists): ?>
          <optgroup label="{$project}">
            {!tpl_options($plists)}
          </optgroup>
          <?php endforeach; ?>
        </select>

        <button type="submit">{L('merge')}</button>

        <input type="hidden" name="project" value="{$proj->id}" />
        <input type="hidden" name="action" value="merge_lists" />
        <input type="hidden" name="project_id" value="{$proj->id}" />
        <input type="hidden" name="area" value="{Req::val('area')}" />
        <input type="hidden" name="do" value="{$do}" />
    </p>
    </form>

    </fieldset>
</div>
