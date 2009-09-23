<?php
require_once("yosdk_lib5/OAuth/OAuth.php");
require_once("yosdk_lib5/Yahoo/YahooOAuthApplication.class.php");
require_once("WordPressSessionStore.php");

function yupdates_menu() {
    global $current_user;
    get_currentuserinfo();

    if(array_key_exists("yupdates_updateusers", $_REQUEST)) {
        if($_REQUEST["yupdates_include_updates"]) {
            yupdatesdb_addUpdatesUser($current_user->user_login);
        }
        else {
            yupdatesdb_removeUpdatesUser($current_user->user_login);
        }
    }

    // fetch application keys from user options
    $ck = get_option("yupdates_consumer_key");
    $cks = get_option("yupdates_consumer_secret");
    $appid = get_option("yupdates_application_id");
	
	$session_store = yupdates_get_sessionStore();
	
	$application = new YahooOAuthApplication($ck, $cks, $appid);
	$application_has_session = yupdates_has_session($application, $session_store);

    $session = NULL;
    $user = NULL;
    $sharingUpdates = false;
	
    if($application_has_session == false) {
       $request_token = $session_store->fetchRequestToken();
       $auth_url = ($request_token && $request_token->key) ? $application->getAuthorizationUrl($request_token) : "";
    } else {
       $sharingUpdates = yupdatesdb_isUpdatesUser($current_user->user_login);
    }
?>
<div class="wrap">
    <h2>Yahoo! Updates</h2>
<?php if(!is_null($application) && $application_has_session) { ?>
	
You have authorized the Yahoo! Updates plugin.
<form method="post">
	
<?php 	if(YUPDATES_WIDGET_ENABLED) { ?>
	
   <p><label for="yupdates-include-updates">Include updates in widget? <input id="yupdates-include-updates" type="checkbox" name="yupdates_include_updates"<?php echo $sharingUpdates ? " checked='checked'" : "" ?>></label></p>
   <input type="submit" name="yupdates_updateusers" value="Update">

<?php 	} ?>

   <input type="submit" name="yupdates_clearauthorization" value="Unauthorize">
</form>	

<?php } else { ?>

You have not yet authorized the Yahoo! Updates plugin.
<p>
    <input type="hidden" name="yupdates_authorize" value="true">
    <input type="submit" value="Authorize" onclick="_yupdates_authorize();">
</p>

<?php } ?>

</div>

<script type="text/javascript">
   var _gel = function(el) {return document.getElementById(el)};
   var _yupdates_auth_url = "<?php echo $auth_url; ?>";
   var _yupdates_authorize = function() {
      if(_yupdates_auth_url != "") PopupManager.open(_yupdates_auth_url,600,435);
   }
</script>
<script type="text/javascript">
// a simplified version of step2 popuplib.js
var PopupManager = {
	popup_window:null,
	interval:null,
	interval_time:80,
	waitForPopupClose: function() {
		if(PopupManager.isPopupClosed()) {
			PopupManager.destroyPopup();
			window.location.reload();
		}
	},
	destroyPopup: function() {
		this.popup_window = null;
		window.clearInterval(this.interval);
		this.interval = null;
	},
	isPopupClosed: function() {
		return (!this.popup_window || this.popup_window.closed);
	},
	open: function(url, width, height) {
		this.popup_window = window.open(url,"",this.getWindowParams(width,height));
		this.interval = window.setInterval(this.waitForPopupClose, this.interval_time);
		
		return this.popup_window;
	},
	getWindowParams: function(width,height) {
		var center = this.getCenterCoords(width,height);
		return "width="+width+",height="+height+",status=1,location=1,resizable=yes,left="+center.x+",top="+center.y;
	},
	getCenterCoords: function(width,height) {
		var parentPos = this.getParentCoords();
		var parentSize = this.getWindowInnerSize();
		
		var xPos = parentPos.width + Math.max(0, Math.floor((parentSize.width - width) / 2));
		var yPos = parentPos.height + Math.max(0, Math.floor((parentSize.height - height) / 2));
		
		return {x:xPos,y:yPos};
	},
	getWindowInnerSize: function() {
		var w = 0;
		var h = 0;
		
		if ('innerWidth' in window) {
			// For non-IE
			w = window.innerWidth;
			h = window.innerHeight;
		} else {
			// For IE
			var elem = null;
			if (('BackCompat' === window.document.compatMode) && ('body' in window.document)) {
				elem = window.document.body;
			} else if ('documentElement' in window.document) {
				elem = window.document.documentElement;
			}
			if (elem !== null) {
				w = elem.offsetWidth;
				h = elem.offsetHeight;
			}
		}
		return {width:w, height:h};
	},
	getParentCoords: function() {
		var w = 0;
		var h = 0;
		
		if ('screenLeft' in window) {
			// IE-compatible variants
			w = window.screenLeft;
			h = window.screenTop;
		} else if ('screenX' in window) {
			// Firefox-compatible
			w = window.screenX;
			h = window.screenY;
	  	}
		return {width:w, height:h};
	}
}
</script>

<?php
}
?>