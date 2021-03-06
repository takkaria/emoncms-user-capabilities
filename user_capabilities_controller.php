<?php

// no direct access
defined('EMONCMS_EXEC') or die('Restricted access');

require_once "Modules/user_capabilities/user_capabilities.php";

function view_file($str, $params = []) {
    $path = "Modules/user_capabilities/views/" . $str . ".php";
    return view($path, $params);
}

function user_capabilities_controller() {
    global $route;
    global $capabilities;

    if (Capabilities::is_db_initialised() === false) {
        $result = Capabilities::init_db();
        if ($result !== true) {
            return $result;
        }
    }

    //
    // You need read permissions to access the below URLs
    //

    if (!user_has_capability('capabilities_view')) {
        http_response_code(404);
        return "404 Not Found";
    }

    if ($route->action === "") {
        $route->format = "html";
        return view_file('index');
    }

    if ($route->action === "list_roles") {
        $route->format = "json";
        return Capabilities::get_all_role_info();
    }

    //
    // You need write permissions to access the below URLs
    //

    if (!user_has_capability('capabilities_edit')) {
        http_response_code(403);
        $route->format = "json";
        return [ 'message' => 'Permission denied' ];
    }

    if ($route->action === "new_role") {
        $route->format = "json";
        return Capabilities::new_role(post('name'));
    }

    if ($route->action === "update_role_capabilities") {
        $route->format = "json";
        return Capabilities::update_role_capabilities((int) post('id'), post('capabilities'));
    }

    if ($route->action === "remove_users_from_role") {
        $route->format = "json";
        return Capabilities::remove_users_from_role((int) post('roleid'), post('users'));
    }

    if ($route->action === "add_user_to_role") {
        $route->format = "json";
        return Capabilities::add_user_to_role((int) post('roleid'), post('username'));
    }

    if ($route->action === "rename_role") {
        $route->format = "json";
        return Capabilities::rename_role((int) post('roleid'), post('name'));
    }

    http_response_code(404);
    return false;
}
