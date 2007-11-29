<?php

/** 
 *  This library really implements what http://recaptcha.net/apidocs/captcha
 *  says.. This is a standalone component to benefit the comunity, do not add
 *  flyspray specific code to this class.
 * @package Flyspray
 * @version $Id$
 * @copyright 2007
 * @author Cristian Rodriguez <judas.iscariote@flyspray.org> 
 * @license BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 */


/* get this file from:
 * http://cvs.php.net/viewvc.cgi/pear/PHP_Compat/Compat/Function/http_build_query.php?view=co
 * this is only needed when running PHP4
 */

//require dirname(__FILE__) . '/compat/http_build_query.php';

/* reCAPTCHA Protocol: Servers */
defined('RECAPTCHA_API_SERVER')        || define('RECAPTCHA_API_SERVER', 'http://api.recaptcha.net');
defined('RECAPTCHA_API_SERVER_SECURE') || define('RECAPTCHA_API_SECURE_SERVER', 'https://api-secure.recaptcha.net');
defined('RECAPTCHA_VERIFY_SERVER')     || define('RECAPTCHA_VERIFY_SERVER', 'api-verify.recaptcha.net');
defined('RECAPTCHA_API_URL_KEY')       || define('RECAPTCHA_API_KEY_URL', 'http://recaptcha.net/api/getkey?');



/**
 * reCAPTCHA_Challenge 
 *  
 *  Object representing a reCAPTCHA Challenge.
 *
 *  $captcha =& new reCAPTCHA_Challenge();
 *  $captcha->setTheme('blackglass');
 *  $captcha->publickey = $public_key
 *  echo $captcha->getChallenge();
 *
 * @package Flyspray
 * @version $Id$
 * @copyright 2007
 * @author Cristian Rodriguez <judas.iscariote@flyspray.org> 
 * @license BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 */

class reCAPTCHA_Challenge 
{
    /**
     * public_key (required)
     * Your public API key, from the reCAPTCHA sign-up page
     * @var string
     * @access public
     */
    var $publickey = null;

    /**
     * error(optional) 
     *  The error code returned from a previous call to reCAPTCHA.
     * @var string
     * @access public
     */

    var $error = null;

    /**
     * theme 
     * Defines which theme to use for reCAPTCHA
     * 'red' | 'white' | 'blackglass'
     * @var string
     * @access public
     * @see SetTheme
     */

    var $theme = 'red';


    /**
     * tabindex 
     * sets a tabindex  for the reCAPTCHA text box. 
     * If other elements in the form use a tabindex, 
     * this should be set so that navigation is easier for the user.
     *
     * @var int
     * @access public
     */

    var $tabindex = 0;

    /**
     * use_ssl
     * shoudl the captcha by used over SSL
     * @var mixed
     * @access public
     */
    var $use_ssl = false;


    /**
     * reCAPTCHA_Challenge 
     * ctor
     * @param bool $use_ssl 
     * @access public
     * @return void
     */
    function reCAPTCHA_Challenge($use_ssl = false)       
    {
        $this->use_ssl = (bool) $use_ssl;
    }

    /**
     * getResponse 
     *  The JavaScript inserts an 
     *  reCAPTCHA HTML widget into your page which includes two form fields - 
     *  'recaptcha_challenge_field' and 'recaptcha_response_field'.
     * @access public
     * @return string
     */

    function getChallenge()
    {
        return sprintf('<script type="text/javascript">
                        var RecaptchaOptions = {
                        theme : %4$s,
                        tabindex : %5$d
                        };
                        </script>
                <script type="text/javascript" src="%1$s/challenge?k=%2$s&error=%3$s"> </script>
                <noscript>
                <iframe src="%1$s/noscript?k=%2$s&error=%3$s"
                height="300" width="500" frameborder="0"></iframe><br>
                <textarea name="recaptcha_challenge_field" rows="3" cols="40">
                </textarea>
                <input type="hidden" name="recaptcha_response_field" value="manual_challenge">
                </noscript>', ($this->use_ssl ? RECAPTCHA_API_SERVER_SECURE : RECAPTCHA_API_SERVER) ,
                               urlencode($this->publickey), urlencode($this->error), 
                               sprintf("'%s'", $this->theme), $this->tabindex);
    }

