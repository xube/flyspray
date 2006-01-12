<input id="{$name}datehidden" type="hidden" name="{$name}date" value="{$date}" />
<span id="{$name}dateview" class="date" title="{$shortdesc}">{$show_date}</span>
<a class="datelink" href="#" onclick="document.getElementById('{$name}datehidden').value = '0';document.getElementById('{$name}dateview').innerHTML = '{$novaldesc}'">X</a>
<script type="text/javascript">Calendar.setup({daFormat: "{$fs->prefs['dateformat']}",inputField: "{$name}datehidden", displayArea: "{$name}dateview", button: "{$name}dateview"});</script>