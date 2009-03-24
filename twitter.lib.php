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
 
/**
 * Twitterlibphp is a PHP implementation of the Twitter API, allowing you
 * to take advantage of it from within your PHP applications.
 *
 * @author Justin Poliey <jdp34@njit.edu>
 * @package twitterlibphp
 */

/**
 * Twitter API class
 * @package twitterlibphp
 */
class Twitter {

	/**
	 * the Twitter credentials in HTTP format, username:password
	 * @access private
	 * @var string
	 */
	private $credentials;
	
	/**
	 * the last HTTP status code returned
	 * @access private
	 * @var integer
	 */
	private $http_status;
	
	/**
	 * the whole URL of the last API call
	 * @access private
	 * @var string
	 */
	private $last_api_call;
	
	/**
	 * the application calling the API
	 * @access private
	 * @var string
	 */
	private $application_source;

	/**
	 * Fills in the credentials {@link $credentials} and the application source {@link $application_source}.
	 * @param string $username Twitter username
	 * @param string $password Twitter password
	 * @param $source string Optional. Name of the application using the API
	 */
	function Twitter($username, $password, $source = null) {
		$this->credentials = sprintf("%s:%s", $username, $password);
		$this->application_source = $source;
	}
	
	/**
	 * Returns the 20 most recent statuses from non-protected users who have set a custom user icon.
	 * @param string $format Return format
	 * @return string
	 */
	function getPublicTimeline($format = 'xml') {
		$api_call = $this->buildRequest('statuses/public_timeline', $format);
		return $this->APICall($api_call);
	}
	
	/**
	 * Returns the 20 most recent statuses posted by the authenticating user and that user's friends.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getFriendsTimeline($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('statuses/friends_timeline', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns the 20 most recent statuses posted from the authenticating user.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getUserTimeline($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('statuses/user_timeline', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns a single status, specified by the $id parameter.
	 * @param string|integer $id The numerical ID of the status to retrieve
	 * @param string $format Return format
	 * @return string
	 */
	function getStatus($id, $format = 'xml') {
		$api_call = $this->buildRequest("statuses/show/{$id}", $format);
		return $this->APICall($api_call);
	}
	
