function createClosure(obj, method) {
    return (function() { obj[method](); });
}

function Perms(id) {
    this.div = document.getElementById(id);
}

Perms.prototype.timeout = null;
Perms.prototype.div     = null;

Perms.prototype.show = function() {
    this.div.style.display = 'block';
    this.div.style.visibility = 'visible';
}

Perms.prototype.hide = function() {
    this.div.style.display = 'none';
}

Perms.prototype.hover = function() {
    if (this.timeout) {
        clearTimeout(this.timeout);
        this.timeout = null;
    }
    closure = createClosure(this, 'hide');
    this.timeout = setTimeout(closure, 1500);
}