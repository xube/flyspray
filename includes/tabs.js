/*
 * $Id: tabs.js 805 2004-12-23 00:02:21Z cr $
 */
addEvent(window, "load", initTabs);
function addEvent(elm, evType, fn, useCapture)
// addEvent and removeEvent
// cross-browser event handling for IE5+,  NS6 and Mozilla
// By Scott Andrew
{
  if (elm.addEventListener){
    elm.addEventListener(evType, fn, useCapture);
    return true;
  } else if (elm.attachEvent){
    var r = elm.attachEvent("on"+evType, fn);
    return r;
  } else {
    alert("Handler could not be removed");
  }
} 

var COOKIE_NAME = 'POLPAY_HELP_SHOWN';
var COOKIE_VALIDITY = 30;

var _HELP_DIVS, _TAB_DIVS;

// OBSŁUGA CIASTEK {{{
function createCookie(name,value,days) {
    if (days) {
	var date = new Date();
	date.setTime(date.getTime()+(days*24*60*60*1000));
	var expires = "; expires="+date.toGMTString();
    } else {
	expires = "";
    }
    document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
	var c = ca[i];
	while (c.charAt(0)==' ') c = c.substring(1,c.length);
	if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}
// }}}

// OBSŁUGA ZAKŁADEK {{{
// pokaż zakładkę o podanym ID
function showTabById(tabid) { // {{{
  var divs = document.getElementsByTagName('div');
  var tab = document.getElementById(tabid);
  var submenu = document.getElementById('submenu');
  var i;

  // ukryj wszystkie zakładki
  for (i=0; i<divs.length; i++) {
    if (divs[i].className && (divs[i].className.indexOf('tab') > -1)) {
      divs[i].style.display = 'none';
    }
  }
  // pokaż żądaną zakładkę
  if (tab) {
    tab.style.display = 'block';
    // ustaw klasę 'active' dla wywojącego elementu
    if (submenu) {
      links = submenu.getElementsByTagName('a');
      for (i=0; i<links.length; i++) { 
	if (links[i].href.match('^.*#'+tabid+'$')) {
	  links[i].className = 'active'; 
	} else { links[i].className = ''; }
      }
    }
  }
  if (window.scrollTo(0,0)) {
      window.scrollTo(0,0);
  }
} // }}}

// utwórz JavaScriptowe wywołania odsłaniające zakładki
function makeTabLinks() { // {{{
  var submenu = document.getElementById('submenu');
  var links, i, target;
 
  if (submenu) {
    links = submenu.getElementsByTagName('a');
    for (i=0; i<links.length; i++) {
      var href = links[i].getAttribute('href');
      target = href.substring(href.indexOf('#')+1);
      links[i]['onclick'] = new Function("showTabById('"+target+"'); return true;");
    }
  }
} // }}}

// pokaż zakładkę o podanym numerze
function showTabByNumber(number) { // {{{
  var targets = new Array();    // nazwy zakładek
  var divs = document.getElementsByTagName('div');
  var i;

  for (i=0; i<divs.length; i++) {
    if (divs[i].className == 'tab') {
      targets.push(divs[i].id);
    }
  }
  if (number >= targets.length) {
    number = targets.length-1;      
  }
  showTabById(targets[number]);
} // }}}

// zwróć listę DIVów zawierających pomoc
function getHelpDivs() {/*{{{*/
  if (_HELP_DIVS == null) {
    var divs = document.getElementsByTagName('div');
    var i;
    _HELP_DIVS = new Array();

    for (i=0; i<divs.length; i++) {
	if (divs[i].className) {
	    if ((divs[i].className == 'help') || 
		    (divs[i].className == 'hideHelp')) {
		_HELP_DIVS.push(divs[i]);
	    }
	}
    }
  }
  return _HELP_DIVS;
}/*}}}*/
  
