<?php

use HimaPro\Router;

Router::guard("hasQ", function($id){
  return $id == "hi";
});