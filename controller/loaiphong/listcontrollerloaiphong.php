<?php
// controller/loaiphong/listcontrollerloaiphong.php
$modPath = __DIR__ . '/../../models/loaiphong/listloaiphong.php';
if (!file_exists($modPath)) { throw new Exception("Missing model: $modPath"); }
require_once $modPath;

class listcontrollerloaiphong {
    private $model;
    public function __construct() { $this->model = new listloaiphong(); }
    public function show_loaiphong() { return $this->model->show_loaiphong(); }
}
