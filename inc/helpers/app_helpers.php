<?php

function notice($msg=false) {
  if(!$msg) {
    if(isset($_SESSION['notice'])) {
      $output = $_SESSION['notice'];

      //clear notice after use
      $_SESSION['notice'] = null;

      return $output;
    }
  } else {
    $_SESSION['notice'] = $msg;
  }
}
