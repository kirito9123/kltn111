<?php
$filepath = realpath(dirname(__FILE__));
include_once($filepath . '/../config/config.php');

?>

<?php

class Database
{
  public $host   = DB_HOST;
  public $user   = DB_USER;
  public $pass   = DB_PASS;
  public $dbname = DB_NAME;

  public $link;
  public $error;

  public function __construct()
  {
    $this->connectDB();
  }

  private function connectDB()
  {
    $this->link = new mysqli(
      $this->host,
      $this->user,
      $this->pass,
      $this->dbname,
      3306
    );
    if ($this->link->connect_errno) {
      $this->error = "Connection fail: " . $this->link->connect_error;
      return false;
    }

    // ✅ Đặt charset cho MySQL
    $this->link->set_charset("utf8mb4");

    // ✅ Set timezone PHP (ở đây thay cho config.php)
    date_default_timezone_set('Asia/Ho_Chi_Minh');

    // ✅ Set timezone cho MySQL session (đảm bảo NOW(), CURDATE() khớp với VN)
    $this->link->query("SET time_zone = '+07:00'");
  }

  // Select or Read data
  public function select($query)
  {
    $result = $this->link->query($query) or
      die($this->link->error . __LINE__);
    if ($result->num_rows > 0) {
      return $result;
    } else {
      return false;
    }
  }

  // Insert data
  public function insert($query)
  {
    $insert_row = $this->link->query($query) or
      die($this->link->error . __LINE__);
    if ($insert_row) {
      return $insert_row;
    } else {
      return false;
    }
  }

  // Update data
  public function update($query)
  {
    $update_row = $this->link->query($query) or
      die($this->link->error . __LINE__);
    if ($update_row) {
      return $update_row;
    } else {
      return false;
    }
  }

  // Delete data
  public function delete($query)
  {
    $delete_row = $this->link->query($query) or
      die($this->link->error . __LINE__);
    if ($delete_row) {
      return $delete_row;
    } else {
      return false;
    }
  }
}
