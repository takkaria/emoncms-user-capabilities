<?php

$schema['roles'] = [
             'id' => [ 'type' => 'int(11)', 'Null' => false, 'Key' => 'PRI', 'Extra' => 'auto_increment' ],
           'name' => [ 'type' => 'varchar(64)' ],
    'description' => [ 'type' => 'varchar(256)' ]
];

$schema['role_capabilities'] = [
        'roleid' => [ 'type' => 'int(11)', 'Null' => false /* foreign key = roles */ ],
    'capability' => [ 'type' => 'varchar(32)' ]
];

$schema['user_roles'] = [
    'userid' => [ 'type' => 'int(11)', 'Null' => false /* foreign key = users */ ],
    'roleid' => [ 'type' => 'int(11)', 'Null' => false /* foreign key = roles */ ]
];


// It's nasty to put this here but it is the best place to put a generic function
// that needs to be accessible anywhere
function capability_check($cap)
{
  if (isset($_SESSION['capability'][$cap])) {
    return $_SESSION['capability'][$cap];
  } else {
    return false;
  }
}
