<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title><?php echo $title; ?></title>
	<?php echo Asset::css('bootstrap.css'); ?>
	<style>
		body { margin: 40px; }
	</style>
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="span16">
				<!-- Navigation -->
				<?php
					// For signed-in users
					if (Auth::instance()->check())
					{
						$link = array('Logged in as: '.Auth::instance()->get_profile_fields("first_name"), Html::anchor('user/dashboard', 'Dashboard'), Html::anchor('user/account', 'Account'), Html::anchor('user/logout', 'Logout'));
					}
					// For visitors
					else
					{
						$link = array(Html::anchor('user/login', 'Login'), Html::anchor('register/', 'Register'));
					}
					echo Html::ul($link);
				?>
				<hr>
				<!-- Alert, Success -->
				<?php if (Session::get_flash('success')): ?>
				<div class="alert-message success">
					<p>
					<?php echo implode('</p><p>', e((array) Session::get_flash('success'))); ?>
					</p>
				</div>
				<hr>
				<?php endif; ?>
				<!-- Alert, Error -->
				<?php if (Session::get_flash('error')): ?>
				<div class="alert-message error">
					<p>
					<?php echo implode('</p><p>', e((array) Session::get_flash('error'))); ?>
					</p>
				</div>
				<hr>
				<?php endif; ?>
			</div>
			<!-- Title -->
			<h2><?php echo $title; ?></h2>
			<hr>
			<!-- Content -->
			<div class="span16">
				<?php echo $content; ?>
			</div>
		</div>
	</div>
</body>
</html>
