<?php

class Model_Request extends \Orm\Model
{
	protected static $_properties = array(
		'id',                    // primary key
		'created_at',            // when request was generated
		'expires_at',            // if no action taken, when the request should expire
		'updated_at',            // when the last action occurred
		'user_id',               // user who requested a gift
		'friend_id',             // user's friend who has been asked to fulfill request
		'budget',                // user's budget at time of request creation (cannot be changed post-facto)
		'actual_cost',           // friend's request for reimbursement
		'user_debit_id',         // venmo charge to user
		'friend_payment_id',     // venmo payment to friend
	);
	/*
	// Ensure their account is in good standing:
	public static function get_account_status($auth)
	{
		$status = array();
		// For a given user
		// Venmo account is active?
		// This only returns whether or not I have an access token for the person, when it expires, etc.
		// I need to add a function to renew Venmo tokens once they expire eventually
		// At least if they have one at this point, I can continue, if not then I stop immediately and list the problem.
		$venmo_auth = Model_User::get_venmo_auth($auth);
		// If there were errors
		if (array_key_exists('e_found', $venmo_auth))
		{
			// Return the error array
			Log::error(print_r($venmo_auth['errors'], true), 'get_account_status()');
			// I should probably build an errors array...
			return false;
		}
		// Budget is set (should be after init reg., but confirm anyway)
		$budget = $auth->get_profile_fields('budget');
		// Minimum number of friends are saved (at least 3 at all times? What do I do about past friends vs. ones who recently picked?)
		$friends = Model_User::get_friends($auth);
		$num_friends = count($friends);
		if ($num_friends < 4) 
		{
			Log::error('User only has '.$num_friends.' friends.', 'get_account_status()');
			return false;
		}
		return true;
	}
	*/
	// Charge the User, THEN generate a request upon confirmation...
	// When do I know when confirmation happens? Flow chart this shit
	public static function charge_user($auth)
	{
		// Steps
		// Check if the user has enough friends
		$friends = Model_User::get_friends($auth);
		$num_friends = count($friends);
		// This should be a constant
		/*
		$threshold = 4;
		if ($num_friends < $threshold) 
		{
			$target = $threshold - $num_friends;
			Log::error('User needs to add '.$target.' friends.', 'create_request');
			return false;
		}
		*/
		// Transfer their budget to me
		$budget = $auth->get_profile_fields('budget');
		// Get their venmo auth array
		$venmo_auth = Model_User::get_venmo_auth($auth);
		// If the user's OAuth access_token has expired...
		if ($venmo_auth['expires_in'] < time())
		{
			// Renew token, probably should save to a var?
			// Make sure renew_token returns a value
			$new_tokens = Model_Request::renew_token($venmo_auth);
			// Save new access token
			// Continue with new token (maybe just update value of $venmo_auth['access_token'] so I don't have to make another DB call)
		}
		// Charge the User
		//Model_Request::request_payment($venmo_auth['venmo_id'], $venmo_auth['access_token'], $budget);
		// Store payment id, balance, user id in a db table
		// Pick a random friend
	}

	// Get money from user
	public static function request_payment($id, $token, $charge)
	{
		// Sandbox
		$request = 'https://sandbox-api.venmo.com/payments';
		$data = array(
			'user_id' => 153136, 
			'amount' => 0.10, 
			'note' => 'sandbox', 
			'access_token' => $token
			);
		/*
		// Live
		$request = 'https://api.venmo.com/v1/payments';
		// For now, stick with email address? I can't, because the user may choose a different address.
		$data = array(
			'user_id' => $id, 
			'amount' => -0.01, 
			'note' => 'noted', 
			'access_token' => 'jsBJVVUUCV7fcSzk7pD8rupXVwDyQgvE'
			);
		*/
		$curl = Request::forge($request, 'curl');
		$curl->set_option(CURLOPT_POSTFIELDS, $data);
		// Run the Request
		try 
		{
			$curl->execute();
			$response = $curl->response();
			if ($response->status == 200)
			{
				// Convert response from JSON into PHP Std Object.
				$record = json_decode($response->body);
				print_r($record);
				// Set the return object
				//return $charge_record;
			}
		} 
		catch (Exception $e) 
		{
			// Decode the JSON error message from Venmo
			$error = json_decode($e->getMessage());
			Log::error(print_r($error->error, true), 'charge_user()');
			//return array('e_found' => true, 'errors' => $error);
		}
	}

