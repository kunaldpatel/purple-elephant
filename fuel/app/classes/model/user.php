<?php

class Model_User extends \Orm\Model
{
	protected static $_properties = array(
		'id',
		'username',
		'password',
		'group',
		'email',
		'last_login',
		'login_hash',
		'profile_fields',
		'created_at',
		'updated_at',
		'first_name',
		'last_name',
		'address_line_1',
		'address_line_2',
		'city',
		'state',
		'zip_code',
		'budget',
	);

	protected static $_observers = array(
		'Orm\Observer_CreatedAt' => array(
			'events' => array('before_insert'),
			'mysql_timestamp' => false,
		),
		'Orm\Observer_UpdatedAt' => array(
			'events' => array('before_save'),
			'mysql_timestamp' => false,
		),
	);

	public static function get_account_status($auth)
	{
		// Should this be more about a request status, rather than account status?
		// What do I care about letting someone know regarding their account?
		// ADD: Once I get into requests, I should also create a function to check the status of that to inform the user
		// ADD: Check for existence of an account_status Session var...if it doesn't exist, create it?
		// ADD: update_account_status($auth) - Update based on changes to budget, # friends, and Save account as well
		// Budget is 0 (only in case of first-time users)
		$budget = $auth->get_profile_fields('budget');
		// Initial user
		if ($budget == 0)
		{
			// Suspend service
			// Are they still in registration session?
				// Yes
					// Add instructional message to array.
				// No
					// Add warning message to array.
		}
		$num_friends = count(Model_User::get_friends($auth));
		// Friends > 0
		if ($num_friends == 0)
		{
			// Suspend service
			// Are they still in registration session?
				// Yes
					// Add instructional message to array.
				// No
					// Add warning message to array.
		}
		// Friends between 1-4, encourage adding more
		elseif ($num_friends <= 4)
		{
			// Add instructional message to array.
		}
		// Is Venmo connected?
		// Can I return a profile using my auth key?
			// No
				// Suspend service. Ask user to reconnect to Venmo. 
				// ADD: Callback router for reconnecting Venmo account.
		// ADD: Return a status object with status, cautions, and warnings?
	}

	public static function get_account(Fieldset $form, $auth)
	{
		$profile = $auth->get_profile_fields();
		// User Profile
		// QUESTION: Should I allow a user to change their email address/username? Don't think FuelPHP allows it
		$form->add('email', 'E-mail:', array('type' => 'email', 'value' => $auth->get_email()), array('required', 'valid_email'));
		$form->add('first_name', 'First Name', array('value' => $profile['first_name']), array('required'));
		$form->add('last_name', 'Last Name', array('value' => $profile['last_name']), array('required'));
		$form->add('address_line_1', 'Address Line 1 (Street)', array('value' => $profile['address_line_1']), array('required'));
		$form->add('address_line_2', 'Address Line 2 (Apt, Ste, Rm, Etc.)', array('value' => $profile['address_line_2']));
		$form->add('city', 'City', array('value' => $profile['city']), array('required'));
		$form->add('state', 'State', array('value' => $profile['state']), array('required'));
		// ADD: Revisit zip code validation - what are best practices?
		$form->add('zip_code', 'Zip Code', array('value' => $profile['zip_code']), array(array('required'), array('valid_string', array('numeric'))));

		// Change Password
		$form->add('old_password', 'Current Password', array('type' => 'password'));
		$form->add('password', 'New Password', array('type' => 'password'));
		$form->add('password_repeat', 'Repeat New Password', array('type' => 'password'));
		
		// Save Changes
		$form->add('account_submit', ' ', array('type' => 'submit', 'value' => 'Save changes'));
		return $form;
	}

