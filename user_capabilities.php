<?php

function user_has_capability($cap)
{
  if (isset($_SESSION['capability'][$cap])) {
    return $_SESSION['capability'][$cap];
  } else {
    return false;
  }
}
