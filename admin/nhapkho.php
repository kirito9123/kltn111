<?php include 'inc/header.php'; ?>
<?php include 'inc/sidebar.php'; ?>
<?php include_once __DIR__ . '/../classes/nguyenvatlieu.php'; ?>

<?php
$nl = new nguyenvatlieu();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_nhapkho'])) {
    $ma_phieu = $_POST['ma_phieu'];
    $nhan_vien = Session::get('adminname'); 
    $ghi_chu = $_POST['ghi_chu'];
    
    $chi_tiet = [];
    if (isset($_POST['id_nl']) && is_array($_POST['id_nl'])) {
        for ($i = 0; $i < count($_POST['id_nl']); $i++) {
            if (!empty($_POST['id_nl'][$i])) {
                $chi_tiet[] = [
                    'id_nl' => $_POST['id_nl'][$i],
                    'so_luong' => $_POST['so_luong'][$i],
                    'id_dvt_nhap' => $_POST['id_dvt_nhap'][$i],
                    // Quan trọng: Lấy thành tiền gửi lên server
                    'thanh_tien' => $_POST['thanh_tien'][$i] 
                ];
            }
        }
    }

    if (!empty($chi_tiet)) {
        $insert = $nl->tao_phieu_nhap($ma_phieu, $nhan_vien, $ghi_chu, $chi_tiet);
        if ($insert) {
            echo "<script>alert('Nhập kho thành công! Chuyển đến lịch sử để in phiếu.'); window.location='lichsunhapkho.php';</script>";
        } else { echo "<script>alert('Lỗi khi nhập kho!');</script>"; }
    } else { echo "<script>alert('Vui lòng chọn nguyên liệu!');</script>"; }
}

$db = new Database();
$q_nl = "SELECT nl.*, dvt.nhom FROM nguyen_lieu nl LEFT JOIN don_vi_tinh dvt ON nl.id_dvt = dvt.id_dvt WHERE nl.xoa = 0 ORDER BY nl.ten_nl ASC";
$ds_nl = $db->select($q_nl);