	/**
	 * Updates the authenticated user's status.
	 * @param string $status Text of the status, no URL encoding necessary
	 * @param string|integer $reply_to ID of the status to reply to. Optional
	 * @param string $format Return format
	 * @return string
	 */
	function updateStatus($status, $reply_to = null, $format = 'xml') {
		// kind of hackish, but it's hackish on twitter's side too
		$args = array('status' => urlencode($status));
		if ($reply_to) {
			$args['in_reply_to_status_id'] = $reply_to;
		}
		$api_call = $this->buildRequest('statuses/update', $format, $args);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Returns the 20 most recent @replies (status updates prefixed with @username) for the authenticating user.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getReplies($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('statuses/replies', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Destroys the status specified by the required ID parameter. The authenticating user must be the author of the specified status.
	 * @param integer|string $id ID of the status to destroy
	 * @param string $format Return format
	 * @return string
	 */
	function destroyStatus($id, $format = 'xml') {
		$api_call = $this->buildRequest("statuses/destroy/{$id}", $format);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Returns the authenticating user's friends, each with current status inline.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getFriends($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('statuses/friends', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns the authenticating user's followers, each with current status inline.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getFollowers($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('statuses/followers', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns extended information of a given user.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function showUser($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('users/show', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns extended information of a given user.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getMessages($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('direct_messages', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns a list of the 20 most recent direct messages sent by the authenticating user.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getSentMessages($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('direct_messages/sent', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Sends a new direct message to the specified user from the authenticating user.
	 * @param string $user The ID or screen name of a recipient
	 * @param string $text The message to send
	 * @param string $format Return format
	 * @return string
	 */
	function newMessage($user, $text, $format = 'xml') {
		$options = array(
			'user' => urlencode($user),
			'text' => urlencode($text)
		);
		$api_call = $this->buildRequest('direct_messages/new', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Destroys the direct message specified in the required $id parameter.
	 * @param integer|string $id The ID of the direct message to destroy
	 * @param string $format Return format
	 * @return string
	 */
	function destroyMessage($id, $format = 'xml') {
		$api_call = sprintf("http://twitter.com/direct_messages/destroy/%s.%s", $id, $format);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Befriends the user specified in the ID parameter as the authenticating user.
	 * @param integer|string $id The ID or screen name of the user to befriend
	 * @param boolean $follow Follow the user as well
	 * @param string $format Return format
	 * @return string
	 */
	function createFriendship($id, $follow = true, $format = 'xml') {
		$options = array(
			'id' => $id,
			'follow' => $follow
		);
		$api_call = $this->buildRequest('friendships/create', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Discontinues friendship with the user specified in the ID parameter as the authenticating user.
	 * @param integer|string $id The ID or screen name of the user to unfriend
	 * @param string $format Return format
	 * @return string
	 */
	function destroyFriendship($id, $format = 'xml') {
		$options = array('id' => $id);
		$api_call = $this->buildRequest('friendships/destroy', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Tests if a friendship exists between two users.
	 * @param integer|string $user_a The ID or screen name of the first user
	 * @param integer|string $user_b The ID or screen name of the second user
	 * @param string $format Return format
	 * @return string
	 */
	function friendshipExists($user_a, $user_b, $format = 'xml') {
		$options = array(
			'user_a' => $user_a,
			'user_b' => $user_b
		);
		$api_call = $this->buildRequest('friendships/exists', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns an array of numeric IDs for every user the specified user is followed by.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getFriendIDs($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('friends/ids', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns an array of numeric IDs for every user the specified user is following.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getFollowerIDs($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('followers/ids', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns an HTTP 200 OK response code and a representation of the requesting user if authentication was successful; returns a 401 status code and an error message if not.
	 * @param string $format Return format
	 * @return string
	 */
	function verifyCredentials($format = 'xml') {
		$api_call = $this->buildRequest('account/verify_credentials', $format);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Ends the session of the authenticating user, returning a null cookie.
	 * @param string $format Return format
	 * @return string
	 */
	function endSession($format = 'xml') {
		$api_call = $this->buildRequest('account/end_session', $format);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Sets which device Twitter delivers updates to for the authenticating user.
	 * @param string $device The delivery device used. Must be sms, im, or none
	 * @return string
	 */
	function updateDeliveryDevice($device, $format = 'xml') {
		$options = array('device' => $device);
		$api_call = $this->buildRequest('account/update_delivery_advice', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Sets one or more hex values that control the color scheme of the authenticating user's profile page on twitter.com.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function updateProfileColors($options, $format = 'xml') {
		$api_call = $this->buildRequest('account/update_profile_colors', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Sets values that users are able to set under the "Account" tab of their settings page.
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function updateProfile($options, $format = 'xml') {
		$api_call = $this->buildRequest('account/update_profile', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Returns the remaining number of API requests available to the requesting user before the API limit is reached for the current hour.
	 * @param $format Return format
	 * @return string
	 */
	function rateLimitStatus($format = 'xml') {
		$api_call = $this->buildRequest('account/rate_limit_status', $format);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Returns the 20 most recent favorite statuses for the authenticating user or user specified by the ID parameter in the requested format. 
	 * @param array $options Options to pass to the method
	 * @param string $format Return format
	 * @return string
	 */
	function getFavorites($options = array(), $format = 'xml') {
		$api_call = $this->buildRequest('favorites', $format, $options);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Favorites the status specified in the ID parameter as the authenticating user.
	 * @param integer|string $id The ID of the status to favorite
	 * @param string $format Return format
	 * @return string
	 */
	function createFavorite($id, $format = 'xml') {
		$options = array('id' => $id);
		$api_call = $this->buildRequest('favorites/create', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Un-favorites the status specified in the ID parameter as the authenticating user.
	 * @param integer|string $id The ID of the status to un-favorite
	 * @param string $format Return format
	 * @return string
	 */
	function destroyFavorite($id, $format = 'xml') {
		$options = array('id' => $id);
		$api_call = $this->buildRequest('favorites/destroy', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Enables notifications for updates from the specified user to the authenticating user.
	 * @param integer|string $id The ID or screen name of the user to follow
	 * @param string $format Return format
	 * @return string
	 */
	function follow($id, $format = 'xml') {
		$options = array('id' => $id);
		$api_call = $this->buildRequest('notifications/follow', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Disables notifications for updates from the specified user to the authenticating user.
	 * @param integer|string $id The ID or screen name of the user to leave
	 * @param string $format Return format
	 * @return string
	 */
	function leave($id, $format = 'xml') {
		$options = array('id' => $id);
		$api_call = $this->buildRequest('notifications/leave', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Blocks the user specified in the ID parameter as the authenticating user.
	 * @param integer|string $id The ID or screen name of the user to block
	 * @param string $format Return format
	 * @return string
	 */
	function createBlock($id, $format = 'xml') {
		$options = array('id' => $id);
		$api_call = $this->buildRequest('blocks/create', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Un-blocks the user specified in the ID parameter as the authenticating user.
	 * @param integer|string $id The ID or screen name of the user to follow
	 * @param string $format Return format
	 * @return string
	 */
	function destroyBlock($id, $format = 'xml') {
		$options = array('id' => $id);
		$api_call = $this->buildRequest('blocks/destroy', $format, $options);
		return $this->APICall($api_call, true, true);
	}
	
	/**
	 * Returns the string "ok" in the requested format with a 200 OK HTTP status code.
	 * @param string $format Return format
	 * @return string
	 */
	function test($format = 'xml') {
		$api_call = $this->buildRequest('help/test', $format);
		return $this->APICall($api_call, true);
	}
	
	/**
	 * Builds an API URL out of a method, format, and option list
	 * @access private
	 * @param $method string Twitter API method
	 * @param $fmt string Return format
	 * @param $options array API method options
	 * @return string
	 */
	private function buildRequest($method, $fmt, $options = array()) {
		$request = sprintf('http://twitter.com/%s.%s', $method, $fmt);
		/* Add application source to the options */
		if ($this->application_source) {
			$options['source'] = $this->application_source;
		}
		/* Convert all options to GET params */
		if (count($options) > 0) {
			$keyvals = array();
			foreach($options as $option => $value) {
				array_push($keyvals, sprintf('%s=%s', $option, $value));
			}
			$request .= '?' . implode($keyvals, '&');
		}
		return $request;
	}
	
	/**
	 * Executes an API call
	 * @param string $api_url Full URL of the API method
	 * @param boolean $require_credentials Whether or not credentials are required
	 * @param boolean $http_post Whether or not to use HTTP POST
	 * @return string
	 */
	private function APICall($api_url, $require_credentials = false, $http_post = false) {
		// echo url only for debugging
		echo "{$api_url}\n";
		$curl_handle = curl_init();
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
	
	/**
	 * Returns the last HTTP status code
	 * @return integer
	 */
	function lastStatusCode() {
		return $this->http_status;
	}
	
	/**
	 * Returns the URL of the last API call
	 * @return string
	 */
	function lastAPICall() {
		return $this->last_api_call;
	}
}
?>
