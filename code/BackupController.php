<?php 

/**
* The Controller which is used to call the BackUpTask
*
* @package backup
*/
class BackupController extends Controller{
	
	static $allowed_actions = array('BackupNow', 'logInAsAdmin');

	/**
	*	Runs the backup task and returns a JSON encoded array with a status value and a data value
	*
	*	@return String
	*/
	public function BackupNow(){
		if($this->verifyIP()){
			$command = sprintf(
				'cd %s && php framework/cli-script.php %s',
				escapeshellarg(BASE_PATH),
				escapeshellarg('/BackupTask')
				);
			shell_exec($command);

			$links = $this->grabDumps();

			return json_encode(array('Status'=>'Success', 'Data' => $links));
		} else {
			return json_encode(array('Status'=>'Error', 'Data' => 'Your IP address does not match the nominated IP addresses for this task'));
		}
	}

	/**
	*	Determins the client's IP Address
	*
	*	@return String
	*/
	public function get_client_ip() {
	     $ipaddress = '';
	     if (array_key_exists('HTTP_CLIENT_IP', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	     else if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	     else if(array_key_exists('HTTP_X_FORWARDED', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	     else if(array_key_exists('HTTP_FORWARDED_FOR', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	     else if(array_key_exists('HTTP_FORWARDED', $_SERVER))
	         $ipaddress = $_SERVER['HTTP_FORWARDED'];
	     else if(array_key_exists('REMOTE_ADDR', $_SERVER))
	         $ipaddress = $_SERVER['REMOTE_ADDR'];
	     else
	         $ipaddress = 'UNKNOWN';

	     return $ipaddress; 
	}

	/**
	*	Verifies that the client's IP matches one of the two nominated IPs in the SiteConfig
	*
	*	@return Boolean
	*/
	public function verifyIP(){
		$client_ip = $this->get_client_ip();
		$ip1 = SiteConfig::current_site_config()->IPAddressOne;
		$ip2 = SiteConfig::current_site_config()->IPAddressTwo;
		if($client_ip != $ip1 && $client_ip != $ip2){
			return false;
		} else {
			return true;
		}
	}

	/**
	*	Gets download links to the latest backups
	*
	*	@return String
	*/
	public function grabDumps(){
		$links = '';
		$dir = scandir(ASSETS_PATH);
		foreach($dir as $file) {
			$extn = pathinfo(ASSETS_PATH.DIRECTORY_SEPARATOR.$file, PATHINFO_EXTENSION);
			if ( $extn != 'gz' ) {
				continue;
			}
	
			$links .= sprintf(
				'<p><a href="%s">%s</a></p>'."\n",
				ASSETS_DIR.DIRECTORY_SEPARATOR.$file,
				$file
			);
		}
		if ( $links == '' ) {
			$links = '<p>No backups so far.</p>';
		}
		return $links;
	}

}