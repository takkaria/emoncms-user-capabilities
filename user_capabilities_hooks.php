<?php

function user_capabilities_on_login_hook($mysqli) {
    $log = new EmonLogger(__FILE__);

    $uid = $_SESSION['userid'];

    $sql = "SELECT role_capabilities.capability AS capability" .
    " FROM role_capabilities INNER JOIN user_roles ON role_capabilities.roleid=user_roles.roleid" .
    " WHERE user_roles.userid=?";

    $query = $this->mysqli->prepare($sql);
    $query->bind_param("d", $uid);
    $query->execute();
    $query->bind_result($capability);

    $_SESSION['capability'] = [];

    while ($query->fetch()) {
        $log->info("capability $capability for $uid")
        $_SESSION['capability'][$capability] = true;
    }
}
