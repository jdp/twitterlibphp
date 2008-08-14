<?php
/*
 * Twitter interface class
 * Justin Poliey <jdp34@njit.edu>
 * First release Nov 26 2007
 * Newest release Aug 14 2008
 *
 * This is a simple interface to the Twitter API.
 * I've tried to keep as close as possible to the real API
 *   calls (some had to be changed due to ambiguity), but all
 *   of the arguments are as they are in the official docs.
 *
 * Usage:
 *  $twitter = new Twitter("username", "password");
 *  $public_timeline_xml = $twitter->getPublicTimeline("xml");
 *
 * Methods:
 *  getPublicTimeline($format [, $since_id])
 *  getFriendsTimeline($format [, $id [, $since ]])
 *  getUserTimeline($format [, $id [, $count [, $since ]]])
 *
 *  showStatus($format, $id)
 *  updateStatus($status)
 *  destroyStatus($format, $id)
 *  getReplies($format [, $page ])
 *  getFriends($format [, $id ])
 *  getFollowers($format [, $lite ])
 *  getFeatured($format)
 *  showUser($format [, $id [, $email ]])
 *
 *  getMessages($format [, $since [, $since_id [, $page ]]])
 *  getSentMessages($format [, $since [, $since_id [, $page ]]])
 *  newMessage($format, $user, $text)
 *  destroyMessage($format, $id)
 *
 *  createFriendship($format, $id)
 *  destroyFriendship($format, $id)
 *  friendshipExists($format, $user_a, $user_b)
 *
 *  verifyCredentials([$format])
 *  endSession()
 *  updateLocation($format, $location)
 *  updateDeliveryDevice($format, $device)
 *  rateLimitStatus($format)
 *
 *  getArchive($format [, $page ])
 *  getFavorites($format [, $id [, $page ]])
 *  createFavorite($format, $id)
 *  destroyFavorite($format, $id)
 *
 *  follow($format, $id)
 *  leave($format, $id)
 *
 *  test($format)
 *  downtimeSchedule($format)
 *
 *  lastStatusCode()
 *  lastAPICall()
 */

class Twitter {
	/* Username:password format string */
	private $credentials;
	
	/* Contains the last HTTP status code returned */
	private $http_status;
	
	/* Contains the last API call */
	private $last_api_call;
	
	/* Twitter class constructor */
	function Twitter($username, $password) {
		$this->credentials = sprintf("%s:%s", $username, $password);
	}
	
	function getPublicTimeline($format, $since_id = 0) {
		$api_call = sprintf("http://twitter.com/statuses/public_timeline.%s", $format);
		if ($since_id > 0) {
			$api_call .= sprintf("?since_id=%d", $since_id);
		}
		return $this->APICall($api_call);
	}
	
	function getFriendsTimeline($format, $id = NULL, $since = NULL) {
		if ($id != NULL) {
			$api_call = sprintf("http://twitter.com/statuses/friends_timeline/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/statuses/friends_timeline.%s", $format);
		}
		if ($since != NULL) {
			$api_call .= sprintf("?since=%s", urlencode($since));
		}
		return $this->APICall($api_call, true);
	}
	
	function getUserTimeline($format, $id = NULL, $count = 20, $since = NULL) {
		if ($id != NULL) {
			$api_call = sprintf("http://twitter.com/statuses/user_timeline/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/statuses/user_timeline.%s", $format);
		}
		if ($count != 20) {
			$api_call .= sprintf("?count=%d", $count);
		}
		if ($since != NULL) {
			$api_call .= sprintf("%ssince=%s", (strpos($api_call, "?count=") === false) ? "?" : "&", urlencode($since));
		}
		return $this->APICall($api_call, true);
	}
	
	function showStatus($format, $id) {
		$api_call = sprintf("http://twitter.com/statuses/show/%d.%s", $id, $format);
		return $this->APICall($api_call);
	}
	
	function updateStatus($status) {
		$status = urlencode(stripslashes(urldecode($status)));
		$api_call = sprintf("http://twitter.com/statuses/update.xml?status=%s", $status);
		return $this->APICall($api_call, true, true);
	}
	
