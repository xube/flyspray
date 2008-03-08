<?php
/**
 * Abstract SVN hook handler
 *
 */
abstract class AbstractHandler {
	/**
	 * Hook repository path
	 *
	 * @var string
	 */
	protected $repos;
	/**
   * Hook repository name (without path)
   * 
   * @var string       
   */
	protected $reposName;
	/**
	 * Hook repository revision
	 *
	 * @var int
	 */
	protected $rev;
	/**
	 * Hook repository  user
	 *
	 * @var string
	 */
	protected $user;

	/**
	 * Revision wrapper
	 *
	 * @var SVNRevisionWrapper
	 */
	protected $revision_wrapper;

	/**
	 * Commands to send to flyspray
	 *
	 * @var array<FlySprayCommand>
	 */
	protected $commands = array();

	/**
	 * Last error text
	 *
	 * @var string
	 */
	public $error = "";

	/**
	 * Constructor
	 *
	 * @param string $repos hook repository
	 * @param int $rev hook revision
	 * @param string $user hook user
	 */
	public function __construct($repos = "", $rev = 0, $user = "") {
		$this->repos = $repos;
		if($repos)
		{
      $parts = explode('/',$repos);
      if(!count($parts))
        $parts = explode('\\',$repos);
      
      $this->reposName = $parts[count($parts)-1];
    }
		$this->rev = $rev;
		$this->user = $user;

		$this->revision_wrapper = new SVNRevisionWrapper($this->rev);
	}

	/**
	 * Commands preparation
	 *
	 * @return bool
	 *
	 */
	abstract public function prepare();

	/**
	 * Send commands to flyspray
	 *
	 * @return bool
	 */
	public function send() {
		try {
			if (!count($this->commands)) return true;

			$conf = SVNHooksConfig::get_instance();
			$cookie_file= $conf->root_dir . "/cookie.tmp";

			$this->login($conf->fs_config->url, $cookie_file, $conf->fs_config->admin_login, $conf->fs_config->admin_pwd);
			$this->transfer($conf->fs_config->api_url, $cookie_file, json_encode($this->commands));
		} catch (Exception $exc) {
			$this->error = $exc->getMessage();
			dbg::get_instance()->write("AbstractHandler::send Exception:\r\n" . $this->error);
			return false;
		}

		return true;
	}

	/**
	 * Login to flyspray
	 *
	 * @param string $url
	 * @param string $cookie_file
	 * @param string $login
	 * @param string $pwd
	 * @throws Exception
	 */
	private function login($url, $cookie_file, $login, $pwd) {
		try {
			$result = $this->send_request($url, $cookie_file, "user_name=$login&password=$pwd");
			dbg::get_instance()->write("login result:\r\n" . $result);
				
			if (strpos($result, "index.php?do=admin") === false) {
				dbg::get_instance()->write("Invalid admin login");
				throw new Exception("Invalid admin login", 999);
			} else {
				dbg::get_instance()->write("Logged in as admin");
			}
		} catch (Exception $exc) {
			dbg::get_instance()->write("AbstractHandler::login threw Exception");
			throw $exc;
		}
	}

	/**
	 * Send commands to flyspray
	 *
	 * @param string $url
	 * @param string $cookie_file
	 * @param string $data JSON encoded array of commands
	 * @throws Exception
	 */
	private function transfer($url, $cookie_file, $data) {
		try {
			dbg::get_instance()->write("Commands to transfer:\r\n" . $data);
			$result = $this->send_request($url, $cookie_file, "commands=$data");
			dbg::get_instance()->write("transfer result:\r\n" . $result);
		} catch (Exception $exc) {
			dbg::get_instance()->write("AbstractHandler::transfer threw Exception");
			throw $exc;
		}
	}

	/**
	 * Sends request and returns response
	 *
	 * @param string $url
	 * @param string $cookie_file
	 * @param string $data POST data to be sent
	 * @return string
	 * @throws Exception
	 */
	private function send_request($url, $cookie_file, $data) {
		$ch = curl_init($url);
		if (!$ch) throw new Exception(curl_error($ch), 1);

		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

		$page_content = curl_exec($ch);
		curl_close($ch);

		if (strlen($page_content) < 1) throw new Exception("Page text length is less 1!", 2);

		if (strpos($page_content, "HTTP/1.1 400 Bad Request") !== false) throw new Exception("Bad request", 400);
		if (strpos($page_content, "HTTP/1.1 200 OK") === false) throw new Exception("Not OK", 200);

		return $page_content;
	}
}
?>