	// Validate account fields, and if valid, update the user's account settings where appropriate
	public static function update_account(Fieldset $form, $auth, $post)
	{
		/* VALIDATE FORM */
		// Password rules in progress
		// ADD: More stringent password requirements...min. character count, etc.
		// If password has been set, has old_password been provided?
		$form->field('old_password')->add_rule('required_with', 'password');
		// Make sure password and password_repeat match (and are both set)
		$form->field('password')->add_rule('match_value', $form->field('password_repeat')->get_attribute('value'));

		$val = $form->validation();
		$val->set_message('required', ':label is required');
		$val->set_message('required_with', ':label is required');
		$val->set_message('valid_email', ':label must be a valid email address');
		$val->set_message('match_value', 'Your new passwords do not match. Please try again.');

		/* UPDATE ACCOUNT */
		if ($val->run())
		{
			/* PREPARE DATA */
			// Get extra profile fields
			$email = $auth->get_email();
			// Add email to front to match the form
			$profile_curr = array('email' => $email) + $auth->get_profile_fields();
			// Remove repeat password & submit button from $post.
			$profile_new = array_slice($post, 0, -2);
			// Compare existing profile to post fields, save differences to an array
			$updates = array_diff_assoc($profile_new, $profile_curr);
			/* UPDATE PROFILE */
			// If any values have changed...
			if(!empty($updates)) {
				try
				{
					$auth->update_user($updates);
				}
				catch (Exception $e)
				{
					$error = $e->getMessage();
					return array('e_found' => true, 'errors' => $error);
				}
			}
		}
		else
		{
			$errors = $val->show_errors();
			return array('e_found' => true, 'errors' => $errors);
		}
	}

	// Retrieve User Budget
	public static function get_budget(Fieldset $form, $auth)
	{
		$budget = $auth->get_profile_fields('budget');
		$form->add('budget', 'Gift Budget', array('type' => 'number', 'value' => $budget), array('required'));
		$form->add('budget_submit', ' ', array('type' => 'submit', 'value' => 'Save budget'));
		return $form;
	}

	// Update User Budget
	public static function update_budget(Fieldset $form, $auth) {
		$form->field('budget')->add_rule('numeric_between', 10, 50);
		$val = $form->validation();
		$val->set_message('numeric_between', 'Please enter a number between $10 and $50');

		if($val->run())
		{
			try
			{
				$auth->update_user(array(
					'budget' => $form->field('budget')->get_attribute('value')
				));
			}
			catch (Exception $e)
			{
				$error = $e->getMessage();
				Log::error(print_r($error, true), 'update_budget()');
			}
		}
		else
		{
			$errors = $val->show_errors();
			return array('e_found' => true, 'errors' => $errors);
		}
	}

	// Retrieve User Friends
	public static function get_friends($auth)
	{
		list(, $user_id) = $auth->get_user_id();
		try
		{
			// How would I do error check on this? Is try/catch enough?
			$result = DB::select()->from('friends')->where('user_id', '=', $user_id)->order_by('name','asc')->execute();
			// ADD: Count for friends that returns an error if there are 0 friends? I think this is being checked in the View right now
			return $result;
		}
		catch (Exception $e)
		{
			$error = $e->getMessage();
			return array('e_found' => true, 'errors' => $error);
		}
	}

	// Form for adding a new Friend
	public static function input_friend(Fieldset $form, $auth)
	{
		$form->add('friend_name', 'First Name', array(), array('required'));
		$form->add('friend_email', 'Email Address', array(), array('required', 'valid_email'));
		$form->add('friend_submit', ' ', array('type' => 'submit', 'value' => 'Add friend'));
		return $form;
	}

	// Add new Friend
	public static function add_friend(Fieldset $form, $auth)
	{
		$val = $form->validation();
		$val->set_message('required', ':label is required');
		$val->set_message('valid_email', ':label must be a valid email address');

		if ($val->run())
		{
			$friend_name = $form->field('friend_name')->get_attribute('value');
			$friend_email = $form->field('friend_email')->get_attribute('value');
			list(, $user_id) = $auth->get_user_id();
			
			try
			{
				$result = DB::insert('friends')->set(array(
					'name' => $friend_name,
					'email' => $friend_email,
					'user_id' => $user_id
				))->execute();
			}
			catch (Exception $e)
			{
				$error = $e->getMessage();
				//Log::error(print_r($error, true), 'update_friends()');
			}
		}
		else
		{
			$errors = $val->show_errors();
			return array('e_found' => true, 'errors' => $errors);
		}
	}

