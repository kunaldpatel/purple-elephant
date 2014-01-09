<?php

class Controller_Public extends Controller_Template
{
	// Provide link to connect to Venmo
	public function action_index()
	{
		// Check if the user is already logged in, and if so route them to the dashboard immediately
		if (Auth::instance()->check())
		{
			Response::redirect('user/dashboard');
		}
		// If the user is unrecognized
		else
		{
			$view = View::forge('public/index');
			$this->template->title = 'Welcome to Purple Elephant';
			$this->template->content = $view;
		}
	}
}