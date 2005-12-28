<table class="double_select">
  <tr>
    <td class="c1">
      <select id="l{!$id}" multiple="multiple"
        ondblclick="dualSelect(this, 'r', '{!$id}')">%s</select>
    </td>
    <td class="c2">
      <input type="button" value="add &#8594;"
        onmouseup="dualSelect('l', 'r', '{!$id}')"/>
      <br /><br />
      <input type="button" value="&#8592; del"
        onmouseup="dualSelect('r', 'l', '{!$id}')"/>
    </td>
    <td class="c3">
      <input type="button" value="&#8593;" onmouseup="selectMove('{!$id}', -1)" />
      <br />
      <select id="r{!$id}" multiple="multiple"
        ondblclick="dualSelect(this, 'l', '{!$id}')">%s</select>
      <br />
      <input type="button" value="&#8595;" onmouseup="selectMove('{!$id}', 1)" />
      <input type="hidden" value="{join(' ', $selected)}" id="v{!$id}" name="{$name}" />
    </td>
  </tr>
</table>

