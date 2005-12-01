Event.observe(window,'load',detailsInit);

function detailsInit() {
  // set current task
  var title = document.getElementsByTagName('title')[0];
  title = title.textContent || title.text; //IE uses .text
  var arr = /\d+/.exec(title);
  Cookie.setVar('current_task',arr[0]);
  if (!$('details')) {
    // make sure the page is not in edit mode
    Event.observe(window,'keypress',keyboardNavigation);
  }
}
function keyboardNavigation(e) {
  var src = Event.element(e);
  if (/input|select|textarea/.test(src.nodeName.toLowerCase())) {
    // don't do anything if key is pressed in input, select or textarea
    return;
  }
  // u 117
  // p 112
  // n 110 
  if ((useAltForKeyboardNavigation && !e.altKey) ||
       e.ctrlKey || e.shiftKey) {
    return;
  }
  if (117 == e.charCode) {
    window.location = $('lastsearchlink').href;
    Event.stop(e);
  }
  if (112 == e.charCode) {
    if ($('prev')) {
      window.location = $('prev').href;
      Event.stop(e);
    }
  }
  if (110 == e.charCode) {
    if ($('next')) {
      window.location = $('next').href;
      Event.stop(e);
    }
  }

}