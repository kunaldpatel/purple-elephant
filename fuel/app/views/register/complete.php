<!-- Errors -->
<?php if (isset($errors)){ echo $errors; } ?>

<!-- Venmo Profile -->

<div class='venmo-profile'>
	<p>Connected to Venmo account <strong><?php echo $venmo_user->username; ?></strong></p>
	<img src=<?php echo $venmo_user->profile_picture_url; ?>>
	<p>Filled in some of your profile with information we pulled from Venmo, so review and complete your profile to create your Purple Elephant account.</p>
</div>

<hr>
<!-- Complete Profile -->
<div class='registration'>
	<?php echo $register_form; ?>
</div>