<?php
// {{{ class Req

class Req
{
    function has($key)
    {
        return isset($_REQUEST[$key]);
    }
    
    function val($key, $default = null)
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }
}

// }}}
// {{{ class Post

class Post
{
    function has($key)
    {
        return isset($_POST[$key]);
    }
    
    function val($key, $default = null)
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
}

// }}}
// {{{ class Get

class Get
{
    function has($key)
    {
        return isset($_GET[$key]);
    }
    
    function val($key, $default = null)
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
}

// }}}
// {{{ class Cookie

class Cookie
{
    function has($key)
    {
        return isset($_COOKIE[$key]);
    }
    
    function val($key, $default = null)
    {
        return isset($_COOKIE[$key]) ? $_COOKIE[$key] : $default;
    }
}

// }}}
?>
