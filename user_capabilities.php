<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

function get_capabilities() {
    global $mysqli;

    $uid = $_SESSION['userid'];

    $sql = "SELECT role_capabilities.capability AS capability" .
    " FROM role_capabilities INNER JOIN user_roles ON role_capabilities.roleid=user_roles.roleid" .
    " WHERE user_roles.userid=?";

    $query = $mysqli->prepare($sql);
    $query->bind_param("d", $uid);
    $r = $query->execute();

    $caps = [];

    $query->bind_result($capability);
    while ($query->fetch()) {
        $caps[$capability] = true;
    }

    return $caps;
}

$user_capability = [];

function user_has_capability($cap) {
    global $user_capability;
    if ($user_capability == []) {
        $user_capability = get_capabilities();
    }

    if (isset($user_capability[$cap])) {
        return $user_capability[$cap];
    } else {
        return false;
    }
}


class Capabilities {
    private static function __result_server_error($message) {
        http_response_code(500);
        return [ 'message' => $message ];
    }

    private static function __result_client_error($message) {
        http_response_code(400);
        return [ 'message' => $message ];
    }

    public static function load_all_capabilities() {
        global $capabilities;

        $dir = scandir("Modules");
        foreach ($dir as $leafname) {
            // Don't look up the tree, only down
            if ($leafname == '.' || $leafname == '..') {
                continue;
            }

            $dirpath = "Modules/${leafname}";
            $capfile = "Modules/${leafname}/${leafname}_capabilities.php";

            if ((is_dir($dirpath) || is_link($dirpath)) && is_file($capfile)) {
                require $capfile;
            }
        }

        return $capabilities;
    }


    //
    // READ API
    //

    public static function get_all_role_info() {
        global $mysqli;
        global $capabilities;

        // Get role data

        $roles = [];
        $roles_query = $mysqli->query("SELECT id, name, description FROM roles");
        if (!$roles_query) {
            return Capabilities::__result_server_error("Couldn't get role data");
        }

        foreach ($roles_query as $role) {
            $id = (int) $role['id'];
            $roles[$id] = [
                'id' => $id,
                'name' => $role['name'],
                'description' => $role['description'],
                'capabilities' => [],
                'users' => []
            ];
        }

        // Get capability data

        $role_capabilities_query = $mysqli->query("SELECT roleid, capability FROM role_capabilities");
        if (!$role_capabilities_query) {
            return Capabilities::__result_server_error("Couldn't get role capability data");
        }

        foreach ($role_capabilities_query as $row) {
            $roleid = (int) $row['roleid'];
            $capability = $row['capability'];
            $roles[$roleid]['capabilities'][] = $capability;
        }

        // Get user data

        $user_roles_query = $mysqli->query("SELECT user_roles.userid, users.username, user_roles.roleid" .
                                           " FROM user_roles INNER JOIN users ON user_roles.userid=users.id");
        if (!$user_roles_query) {
            return Capabilities::__result_server_error("Couldn't get user role data");
        }

        foreach ($user_roles_query as $row) {
            $roleid = (int) $row['roleid'];

            $roles[$roleid]['users'][] = [
                'uid' => $row['userid'],
                'username' => $row['username']
            ];
        }


        return $roles;
    }

    private static function get_users_with_role($roleid) {
        global $mysqli;

        $query = $mysqli->prepare("SELECT user_roles.userid, users.username" .
                                  " FROM user_roles INNER JOIN users ON user_roles.userid=users.id" .
                                  " WHERE user_roles.roleid=?");
        if (!$query) {
            return Capabilities::__result_server_error("Error forming user roles SQL query");
        }

        // Run query
        $query->bind_param("d", $roleid);
        $query->execute();

        $new_users = [];
        $query->bind_result($userid, $username);

        while ($query->fetch()) {
            $new_users[] = [
                'uid' => $userid,
                'username' => $username
            ];
        }

        return $new_users;
    }


    //
    // WRITE API
    //

    public static function new_role($name, $description = '') {
        global $mysqli;

        if (!$name) {
            return Capabilities::__result_client_error("No name provided");
        }

        $query = $mysqli->prepare("INSERT INTO roles (name, description) VALUES (?,?)");
        $query->bind_param("ss", $name, $description);
        if (!$query->execute()) {
            return Capabilities::__result_server_error("Couldn't create new role");
        }

        $id = $mysqli->insert_id;

        return [
            "id" => $id,
            "name" => $name,
            "description" => $description,
            "capabilities" => []
        ];
    }

