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
  src = eventGetSrc(e);
  if (src.nodeName != 'TD') {
    return;
  }
  // remove the word 'task' from "task123"
  id = src.parentNode.id.substr(4);
  window.location = '?do=details&id=' + id;
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
  for (var i = 0; i < document.massops.elements.length; i++) {
    if(document.massops.elements[i].type == 'checkbox'){
      document.massops.elements[i].checked =         !(document.massops.elements[i].checked);
    }
  }
}
