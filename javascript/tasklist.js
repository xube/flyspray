Event.observe(window,'load',tasklistInit);
function tasklistInit() {
  if ($('tasklist_table')) {
    Caret.init();
  }
}

var Caret = {
  init: function () {
    Caret.currentRow = $('tasklist_table').getElementsByTagName('tbody')[0].getElementsByTagName('tr')[0];
    Event.observe(window,'keypress',Caret.keypress);
    Element.addClassName(Caret.currentRow,'current_row');
  },
  keypress: function (e) {
    var src = Event.element(e);
    if (/input|select|textarea/.test(src.nodeName.toLowerCase())) {
        // don't do anything if key is pressed in input, select or textarea
      return;
    }//106 -j 107 - k 111 - o

    if (e.altKey && 106 == e.charCode) {
      // user pressed "j" move down
      Element.removeClassName(Caret.currentRow,'current_row');
      Caret.nextRow();
      Element.addClassName(Caret.currentRow,'current_row');
      Event.stop(e);
    }
    if (e.altKey && 107 == e.charCode) {
      // user pressed "k" move up
      Element.removeClassName(Caret.currentRow,'current_row');
      Caret.previousRow();
      Element.addClassName(Caret.currentRow,'current_row');
      Event.stop(e);
    }
    if (e.altKey && 111 == e.charCode) {
      // user pressed "o" open task
      window.location = Caret.currentRow.getElementsByTagName('a')[0].href;
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
  },
  previousRow: function () {
    var row = Caret.currentRow;
    while ((row = row.previousSibling)) {
      if ('tr' == row.nodeName.toLowerCase()) {
        Caret.currentRow = row;
        return;
      }
    }
  }
};