	function getReplies($format, $page = 0) {
		$api_call = sprintf("http://twitter.com/statuses/replies.%s", $format);
		if ($page) {
			$api_call .= sprintf("?page=%d", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function destroyStatus($format, $id) {
		$api_call = sprintf("http://twitter.com/statuses/destroy/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function getFriends($format, $id = NULL) {
		// take care of the id parameter
		if ($id != NULL) {
			$api_call = sprintf("http://twitter.com/statuses/friends/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/statuses/friends.%s", $format);
		}
		return $this->APICall($api_call, true);
	}
	
	function getFollowers($format, $lite = NULL) {
		$api_call = sprintf("http://twitter.com/statuses/followers.%s%s", $format, ($lite) ? "?lite=true" : NULL);
		return $this->APICall($api_call, true);
	}
	
	function getFeatured($format) {
		$api_call = sprintf("http://twitter.com/statuses/featured.%s", $format);
		return $this->APICall($api_call);
	}
	
	function showUser($format, $id, $email = NULL) {
		if ($email == NULL) {
			$api_call = sprintf("http://twitter.com/users/show/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/users/show.xml?email=%s", $email);
		}
		return $this->APICall($api_call, true);
	}
	
	function getMessages($format, $since = NULL, $since_id = 0, $page = 1) {
		$api_call = sprintf("http://twitter.com/direct_messages.%s", $format);
		if ($since != NULL) {
			$api_call .= sprintf("?since=%s", urlencode($since));
		}
		if ($since_id > 0) {
			$api_call .= sprintf("%ssince_id=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $since_id);
		}
		if ($page > 1) {
			$api_call .= sprintf("%spage=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function getSentMessages($format, $since = NULL, $since_id = 0, $page = 1) {
		$api_call = sprintf("http://twitter.com/direct_messages/sent.%s", $format);
		if ($since != NULL) {
			$api_call .= sprintf("?since=%s", urlencode($since));
		}
		if ($since_id > 0) {
			$api_call .= sprintf("%ssince_id=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $since_id);
		}
		if ($page > 1) {
			$api_call .= sprintf("%spage=%d", (strpos($api_call, "?since") === false) ? "?" : "&", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function newMessage($format, $user, $text) {
		$text = urlencode(stripslashes(urldecode($text)));
		$api_call = sprintf("http://twitter.com/direct_messages/new.%s?user=%s&text=%s", $format, $user, $text);
		return $this->APICall($api_call, true, true);
	}
	
	function destroyMessage($format, $id) {
		$api_call = sprintf("http://twitter.com/direct_messages/destroy/%s.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function createFriendship($format, $id) {
		$api_call = sprintf("http://twitter.com/friendships/create/%s.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function destroyFriendship($format, $id) {
		$api_call = sprintf("http://twitter.com/friendships/destroy/%s.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function friendshipExists($format, $user_a, $user_b) {
		$api_call = sprintf("http://twitter.com/friendships/exists.%s?user_a=%s&user_b=%s", $format, $user_a, $user_b);
		return $this->APICall($api_call, true);
	}
	
	function verifyCredentials($format = NULL) {
		$api_call = sprintf("http://twitter.com/account/verify_credentials%s", ($format != NULL) ? sprintf(".%s", $format) : NULL);
		return $this->APICall($api_call, true);
	}
	
	function endSession() {
		$api_call = "http://twitter.com/account/end_session";
		return $this->APICall($api_call, true);
	}
	
	function updateLocation($format, $location) {
		$api_call = sprintf("http://twitter.com/account/update_location.%s?location=%s", $format, $location);
		return $this->APICall($api_call, true, true);
	}
	
	function updateDeliveryDevice($format, $device) {
		$api_call = sprintf("http://twitter.com/account/update_delivery_device.%s?device=%s", $format, $device);
		return $this->APICall($api_call, true, true);
	}
	
	function rateLimitStatus($format) {
		$api_call = sprintf("http://twitter.com/account/rate_limit_status.%s", $format);
		return $this->APICall($api_call, true);
	}
	
	function getArchive($format, $page = 1) {
		$api_call = sprintf("http://twitter.com/account/archive.%s", $format);
		if ($page > 1) {
			$api_call .= sprintf("?page=%d", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function getFavorites($format, $id = NULL, $page = 1) {
		if ($id == NULL) {
			$api_call = sprintf("http://twitter.com/favorites.%s", $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/favorites/%s.%s", $id, $format);
		}
		if ($page > 1) {
			$api_call .= sprintf("?page=%d", $page);
		}
		return $this->APICall($api_call, true);
	}
	
	function createFavorite($format, $id) {
		$api_call = sprintf("http://twitter.com/favourings/create/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function destroyFavorite($format, $id) {
		$api_call = sprintf("http://twitter.com/favourings/destroy/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function follow($format, $id) {
		$api_call = sprintf("http://twitter.com/notifications/follow/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function leave($format, $id) {
		$api_call = sprintf("http://twitter.com/notifications/leave/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function createBlock($format, $id) {
		$api_call = sprintf("http://twitter.com/blocks/create/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function destroyBlock($format, $id) {
		$api_call = sprintf("http://twitter.com/blocks/destroy/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function test($format) {
		$api_call = sprintf("http://twitter.com/help/test.%s", $format);
		return $this->APICall($api_call, true);
	}
	
	function downtimeSchedule($format) {
		$api_call = sprintf("http://twitter.com/help/downtime_schedule.%s", $format);
		return $this->APICall($api_call, true);
	}
	
	private function APICall($api_url, $require_credentials = false, $http_post = false) {
		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $api_url);
		if ($require_credentials) {
			curl_setopt($curl_handle, CURLOPT_USERPWD, $this->credentials);
		}
		if ($http_post) {
			curl_setopt($curl_handle, CURLOPT_POST, true);
		}
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
		$twitter_data = curl_exec($curl_handle);
		$this->http_status = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);
		$this->last_api_call = $api_url;
		curl_close($curl_handle);
		return $twitter_data;
	}
	
	function lastStatusCode() {
		return $this->http_status;
	}
	
	function lastAPICall() {
		return $this->last_api_call;
	}
}
?>