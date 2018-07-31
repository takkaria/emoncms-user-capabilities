<?php

function user_has_capability($cap)
{
  if (isset($_SESSION['capability'][$cap])) {
    return $_SESSION['capability'][$cap];
  } else {
    return false;
  }
}

function capability_load_list() {
    global $capabilities;
    $capabilities = [];

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
}

capability_load_list();
