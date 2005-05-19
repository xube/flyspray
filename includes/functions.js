// Set up the error/success bar fader
addEvent(window,'load',setUpFade);
// Set up the task list onclick handler
addEvent(window,'load',setUpTasklistTable);

function Disable(formid)
{
   document.formid.buSubmit.disabled = true;
   document.formid.submit();
}

function openTask( url )
{
   window.location = url;
}

 function showstuff(boxid){
   document.getElementById(boxid).style.visibility="visible";
}

function hidestuff(boxid){
   document.getElementById(boxid).style.visibility="hidden";
}

function showhidestuff(boxid) {
   switch (document.getElementById(boxid).style.visibility) {
      case '': document.getElementById(boxid).style.visibility="visible"; break
      case 'hidden': document.getElementById(boxid).style.visibility="visible"; break
      case 'visible': document.getElementById(boxid).style.visibility="hidden"; break
   }
}

function setUpFade() {
  if (document.getElementById('errorbar')) {
    elName = 'errorbar';
  } else if (document.getElementById('successbar')) {
    elName = 'successbar';
  } else {
    return;
  }
  fader(elName,2000,50,2500);
}
// Fades an element
// elName - id of the element
// start - time in ms when the fading should start
// steps - number of fading steps
// time - the length of the fade in ms
function fader(elName,start,steps,time) {
  setOpacity(elName,100); // To prevent flicker in Firefox
                          // The first time the opacity is set
                          // the element flickers in Firefox
  fadeStep = 100/steps;
  timeStep = time/steps;
  opacity = 100;
  time = start + 100;
  while (opacity >=0) {
    window.setTimeout("setOpacity('"+elName+"',"+opacity+")",time);
    opacity -= fadeStep;
    time += timeStep;
  }
}
function setOpacity(elName,opacity) {
  opacity = (opacity == 100)?99:opacity;
  el = document.getElementById(elName);
  // IE
  el.style.filter = "alpha(opacity:"+opacity+")";
  // Safari < 1.2, Konqueror
  el.style.KHTMLOpacity = opacity/100;
  // Old Mozilla
  el.style.MozOpacity = opacity/100;
  // Safari >= 1.2, Firefox and Mozilla, CSS3
  el.style.opacity = opacity/100
}
function setUpTasklistTable() {
  if (!document.getElementById('tasklist_table')) {
    // No tasklist on the page
    return;
  }
  var table = document.getElementById('tasklist_table');
  addEvent(table,'click',tasklistTableClick);
}
function tasklistTableClick(e) {
  var src = eventGetSrc(e);
  if (src.nodeName != 'TD') {
    return;
  }
  if (src.hasChildNodes()) {
    var checkBoxes = src.getElementsByTagName('input');
    if (checkBoxes.length > 0) {
      // User clicked the cell where the task select checkbox is
      if (checkBoxes[0].checked) {
        checkBoxes[0].checked = false;
      } else {
        checkBoxes[0].checked = true;
      }
      return;
    }
  }
  var row = src.parentNode;
  var aElements = row.getElementsByTagName('A');
  if (aElements.length > 0) {
    window.location = aElements[0].href;
  } else {
    // If both the task id and the task summary columns are non-visible
    // just use the good old way to get to the task
    window.location = '?do=details&id=' + row.id.substr(4);
  }
}

function eventGetSrc(e) {
  if (e.target) {
    return e.target;
  } else if (window.event) {
    return window.event.srcElement;
  } else {
    return;
  }
}

function ToggleSelectedTasks() {
  var inputs = document.getElementById('massops').getElementsByTagName('input');
  for (var i = 0; i < inputs.length; i++) {
    if(inputs[i].type == 'checkbox'){
      inputs[i].checked = !(inputs[i].checked);
    }
  }
  // Return false to prevent the the browser from following the href
  return false;
}

function addUploadFields() {
  // Get all the table rows
  var el = document.getElementById('uploadfilebox');
  // The text
  el.appendChild(el.childNodes[0].cloneNode(false));
  // The input box
  var input = document.createElement('input');
  input.setAttribute('type','file');
  input.setAttribute('size','55');
  input.setAttribute('name','userfile[]');
  el.appendChild(input);
  // The <br />
  el.appendChild(el.childNodes[2].cloneNode(false));
}
