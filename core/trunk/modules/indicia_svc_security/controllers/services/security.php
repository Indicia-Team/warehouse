<?php

class Security_Controller extends Service_Base_Controller {

	public function get_nonce() {
		$website_id=$_POST['website_id'];
		// Store nonce in the Kohana cache?
		$nonce = sha1(time().':'.rand().$_SERVER['REMOTE_ADDR'].':'.kohana::config('indicia.private_key'));
		// Save the nonce in the cache
		$this->cache = new Cache();
		$this->cache->set($nonce, $website_id, 'write');
		echo $nonce;
	}

	public function get_read_nonce() {
		$website_id = $_POST['website_id'];
		$nonce = sha1(time().':'.rand().$_SERVER['REMOTE_ADDR'].':'.kohana::config('indicia.private_key'));
		$this->cache = new Cache();
		$this->cache->set($nonce, $website_id, 'read');
		echo $nonce;
	}


}

?>
