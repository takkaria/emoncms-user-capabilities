<?php

function user_capabilities_on_login($mysqli) {
    $uid = $_SESSION['userid'];

    $sql = "SELECT role_capabilities.capability AS capability" .
    " FROM role_capabilities INNER JOIN user_roles ON role_capabilities.roleid=user_roles.roleid" .
    " WHERE user_roles.userid=?";

    $query = $mysqli->prepare($sql);
    $query->bind_param("d", $uid);
    $r = $query->execute();

    $_SESSION['capability'] = [];

    $query->bind_result($capability);
    while ($query->fetch()) {
        $_SESSION['capability'][$capability] = true;
    }
}

/*

Initial SQL

INSERT INTO roles VALUES (NULL, "Super-admin", "All permissions granted");
INSERT INTO role_capabilities VALUES (1, "groups_can_create");
INSERT INTO user_roles VALUES (1, 1);

*/