    public static function rename_role($roleid, $name) {
        global $mysqli;

        if (!$roleid) {
            return Capabilities::__result_client_error("No role ID provided");
        }
        if (!$name) {
            return Capabilities::__result_client_error("No name provided");
        }

        $query = $mysqli->prepare("UPDATE roles SET name=? WHERE id=?");
        $query->bind_param("sd", $name, $roleid);
        if (!$query->execute()) {
            return Capabilities::__result_server_error("Couldn't update role");
        }

        return [
            "name" => $name,
        ];
    }

    public static function update_role_capabilities($roleid, $capabilities) {
        global $mysqli;

        // Error handling
        if (!$roleid) {
            return Capabilities::__result_client_error("No role ID provided");
        }
        if (!$capabilities) {
            return Capabilities::__result_client_error("No capabilities provided");
        }

        $capabilities = json_decode($capabilities);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return Capabilities::__result_client_error("Invalid JSON in capability field - " . json_last_error_msg());
        }

        // Perform update, using transaction support
        $mysqli->query("START TRANSACTION");

        foreach ($capabilities as $capability => $setting) {
            if ($setting === true) {
                $query =
                    "INSERT INTO role_capabilities (roleid, capability) VALUES (?, ?)" .
                    " ON DUPLICATE KEY UPDATE roleid=roleid";
            } else {
                $query = "DELETE FROM role_capabilities WHERE roleid=? AND capability=?";
            }

            $query = $mysqli->prepare($query);
            if (!$query) {
                return Capabilities::__result_server_error("Error updating capability $capability to $setting");
            }

            $query->bind_param("ds", $roleid, $capability);

            // Execute command
            $result = $query->execute();
            $query->close();

            // On failure
            if (!$result) {
                $mysql->query("ROLLBACK");
                return Capabilities::__result_server_error("Error updating capabilities");
            }
        }

        // All good
        $mysqli->query("COMMIT");

        // Return updated capabilities in the same format as get_all_role_info()
        $updated = [];
        foreach ($capabilities as $capability => $setting) {
            if ($setting === true) {
                $updated[] = $capability;
            }
        }

        return $updated;
    }

    public static function remove_users_from_role($roleid, $users) {
        global $mysqli;

        // Error handling
        if (!$roleid) {
            return Capabilities::__result_client_error("No role ID provided");
        }
        if (!$users) {
            return Capabilities::__result_client_error("No users provided");
        }

        $users = json_decode($users);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return Capabilities::__result_client_error("Invalid JSON in user field - " . json_last_error_msg());
        }

        // Actually remove the users
        foreach ($users as $user) {
            $query = $mysqli->prepare("DELETE FROM user_roles WHERE roleid=? AND userid=?");
            $query->bind_param("ds", $roleid, $user);
            $result = $query->execute();
            if (!$result) {
                return Capabilities::__result_server_error("Error deleting user(s) from role");
            }
        }

        // Return the new user listing for this role
        return Capabilities::get_users_with_role($roleid);
    }

    /*
     * POST /add_user_to_role
     *      roleid      number
     *      username    string
     */
    public static function add_user_to_role($roleid, $username) {
        global $mysqli;

        // Error handling
        if (!$roleid) {
            return Capabilities::__result_client_error("No role ID provided");
        }
        if (!$username) {
            return Capabilities::__result_client_error("No username");
        }

        // Find out if the user exists
        $query = $mysqli->prepare("SELECT id FROM users WHERE username=?");
        $query->bind_param("s", $username);
        $query->execute();
        $query->bind_result($userid);
        $query->fetch();
        if (!$userid) {
            return Capabilities::__result_client_error("User doesn't exist");
        }
        $query->close();

        // Don't add the user twice
        $query = $mysqli->prepare("SELECT 1 FROM user_roles WHERE userid=? AND roleid=?");
        if (!$query) {
            return Capabilities::__result_server_error("Error forming existing user check SQL query");
        }
        $query->bind_param("dd", $userid, $roleid);
        $query->execute();
        $query->bind_result($exists);
        $query->fetch();
        if ($exists) {
            return Capabilities::__result_client_error("User already has role");
        }
        $query->close();

        // Return the new user listing for this role
        $query = $mysqli->prepare("INSERT INTO user_roles (userid, roleid) VALUES (?, ?)");
        if (!$query) {
            return Capabilities::__result_server_error("Error forming user add SQL query");
        }

        // Run query
        $query->bind_param("dd", $userid, $roleid);
        $query->execute();
        $query->close();

        // Return list of users for this role
        return Capabilities::get_users_with_role($roleid);
    }

}

Capabilities::load_all_capabilities();
