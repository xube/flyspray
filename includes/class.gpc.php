<?php
// {{{ class Req

class Req
{
    function has($key)
    {
        return isset($_REQUEST[$key]) && $_REQUEST[$key] != '';
    }
    
    function val($key, $default = null)
    {
        return Req::has($key) ? $_REQUEST[$key] : $default;
    }
}

// }}}
// {{{ class Post

class Post
{
    function has($key)
    {
        // XXX semantics is different for POST, as POST of '' values is never
        //     unintentionnal, whereas GET/COOKIE may have '' values for empty
        //     ones.
        return isset($_POST[$key]);
    }
    
    function val($key, $default = null)
    {
        return Post::has($key) ? $_POST[$key] : $default;
    }
}

// }}}
// {{{ class Get

class Get
{
    function has($key)
    {
        return isset($_GET[$key]) && $_GET[$key] != '';
    }
    
    function val($key, $default = null)
    {
        return Get::has($key) ? $_GET[$key] : $default;
    }
}

// }}}
// {{{ class Cookie

class Cookie
{
    function has($key)
    {
        return isset($_COOKIE[$key]) && $_COOKIE[$key] != '';
    }
    
    function val($key, $default = null)
    {
        return Cookie::has($key) ? $_COOKIE[$key] : $default;
    }
}

// }}}
?>
