<?php

class Controller_Register extends Controller_Template
{
	// Provide link to connect to Venmo
	public function action_index()
	{
		$view = View::forge('register/index');
		$venmo_link = 'https://api.venmo.com/v1/oauth/authorize?client_id=1530&scope=ACCESS_PROFILE,ACCESS_EMAIL,ACCESS_FRIENDS,MAKE_PAYMENTS&response_type=code';
		$view->set('link', $venmo_link, false);
		$this->template->title = 'Register &raquo; Connect to Venmo';
		$this->template->content = $view;
	}
	// Exists to route the callback for Venmo OAuth
	public function action_callback()
	{
		$view = View::forge('register/callback');
		if (Input::get('code'))
		{
			$venmo_code = Input::get('code');
			$venmo_user_info = Model_Register::exchange_code($venmo_code);
			// Store Venmo info from callback into a Session var to access it on next page
			Session::set('venmo', $venmo_user_info);
			Response::redirect('register/complete');
		}
		elseif (Input::get('error'))
		{
			Session::set_flash('error', 'Please connect to a Venmo account to continue.');
			Response::redirect('register/');
		}
	}
	// Complete registration and save info to DB
	public function action_complete()
	{
		$view = View::forge('register/complete');
		$register_form = Fieldset::forge('register_form');
		// Retrieve Venmo info from Session var
		$venmo_user_info = Session::get('venmo');
		$auth = Auth::instance();
		Model_Register::get_registration($register_form, $venmo_user_info->user);

		if (Input::post())
		{
			// Validate the form
			$register_form->repopulate();
			$val = Model_Register::validate_registration($register_form, $auth);
			// If there were errors found in validation
			if (isset($val['e_found']))
			{
				$view->set('errors', $val['errors'], false);
			}
			// If form is good to go...
			else
			{
				// Add user information to DB
				$result = Model_Register::save_user($val, $venmo_user_info, $auth);
				if ($result['e_found'])
				{
					$view->set('errors', $result['errors'], false);
				}
				else
				{
					Session::set_flash('success', 'User created.');
					Response::redirect('user/dashboard');
				}
			}
		}
		$view->set('register_form', $register_form->build(), false);
		$view->set('venmo_user', $venmo_user_info->user, false);
		$this->template->title = 'Register &raquo; Complete your Profile';
		$this->template->content = $view;
	}
}