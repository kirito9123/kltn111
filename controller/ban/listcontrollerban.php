<?php
// controller/ban/listcontrollerban.php
$modPath = __DIR__ . '/../../models/ban/listban.php';
if (!file_exists($modPath)) { throw new Exception("Missing model: $modPath"); }
require_once $modPath;

class listcontrollerban {
    private $model;
    public function __construct() { $this->model = new listban(); }

    // KHỚP TÊN với datban.php
    public function show_ban($id_phong, $id_loaiban) {
        return $this->model->show_ban($id_phong, $id_loaiban);
    }
}
