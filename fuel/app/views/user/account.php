<!-- Errors -->
<?php if (isset($errors)){ echo $errors; } ?>

<!-- If there is a user signed in -->
<?php if (Auth::instance()->check()) : ?>

	<!-- User Profile -->
	<div class='profile'>
		<?php echo $account_form; ?>
		<?php echo Html::anchor('user/account', 'Cancel'); ?>
	</div>
	<hr>
	<!-- Venmo Status -->
	<!-- Is there a way I could re-use this for registration? -->
	<div class='venmo-profile'>
		<!-- Venmo profile -->
		<?php if (is_object($venmo_profile)): ?>
			<p>Connected to Venmo account <strong><?php echo $venmo_profile->username; ?></strong></p>
			<img src=<?php echo $venmo_profile->profile_picture_url; ?>>
		<!-- No Venmo profile available -->
		<?php else: ?>
			<!-- Is this unable to connect, or user removed their Venmo account? How do I tell the difference? Maybe I should just make suspending the account a separate action from Venmo info. -->
		<?php endif; ?>
	</div>

<!-- Not signed in -->
<?php else: ?>
	<p>You must be logged in to see this page.</p> 
	<p><?php echo Html::anchor('user/login', 'Login'); ?> or <?php echo Html::anchor('register/', 'Register'); ?></p>

<?php endif; ?>