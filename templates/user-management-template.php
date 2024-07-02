<h2>User Management</h2>
<h3>Add/Edit User</h3>
<form method="post">
    <p>
        <label for="username">Username</label>
        <input type="text" id="yaum_username" name="yaum_username" value="<?php echo ($edit_user) ? $edit_user['username'] : ''; ?>" <?php echo isset($edit_user) ? 'readonly' : ''; ?>>
    </p>
    <p>
        <label for="password">Password</label>
        <input type="password" id="yaum_password" name="yaum_password" <?php echo isset($edit_user) ? '' : 'required'; ?>>
    </p>
    <p>
        <label for="role_id">Role</label>
        <select id="role_id" name="role_id">
            <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['id']; ?>" <?php echo (isset($edit_user) && $edit_user['role_id'] == $role['id']) ? 'selected' : ''; ?>><?php echo $role['name']; ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <p>
        <?php if (isset($edit_user)): ?>
            <input type="hidden" name="nonce" value="<?php echo yourls_create_nonce('update_user'); ?>">
            <input type="hidden" name="user_id" value="<?php echo $edit_user['id']; ?>">
            <input type="submit" name="update_user" value="Save">
            <input type="submit" name="cancel_edit" value="Cancel">
        <?php else: ?>
            <input type="hidden" name="nonce" value="<?php echo yourls_create_nonce('add_user'); ?>">
            <input type="submit" name="add_user" value="Add User">
        <?php endif; ?>
    </p>
</form>

<h3>Existing Users</h3>
<table>
    <tr>
        <th>Username</th>
        <th>Role</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($users as $user) { ?>
        <tr>
            <td><?php echo $user['username']; ?></td>
            <td><?php echo $roles[array_search($user['role_id'], array_column($roles, 'id'))]['name']; ?></td>
            <td>
                <?php if ($user['id'] !== $current_user_id) { ?>
                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="submit" name="edit_user" value="Edit">
                    </form>
                    <form method="post" action="" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <!-- Select new user for transferring links -->
                        <select name="new_user_id">
                            <?php foreach ($users as $new_user): ?>
                                <?php if ($new_user['id'] != $user['id']): ?>
                                    <option value="<?php echo $new_user['id']; ?>"><?php echo $new_user['username']; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" name="delete_user" value="Delete" onclick="return confirm('Are you sure you want to delete this role?');">
                    </form>
                <?php } ?>
            </td>
        </tr>
    <?php } ?>
</table>