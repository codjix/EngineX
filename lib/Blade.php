<?php

namespace HimaPro;

use eftec\bladeone\BladeOne;
use eftec\bladeonehtml\BladeOneHtml;

class BladeAll extends BladeOne {
  use BladeOneHtml;
}

class Blade {
  public static $app;
  public static function init($dir, string $base = "/") {
    $blade = new BladeAll($dir . "/views", $dir . "/cache", BladeOne::MODE_SLOW);
    $blade->setBaseUrl($base);
    $blade->pipeEnable = true;
    self::$app = $blade;
  }
}
