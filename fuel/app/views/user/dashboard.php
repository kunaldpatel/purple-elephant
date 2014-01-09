<!-- Errors -->
<?php if (isset($errors)){ echo $errors; } ?>

<!-- If there is a user signed in -->
<?php if (Auth::instance()->check()) : ?>

	<!-- Budget -->
	<div class='budget'>
		<div class='heading'>
			<!-- Initial User -->
			<?php if ($budget == 0): ?>
				<h2>Set your gift budget</h2>
				<p>Pick a number, any number...between $10 and $50. Suggest padding the number you have in mind by $5 to account for tax &amp; shipping.</p>
			<!-- Returning User -->
			<?php else: ?>
				<h2>Your next gift will cost $<?php echo $budget; ?>.</h2>
				<p>You can change this number until {date} for it to apply to your next gift.</p>
			<?php endif; ?>
		</div>
		<!-- Budget Form -->
		<?php echo $budget_form; ?>
		<hr>
	</div>

	<!-- Friends -->
	<div class='friends'>
		<div class='heading'>
			<!-- Initial User / Removed all friends -->
			<?php if (count($friends) == 0): ?>
				<h2>Add some friends to start your service</h2>
				<p>How about starting with 4?</p>
			<!-- Returning User / Has friends -->
			<?php else : ?>
				<h2>One of your {num} friends will pick your next gift.</h2>
				<p>If you add {num} more friends, it will ensure a different person picks each gift for the rest of the year.</p>
			<?php endif; ?>
		</div>
		<div class='friends'>
			<ul class='friends-list'>
				<?php foreach ($friends as $friend): ?>
					<li class='friend'>
						<?php echo $friend['name'].' - '.$friend['email'].' '.Html::anchor('user/dashboard/friends/remove/'.$friend['id'], 'remove'); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class='add-friend'>
			<h4>Add a new friend manually</h4>
			<?php echo $input_friend_form; ?>
		</div>
		<?php if (count($venmo_friends) > 0): ?>
			<div class='venmo-friends'>
				<h4>Or quickly add your Venmo friends</h4>
				<ul class='venmo-friends-list'>
					<?php foreach ($venmo_friends as $friend): ?>
						<li class='venmo-friend'>
							<?php echo $friend->display_name; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>

<!-- Not signed in -->
<?php else: ?>
	<p>You must be logged in to see this page.</p> 
	<p><?php echo Html::anchor('user/login/', 'Login'); ?> or <?php echo Html::anchor('register/', 'Register'); ?></p>

<?php endif; ?>