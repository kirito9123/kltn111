<?php
// controller/phong/listcontrollerphong.php
$modPath = __DIR__ . '/../../models/phong/listphong.php';
if (!file_exists($modPath)) { throw new Exception("Missing model: $modPath"); }
require_once $modPath;

class listcontrollerphong {
    private $model;
    public function __construct() { $this->model = new listphong(); }

    public function show_phong_all() { return $this->model->show_phong_all(); }

    // KHỚP TÊN với datban.php
    public function show_phongbyloaiphong($maloaiphong) {
        return $this->model->show_phongbyloaiphong($maloaiphong);
    }

    public function get_phong($id) { return $this->model->get_phong($id); }
}