$ds_dvt = $nl->show_don_vi_tinh();
$arr_dvt = []; 
if($ds_dvt){ while($row = $ds_dvt->fetch_assoc()){ $arr_dvt[] = $row; } }
$next_ma = $nl->get_next_ma_phieu();
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
    .form-header { display: flex; gap: 20px; margin-bottom: 25px; background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #e1e1e1; }
    .form-group { display: flex; flex-direction: column; }
    .form-group label { font-weight: bold; margin-bottom: 5px; color: #555; }
    .form-control { padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px; width: 100%; box-sizing: border-box; }
    .table-input { width: 100%; border-collapse: collapse; margin-top: 10px; table-layout: fixed; }
    .table-input th { background: #0d6efd; color: white; padding: 12px; text-align: center; }
    .table-input td { padding: 8px; border: 1px solid #dee2e6; vertical-align: top; }
    .btn-add-row { background: #27ae60; color: white; padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-top: 10px;}
    .btn-remove { background: #e74c3c; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center;}
    .btn-save { background: #0d6efd; color: white; padding: 12px 30px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: bold; }
    .total-display { font-weight: bold; font-size: 20px; color: #d63031; text-align: right; padding: 15px 0; border-top: 2px solid #eee; margin-top: 15px; }
    .select2-container .select2-selection--single { height: 38px !important; border: 1px solid #ccc !important; display: flex; align-items: center; }
</style>

<div class="grid_10">
    <div class="box round first grid">
        <h2>Lập Phiếu Nhập Kho (Nhập theo Thành Tiền)</h2>
        <div class="block">
            <form action="" method="post">
                <div class="form-header">
                    <div class="form-group" style="width: 15%;"><label>Mã Phiếu</label><input type="text" name="ma_phieu" value="<?php echo $next_ma; ?>" readonly class="form-control" style="background: #e9ecef; font-weight:bold;"></div>
                    <div class="form-group" style="width: 25%;"><label>Người nhập</label><input type="text" value="<?php echo Session::get('adminname'); ?>" readonly class="form-control" style="background: #e9ecef;"></div>
                    <div class="form-group" style="width: 60%;"><label>Ghi chú</label><input type="text" name="ghi_chu" class="form-control" placeholder="Ví dụ: Nhập từ Siêu thị Go!..."></div>
                </div>

                <table class="table-input">
                    <thead>
                        <tr>
                            <th width="30%">Nguyên Liệu</th>
                            <th width="15%">Số Lượng</th>
                            <th width="15%">Đơn Vị</th>
                            <th width="15%">Đơn Giá (Gợi ý)</th>
                            <th width="20%">Thành Tiền (VNĐ)</th>
                            <th width="5%"></th>
                        </tr>
                    </thead>
                    <tbody id="nhap_body"></tbody>
                </table>

                <button type="button" class="btn-add-row" onclick="addRow()">+ Thêm dòng</button>
                <div class="total-display">Tổng Tiền: <span id="final-total">0</span> VNĐ</div>
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="submit_nhapkho" class="btn-save"><i class="fa fa-save"></i> LƯU PHIẾU</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const allUnits = <?php echo json_encode($arr_dvt); ?>;
    const optionsNL = `<option value="">-- Tìm món --</option>
    <?php 
        if($ds_nl){
            $ds_nl->data_seek(0);
            while($row = $ds_nl->fetch_assoc()){
                echo "<option value='{$row['id_nl']}' data-dvt-goc='{$row['id_dvt']}' data-gia='{$row['gia_nhap_tb']}' data-nhom='{$row['nhom']}'>{$row['ten_nl']}</option>";
            }
        }
    ?>`;

    $(document).ready(function() { addRow(); });

    function addRow() {
        var html = `<tr>
            <td>
                <select name="id_nl[]" class="form-control nl-select select2-new" required onchange="handleIngredientChange(this)">${optionsNL}</select>
            </td>
            <td><input type="number" step="0.01" name="so_luong[]" class="form-control qty" required oninput="calcByQty(this)" placeholder="SL"></td>
            <td><select name="id_dvt_nhap[]" class="form-control dvt-select"><option value="">--</option></select></td>
            
            <td><input type="number" step="1" class="form-control price" oninput="calcByPrice(this)" placeholder="Đơn giá"></td>
            
            <td><input type="number" step="100" name="thanh_tien[]" class="form-control total" required oninput="calcByTotal(this)" style="font-weight:bold; color:#d63031"></td>
            
            <td style="text-align:center;"><button type="button" class="btn-remove" onclick="removeRow(this)">&times;</button></td>
        </tr>`;
        
        var newRow = $(html);
        $('#nhap_body').append(newRow);
        newRow.find('.select2-new').select2({ width: '100%', placeholder: "-- Tìm món --", allowClear: true });
    }

    function removeRow(btn) { $(btn).closest('tr').remove(); calcFinalTotal(); }

    function handleIngredientChange(select) {
        var option = $(select).find(':selected');
        var row = $(select).closest('tr');
        var id_dvt_goc = option.data('dvt-goc');
        var nhom = option.data('nhom');

        var selectDVT = row.find('.dvt-select');
        selectDVT.empty();
        allUnits.forEach(function(u) {
            if (u.nhom == nhom) selectDVT.append(`<option value="${u.id_dvt}">${u.ten_dvt}</option>`);
        });
        if(id_dvt_goc) selectDVT.val(id_dvt_goc);
    }

    function roundMoney(num) {
        return Math.round(num); // Làm tròn đến hàng đơn vị (VD: 3.88 -> 4)
    }

    // 1. Nhập Số lượng -> Tính Thành Tiền
    function calcByQty(el) {
        var row = $(el).closest('tr');
        var qty = parseFloat(row.find('.qty').val()) || 0;
        var price = parseFloat(row.find('.price').val()) || 0;
        
        if(price > 0) {
            let total = roundMoney(qty * price);
            row.find('.total').val(total);
        }
        calcFinalTotal();
    }

    // 2. Nhập Đơn Giá -> Tính Thành Tiền
    function calcByPrice(el) {
        var row = $(el).closest('tr');
        var qty = parseFloat(row.find('.qty').val()) || 0;
        var price = parseFloat(row.find('.price').val()) || 0;
        
        let total = roundMoney(qty * price);
        row.find('.total').val(total);
        calcFinalTotal();
    }

    // 3. Nhập Thành Tiền -> Tự chia ngược lại ra Đơn Giá (Để tham khảo)
    // Đây là chỗ hay bị số lẻ nhất -> Sẽ làm tròn luôn
    function calcByTotal(el) {
        var row = $(el).closest('tr');
        var total = parseFloat(row.find('.total').val()) || 0;
        var qty = parseFloat(row.find('.qty').val()) || 0;
        
        if(qty > 0) {
            let price = total / qty;
            
            // Nếu giá bị lẻ quá (VD: 3333.3333), làm tròn 2 số thập phân hoặc số nguyên
            // Ở đây mình làm tròn thành số nguyên cho gọn (Math.round)
            row.find('.price').val(Math.round(price)); 
        }
        calcFinalTotal();
    }

    function calcFinalTotal() {
        var total = 0;
        $('.total').each(function() {
            total += parseFloat($(this).val()) || 0;
        });
        $('#final-total').text(new Intl.NumberFormat('vi-VN').format(total));
    }
</script>

<?php include 'inc/footer.php'; ?>