<?php

namespace HimaPro;

/**
 * A Pure PHP Class to manage SQL database in key: value mode.
 * @version 01.08.2023
 */
class SQL {

  /**
   * @var PDO $pdo a Secure MySQL class Built In with PHP.
   * @since 01.08.2023
   */
  private static $pdo;

  public static function Instance(){
    return new static();
  }

  /** 
   * Use this static function to connect to the SQL Server.
   * @param Object $props SQL server credentials.
   * @return null
   * @since 01.08.2023
   * Usage Example:
   * ```
   * SQL::Connect((object) [
   *  'host' => 'localhost',
   *  'db' => 'db_name',
   *  'user' => 'root',
   *  'pass' => ''
   * ]);
   * ```
   */
  public static function Connect($props) {
    $dsn = "mysql:host=".$props->host.";dbname=".$props->db;
    $options = [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_OBJ,
      \PDO::ATTR_EMULATE_PREPARES => false,
    ];

    self::$pdo = new \PDO($dsn, $props->user, $props->pass, $options);
  }

  /** 
   * @param String $tableName Name of the table to create it if not exist. 
   * @since 01.08.2023
   */
  private static function table($tableName) {
    $query = "CREATE TABLE IF NOT EXISTS {$tableName} (`name` VARCHAR(255), `content` TEXT, `tag` TEXT, `modified` TIMESTAMP PRIMARY KEY)";
    self::$pdo->exec($query);
  }

  /** 
   * @param String $tableName Name of the table to create it if not exist. 
   * @param String $key Key of the stored data. 
   * @param String $value Content of the stored data. 
   * @return String|true String if error, true if success.
   * @since 01.08.2023
   */
  public static function Insert($tableName, $key, $value, $tag = null) {
    self::table($tableName);
    if (gettype(self::Get($tableName, $key, $tag)) == "object") {
      return false;
    } else {
      $query = "INSERT INTO {$tableName} (`name`, `content`, `tag`, `modified`) VALUES (:key, :value, :tag, UTC_TIMESTAMP())";
      $stmt = self::$pdo->prepare($query);
      if ($stmt->execute([':key' => $key, ':value' => json_encode([$value]), ':tag' => $tag])) {
        return true;
      }
    }
  }

  /** 
   * @param String $tableName Name of the table to create it if not exist. 
   * @param String $key Key of the stored data. 
   * @return Object|false Object if success, false if not.
   * @since 01.08.2023
   */
  public static function Get($tableName, $key, $tag = null) {
    self::table($tableName);
    $query = "SELECT * FROM {$tableName} WHERE `name` = :key AND `tag` = :tag";
    $stmt = self::$pdo->prepare($query);
    $stmt->execute([':key' => $key, ':tag' => $tag]);
    $res = $stmt->fetch();
    if ($res) {
      $res->content = json_decode($res->content)[0];
      return $res;
    } else return false;
  }

  /** 
   * @param String $tableName Name of the table to create it if not exist. 
   * @return Array Array contains all taggs of stored data.
   * @since 01.08.2023
   */
  public static function GetAllTags($tableName) {
    $result = [];
    self::table($tableName);
    $query = "SELECT `tag` FROM {$tableName}";
    $stmt = self::$pdo->query($query);
    $res = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    $counts = array_count_values($res);
    foreach ($counts as $tag => $count) {
      $result[] = (object) ["tag" => $tag, "count" => $count];
    }
    return $result;
  }

  /** 
   * @param String $tableName Name of the table to create it if not exist. 
   * @param String $tag name of a collection. 
   * @return Array Array contains all stored data in format: key: value.
   * @since 01.08.2023
   */
  public static function GetAll($tableName, $tag = null) {
    self::table($tableName);
    $query = "SELECT * FROM {$tableName}";
    $stmt = self::$pdo->query($query);
    $res = $stmt->fetchAll();
    $result = [];
    for ($i = 0; $i < count($res); $i++) {
      if($res[$i]->tag == $tag){
        $item = $res[$i];
        $item->content = json_decode($item->content)[0];
        array_push($result, $item);
      }
    }
    return $result;
  }

  /**
   * @param String $tableName Name of the table to create it if not exist. 
   * @param String $key Key of the stored data. 
   * @param String $value Content of the stored data. 
   * @param String $tag name of a collection. 
   * @return String|true String if error, true if success.
   * @since 01.08.2023
   */
  public static function Update($tableName, $key, $value, $tag = null) {
    self::table($tableName);
    if (gettype(self::Get($tableName, $key, $tag)) === "object") {
      $query = "UPDATE {$tableName} SET `content` = :value, `modified` = UTC_TIMESTAMP() WHERE `name` = :key AND `tag` = :tag";
      $stmt = self::$pdo->prepare($query);
      if ($stmt->execute([':key' => $key, ':value' => json_encode([$value]), ':tag' => $tag])) {
        return true;
      }
    } else {
      return false;
    }
  }

  /**
   * @param String $tableName Name of the table to create it if not exist. 
   * @param String $key Key of the stored data. 
   * @param String $tag name of a collection. 
   * @return bool String if error, true if success.
   * @since 01.08.2023
   */
  public static function Delete($tableName, $key, $tag = null) {
    self::table($tableName);
    if (gettype(self::Get($tableName, $key, $tag)) == "object") {
      $query = "DELETE FROM {$tableName} WHERE `name` = :key AND `tag`= :tag";
      $stmt = self::$pdo->prepare($query);
      if ($stmt->execute([':key' => $key, ':tag' => $tag])) {
        return true;
      }
    } else {
      return false;
    }
  }

  /**
   * @param String $tableName Name of the table to create it if not exist. 
   * @param String $search String to search for it in all stored values.
   * @param String $tag name of a collection. 
   * @return Array Array contains all keys that thier value contains $query.
   * @since 01.08.2023
   */
  public static function Search($tableName, $search, $tag = null) {
    self::table($tableName);
    $result = array();
    $res = self::GetAll($tableName, $tag);
    for ($i = 0; $i < count($res); $i++) {
      if(mb_stripos($res[$i]->content, $search) !== false) {
        array_push($result, $res[$i]);
      }
    }
    return $result;
  }

  /**
   * @param String $tableName Name of the table to create it if not exist. 
   * @param String $tag name of a collection. 
   * @return Number The amount of all stored data.
   * @since 11.08.2023
   */
  public static function Count($tableName, $tag = null) {
    self::table($tableName);
    $query = "SELECT COUNT(`name`) FROM {$tableName} WHERE `tag` = :tag";
    $stmt = self::$pdo->prepare($query);
    $stmt->execute([":tag" => $tag]);
    return $stmt->fetchAll(\PDO::FETCH_COLUMN)[0];
  }
}