	// Remove Friend
	public static function remove_friend($id)
	{
		try
		{
			$result = DB::delete('friends')->where('id', '=', $id)->execute();
			//Log::error('Number of rows affected was: '.$result, 'remove_friend()');
		}
		catch (Exception $e)
		{
			$error = $e->getMessage();
			return array('e_found' => true, 'errors' => $error);
		}
	}
	// QUESTION: Unsure if this should be in a separate function
	public static function get_venmo_auth($auth)
	{
		// Get the user id
		list(, $user_id) = $auth->get_user_id();
		// Retrieve their access token from DB
		try
		{
			$result = DB::select()->from('venmo')->where('user_id', '=', $user_id)->execute();
			return $result[0];
		}
		catch (Exception $e)
		{
			$error = $e->getMessage();
			return array('e_found' => true, 'errors' => $error);
		}
	}

	public static function get_venmo_profile($auth)
	{
		// Retrieve user's Venmo id and access token
		// Should prob work in error handling for this as well
		$venmo_auth = Model_User::get_venmo_auth($auth);
		// Build request string for user profile query
		$request = 'https://api.venmo.com/users/'.$venmo_auth['venmo_id'].'?access_token='.$venmo_auth['access_token'];
		// Create and execute cURL request
		$curl = Request::forge($request, 'curl');
		try 
		{
			$curl->execute();
			$response = $curl->response();
			if ($response->status == 200)
			{
				// Convert response from JSON into PHP Std Object.
				$profile = json_decode($response->body);
				// Set the return object
				return $profile->data;
			}
		} 
		catch (Exception $e) 
		{
			$error = $e->getMessage();
			return array('e_found' => true, 'errors' => $error);
		}
	}
	// This data is meaningful on the Javascript side, so as such, I should move it accordingly...
	// Or post the outcome to a .json file that is accessed by the Javascript on the page? Seems like the approach being mentioned in Stack Overflow
	public static function get_venmo_friends($auth)
	{
		// Retrieve user's Venmo id and access token
		// Should prob work in error handling for this as well
		$venmo_auth = Model_User::get_venmo_auth($auth);
		// I should really wrap this into a function given the number of cURL requests I have, and interpret the errors properly from Venmo...
		// Maybe it could just be a Venmo controller, where I input my request and it re-routes it accordingly
		// This one needs to paginate, so my request would need to be updated as it works through the loop...
			// something like if isset(pagination->next), then update request string and run the function again
			// add extra results onto the end of a stripped friends array (to remove any unnecessary parts)
		$venmo_friends = array();
		$continue = true;
		$request = 'https://sandbox-api.venmo.com/users/'.$venmo_auth['venmo_id'].'/friends?access_token='.$venmo_auth['access_token'];
		$next_page = '';
		// While I have friends to retrieve
		while ($continue == true) {
			// Set the base request string
			$request .= $next_page;
			// Call the Venmo API
			$curl = Request::forge($request, 'curl');
			$curl->execute();
			$response = $curl->response();
			// If we have a successful response
			if ($response->status == 200)
			{
				// Decode the JSON body
				$result = json_decode($response->body);
				// Add friend objects to our array
				$venmo_friends = array_merge($venmo_friends, $result->data);
				// Check if we should continue
				if (property_exists($result->pagination, 'next'))
				{
					//Log::error('$result->pagination is: '.print_r($result->pagination, true), 'get_venmo_friends()');
					$next = explode('?', $result->pagination->next);
					$next_page = '&'.$next[1];
					//Log::error('Add this to request: '.$next_page, 'get_venmo_friends()');
				}
				// If not...
				else
				{
					// Break the loop
					$continue = false;
				}
			}
			else
			{
				// See what the problem with the request was
				Log::error(print_r($response, true), 'get_venmo_friends()');
				// Break the loop
				$continue = false;
			}
		}
		//return $venmo_friends;
		function cmp($a, $b)
		{
			return strnatcasecmp($a->first_name, $b->first_name);
		}
		usort($venmo_friends, "cmp");
		//Log::error(print_r(usort($venmo_friends, "cmp"), true));
		return $venmo_friends;
	}

	// This will need to be moved somewhere, probably. Or break this down into sub-models.
	public static function create_request($auth)
	{
		// ADD: Function to determine if a request should be generated or not
		// Retrieve the user's friends
			// After a request goes through, how do I move friend to an inactive list? Friend should have 2 states. Active/Inactive. If they have previously provided a Venmo account to use, should I keep that ID on file as well for repayment? If they're coming from Venmo already, that would make total sense. Def. should be included in the friends table.
		// Pick one at random
	}
}