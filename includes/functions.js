function Disable1()
{
   document.form1.buSubmit.disabled = true;
   document.form1.submit();
}

function Disable2()
{
   document.form2.buSubmit.disabled = true;
   document.form2.submit();
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

window.setTimeout(fader,4000);
function fader() {
  if (document.getElementById('errorbar')) {
    el = document.getElementById('errorbar');
  } else if (document.getElementById('successbar')) {
    el = document.getElementById('successbar');
  } else {
    return;
  }
  opacity = el.style.opacity;
  if (opacity == '') {
    el.style.opacity = 1;
    window.setTimeout(fader,10);
  } else if (opacity == 0) {
    el.style.display = 'none';
  } else if (!isNaN(opacity)) {
    opacity -= .01;
    el.style.opacity = opacity;
    window.setTimeout(fader,10);
  }
}

function ToggleSelectedTasks() {
  for (var i = 0; i < document.massops.elements.length; i++) {
    if(document.massops.elements[i].type == 'checkbox'){
      document.massops.elements[i].checked =         !(document.massops.elements[i].checked);
    }
  }
}