<?php if (isset($errors)){ echo 'Here are errors: '.$errors; } ?>
<?php if (!$callback): ?>
	<p>Successfully connected to Venmo. <?php echo Html::anchor('register/complete','Complete your profile'); ?></p>
<?php else : ?>
	<p>Reached page in error. You can register <?php echo Html::anchor('register/','here'); ?></p>
<?php endif; ?>