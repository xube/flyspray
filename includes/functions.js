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