function createClosure(obj, method) {
    return (function() { obj[method](); });
}

function Perms(id) {
    this.div = document.getElementById(id);
}

Perms.prototype.timeout = null;
Perms.prototype.div     = null;

Perms.prototype.clearTimeout = function() {
    if (this.timeout) {
        clearTimeout(this.timeout);
        this.timeout = null;
    }
}

Perms.prototype.do_later = function(action) {
    this.clearTimeout();
    closure = createClosure(this, action);
    this.timeout = setTimeout(closure, 1000);
}

Perms.prototype.show = function() {
    this.clearTimeout();
    this.div.style.display = 'block';
    this.div.style.visibility = 'visible';
}

Perms.prototype.hide = function() {
    this.clearTimeout();
    this.div.style.display = 'none';
}

