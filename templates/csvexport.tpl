<?php foreach ($visible as $column): ?>
<?php if (isset($proj->fields[$column])): ?>
{$proj->fields[$column]->prefs['field_name']};<?php else: ?>
{$column};<?php
endif;
endforeach; ?>

<?php foreach ($tasks as $task): ?>
<?php foreach ($visible as $column): ?>
{!tpl_csv_cell($task, $column)};<?php
endforeach; ?>

<?php endforeach; ?>