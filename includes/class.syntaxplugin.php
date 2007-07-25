<?php

/*
 * "Interface" for Flyspray's syntax plugins
 * For common sense, at least one of the methods beforeCache() or afterCache
 * should be implemented. getOrder() must be implemented.
 */
class SyntaxPlugin
{
    /*
     * Decides on the order the plugins are run.
     * Lower = earlier. If two plugins have the same order,
     * only one is used.
     *
     * @return int
     */
    function getOrder() {
        return -1;
    }

    /*
     * If this returns true, the syntax plugin is used
     * in areas with "basic" syntax (closure comments etc) as well.
     *
     * @return bool false by default
     */
    function isBasic() {
        return false;
    }

    /*
     * Rules that need to be applied before caching.
     * So whatever output a plugin generates is saved in the
     * cache.
     *
     * @param string $input by reference, manipulate its contents
     */
    function beforeCache(&$input) { }

    /*
     * Rules that need to be applied after
     * loading data from cache, for example
     * hyperlinks to tasks which always need to be current
     *
     * @param string $input by reference, manipulate its contents
     */
    function afterCache(&$input) { }

    /*
     * Allows to add some HTML before the actual text area (toolbars etc)
     *
     * @return string %id in the output string is replaced with the textarea id
     */
    function getHtmlBefore() { return ''; }

    /*
     * Allows to add some HTML after the actual text area (buttons etc)
     *
     * @return string %id in the output string is replaced with the textarea id
     */
    function getHtmlAfter() { return ''; }
}

?>