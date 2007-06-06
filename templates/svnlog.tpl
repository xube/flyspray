<div class="box svnlog">

<table id="svnlog">
<cols>
  <col style="width:5em;" />
  <col style="width:6em;" />
  <col style="width:9em;" />
</cols>
<thead>
  <tr>
    <th>{L('revision')}</th>
    <th>{L('date')}</th>
    <th>{L('author')}</th>
    <th>{L('commitmessage')}</th>
  </tr>
</thead>
<?php $i = 0; foreach ($svnlog as $log): ?>
<tr class="rowstyle{$i++ % 2}">
  <td>{$log['version-name']}</td>
  <td>{formatDate(strtotime($log['date']))}</td>
  <td>{!$log['creator-displayname']}</td>
  <td>{!$log['comment']}</td>
</tr>
<?php endforeach; ?>

</table>

</div>