<h2>Role Management</h2>
<h3>Add/Edit Role</h3>
<form method="post">
    <p>
        <label for="role_name">Role Name</label>
        <input type="text" id="role_name" name="role_name" required>
    </p>
    <p>
        <input type="submit" name="add_role" value="Add Role">
    </p>
</form>
<form method="post" style="display:inline;">
    <input type="submit" name="reset_roles" value="Reset All Roles" onclick="return confirm('Are you sure you want to reset all roles to default values?');">
</form>
<h3>Existing Roles</h3>
<ul>
    <?php foreach ($roles as $role): ?>
        <li>
            <?php echo $role['name']; ?>
            <form method="post" style="display:inline;">
                <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                <input type="submit" name="delete_role" value="Delete" onclick="return confirm('Are you sure you want to delete this role?');">
            </form>
            <form method="post" style="display:inline;">
                <select name="capability_id">
                    <?php foreach ($capabilities as $capability): ?>
                        <option value="<?php echo $capability['id']; ?>"><?php echo $capability['name']; ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                <input type="submit" name="assign_capability" value="Assign Capability">
            </form>
            <ul>
                <?php
                $role_capabilities = RolesCapabilities::get_capabilities_for_role( $role['id']);
                foreach ($role_capabilities as $capability): ?>
                    <li>
                        <?php echo $capability['name']; ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                            <input type="hidden" name="capability_id" value="<?php echo $capability['id']; ?>">
                            <input type="submit" name="remove_capability" value="Remove Capability">
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php endforeach; ?>
</ul>
