<?php

/**
 * CommandExecution 
 * Executing external commands is a risky business, this class
 * provides a safe yet easy to use interface to command Execution.
 * 
 *  $foo = new CommandExecution();
 *  $foo->setCmd('ls');
 *  $foo->lha = dirname( __FILE__);
 *   var_dump($foo->getCmdForExec( ));
 *    echo $foo->getCmdResult();
 *
 * @package Flyspray
 * @version $Id$
 * @copyright 2007
 * @author Cristian Rodriguez <judas.iscariote@flyspray.org> 
 * @license BSD {@link http://www.opensource.org/licenses/bsd-license.php}
 * @notes experimental, WIP.
 */

class CommandExecution {

    /**
     * flags 
     * the command line flags 
     * @var array
     * @access public
     */
    var $flags = array();

    /**
     * command 
     * The command to be executed (doh!)
     * @var string 
     * @access public
     */
    var $command = NULL;

    /**
     * __set 
     * Overloading method to easily set command line flags
     * @param mixed $prop_name 
     * @param mixed $prop_value 
     * @access protected
     * @return bool
     */

    function __set($prop_name, $prop_value) 
    {
        
        if(!preg_match('/^[a-z0-9_]+$/iD', $prop_name)) {
            return false;
        }

        $this->flags[$prop_name] = escapeshellarg($prop_value);
        
        return true;
    }

    /**
     * setCmd 
     *  Set the command to execute f.e /bin/ls
     * @param string $command 
     * @access public
     * @return void
     */
    function setCmd($command)
    {
        $this->command = escapeshellcmd($command);
    }
    
    /**
     * getCmdForExec 
     * Returns the command ready and properly escaped for execution.
     * @access public
     * @return string
     */
    function getCmdForExec()
    {
        $cmd = $this->command;

        /* hint, hint.. silly workaround for PHP4 braindead overloading !! */
        $flags = $this->flags;

        foreach($flags as $option=>$value){

            $cmd .= sprintf(' -%s %s', $option, $value);
        }

        return $cmd;
    }

    /**
     * getCmdResult 
     *  returns the result returned by the command, or error in case the command failed. 
     * @access public
     * @return mixed string on sucess, false and error on faliure.
     */
    
    function getCmdResult() 
    {
       $descriptorspec = array(
                            0 => array('pipe', 'r'),
                            1 => array('pipe', 'w'),
                            2 => array('pipe', 'w')
                            );

       $proc = proc_open($this->getCmdForExec(), $descriptorspec, $pipes);
        //so long...       
       fclose($pipes[0]);

       $stdout = NULL;
       $stderr = NULL;
      
       foreach(array('stdout' => $pipes[1] , 'stderr' =>$pipes[2]) as $pipename=> $piperes) {

           while(!feof($piperes)) {
               $$pipename .= fgets($piperes);
           }

           fclose($piperes);
       }

       if(!empty($stderr)) {
           trigger_error(sprintf('Command %s failed : %s', htmlspecialchars($this->command), htmlspecialchars($stderr)));
           return false;
       }

       return $stdout;
    }

}
//PHP4 requires this non-sense
if(PHP_VERSION <  5) overload('CommandExecution');

