<?php
class Session {
    public static function init() {
        if (version_compare(phpversion(), '5.4.0', '<')) {
            if (session_id() == '') {
                session_start();
            }
        } else {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
        }
    }

    public static function set($key, $val) {
        $_SESSION[$key] = $val;
    }

    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : false;
    }

    public static function checkSession() {
        self::init();
        if (!self::get("adminlogin")) {
            self::destroy();
            exit();
        }
    }

    public static function checkLogin() {
        self::init();
        if (self::get("adminlogin")) {
            header("Location:index.php");
            exit();
        }
    }

    public static function destroy() {
        session_destroy();
        header("Location:login.php");
        exit();
    }
}
