<h2>Change Password</h2>
<form method="post">
    <input type="hidden" name="nonce" value="<?php echo yourls_create_nonce('change_password'); ?>">

    <p>
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" required>
    </p>
    <p>
        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" required>
    </p>
    <p>
        <input type="submit" name="submit" value="Change Password">
    </p>
    <?php if ($error_message): ?>
        <p class="error"><?php echo $error_message; ?></p>
    <?php endif; ?>
</form>
