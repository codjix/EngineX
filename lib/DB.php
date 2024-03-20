<?php

namespace HimaPro;

class DB {

  # Initialize
  private static $path;
  public static function setup($path) {
    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }
    self::$path = $path;
  }

  # public preview functions
  public static function get($key, $subPath = null) {
    $filePath = self::getFilePath($key, $subPath);
    if (!file_exists($filePath)) return false;
    $content = file_get_contents($filePath);
    $data = json_decode($content, true);
    return array(
      "modified" => filemtime($filePath),
      "content" => $data[0]
    );
  }
  public static function getAll($subPath = null) {
    $dirPath = self::getDirPath($subPath);
    if (!file_exists($dirPath)) return false;
    $items = array();
    $files = glob($dirPath . '/*');
    foreach ($files as $file) {
      if (is_dir($file)) {
        $items[] = array(
          'name' => basename($file),
          'modified' => filemtime($file),
          'type' => 'table'
        );
      } else {
        $key = basename($file, '.json');
        $items[] = array(
          'name' => $key,
          'modified' => filemtime($file),
          'type' => 'item'
        );
      }
    }
    return $items;
  }
  public static function exist($key, $subPath = null) {
    $filePath = self::getFilePath($key, $subPath);
    if (!file_exists($filePath)) {
      return false;
    } else {
      return true;
    }
  }
  public static function search($query, $subPath = null) {
    return self::findStringInFolder(self::getDirPath($subPath), $query);
  }

  # public control functions
  public static function add($key, $value, $subPath = null) {
    $filePath = self::getFilePath($key, $subPath);
    if (file_exists($filePath)) return false;
    self::createSubPath($subPath);
    $data = array($value);
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($filePath, $json);
    return true;
  }
  public static function update($key, $value, $subPath = null) {
    $filePath = self::getFilePath($key, $subPath);
    self::createSubPath($subPath);
    $data = array($value);
    $json = json_encode($data, JSON_PRETTY_PRINT);
    file_put_contents($filePath, $json);
    return true;
  }
  public static function rename($old, $new, $subPath = null) {
    $oldName = self::getFilePath($old, $subPath);
    $newName = self::getFilePath($new, $subPath);
    if (file_exists($oldName)) {
      if (file_exists($newName)) {
        return "new name already exist";
      } else {
        rename($oldName, $newName);
        return "success";
      }
    } else {
      return "not found";
    }
  }
  public static function delete($key, $subPath = null) {
    $filePath = self::getFilePath($key, $subPath);
    if (!file_exists($filePath)) {
      return false;
    }
    unlink($filePath);
    self::clean($subPath);
    return true;
  }
  public static function deleteAll($subPath = null) {
    $dirPath = self::getDirPath($subPath);
    if (!file_exists($dirPath)) {
      return false;
    }
    system('rm -rf -- ' . escapeshellarg($dirPath), $retval);
    self::clean($subPath);
    return true;
  }

  # control functions
  private static function clean($subPath = null) {
    $dirPath = self::getDirPath($subPath);
    if (!file_exists($dirPath)) {
      return;
    }
    $files = glob($dirPath . '/*');
    foreach ($files as $file) {
      if (is_dir($file)) {
        self::clean($subPath . '/' . basename($file));
        if (count(glob($file . '/*')) === 0) {
          rmdir($file);
        }
      }
    }
    if (count(glob($dirPath . '/*')) === 0) {
      rmdir($dirPath);
    }
  }
  private static function findStringInFolder($folder, $searchString) {
    $results = array();
    $files = scandir($folder);
    foreach ($files as $file) {
      if ($file != '.' && $file != '..') {
        $itemPath = $folder . DIRECTORY_SEPARATOR . $file;
        if (is_dir($itemPath)) {
          $results = array_merge($results, self::findStringInFolder($itemPath, $searchString));
        } else {
          $handle = fopen($itemPath, 'r');
          while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            if (strpos($chunk, $searchString) !== false) {
              $name = explode(".", end(explode("/", $file)))[0];
              $path = str_replace(self::$path, "", dirname($itemPath));
              $results[] = array(
                'name' => $name,
                'modified' => filemtime($itemPath),
                'path' => $path == "" ? "/" : $path
              );
              break;
            }
          }
          fclose($handle);
        }
      }
    }
    return $results;
  }
  private static function getDirPath($subPath = null) {
    if ($subPath === null) {
      return self::$path;
    }
    return self::$path . '/' . trim($subPath, '/');
  }
  private static function getFilePath($key, $subPath = null) {
    $dirPath = self::getDirPath($subPath);
    return $dirPath . '/' . $key . '.json';
  }
  private static function createSubPath($subPath = null) {
    if ($subPath === null) return;
    $fullPath = self::$path . '/' . trim($subPath, '/');
    if (!file_exists($fullPath)) {
      mkdir($fullPath, 0777, true);
    }
  }
}
