<input id="{$name}datehidden" type="hidden" name="{$name}date" value="{$date}" />
<span id="{$name}dateview" class="date" title="{$shortdesc}">{$show_date}</span>
<a class="datelink" href="#" onclick="document.getElementById('{$name}datehidden').value = '0';document.getElementById('{$name}dateview').innerHTML = '{$description}'">X</a>
<script type="text/javascript">
   Calendar.setup({
      inputField  : "{$name}datehidden",  // ID of the input field
      displayArea : "{$name}dateview",    // The display field
      button      : "{$name}dateview"     // ID of the button
   });
</script>