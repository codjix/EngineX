<?php
use HimaPro\Router;
use HimaPro\Blade;
use HimaPro\Validation;

Router::get("/", function () {

  echo Blade::$app->run("pages.home");
  $age = Validation::setName("age")::setValue(33)::min(18)::max(40);
  echo $age::isSuccess();
  // Router::guard();
});

Router::get("/404/{id}", function ($id) {
  if(Router::withGuard("hasQ", $id)) {
    echo "you are welcome";
  } else {
    echo Blade::$app->run("pages.404");
  }
});