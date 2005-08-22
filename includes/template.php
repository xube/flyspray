<?php
// +----------------------------------------------------------------------
// | PHP Source                                                           
// +----------------------------------------------------------------------
// | Copyright (C) 2003 Brian E. Lozier (brian@massassi.net)
// +----------------------------------------------------------------------
// |
// | Copyright: See COPYING file that comes with this distribution
// +----------------------------------------------------------------------
//
// http://www.sitepoint.com/print/beyond-template-engine

/**
 * Copyright (c) 2003 Brian E. Lozier (brian@massassi.net)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

if (!defined('VALID_FLYSPRAY')) die('Sorry you cannot access this file directly');

class Template
{
  var $vars = array(); /// Holds all the template variables
  var $path; /// Path to the templates

  /**
   * Constructor
   * @param string $path the path to the templates
   * @return void
   */
  function Template($path = null)
  {
    $this->path = $path;
  }

  /**
   * Set the path to the template files.
   * @param string $path path to template files
   * @return void
   */
  function SetPath($path)
  {
    $this->path = $path;
  }

  /**
   * Set a template variable.
   * @param string $name name of the variable to set
   * @param mixed $value the value of the variable
   * @return void
   */
  function Set($name, $value)
  {
    $this->vars[$name] = $value;
  }

  /**
   * Set a bunch of variables at once using an associative array.
   * @param array $vars array of vars to set
   * @param bool $clear whether to completely overwrite the existing vars
   * @return void
   */
  function SetVars($vars, $clear = false)
  {
    if($clear)
    {
      $this->vars = $vars;
    }
    else
    {
      if(is_array($vars)) $this->vars = array_merge($this->vars, $vars);
    }
  }
    
  /**
   * Append a template variable.
   * @param string $name name of the variable to set
   * @param mixed $value the value of the variable
   * @return void
   */
  function Append($name, $value)
  {
    if (isset($this->vars[$name]))
    {
      $this->vars[$name] = $this->vars[$name]  . $value;
    }
    else
    {
      $this->vars[$name] = $value;
    }
  }


  /**
   * Open, parse, and return the template file.
   * @param string string the template file name
   * @return string
   */
  function Fetch($file)
  {
    extract($this->vars);          // Extract the vars to local namespace
    ob_start();                    // Start output buffering
    include($this->path . '/' . $file);  // Include the file
    $contents = ob_get_contents(); // Get the contents of the buffer
    ob_end_clean();                // End buffering and discard
    return $contents;              // Return the contents
  }
}
?>
