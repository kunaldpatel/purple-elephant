<?php

class Controller_User extends Controller_Template
{

	public function action_login()
	{
		$view = View::forge('user/login');
		$form = Form::forge('login_form');
		$auth = Auth::instance();
		$form->add('email', 'Email:');
		$form->add('password', 'Password:', array('type' => 'password'));
		$form->add('submit', ' ', array('type' => 'submit', 'value' => 'Login'));
		if (Input::post())
		{
			if ($auth->login(Input::post('email'), Input::post('password')))
			{
				Session::set_flash('success', 'Successfully logged in! Welcome '.$auth->get_profile_fields('first_name'));
				Response::redirect('user/dashboard');
			}
			else
			{
				Session::set_flash('error', 'Username or password incorrect.');
			}
		}
		$view->set('form', $form, false);
		$this->template->title = 'User &raquo; Login';
		$this->template->content = $view;
	}

	public function action_logout()
	{
		$auth = Auth::instance();
		$auth->logout();
		// Upon logout, route someone to the Login page and confirm they have been logged out
		Session::set_flash('success', 'Logged out.');
		Response::redirect('user/login');
	}

	// QUESTION: Why does my approach to dealing w/ visiting a User page when you're unrecognized work for the Dashboard, but not for the Account page? Interesting... 
	public function action_account()
	{
		$view = View::forge('user/account');
		$auth = Auth::instance();
		$account_form = Fieldset::forge('account_form');

		// Load account information
		Model_User::get_account($account_form, $auth);
		// Request Venmo profile (and therefore status)
		$venmo_profile = Model_User::get_venmo_profile($auth);
		Log::error(print_r($venmo_profile, true), 'action_account()');
		// If someone tries to save changes to their settings
		if (Input::post())
		{
			$form->repopulate();
			$result = Model_User::update_account($account_form, $auth, Input::post());
			if ($result['e_found'])
			{
				$view->set('errors', $result['errors'], false);
			}
			else
			{
				Session::set_flash('success', 'Account updated.');
				Response::redirect('user/account');
			}
		}

		$view->set('account_form', $account_form->build(), false);
		$view->set('venmo_profile', $venmo_profile, false);
		$this->template->title = 'User &raquo; Account';
		$this->template->content = $view;
	}

	public function action_dashboard($section = null, $action = null, $data = null)
	{
		$view = View::forge('user/dashboard');
		$auth = Auth::instance();

		Model_Request::charge_user($auth);
		
		// Init Budget
		$budget_form = Fieldset::forge('budget');
		Model_User::get_budget($budget_form, $auth);
		
		// Init Friends
		// I wonder if I could have the friends array be returned from check_account_status to save an extra call?
		// Perhaps not if check_account_status() is moved into a task. I would need some global access to common functions
		$friends = Model_User::get_friends($auth);
		if (array_key_exists('e_found', $friends))
		{
			// If we can't load the friends list...
			$view->set('errors', $friends['errors'], false);
		}
		else
		{
			// If we can, show the friends
			$view->set('friends', $friends, false);
		}
		// Init Adding a Friend
		$input_friend = Fieldset::forge('input_friend');
		Model_User::input_friend($input_friend, $auth);

		// Suggesting a friend from Venmo
		//$venmo_friends = Model_User::get_venmo_friends($auth);
		$venmo_friends = array();
        $post = Input::post();
		// Manage updates
		// Check if there's an action to run
		if ($section)
		{
			switch ($section) 
			{
				case 'budget':
					if ($action == 'update')
					{
						// Updating budget
						if (!empty($post['budget_submit']))
						{
							$budget_form->repopulate();
							$result = Model_User::update_budget($budget_form, $auth);
							if ($result['e_found'])
							{
								$view->set('errors', $result['errors'], false);
							}
							else
							{
								Session::set_flash('success', 'Budget updated.');
								Response::redirect('user/dashboard');
							}
						}
					}
					break;

				case 'friends':
					switch ($action)
					{
						case 'add':
							if (!empty($post['friend_submit']))
							{
								$input_friend->repopulate();
								$result = Model_User::add_friend($input_friend, $auth);
								if ($result['e_found'])
								{
									$view->set('errors', $result['errors'], false);
								}
								else
								{
									Session::set_flash('success', 'Friend added.');
									Response::redirect('user/dashboard');
								}
							}
							break;

						case 'remove':
							$result = Model_User::remove_friend($data);
							if ($result['e_found'])
							{
								$view->set('errors', $result['errors'], false);
							}
							else
							{
								Session::set_flash('success', 'Friend removed.');
								Response::redirect('user/dashboard');
							}							
							break;

						default:
							// code
							break;
					}
				
				default:
					// code
					break;
				
			}
		}

		// Populate the View
		$view->set('budget', $auth->get_profile_fields('budget'), false);
		$view->set('budget_form', $budget_form->build(Uri::create('user/dashboard/budget/update')), false);
		$view->set('input_friend_form', $input_friend->build(Uri::create('user/dashboard/friends/add')), false);
		$view->set('venmo_friends', $venmo_friends, false);

		// Populate the Template vars
		$this->template->title = 'User &raquo; Dashboard';
		$this->template->content = $view;
	}
}
