<p><b>Pruning Level: </b>{!implode(" &nbsp;|&nbsp; \n", $strlist)}</p>
<?php if($taskid): ?>
<h3>FS#{!$taskid}: {$language['dependencygraph']}</h3>
<img src="{$baseurl}/{!$image}" alt="Dependencies for task {!$taskid}" usemap="#{!$graphname}" />
<p>Page and image generated in {$time} seconds.<p>
<?php else: ?>
<p><strong>Error: The graph cannot be displayed because of the server's security settings.</strong></p>
<?php endif; ?>