    /**
     * setTheme 
     *  Set the captcha theme, if $theme not valid, the deafult skin will be used.
     * @param string $theme 
     * @access public
     * @return void
     */
    function setTheme($theme)
    {
        if(in_array($theme, array('red', 'white', 'blackglass'))) {
            $this->theme = $theme;
        }
    }
}

/**
 * reCAPTCHA_Solution 
 *  Object that represents a reCAPTCHA_Solution
 *
 *  $solution =& new reCAPTCHA_Solution();
 *  $solution->privatekey = $yourprivatekey
 *  $solution->remoteip = $_SERVER['REMOTE_ADDR'];
 *  $solution->challenge = $_POST['recaptcha_challenge_field'];
 *  $solution->response = $_POST['recaptcha_response_field'];
 *  if($solution->isValid()) {
 *    echo "you are a human"
 *  } else {
 *    printf('Error:%s', $solution->error_code);
 *  }
 *
 * @package Flyspray
 * @version $Id$
 * @copyright 2007
 * @author Cristian Rodriguez <judas.iscariote@flyspray.org> 
 * @license BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 */
class reCAPTCHA_Solution 
{
    /**
     * privatekey (required)
     * Your private key
     * @var mixed
     * @access public
     */
    var $privatekey = null;

    /**
     * remoteip(required) 
     * The IP address of the user who solved the CAPTCHA. 
     * @var float
     * @access public
     */
    var $remoteip = null;

    /**
     * challenge(required) 
     * The value of "recaptcha_challenge_field" sent via the form
     * @var string
     * @access public
     */
    var $challenge = null;

    /**
     * response (required)
     *  The value of "recaptcha_response_field" sent via the form
     * @var mixed
     * @access public
     */
    var $response = null;

    /**
     * error_code 
     * 
     * @var string
     * @access public
     */
    var $error_code = null;


    /**
     * getSolution 
     * returns 
     * @access public
     * @return bool
     */
    function isValid()
    {
        $server_response = '';
        $request = array(); 
        /* "If the value of "recaptcha_challenge_field" or "recaptcha_response_field" is not set, 
         * avoid sending a request" */

        if(empty($this->challenge) || empty($this->response)) {
            $this->error_code = 'incorrect-captcha-sol';
            return false;
        }

        $payload = http_build_query(array('privatekey' => $this->privatekey,
                                           'remoteip' => (empty($this->remoteip) ? $_SERVER['REMOTE_ADDR'] : $this->remoteip),
                                           'challenge' => $this->challenge,
                                           'response' => $this->response));

        $request[] = 'POST /verify HTTP/1.0';
        $request[] = sprintf('Host: %s', RECAPTCHA_VERIFY_SERVER);
        $request[] = 'Content-Type: application/x-www-form-urlencoded';
        $request[] = sprintf('Content-Length: %d', strlen($payload));
        $request[] = 'User-Agent: reCAPTCHA/PHP/Flyspray';
        $request[] = "\r\n";
        $finalrequest = implode("\r\n", $request) . $payload . "\r\n\r\n";

        // RECAPTCHA_VERIFY_SERVER only listens on non-ssl ..
        if($sh = @fsockopen(RECAPTCHA_VERIFY_SERVER, 80, $errno, $errstr, 10)) {

            stream_set_blocking($sh, 0);        
            
            if(fwrite($sh, $finalrequest) !== false) {
                
                while (!feof($sh)) {
                        $server_response .= fgets($sh);
                }
                
                //have the data.. say good bye ASAP.
                fclose($sh);

                if($server_response) {

                    $pos = strpos($server_response, "\r\n\r\n");
                    
                    if ($pos !== false) {
                        //strip the http headers.
                        $server_response = substr($server_response, $pos + 2 * strlen("\r\n"));
                    }
                        /* "The response from verify is a series of strings separated by \n.
                         * To read the string, split the line and read each field.
                        * New lines may be added in the future. Implementations should ignore these lines"
                        */
                    $server_response = array_map('trim', explode("\n", $server_response, 2));

                    if(count($server_response)) {
                        if($server_response[0] === 'false') {
                            $this->error_code = isset($server_response[1]) ? $server_response[1] : 'unknown';
                        } elseif($server_response[0] === 'true') {
                            return true;
                        }
                    }
                }
            }
        
        } else {
                /* "A plugin should manually return this code in the unlikely event that 
                 * it is unable to contact the reCAPTCHA verify server" */
                $this->error_code = 'recaptcha-not-reachable';
        }

            return false;

    }
}


?>
