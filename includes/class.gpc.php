<?php
// {{{ class  Req

class Req
{
    function has($key)
    {
        return isset($_REQUEST[$key]);
    }

    function val($key, $default = null)
    {
        return Req::has($key) ? $_REQUEST[$key] : $default;
    }

    //it will always return a number no matter what(null is 0)
    function num($key, $default = null)
    {
        return Filters::num(Req::val($key, $default));
    }
    
    function enum($key, $options, $default = null)
    {
        return Filters::enum(Req::val($key, $default), $options);
    }

    //always a string (null is typed to an empty string)
    function safe($key)
    {
        return Filters::noXSS(Req::val($key));
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

    //it will always return a number no matter what(null is 0)
    function num($key, $default = null)
    {
        return Filters::num(Post::val($key, $default));
    }

    //always a string (null is typed to an empty string)
    function safe($key)
    {
        return Filters::noXSS(Post::val($key));
    }
}

// }}}
// {{{ class Get

class Get
{
    function has($key)
    {
        return isset($_GET[$key]) && $_GET[$key] !== '';
    }

    function val($key, $default = null)
    {
        return Get::has($key) ? $_GET[$key] : $default;
    }

    //it will always return a number no matter what(null is 0)
    function num($key, $default = null)
    {
        return Filters::num(Get::val($key, $default));
    }

    //always a string (null is typed to an empty string)
    function safe($key)
    {
        return Filters::noXSS(Get::val($key));
    }

    function clean($key)
    {
        return Filters::noHTML(Get::val($key));
    }
    
    function enum($key, $options, $default = null)
    {
        return Filters::enum(Get::val($key, $default), $options);
    }

}

// }}}
//{{{ class  Cookie

class Cookie
{
    function has($key)
    {
        return isset($_COOKIE[$key]) && $_COOKIE[$key] !== '';
    }

    function val($key, $default = null)
    {
        return Cookie::has($key) ? $_COOKIE[$key] : $default;
    }
}
//}}}
 /*{{{  Class Filters
 *
 * This is a simple class for safe input validation
 * no mixed stuff here, functions returns always the same type.
 * @author Cristian Rodriguez R <soporte@onfocus.cl>
 * @license BSD
 */

class Filters {
    /**
     *  give me a  number only please?
     *  @return int
     *  @access public static
     */

    function num($data)
    {
         return (int) $data;
    }
    /**
    * Give user input free from potentially mailicious html
    * @return string
    * @access public static
    */

    function noXSS($data)
    {
        return (string) htmlspecialchars($data, ENT_QUOTES , 'utf-8');
    }
    /**
     * in the case we don't want html..
     * @return string
     * @access public static
     */

    function noHTML($data)
    {
        return (string) strip_tags($data);
    }

    function isAlnum($data)
    {
        return (bool) strlen($data) ? ctype_alnum($data) : false;
    }
    
    function enum($data, $options)
    {
        if (!in_array($data, $options) && isset($options[0])) {
            return $options[0];
        }
        
        return $data;
    }
}

?>
