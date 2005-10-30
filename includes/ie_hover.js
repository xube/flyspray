addEvent(window,'load',setUpIEHover);
var g_severityCSSHoverBackground = Array();
var g_severityCSSHoverColor = Array();
var g_severityBackgroundColor;
var g_severityColor;
function setUpIEHover() {

  if (!document.getElementById('tasklist_table')) {
    return;
  }
  styleSheets = document.styleSheets;
  var sheet;
  for (var i = 0; i < styleSheets.length; i++) {
  //alert(styleSheets[i].href);

    if (styleSheets[i].href.indexOf('theme.css')>0) {
      sheet = styleSheets[i].cssText;
      break;
    }
    
  }
  // Remove the line breaks
  sheet = sheet.replace(/[\n\r]/gm,'');
  var arr;
  for (i = 1; i <=5; i++) {
     re = new RegExp('severity' + i +':hover {.*?\}','m');
     arr = re.exec(sheet);
     g_severityCSSHoverBackground['severity' +i] = getBackgroundColor(arr);
     g_severityCSSHoverColor['severity' + i] = getColor(arr);  
  }
  el = document.getElementById('tasklist_table');
  addEvent(el,'mouseover',tasklistTableMouseOver);
  addEvent(el,'mouseout',tasklistTableMouseOut);
}

function getBackgroundColor(str) {
  re = new RegExp('background-color.*?(#......)','i');
  re.exec(str);
  return RegExp.$1;
}
function getColor(str) {
  re = new RegExp('color.*?:.*?(#......)','i');
  re.exec(str);
  return RegExp.$1;
}

function tasklistTableMouseOver() {
  var src = window.event.srcElement;
  src = getRowFromCell(src);
  if (src && (src.parentNode.nodeName != 'THEAD')) {
    var className = src.className;
    g_severityBackgroundColor = src.style.backgroundColor;
    g_severityColor = src.style.color;
    src.style.backgroundColor = g_severityCSSHoverBackground[className];
    src.style.color = g_severityCSSHoverColor[className];
    src.style.cursor = 'pointer';
  }
}
function tasklistTableMouseOut(e) {
  var src = window.event.srcElement;
  src = getRowFromCell(src);
  if (src) {
    src.style.backgroundColor = g_severityBackgroundColor;
    src.style.color = g_severityColor;
    src.style.cursor = '';
  }
}

function getRowFromCell(el) {
  if (el.nodeName == 'TABLE') {
    return;
  }
  // Make sure the event came from a cell
  el = el.parentNode;
  while (el.nodeName != 'TR') {
    if (el.nodeName == 'TABLE') {
      return;
    }
    el = el.parentNode;
  }
  return el;
}