// zwróć listę DIVów zawierających zakładki
function getTabDivs() {/*{{{*/
    if (_TAB_DIVS == null) {
	_TAB_DIVS = new Array();
	var divs = document.getElementsByTagName('div');
	var i;
	for (i=0; i<divs.length; i++) {
	    if (divs[i].className && (divs[i].className.indexOf('tab') > -1)) {
		_TAB_DIVS.push(divs[i]);
	    }
	}
    }
    return _TAB_DIVS;
}/*}}}*/

// ustaw prawostronne dopełnienie elementu body
function setBodyPadding(newWidth) {/*{{{*/
    newWidth += 20;
    document.getElementById("menu").style.paddingRight = ""+newWidth+"px";
    document.getElementById("content").style.paddingRight = ""+newWidth+"px";
    // document.getElementsByTagName('body')[0].style.paddingRight = ""+newWidth+"px";
}/*}}}*/

// czy panel pomocy jest rozwinięty?
function isHelpShown() {/*{{{*/
  var divs = getHelpDivs();
  if (divs.length > 0) {
    return (divs[0].className == "help");
  } else {
    return false;
  }
}/*}}}*/

// zwiń panel pomocy -- podmień nazwę klasy elementów pomocy
// patrz również: showHelp()
function hideHelp() {/*{{{*/
  var i, divs = getHelpDivs();
  setBodyPadding(0);
  for (i=0; i<divs.length; i++) {
    divs[i].className = "hiddenhelp";
  }
  createCookie(COOKIE_NAME, 0, COOKIE_VALIDITY);
}/*}}}*/

// rozwiń panel pomocy -- podmień nazwę klasy elementów pomocy
// patrz również: hideHelp()
function showHelp() {/*{{{*/
  var i, divs = getHelpDivs();
  setBodyPadding(150);
  for (i=0; i<divs.length; i++) {
    divs[i].className = "help";
  }
  createCookie(COOKIE_NAME, 1, COOKIE_VALIDITY);
}/*}}}*/

// przestaw widoczność panelu pomocy
function toggleHelpWidth() {/*{{{*/
  if (getHelpDivs().length > 0) {
    if (isHelpShown()) { hideHelp(); }
    else { showHelp(); }
  }
}/*}}}*/

// utwórz reakcję na kliknięcie na panelu pomocy
function createTabToggle() {/*{{{*/
  var i, divs = getHelpDivs();

  for (i=0; i<divs.length; i++) {
    divs[i]["onclick"] = new Function("toggleHelpWidth()");
  }
}/*}}}*/

// utwórz puste DIVy pomocy w zakładkach, w których ich brakuje
function createMissingHelpDivs() {/*{{{*/
    var i, divs = getTabDivs();
    for (i=0; i<divs.length; i++) {
	var j, innerdivs = divs[i].getElementsByTagName('div');
	var helppresent = false;
	
	for (j=0; j<innerdivs.length; j++) {
	    if (innerdivs[j].className &&
		    (innerdivs[j].className.indexOf('help') >=0)) {
		helppresent = true;
		break;
	    }
	}
	
	if (!helppresent) {
	    var helpdiv = document.createElement('div');
	    helpdiv.className = 'help';
	    divs[i].appendChild(helpdiv);
	}
    }
}/*}}}*/

// inicjalizacja zakładek
// pokaż pierwszą zakładkę lub zakładkę o nazwie podanej w adresie (za #)
function initTabs() {/*{{{*/
  var target = location.href.substring(location.href.indexOf('#')+1);
  makeTabLinks();
  createMissingHelpDivs();
  createTabToggle();

  if (target && document.getElementById(target)) {
    showTabById(target);
  } else {
    showTabByNumber(0);
  }

  var helpVisible = readCookie(COOKIE_NAME);
  if (helpVisible > 0) {
    showHelp();
  } else {
    hideHelp();
  }
}/*}}}*/

// }}}
// vim:enc=utf-8:fenc=utf-8:fdm=marker
