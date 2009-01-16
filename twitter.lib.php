<?php
/*
 * Copyright (c) <2008> Justin Poliey <jdp34@njit.edu>
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

class Twitter {
	/* Username:password format string */
	private $credentials;
	
	/* Contains the last HTTP status code returned */
	private $http_status;
	
	/* Contains the last API call */
	private $last_api_call;
	
	/* Contains the application calling the API */
	private $application_source;

	/* Twitter class constructor */
	function Twitter($username, $password, $source=false) {
		$this->credentials = sprintf("%s:%s", $username, $password);
		$this->application_source = $source;
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
	
	function getFollowers($format, $id = NULL, $page = 1, $lite = false) {
		// either get authenticated users followers, or followers of specified id
		if ($id) {
			$api_call = sprintf("http://twitter.com/statuses/followers/%s.%s", $id, $format);
		}
		else {
			$api_call = sprintf("http://twitter.com/statuses/followers.%s", $format);
		}
		// pagination
		if ($page > 1) {
			$api_call .= "?page={$page}";
		}
		// this isnt in the documentation, but apparently it works
		if ($lite) {
			$api_call .= sprintf("%slite=true", ($page > 1) ? "&" : "?");
		}
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
		$api_call = sprintf("http://twitter.com/favorites/create/%d.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	function destroyFavorite($format, $id) {
		$api_call = sprintf("http://twitter.com/favorites/destroy/%d.%s", $id, $format);
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
		if($this->application_source){
			$api_url .= "&source=" . $this->application_source;
		}
		curl_setopt($curl_handle, CURLOPT_URL, $api_url);
		if ($require_credentials) {
			curl_setopt($curl_handle, CURLOPT_USERPWD, $this->credentials);
		}
		if ($http_post) {
			curl_setopt($curl_handle, CURLOPT_POST, true);
		}
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
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
