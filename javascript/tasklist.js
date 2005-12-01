Event.observe(window,'load',tasklistInit);
function tasklistInit() {
  Caret.init();
}
var Caret = {
  init: function () {
    var task = Cookie.getVar('current_task');
    if ('bottom' == task) {
      var rows = $('tasklist_table').getElementsByTagName('tbody')[0].getElementsByTagName('tr');  
      Caret.currentRow = rows[rows.length-1];
      Cookie.removeVar('current_task');
    } else if (task && $('task'+task)) {
      Caret.currentRow = $('task'+task);
    } else {
      Caret.currentRow = $('tasklist_table').getElementsByTagName('tbody')[0].getElementsByTagName('tr')[0];
    }
    Element.addClassName(Caret.currentRow,'current_row');
    Event.observe(window,'keypress',Caret.keypress);
  },
  keypress: function (e) {
    var src = Event.element(e);
    if (/input|select|textarea/.test(src.nodeName.toLowerCase())) {
        // don't do anything if key is pressed in input, select or textarea
      return;
    }//106 -j 107 - k 111 - o
    if ((useAltForKeyboardNavigation && !e.altKey) ||
        (!useAltForKeyboardNavigation && e.altKey) ||
         e.ctrlKey || e.shiftKey) {
      return;
    }
    // if:s instead of a switch, I expect to do some IE tweaking later
    if (106 == e.charCode) {
      // user pressed "j" move down
      Element.removeClassName(Caret.currentRow,'current_row');
      Caret.nextRow();
      Element.addClassName(Caret.currentRow,'current_row');
      Event.stop(e);
    }
    if (107 == e.charCode) {
      // user pressed "k" move up
      Element.removeClassName(Caret.currentRow,'current_row');
      Caret.previousRow();
      Element.addClassName(Caret.currentRow,'current_row');
      Event.stop(e);
    }
    if (111 == e.charCode) {
      // user pressed "o" open task
      window.location = Caret.currentRow.getElementsByTagName('a')[0].href;
      Event.stop(e);
    }
  },
  nextRow: function () {
    var row = Caret.currentRow;
    while ((row = row.nextSibling)) {
      if ('tr' == row.nodeName.toLowerCase()) {
        Caret.currentRow = row;
        return;
      }
    }
    // we've reached the bottom of the list
    if ($('next')) {
      window.location = $('next').href;
      return;
    }
  },
  previousRow: function () {
    var row = Caret.currentRow;
    while ((row = row.previousSibling)) {
      if ('tr' == row.nodeName.toLowerCase()) {
        Caret.currentRow = row;
        return;
      }
    }
    // we've reached the top of the list
    if ($('previous')) {
      Cookie.setVar('current_task','bottom');
      window.location = $('previous').href;
      return;
    }
    
  }
};

