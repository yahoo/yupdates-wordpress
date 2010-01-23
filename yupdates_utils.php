<?php
/**
 * Yahoo! Updates Wordpress Plugin
 *
 * Find documentation and support on Yahoo! Developer Network: http://developer.yahoo.com
 *
 * Hosted on GitHub: http://github.com/yahoo/yos-updates-wordpress/tree/master
 *
 * @package    yos-updates-wordpress
 * @subpackage yahoo
 *
 * @author     Ryan Kennedy
 * @author     Lawrence Morrisroe <lem@yahoo-inc.com>, 
 * @author     Zach Graves <zachg@yahoo-incnc.com>
 * @copyright  Copyrights for code authored by Yahoo! Inc. is licensed under the following terms:
 * @license    BSD Open Source License
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a copy
 *   of this software and associated documentation files (the "Software"), to deal
 *   in the Software without restriction, including without limitation the rights
 *   to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *   copies of the Software, and to permit persons to whom the Software is
 *   furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in
 *   all copies or substantial portions of the Software.
 *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *   IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *   FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
 *   AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *   LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *   OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *   THE SOFTWARE.
 **/	
$yupdates_session_store = NULL;

function yupdates_has_session($session) {
	if($session->store->hasAccessToken()) 
	{
		$access_token = $session->store->fetchAccessToken();
		
		if(!$access_token->key || !$access_token->secret) {
			return false;
		}
		
		// refresh the token.
		$access_token = yupdates_get_accessToken($session, $access_token);
		
		$token = $session->application->token;
		
		return ($token && $token->key);
	} 
	else if($session->store->hasRequestToken()) 
	{
		$request_token = yupdates_get_requestToken($session);
		// $request_token = $session->store->fetchRequestToken();
		
		if(!$request_token->key || !$request_token->secret) {
			$session->store->clearRequestToken();
			$token = yupdates_get_requestToken($session);
			
			return false;
		}
		
		if(array_key_exists("oauth_token", $_REQUEST) && array_key_exists("oauth_verifier", $_REQUEST)) {
			$oauth_verifier = $_REQUEST["oauth_verifier"];
			$access_token = $session->application->getAccessToken($request_token, $oauth_verifier);
			
			if($access_token->key && $access_token->secret) {
				$session->store->clearRequestToken();
				$session->store->storeAccessToken($access_token);
				
				return TRUE;
			}
		}
		
		return false;
	}
	else if($session->application->consumer_key && $session->application->consumer_secret)
	{
		$token = yupdates_get_requestToken($session);
		
		return false;
	} 
	
	return false;
}

function yupdates_get_requestToken($session) {
	$callback_params = array("auth_popup"=>"true");
	$callback = yupdates_get_oauthCallback($callback_params);
	
	$request_token = $session->application->getRequestToken($callback);
	$session->store->storeRequestToken($request_token);
	
	return $request_token;
}

function yupdates_get_accessToken($session, $access_token=NULL) {
	$access_token = $session->application->getAccessToken($access_token);
	$session->store->storeAccessToken($access_token);
	
	return $access_token;
}

function yupdates_clear_session() {
	global $current_user;
	get_currentuserinfo();
	
	$user = $current_user->user_login;
	$session_store = yupdates_get_sessionStore($user);
	
	$session_store->clearRequestToken();
	$session_store->clearAccessToken();
	
	/* delete keys 
	go to /wp-admin/options.php to update the array with any yupdates_* keys.
	$options = array(
	   'yupdates_application_id','yupdates_bitly_apiKey','yupdates_bitly_login','yupdates_consumer_key',
		'yupdates_consumer_secret','yupdates_title_template','yupdates_tokens_','yupdates_tokens_admin',
		'yupdates_tokens_admin_dj0yJmk9WGhERFFkSHMzWEZxJmQ9WVdrOVJXVlhXVE','yupdates_tokens__dj0yJmk9WGhERFFkSHMzWEZxJmQ9WVdrOVJXVlhXVEZWTm5',
		'yupdates_updates_widget_users','yupdates_update_title','yupdates_update_title_template','yupdates_widget_count'
	);
	foreach($options as $name) {
		delete_option($name);
	}
	*/
	
	// todo: infinite looping
	header(sprintf("Location: %s", $_SERVER["HTTP_HOST"]));
	exit();
}

function yupdates_get_oauthCallback($parameters=array()) {
	return sprintf("http://%s%s&%s",$_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"], http_build_query($parameters));
} 

function yupdates_get_currentUserSessionStore() {
	if(!$yupdates_session_store) {
		global $current_user;
    	get_currentuserinfo();
		
		$user = $current_user->user_login;
		$yupdates_session_store = yupdates_get_sessionStore($user);
	}
	return $yupdates_session_store;
}

function yupdates_get_sessionStore($user) {
	$consumer_key = get_option("yupdates_consumer_key");
	return new WordPressSessionStore($user, $consumer_key);
}

function yupdates_get_application() {
	// fetch application keys from user options
	$consumer_key = get_option("yupdates_consumer_key");
	$consumer_secret = get_option("yupdates_consumer_secret");
	$appid = get_option("yupdates_application_id");
	
	return new YahooOAuthApplication($consumer_key, $consumer_secret, $appid);
}

function yupdates_get_session($user=NULL) {
	// create session object with application, token store
	$session = new stdclass();
	$session->application = yupdates_get_application();
	$session->store = (is_null($user)) ? yupdates_get_currentUserSessionStore() : yupdates_get_sessionStore($user);
	$session->user = $user;
	
	// pass the session off to check for tokens in the store. 
	// updates tokens as needed and returns true/false if a session exists
	// (if access token exists)
	$session->hasSession = yupdates_has_session($session);
	
	return $session;
}

function yupdates_get_bitly_options() {
	$options = new stdclass();
	$options->apiKey = get_option("yupdates_bitly_apiKey"); 
	$options->login = get_option("yupdates_bitly_login"); 
	
	return $options;
}

function yupdates_bitly_shorten($permalink, $apiKey, $login) {
	$base_url = "http://api.bit.ly/shorten";
	$params = array(
		'apiKey' => $apiKey,
		'login' => $login,
		'longUrl' => $permalink,
		'version' => '2.0.1',
		'history' => '1'
	);
	
	$http = YahooCurl::fetch($base_url, $params);
	
	$rsp = $http["response_body"];
	$data = json_decode($rsp);

	if($data && $data->statusCode == "OK" && isset($data->results)) {
		$results = get_object_vars($data->results);
		$site = $results[$permalink];
		
		if($site && isset($site->shortUrl)) {
			$shortUrl = $site->shortUrl;
			return $shortUrl;
		}
	}
	
	return $permalink;
}

function yupdates_close_popup() {
?>
<script type="text/javascript">
window.close();
</script>
<?php
}
?>