	// Renew access token
	// This will need more work
	public static function renew_token($venmo)
	{
		// Get refresh token
		$refresh_token = $venmo['refresh_token'];
		// Make a new call to the API
		// Sandbox?
		//$request = 'https://sandbox-api.venmo.com/oauth/access_token';
		// Live
		$request = 'https://api.venmo.com/v1/oauth/access_token';
		$data = array(
			"client_id" => 1530, 
			"client_secret" => 'cWnuEVzLMWFedgAkLLeEC27ZgwSm5rJd',
			"refresh_token" => $refresh_token
			);
		// Create cURL Request object
		$curl = Request::forge($request, 'curl');
		$curl->set_option(CURLOPT_POSTFIELDS, $data);
		// Try running the Request
		try 
		{
			$curl->execute();
			$response = $curl->response();
			Log::error(print_r($response, true), 'renew_token()');
		} 
		catch (Exception $e) 
		{
			Log::error(print_r($e->getMessage(), true), 'renew_token()');
		}
	}

	// Send request email to friend
		// Pull request from DB to retrieve user_id, friend_id, budget, expires_at
		// Retrieve: 
			// via user_id: user first and last names 
			// via friend_id: friend first_name, friend email
		// Generate email to be sent to friend
			// Generate a unique URL for redemption (How would I do this? Brings up question whether homepage should have a link for friends to get repaid as well. Lookup by your email address, friend's name or email address?)
			// Address them by name (friend: first_name)
			// Mention user's full name (user: first_name, last_name)
			// Include the user's suggested budget (user: budget)
			// Provide the expiry date (request: expires_at)
		// Send the email
		// Bonus - any follow-up emails close to expiry? Post-expiry?

	// Updates to Request
		// Statuses: 
			// 0 - closed. current time > expires_at.
			// 1 - open. request generated, waiting to hear from friend.
			// 2 - active. friend requested repayment.
			// 3 - complete. user was debited. friend was repaid. (calls into question of when to charge user, at start of month, or after? Do I reimburse upon a status being re-set to 0 if I collect money upfront?)
		// Close due to expiration
			// Should I include a status in the Request Object to account for this? 
			// If current timestamp > expires_at AND status == 1 (open)
				// Set status = 0 to close the request
		// Friend requests reimbursement
			// Generate the reimbursement page
				// Pull request from DB to retrieve user_id, friend_id, budget, expires_at
				// Retrieve: 
					// via user_id: user first and last names 
					// via friend_id: friend first_name, friend email
				// Those are same first 2 steps as sending email to the friend. Maybe I should wrap them in a get_request call that creates a local copy of a full Request Object
				// Create form with validation rules for their reimbursement
					// Confirm email address the request was sent to (is this my initial auth measure?)
					// Do I know if they're a Venmo user?
						// Check in my DB - Look up their info in friends table to see if a Venmo id exists
						// If not there, can I check Venmo API by email address? 
						// If they are, load their profile from Venmo
					// Otherwise, ask user if they have a Venmo account, and to provide that email address/phone number if different from the one on file
						// Would require an optional form field for email address
					// Budget input with max set at the user's request (Down the road I could adjust this, don't forget to mention the change will go to charity)
			// Upon submission of reimbursement request
				// Validate the budget and their email address
				// Maybe make a call to Venmo then to see if their profile exists?
				// Store their Venmo id on file for future reference in friends table
				// If everything so far checks out, set status = 2
		// Repay friend
			// Decide if this should be done immediately, if I can authenticate the friend (will Venmo let you accept money from a non-friend?)
			// Pull the request, if status = 2
			// Retrieve user: first_name, friend: venmo id, first_name
			// Create a Venmo API payment request to pay the friend
				// Fuck. Possible error situation - Venmo request is generated, but the friend doesn't take action on it in time. The request expires. How do I generate a new one? How long will a payout request remain active?
				// Check Venmo docs on what is needed
				// If payment is successful, update status = 3

	// Notify user on dashboard
		// How much info do I want to give them?
		// Cut-off date to make adjustments for their next request
		// Alert as to why a request failed (prob an email to follow up would be best, if the acct was active but something went wrong)
		// If status = 1 let them know a gift request is out
		// If status = 2, could update the message that the gifting mystery is afoot

}