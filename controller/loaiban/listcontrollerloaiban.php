<?php
// controller/loaiban/listcontrollerloaiban.php
$modPath = __DIR__ . '/../../models/loaiban/listloaiban.php';
if (!file_exists($modPath)) { throw new Exception("Missing model: $modPath"); }
require_once $modPath;

class listcontrollerloaiban {
    private $model;
    public function __construct() { $this->model = new listloaiban(); }
    public function show_loaiban() { return $this->model->show_loaiban(); }
}
