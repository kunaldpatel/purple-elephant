<?php

class Model_Register extends \Orm\Model
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

	public static function exchange_code($code)
	{
		// Create curl request
		// I should move the params out of this function and into constants, perhaps. Also hidden in another file?
		$curl = Request::forge('https://api.venmo.com/oauth/access_token', 'curl');
		// Exchange auth code for token via server-side $_POST request
		$curl->set_method('post');
		// Input auth code and app data
		$curl->set_params(array(
			'client_id' => 1530, 
			'client_secret' => 'cWnuEVzLMWFedgAkLLeEC27ZgwSm5rJd',
			'code' => $code
			));
		// Make curl request
		$curl->execute();
		// Parse out the response.
		$response = $curl->response();
		// If the response is favorable
		if ($response->status == 200)
		{
			// Convert response from JSON into PHP Std Object.
			$venmo_user = json_decode($response->body);
			return $venmo_user;
		}
	}

	public static function get_registration(Fieldset $form, $venmo)
	{
		// Construct the form fields
		$form->add('email', 'E-mail', array('type' => 'email'), array('required', 'valid_email'));
		$form->add('first_name', 'First Name', array(), array('required'));
		$form->add('last_name', 'Last Name', array(), array('required'));
		$form->add('address_line_1', 'Address Line 1 (Street)', array(), array('required'));
		$form->add('address_line_2', 'Address Line 2 (Apt, Ste, Rm, Etc.)');
		$form->add('city', 'City', array(), array('required'));
		$form->add('state', 'State', array(), array('required'));
		$form->add('zip_code', 'Zip Code', array(), array('required'));
		$form->add('password', 'Choose Password', array('type' => 'password'), array('required'));
		$form->add('password2', 'Re-type Password', array('type' => 'password'), array('required'));
		$form->add('accept_terms', 'I agree to the terms & conditions', array('type' => 'checkbox', 'value' => 'true'), array('required'));
		$form->add('register_submit', ' ', array('type' => 'submit', 'value' => 'Register'));

		// Add Venmo info where applicable
		$form->populate(array(
			'email' => $venmo->email,
			'first_name' => $venmo->firstname,
			'last_name' => $venmo->lastname,
			));

		return $form;
	}

	public static function validate_registration(Fieldset $form, $auth)
	{
		// I should probably check to make sure the email is not already in use. Son of a bitch.
		// Also character rules for state length and zip code are not working :-/
		$form->field('password')->add_rule('match_value', $form->field('password2')->get_attribute('value'));
		$val = $form->validation();
		$val->set_message('required', 'The field :field is required');
		$val->set_message('valid_email', 'The field :field must be an email address');
		$val->set_message('match_value', 'The passwords must match');
		// If validation is successful
		if ($val->run())
		{
			// Return the relevant validated fields
			$validated = array_slice($val->validated(), 0, -3);
			return $validated;
		}
		else
		{
			$errors = $val->show_errors();
			//Log::error(print_r($errors, true), 'validate_registration()');
			return array('e_found' => true, 'errors' => $errors);
		}
	}

	public static function save_user($val, $venmo, $auth)
	{
		// Log the validated values
		$email = $val['email'];
		$password = $val['password'];
		// Try adding the user to the database
		try
		{
			$user = $auth->create_user(
				$email,	// repeating email twice since username is required
				$password, 
				$email,
				1,		// put into group 1 
				array(	// profile fields array
					'first_name' => $val['first_name'],
					'last_name' => $val['last_name'],
					'address_line_1' => $val['address_line_1'],
					'address_line_2' => $val['address_line_2'],
					'city' => $val['city'],
					'state' => $val['state'],
					'zip_code' => $val['zip_code'],
					'gift_budget' => 0
				));
		}
		catch (Exception $e)
		{
			$error = $e->getMessage();
			//Log::error('Creating the user via auth object, error: '.print_r($error, true), 'save_user()');
		}
		// If the user variable is set
		if (isset($user))
		{
			// Log in our new user. Let's wait to do this until the venmo_db is also completed...
			$auth->login($email, $password);
		}
		else
		{
			// If there is a specific error with the user variable
			if (isset($error))
			{
				$li = $error;
				//Log::error('If the user object is not set, error: '.print_r($error, true), 'save_user()');
			}
			// Write a general error
			else
			{
				$li = 'Something went wrong with creating the user!';
			}
			$errors = Html::ul(array($li));
			return array('e_found' => true, 'errors' => $errors);
		}

		// Create the venmo database fields
		try
		{
			list(, $user_id) = $auth->get_user_id();
			$result = DB::insert('venmo')->set(array(
				'user_id' => $user_id,
				'venmo_id' => $venmo->user->id,
				'access_token' => $venmo->access_token,
				'expires_in' => $venmo->expires_in,
				'refresh_token' => $venmo->refresh_token,
				))->execute();
		}
		catch (Exception $e)
		{
			$error = $e->getMessage();
			//Log::error('Saving venmo info to DB, error: '.print_r($error, true), 'save_user()');
			return array('e_found' => true, 'errors' => $error);
		}
	}
}