<?php

defined('EMONCMS_EXEC') or die('Restricted access');
global $path;
global $capabilities;

?>

<link rel="stylesheet" href="<?= $path ?>Modules/user_capabilities/user_capabilities.css">

<h1>User capabilities editor</h1>

<div class="panels">
    <div class="panel panel--roles">
        <h2 class="panel-header">Roles</h2>

        <ul class="roles">
            <li class='role'>
                <span class='role-name preload-box'></span>
                <span class='role-edit'><i class='icon-edit'></i></span>
            </li>

            <li class='role'>
                <span class='role-name preload-box'></span>
                <span class='role-edit'><i class='icon-edit'></i></span>
            </li>
        </ul>

        <button class="panel--roles-add btn" disabled>Add new</button>
    </div>

    <div class="panel panel--capabilities">
        <h2 class="panel-header">Capabilities</h2>

        <form id="form-capabilities">
            <ul class="capabilities">

<?php
            /*
             * The capability list is stored grouped by module,
             * and then as an associative array like
             *   'machine_name' => 'human readable name'
             */
            foreach ($capabilities as $groupname => $capgroup) {
                foreach ($capgroup as $capability => $readable) {
?>

                <li>
                    <input class="capabilities--input" type="checkbox" id="cap-<?= $capability ?>">
                    <label class="capabilities--label" for="cap-<?= $capability ?>"><?= $groupname ?>: <?= $readable ?></label>

<?php
                } // capability
            } // capgroup
?>

            </ul>

            <div class="buttonbox">
                <button class="btn btn-primary capabilities-update" disabled>Update</button>
            </div>

        </form>
    </div>

    <div class="panel panel--users">
        <h2 class="panel-header">Users with role</h2>

        <form id="form-users">
            <ul class="users">
                <li>
                    <label class="users-label" for="user-1">
                        <input type="checkbox" id="user-1">
                        <span class="users-name preload-box"></span>
                    </label>
                </li>

                <li>
                    <label class="users-label" for="user-2">
                        <input type="checkbox" id="user-2">
                        <span class="users-name preload-box"></span>
                    </label>
                </li>
            </ul>

            <div class="buttonbox">
                <button class="btn btn-danger" id="user-role-remove" disabled>Remove</button>
            </div>

            <div class="form-inline users-add">
                <input id="add-user-to-role-username" placeholder="Username..." type="text">
                <button id="add-user-to-role" class="btn btn-primary" disabled>Add new</button>
            </div>
        </form>
    </div>

</div>

<script src="<?= $path ?>Modules/user_capabilities/user_capabilities.js"></script>
<script>

$(function() {
    const editor = new CapabilityEditor({
        apiRoot: '<?= $path ?>'
    })
})

</script>
