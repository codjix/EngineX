<?php

namespace HimaPro;

class Validation {
    public static $patterns = array(
        'uri' => '[A-Za-z0-9-\/_?&=]+',
        'url' => '[A-Za-z0-9-:.\/_?&=#]+',
        'alpha' => '[\p{L}]+',
        'words' => '[\p{L}\s]+',
        'alphanum' => '[\p{L}0-9]+',
        'int' => '[0-9]+',
        'float' => '[0-9\.,]+',
        'tel' => '[0-9+\s()-]+',
        'text' => '[\p{L}0-9\s-.,;:!"%&()?+\'°#\/@]+',
        'file' => '[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+\.[A-Za-z0-9]{2,4}',
        'folder' => '[\p{L}\s0-9-_!%&()=\[\]#@,.;+]+',
        'address' => '[\p{L}0-9\s.,()°-]+',
        'date_dmy' => '[0-9]{1,2}\-[0-9]{1,2}\-[0-9]{4}',
        'date_ymd' => '[0-9]{4}\-[0-9]{1,2}\-[0-9]{1,2}',
        'email' => '[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+[.]+[a-z-A-Z]'
    );

    public static $errors = array();
    public static $value;
    public static $name;
    public static $file;

    public static function setName(string $name) {
        self::$name = $name;
        return new static();
    }

    public static function setValue($value) {
        self::$value = $value;
        return new static();
    }

    public static function setFile($file) {
        self::$file = $file;
        return new static();
    }

    public static function pattern($name) {
        if ($name == "array") {
            if (!is_array(self::$value)) {
                self::$errors[] = "Format " . self::$name . " not valid.";
            }
        } else {
            $regex = "/^(" . self::$patterns[$name] . ")$/u";
            if (self::$value != "" && !preg_match($regex, self::$value)) {
                self::$errors[] = "Format " . self::$name . " not valid.";
            }
        }
        return new static();
    }

    public static function customPattern($pattern) {
        $regex = "/^($pattern)$/u";
        if (self::$value != "" && !preg_match($regex, self::$value)) {
            self::$errors[] = "Format " . self::$name . " not valid.";
        }
        return new static();
    }

    public static function required() {
        if ((isset(self::$file) && self::$file["error"] == 4) || (self::$value == "" || self::$value == null)) {
            self::$errors[] = self::$name . " is required.";
        }
        return new static();
    }

    public static function min(int $length) {
        if (is_string(self::$value)) {
            if (strlen(self::$value) < $length) {
                self::$errors[] = "Value " . self::$name . " less than $length.";
            }
        } else {
            if (self::$value < $length) {
                self::$errors[] = "Value " . self::$name . " less than $length.";
            }
        }
        return new static();
    }

    public static function max(int $length) {
        if (is_string(self::$value)) {
            if (strlen(self::$value) > $length) {
                self::$errors[] = "Value " . self::$name . " more than $length.";
            }
        } else {
            if (self::$value > $length) {
                self::$errors[] = "Value " . self::$name . " more than $length.";
            }
        }
        return new static();
    }

    public static function equal($value) {
        if (self::$value != $value) {
            self::$errors[] = "Value " . self::$name . " not equal $value.";
        }
        return new static();
    }

    public static function maxSize(int $size) {
        if (self::$file["error"] != 4 && self::$file["size"] > $size) {
            self::$errors[] = "file " . self::$name . " is bigger than " . number_format($size / 1048576, 2) . " MB.";
        }
        return new static();
    }

    public static function ext(string $extension) {
        if (self::$file["error"] != 4 && pathinfo(self::$file["name"], PATHINFO_EXTENSION) != $extension && strtoupper(pathinfo(self::$file["name"], PATHINFO_EXTENSION)) != $extension) {
            self::$errors[] = "file " . self::$name . " is not $extension .";
        }
        return new static();
    }

    public static function purify(string $string) {
        return htmlspecialchars($string, ENT_QUOTES, "UTF-8");
    }

    public static function isSuccess() {
        return (empty(self::$errors)) ? true : false;
    }

    public static function getErrors() {
        if (!self::isSuccess()) return self::$errors;
    }

    public static function displayErrors() {
        $html = "<ul>";
        foreach (self::getErrors() as $error) {
            $html .= "<li>" . $error . "</li>";
        }
        $html .= "</ul>";
        return $html;
    }

    public static function filter($filter) {
        if (!self::is($filter, self::$value)) {
            self::$errors[] = self::$name . " is not $filter";
        }
        return new static();
    }

    public static function is($filter, $value) {
        $filters = array(
            "alpha" => filter_var($value, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^[a-zA-Z]+$/"))),
            "alphanum" => filter_var($value, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^[a-zA-Z0-9]+$/"))),
            "bool" => is_bool(filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)),
            "email" => filter_var($value, FILTER_VALIDATE_EMAIL),
            "float" => filter_var($value, FILTER_VALIDATE_FLOAT),
            "int" => filter_var($value, FILTER_VALIDATE_INT),
            "uri" => filter_var($value, FILTER_VALIDATE_REGEXP, array("options" => array("regexp" => "/^[A-Za-z0-9-\/_]+$/"))),
            "url" => filter_var($value, FILTER_VALIDATE_URL),
        );
        return $filters[$filter];
    }
}
