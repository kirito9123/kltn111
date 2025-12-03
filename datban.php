	<?php
	$filepath = realpath(dirname(__FILE__));
	include_once($filepath . '/lib/session.php');

	Session::init();

	// Kiểm tra đăng nhập USER (không phải admin)
	if (!Session::get('userlogin')) {
		header('Location: login.php');
		exit();
	}

	// Lấy thông tin USER từ session (dùng đúng key!)
	$user_id    = Session::get('id') ?: 0;        // ← ĐỔI từ 'idadmin' → 'id'
	$user_name  = Session::get('name') ?: 'Người dùng';
	$user_sdt   = Session::get('sdt') ?: '';

	// Lấy thông tin chi tiết từ DB
	include_once __DIR__ . '/classes/user.php';
	$userObj = new user();
	$userInfo = $userObj->show_thongtin($user_id); // Trả về mysqli_result

	if ($userInfo && $userInfo->num_rows > 0) {
		$u = $userInfo->fetch_assoc();
		$customer_name  = $u['ten'];
		$customer_phone = $u['sodienthoai'];
		$customer_email = isset($u['email']) ? $u['email'] : 'Chưa cập nhật';
	} else {
		$customer_name  = $user_name;
		$customer_phone = $user_sdt;
		$customer_email = 'Chưa cập nhật';
	}

	/* ================== NẠP CONTROLLER ================== */
	include_once __DIR__ . '/controller/loaiphong/listcontrollerloaiphong.php';
	include_once __DIR__ . '/controller/phong/listcontrollerphong.php';
	include_once __DIR__ . '/controller/ban/listcontrollerban.php';
	include_once __DIR__ . '/controller/loaiban/listcontrollerloaiban.php';

	$listloaiphong = new listcontrollerloaiphong();
	$listphong     = new listcontrollerphong();
	$listban       = new listcontrollerban();
	$listloaiban   = new listcontrollerloaiban();

	/* ================== DỮ LIỆU BƯỚC 3: LOẠI PHÒNG ================== */
	$show_loaiphong = null;
	try {
		$show_loaiphong = $listloaiphong->show_loaiphong();
	} catch (Throwable $e) {
		// error_log($e->getMessage());
		$show_loaiphong = null;
	}

	/* (Dùng cho bước 5) – nạp loại bàn để render 3 ô chọn */
	$show_loaiban = null;
	try {
		$show_loaiban = $listloaiban->show_loaiban();
	} catch (Throwable $e) {
		// error_log($e->getMessage());
		$show_loaiban = null;
	}

	/* ================== TIỆN ÍCH TRẢ JSON SẠCH ================== */
	function _json_start()
	{
		ini_set('display_errors', 0);
		ini_set('log_errors', 1);
		error_reporting(E_ALL);
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-store');
	}
	function _json_end($payload)
	{
		while (ob_get_level() > 0) {
			ob_end_clean();
		}
		echo json_encode($payload, JSON_UNESCAPED_UNICODE);
		exit;
	}

	/* ============ AJAX: DANH SÁCH PHÒNG THEO LOẠI PHÒNG ============ */
	/* GET: datban.php?ajax=phong&maloaiphong=ID */
	if (isset($_GET['ajax']) && $_GET['ajax'] === 'phong') {
		_json_start();
		try {
			$id = isset($_GET['maloaiphong']) ? (int)$_GET['maloaiphong'] : 0;
			if ($id <= 0) {
				_json_end(['success' => false, 'message' => 'Thiếu hoặc sai maloaiphong']);
			}

			$rs = $listphong->show_phongbyloaiphong($id);
			if (!$rs instanceof mysqli_result) {
				_json_end(['success' => false, 'message' => 'Kiểu trả về không hỗ trợ']);
			}

			$rooms = [];
			while ($r = $rs->fetch_assoc()) {
				$rooms[] = [
					'id_phong'   => (int)$r['id_phong'],
					'tenphong'   => $r['tenphong'],
					'images' => !empty($r['hinhanh']) ? 'images/' . $r['hinhanh'] : null,
					'soluongban' => 16, // thay bằng cột thật nếu có
				];
			}

			_json_end(['success' => true, 'rooms' => $rooms]);
		} catch (Throwable $e) {
			// error_log('AJAX phong error: '.$e->getMessage());
			_json_end(['success' => false, 'message' => 'Lỗi server khi load phòng']);
		}
	}

	/* ============ AJAX: DANH SÁCH BÀN THEO PHÒNG + LOẠI BÀN ============ */
	/* GET: datban.php?ajax=ban&id_phong=ID&id_loaiban=ID */
	if (isset($_GET['ajax']) && $_GET['ajax'] === 'ban') {
		_json_start();
		try {
			$id_phong   = isset($_GET['id_phong'])   ? (int)$_GET['id_phong']   : 0;
			$id_loaiban = isset($_GET['id_loaiban']) ? (int)$_GET['id_loaiban'] : 0;

			// 1. Lấy ngày khách chọn (nếu không có thì lấy hôm nay)
			$ngay_dat   = isset($_GET['ngay_dat']) && !empty($_GET['ngay_dat'])
				? $_GET['ngay_dat']
				: date('Y-m-d');

			if ($id_phong <= 0 || $id_loaiban <= 0) {
				_json_end(['success' => false, 'message' => 'Thiếu id_phong hoặc id_loaiban']);
			}

			// 2. Kết nối DB trực tiếp để kiểm tra lịch
			// Đảm bảo đường dẫn này đúng với cấu trúc thư mục của bạn
			include_once __DIR__ . '/lib/database.php';
			$db = new Database();

			$ngay_sql = mysqli_real_escape_string($db->link, $ngay_dat);

			// 3. Câu SQL kiểm tra xem ngày đó bàn nào đã có hợp đồng
			$query = "
                SELECT b.id_ban, b.tenban,
                (
                    SELECT COUNT(*) 
                    FROM hopdong h 
                    WHERE FIND_IN_SET(b.id_ban, h.so_ban) > 0 
                    AND h.dates = '$ngay_sql' 
                    AND h.payment_status != 'cancelled'
                ) as da_dat
                FROM ban b
                WHERE b.id_phong = $id_phong AND b.id_loaiban = $id_loaiban
                ORDER BY b.tenban ASC
            ";

			$rs = $db->select($query);

			$tables = [];
			if ($rs) {
				while ($r = $rs->fetch_assoc()) {
					// Nếu da_dat > 0 nghĩa là ngày đó có đơn rồi -> Bàn bận
					$is_busy = ($r['da_dat'] > 0);

					$tables[] = [
						'id_ban'    => (int)$r['id_ban'],
						'tenban'    => $r['tenban'],
						// available = TRUE nếu chưa bị đặt
						'available' => !$is_busy
					];
				}
			}

			_json_end(['success' => true, 'tables' => $tables]);
		} catch (Throwable $e) {
			_json_end(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
		}
	}
	?>

	<!DOCTYPE html>
	<html lang="vi">

	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Đặt Bàn - Nhà Hàng</title>
		<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
		<link rel="stylesheet" href="css/datban/datban.css">
	</head>

	<body>
		<div style="margin: 12px 0;">
			<a href="index.php"
				style="display:inline-flex;align-items:center;gap:8px;
						padding:8px 12px;border-radius:8px;
						border:1px solid #0d6efd;
						background:#0d6efd;
						color:#fff;
						text-decoration:none;">
				<span style="font-size:18px;line-height:1;color:#fff;">←</span>
				<span style="color:#fff;">Quay lại trang chủ</span>
			</a>
		</div>


		<div class="container">
			<div class="header">
				<h1><i class="fas fa-utensils"></i> Đặt Bàn Nhà Hàng</h1>
				<p>Trải nghiệm đẳng cấp, dịch vụ hoàn hảo</p>
			</div>

			<!-- Progress Bar -->
			<div class="progress-bar">
				<div class="progress-line" id="progressLine"></div>
				<div class="step-indicator active" data-step="1">
					<div class="step-circle">1</div>
					<div class="step-label">Thông tin</div>
				</div>
				<div class="step-indicator" data-step="2">
					<div class="step-circle">2</div>
					<div class="step-label">Ngày giờ</div>
				</div>
				<div class="step-indicator" data-step="3">
					<div class="step-circle">3</div>
					<div class="step-label">Loại phòng</div>
				</div>
				<div class="step-indicator" data-step="4">
					<div class="step-circle">4</div>
					<div class="step-label">Chọn phòng</div>
				</div>
				<div class="step-indicator" data-step="5">
					<div class="step-circle">5</div>
					<div class="step-label">Chọn bàn</div>
				</div>
				<div class="step-indicator" data-step="6">
					<div class="step-circle">6</div>
					<div class="step-label">Xác nhận</div>
				</div>
			</div>

			<!-- Form Card -->
			<div class="form-card">
				<form id="bookingForm" method="POST" action="">
					<!-- Step 1: Thông tin khách hàng -->
					<div class="step-content active" data-step="1">
						<h2 class="step-title">Thông tin khách hàng</h2>
						<p class="step-subtitle">Vui lòng xác nhận thông tin của bạn</p>

						<div class="row">
							<div class="form-group">
								<label><i class="fas fa-user"></i> Họ và tên</label>
								<input type="text" name="customer_name" value="<?php echo htmlspecialchars($customer_name); ?>" disabled>
							</div>
							<div class="form-group">
								<label><i class="fas fa-phone"></i> Số điện thoại</label>
								<input type="text" name="customer_phone" value="<?php echo htmlspecialchars($customer_phone) ?>" disabled>
							</div>
						</div>

						<div class="form-group">
							<label><i class="fas fa-envelope"></i> Email</label>
							<input type="email" name="customer_email" value="<?php echo htmlspecialchars($customer_email) ?> " disabled>
						</div>

						<div class="info-box">
							<i class="fas fa-info-circle"></i>
							<span>Thông tin này được lấy từ tài khoản của bạn và không thể chỉnh sửa</span>
						</div>

						<div class="button-group">
							<button type="button" class="btn btn-next" onclick="nextStep()">
								Tiếp theo <i class="fas fa-arrow-right"></i>
							</button>
						</div>
					</div>

					<!-- Step 2: Ngày giờ -->
					<div class="step-content" data-step="2">
						<h2 class="step-title">Chọn ngày và giờ</h2>
						<p class="step-subtitle">Chọn thời gian bạn muốn đến nhà hàng</p>

						<div class="row">
							<div class="form-group">
								<label><i class="fas fa-calendar-alt"></i> Ngày đặt bàn</label>
								<input type="date" name="booking_date" id="bookingDate" required>
							</div>
							<div class="form-group">
								<label><i class="fas fa-clock"></i> Giờ đặt bàn</label>
								<input type="time" name="booking_time" id="bookingTime" value="19:00" required>
							</div>
						</div>

						<div class="button-group">
							<button type="button" class="btn btn-prev" onclick="prevStep()">
								<i class="fas fa-arrow-left"></i> Quay lại
							</button>
							<button type="button" class="btn btn-next" onclick="nextStep()">
								Tiếp theo <i class="fas fa-arrow-right"></i>
							</button>
						</div>
					</div>

					<!-- Step 3: Chọn loại phòng (Load từ DB) -->
					<div class="step-content" data-step="3">
						<h2 class="step-title">Chọn loại phòng</h2>
						<p class="step-subtitle">Bạn muốn tổ chức loại sự kiện nào?</p>

						<div class="event-grid" id="loaiPhongGrid">
							<?php
							if ($show_loaiphong instanceof mysqli_result && $show_loaiphong->num_rows > 0) {
								$first = true;
								while ($row = $show_loaiphong->fetch_assoc()) {
									$selected = $first ? 'selected' : '';
									$checked  = $first ? 'checked'  : '';
									$first = false;

									$icons = [
										'sinh nhật' => '🎂',
										'đám cưới'  => '💍',
										'gặp mặt'   => '🤝',
										'hải sản'   => '🦞',
										'công việc' => '💼',
										'default'   => '🎉'
									];

									$tenLoaiPhong = strtolower($row['tenloaiphong']);
									$icon = $icons['default'];
									foreach ($icons as $key => $value) {
										if (strpos($tenLoaiPhong, $key) !== false) {
											$icon = $value;
											break;
										}
									}

									echo '<label class="event-card ' . $selected . '" data-loaiphong-id="' . $row['maloaiphong'] . '">';
									echo '<input type="radio" name="loai_phong_id" value="' . $row['maloaiphong'] . '" ' . $checked . '>';
									echo '<div class="event-icon">' . $icon . '</div>';
									echo '<div class="event-name">' . htmlspecialchars($row['tenloaiphong']) . '</div>';
									echo '<div class="event-desc">' . (!empty($row['mota']) ? htmlspecialchars($row['mota']) : 'Phù hợp cho sự kiện') . '</div>';
									echo '</label>';
								}
							} else {
								echo '<p>Không có loại phòng khả dụng</p>';
							}
							?>
						</div>


						<div class="button-group">
							<button type="button" class="btn btn-prev" onclick="prevStep()">
								<i class="fas fa-arrow-left"></i> Quay lại
							</button>
							<button type="button" class="btn btn-next" onclick="nextStepLoadPhong()">
								Tiếp theo <i class="fas fa-arrow-right"></i>
							</button>
						</div>
					</div>

					<!-- Step 4: Chọn phòng (Load từ DB dựa vào loại phòng) -->
					<div class="step-content" data-step="4">
						<h2 class="step-title">Chọn phòng</h2>
						<p class="step-subtitle">Chọn phòng phù hợp với nhu cầu của bạn</p>

						<div class="room-grid" id="phongGrid">
							<!-- Sẽ được load bằng AJAX -->
						</div>

						<div class="button-group">
							<button type="button" class="btn btn-prev" onclick="prevStep()">
								<i class="fas fa-arrow-left"></i> Quay lại
							</button>
							<button type="button" class="btn btn-next" onclick="nextStep()">
								Tiếp theo <i class="fas fa-arrow-right"></i>
							</button>
						</div>
					</div>

					<!-- Step 5: Chọn bàn -->
					<div class="step-content" data-step="5">
						<h2 class="step-title">Chọn bàn</h2>
						<p class="step-subtitle">Chọn loại bàn trước, sau đó chọn bàn cụ thể</p>

						<!-- CHỌN LOẠI BÀN (3 ô) -->
						<div class="event-grid" id="loaiBanGrid">
							<?php
							// Nếu có $show_loaiban từ PHP, render động; nếu không, render cứng 3 loại
							$loaibans = [];
							if (isset($show_loaiban) && $show_loaiban instanceof mysqli_result && $show_loaiban->num_rows > 0) {
								// ← SỬA: đổi thành $show_loaiban
								while ($lb = $show_loaiban->fetch_assoc()) {
									$loaibans[] = [
										'id'   => (int)$lb['id_loaiban'],
										'name' => $lb['tenloaiban'],
										'desc' => !empty($lb['mota']) ? $lb['mota'] : 'Phù hợp cho nhóm tương ứng'
									];
								}
							} else {
								// fallback nếu không có data
								$loaibans = [
									['id' => 1, 'name' => 'Bàn loại 1', 'desc' => 'Tiêu chuẩn, 4–6 khách'],
									['id' => 2, 'name' => 'Bàn loại 2', 'desc' => 'Trung, 6–8 khách'],
									['id' => 3, 'name' => 'Bàn loại 3', 'desc' => 'Lớn, 10–12 khách'],
								];
							}

							// Render các loại bàn
							foreach ($loaibans as $lb) {
								echo '<label class="event-card" data-loaiban-id="' . $lb['id'] . '">';
								echo '  <input type="radio" name="loai_ban_id" value="' . $lb['id'] . '">';
								echo '  <div class="event-icon"><i class="fas fa-chair"></i></div>';
								echo '  <div class="event-name">' . htmlspecialchars($lb['name']) . '</div>';
								echo '  <div class="event-desc">' . htmlspecialchars($lb['desc']) . '</div>';
								echo '</label>';
							}
							?>
						</div>

						<!-- GỢI Ý: nhắc chọn loại bàn (hiện ban đầu) -->
						<div id="noteChonLoaiBan" class="info-box" style="display:block; margin-top:12px;">
							<i class="fas fa-info-circle"></i>
							<span>Vui lòng chọn <b>loại bàn</b> để xem danh sách bàn khả dụng.</span>
						</div>

						<!-- KHU VỰC DANH SÁCH BÀN (ẩn ban đầu, chỉ hiện khi đã chọn loại bàn) -->
						<div id="banSection" style="display:none; margin-top:12px;">
							<div class="form-group" style="margin-top:10px;">
								<label><i class="fas fa-chair"></i> Các bàn có sẵn</label>

								<div class="table-grid" id="banGrid">
									<!-- sẽ được fill bằng JS -->
								</div>

								<div class="warning-box" style="margin-top:10px;">
									<i class="fas fa-info-circle"></i>
									<span>Bàn màu xám đã được đặt. Bạn có thể chọn nhiều bàn cùng lúc</span>
								</div>
							</div>
						</div>

						<div class="button-group">
							<button type="button" class="btn btn-prev" onclick="prevStep()">
								<i class="fas fa-arrow-left"></i> Quay lại
							</button>
							<button type="button" class="btn btn-next" onclick="nextStepShowSummary()">
								Tiếp theo <i class="fas fa-arrow-right"></i>
							</button>
						</div>
					</div>
					<!-- Step 6: Xác nhận -->
					<div class="step-content" data-step="6">
						<h2 class="step-title">Xác nhận đặt bàn</h2>
						<p class="step-subtitle">Kiểm tra lại thông tin trước khi hoàn tất</p>

						<div class="summary-box">
							<h3 style="margin-bottom: 15px; color: #333; font-size: 18px;">Thông tin đặt bàn</h3>

							<div class="summary-item">
								<span class="summary-label"><i class="fas fa-user"></i> Họ tên:</span>
								<span class="summary-value" id="summaryName"><?php echo htmlspecialchars($customer_name); ?></span>
							</div>

							<div class="summary-item">
								<span class="summary-label"><i class="fas fa-phone"></i> Số điện thoại:</span>
								<span class="summary-value" id="summaryName"><?php echo htmlspecialchars($customer_phone); ?></span>
							</div>

							<div class="summary-item">
								<span class="summary-label"><i class="fas fa-calendar-alt"></i> Ngày đặt:</span>
								<span class="summary-value" id="summaryDate">-</span>
							</div>

							<div class="summary-item">
								<span class="summary-label"><i class="fas fa-clock"></i> Giờ đặt:</span>
								<span class="summary-value" id="summaryTime">-</span>
							</div>

							<div class="summary-item">
								<span class="summary-label"><i class="fas fa-door-open"></i> Loại phòng:</span>
								<span class="summary-value" id="summaryLoaiPhong">-</span>
							</div>

							<div class="summary-item">
								<span class="summary-label"><i class="fas fa-home"></i> Phòng:</span>
								<span class="summary-value" id="summaryPhong">-</span>
							</div>

							<div class="summary-item">
								<span class="summary-label"><i class="fas fa-chair"></i> Bàn đã chọn:</span>
								<span class="summary-value" id="summaryTables">-</span>
							</div>
						</div>

						<div class="info-box">
							<i class="fas fa-info-circle"></i>
							<span>Sau khi xác nhận, chúng tôi sẽ liên hệ với bạn trong vòng 24 giờ để xác nhận đặt bàn</span>
						</div>

						<div class="button-group">
							<button type="button" class="btn btn-prev" onclick="prevStep()">
								<i class="fas fa-arrow-left"></i> Quay lại
							</button>
							<button type="submit" class="btn btn-submit">
								<i class="fas fa-check-circle"></i> Xác nhận đặt bàn
							</button>
						</div>
					</div>
				</form>
			</div>
		</div>

		<script>
			/* =========================
	FLOW đặt bàn 6 bước + overlay chọn cách gọi món
	========================= */

			let currentStep = 1;
			const totalSteps = 6; // vẫn là 6 bước – overlay không tính vào progress

			// Lưu phòng đã chọn (bước 4) để dùng khi chọn loại bàn ở bước 5
			let selectedPhongId = null;

			// Set min date = hôm nay
			const dateInput = document.getElementById('bookingDate');
			const today = new Date();
			if (dateInput) {
				dateInput.min = today.toISOString().split('T')[0];
				dateInput.value = today.toISOString().split('T')[0];
			}

			function updateProgressBar() {
				const progressLine = document.getElementById('progressLine');
				const percentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
				if (progressLine) progressLine.style.width = percentage + '%';

				document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
					const step = index + 1;
					if (step < currentStep) {
						indicator.classList.add('completed');
						indicator.classList.remove('active');
					} else if (step === currentStep) {
						indicator.classList.add('active');
						indicator.classList.remove('completed');
					} else {
						indicator.classList.remove('active', 'completed');
					}
				});
			}

			function _nextStepBase() {
				if (currentStep < totalSteps) {
					const cur = document.querySelector(`.step-content[data-step="${currentStep}"]`);
					if (cur) cur.classList.remove('active');
					currentStep++;
					const nxt = document.querySelector(`.step-content[data-step="${currentStep}"]`);
					if (nxt) nxt.classList.add('active');
					updateProgressBar();
					window.scrollTo({
						top: 0,
						behavior: 'smooth'
					});
				}
			}

			// === KHỞI TẠO BƯỚC 5: Ẩn danh sách bàn cho đến khi chọn loại bàn ===
			function initStep5() {
				document.querySelectorAll('#loaiBanGrid .event-card').forEach(c => {
					c.classList.remove('selected');
					const r = c.querySelector('input[name="loai_ban_id"]');
					if (r) r.checked = false;
				});

				const banSection = document.getElementById('banSection');
				const note = document.getElementById('noteChonLoaiBan');
				const banGrid = document.getElementById('banGrid');

				if (banGrid) banGrid.innerHTML = '';
				if (banSection) banSection.style.display = 'none';
				if (note) note.style.display = 'block';
			}

			// Override nextStep để khởi tạo step 5
			function nextStep() {
				_nextStepBase();
				if (currentStep === 5) {
					bindLoaiBanClicks();
					initStep5();
				}
			}

			function prevStep() {
				if (currentStep > 1) {
					const cur = document.querySelector(`.step-content[data-step="${currentStep}"]`);
					if (cur) cur.classList.remove('active');
					currentStep--;
					const pre = document.querySelector(`.step-content[data-step="${currentStep}"]`);
					if (pre) pre.classList.add('active');
					updateProgressBar();
					window.scrollTo({
						top: 0,
						behavior: 'smooth'
					});
				}
			}

			// Sau khi render phòng (bước 4) gọi hàm này để bind click và set selectedPhongId
			function bindRoomCardClicks() {
				document.querySelectorAll('.room-card').forEach(card => {
					card.addEventListener('click', function() {
						document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
						this.classList.add('selected');
						const radio = this.querySelector('input[type="radio"]');
						if (radio) radio.checked = true;
						selectedPhongId = this.getAttribute('data-phong-id');

						const selectedLoaiBan = document.querySelector('input[name="loai_ban_id"]:checked');
						if (currentStep === 5 && selectedPhongId && selectedLoaiBan) {
							showBanSection();
							loadBanFor(selectedPhongId, selectedLoaiBan.value);
						}
					});
				});

				const first = document.querySelector('.room-card.selected');
				if (first) {
					selectedPhongId = first.getAttribute('data-phong-id');
				} else {
					const firstRadio = document.querySelector('.room-card input[name="phong_id"]');
					if (firstRadio) {
						selectedPhongId = firstRadio.closest('.room-card').getAttribute('data-phong-id');
					}
				}
			}

			// Step 3 → 4: load phòng theo loại phòng
			function nextStepLoadPhong() {
				const selectedLoaiPhong = document.querySelector('input[name="loai_phong_id"]:checked');
				if (!selectedLoaiPhong) {
					alert('Vui lòng chọn loại phòng!');
					return;
				}
				const loaiPhongId = selectedLoaiPhong.value;

				fetch(`datban.php?ajax=phong&maloaiphong=${loaiPhongId}`)
					.then(async (response) => {
						const text = await response.text();
						try {
							const data = JSON.parse(text);
							if (!response.ok) throw new Error(data.message || 'HTTP error');
							return data;
						} catch (e) {
							console.error('Raw response:', text);
							throw e;
						}
					})
					.then((data) => {
						const phongGrid = document.getElementById('phongGrid');
						phongGrid.innerHTML = '';

						if (data.success && data.rooms.length > 0) {
							data.rooms.forEach((room, index) => {
								const selected = index === 0 ? 'selected' : '';
								const checked = index === 0 ? 'checked' : '';
								phongGrid.innerHTML += `
			<label class="room-card ${selected}" data-phong-id="${room.id_phong}">
				<input type="radio" name="phong_id" value="${room.id_phong}" ${checked}>
				<img src="${room.images || 'https://via.placeholder.com/400x300'}"
					alt="${room.tenphong}" class="room-image">
				<div class="room-info">
				<div class="room-name">${room.tenphong}</div>
				<div class="room-capacity"><i class="fas fa-chair"></i> ${room.soluongban || 0} bàn</div>
				</div>
			</label>`;
							});

							bindRoomCardClicks();
							nextStep(); // sang bước 4
						} else {
							alert('Không có phòng nào cho loại phòng này!');
						}
					})
					.catch((error) => {
						console.error('Fetch/JSON error:', error);
						alert('Có lỗi khi tải danh sách phòng!');
					});
			}

			// === BƯỚC 5: CHỌN LOẠI BÀN → LOAD BÀN ===
			function bindLoaiBanClicks() {
				document.querySelectorAll('#loaiBanGrid .event-card').forEach(card => {
					card.addEventListener('click', function() {
						document.querySelectorAll('#loaiBanGrid .event-card').forEach(c => c.classList.remove('selected'));
						this.classList.add('selected');
						const radio = this.querySelector('input[type="radio"]');
						if (radio) radio.checked = true;

						const idLoaiBan = this.getAttribute('data-loaiban-id');
						if (!selectedPhongId) {
							alert('Vui lòng chọn phòng ở bước 4 trước!');
							return;
						}

						showBanSection();
						loadBanFor(selectedPhongId, idLoaiBan);
					});
				});
			}

			function showBanSection() {
				const banSection = document.getElementById('banSection');
				const note = document.getElementById('noteChonLoaiBan');
				if (note) note.style.display = 'none';
				if (banSection) banSection.style.display = 'block';
			}

			function loadBanFor(idPhong, idLoaiBan) {
				const banGrid = document.getElementById('banGrid');
				if (!banGrid) return;

				// --- LẤY NGÀY TỪ Ô INPUT ---
				const dateInput = document.getElementById('bookingDate');
				const selectedDate = dateInput ? dateInput.value : '';
				// ---------------------------

				banGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;">Đang tải...</div>';

				// --- GỬI KÈM NGÀY LÊN SERVER ---
				fetch(`datban.php?ajax=ban&id_phong=${idPhong}&id_loaiban=${idLoaiBan}&ngay_dat=${selectedDate}`)
					// -------------------------------
					.then(async (response) => {
						const text = await response.text();
						try {
							const data = JSON.parse(text);
							if (!response.ok) throw new Error(data.message || 'HTTP error');
							return data;
						} catch (e) {
							console.error('Raw response (ban):', text);
							throw e;
						}
					})
					.then((data) => {
						banGrid.innerHTML = '';
						if (data.success && Array.isArray(data.tables) && data.tables.length > 0) {
							data.tables.forEach(tbl => {
								// Nếu available = false thì thêm class unavailable và disabled
								const unavailable = tbl.available ? '' : 'unavailable';
								const disabled = tbl.available ? '' : 'disabled';
								const statusText = tbl.available ? 'Trống' : 'Đã đặt';

								banGrid.innerHTML += `
                            <label class="table-item ${unavailable}" data-table-id="${tbl.id_ban}">
                            <input type="checkbox" name="tables[]" value="${tbl.id_ban}" ${disabled}>
                            <div class="table-icon"><i class="fas fa-table"></i></div>
                            <div class="table-number">${tbl.tenban}</div>
                            <div class="table-status">${statusText}</div>
                            </label>`;
							});

							bindTableClicks();
						} else {
							banGrid.innerHTML = '<div style="grid-column:1/-1;text-align:center;">Không có bàn phù hợp</div>';
						}
					})
					.catch(err => {
						console.error('Fetch/JSON error (ban):', err);
						alert('Có lỗi khi tải danh sách bàn!');
						banGrid.innerHTML = '';
					});
			}

			// Gắn click cho bàn có thể chọn
			function bindTableClicks() {
				document.querySelectorAll('#banGrid .table-item:not(.unavailable)').forEach(item => {
					item.replaceWith(item.cloneNode(true));
				});

				document.querySelectorAll('#banGrid .table-item:not(.unavailable)').forEach(item => {
					item.addEventListener('click', function(e) {
						e.preventDefault();
						const checkbox = this.querySelector('input[type="checkbox"]');
						if (this.classList.contains('selected')) {
							this.classList.remove('selected');
							checkbox.checked = false;
						} else {
							this.classList.add('selected');
							checkbox.checked = true;
						}
					});
				});
			}

			// Summary (bước 6)
			function nextStepShowSummary() {
				const selectedTables = document.querySelectorAll('input[name="tables[]"]:checked');
				if (selectedTables.length === 0) {
					alert('Vui lòng chọn ít nhất một bàn trước khi tiếp tục!');
					return;
				}

				const date = document.getElementById('bookingDate').value;
				const time = document.getElementById('bookingTime').value;

				const loaiPhongName = document.querySelector('#loaiPhongGrid .event-card.selected .event-name')?.textContent || '-';
				const phongName = document.querySelector('.room-card.selected .room-name')?.textContent || '-';

				const tableNames = Array.from(selectedTables).map(t => {
					const label = t.closest('.table-item');
					return label ? label.querySelector('.table-number').textContent : t.value;
				}).join(', ');

				document.getElementById('summaryDate').textContent = date;
				document.getElementById('summaryTime').textContent = time;
				document.getElementById('summaryLoaiPhong').textContent = loaiPhongName;
				document.getElementById('summaryPhong').textContent = phongName;
				document.getElementById('summaryTables').textContent = tableNames;

				_nextStepBase(); // sang bước 6
			}

			// Bind chọn loại phòng (step 3)
			document.querySelectorAll('#loaiPhongGrid .event-card').forEach(card => {
				card.addEventListener('click', function() {
					document.querySelectorAll('#loaiPhongGrid .event-card').forEach(c => c.classList.remove('selected'));
					this.classList.add('selected');
					const radio = this.querySelector('input[type="radio"]');
					if (radio) radio.checked = true;
				});
			});

			// Nếu còn các bàn demo tĩnh khác thì toggle
			document.querySelectorAll('.table-item:not(.unavailable)').forEach(item => {
				item.addEventListener('click', function() {
					this.classList.toggle('selected');
					const checkbox = this.querySelector('input[type="checkbox"]');
					checkbox.checked = !checkbox.checked;
				});
			});

			/* ========= THÊM OVERLAY CHỌN CÁCH GỌI MÓN – KHÔNG CẦN SỬA HTML ========= */

			// 1) Thay nút "Xác nhận đặt bàn" ở bước 6 thành nút "Tiếp theo"
			function replaceSubmitWithNext() {
				const step6 = document.querySelector('.step-content[data-step="6"]');
				if (!step6) return;
				const btnSubmit = step6.querySelector('.btn-submit');
				if (!btnSubmit) return;

				// Tạo nút Next
				const btnNext = document.createElement('button');
				btnNext.type = 'button';
				btnNext.className = 'btn btn-next';
				btnNext.innerHTML = 'Tiếp theo <i class="fas fa-arrow-right"></i>';
				btnNext.addEventListener('click', showChoiceOverlay);

				// Thay trong DOM
				btnSubmit.parentNode.replaceChild(btnNext, btnSubmit);
			}

			// 2) Tạo overlay 3 lựa chọn bằng JS
			function ensureChoiceOverlay() {
				if (document.getElementById('choiceOverlay')) return;

				const wrap = document.createElement('div');
				wrap.id = 'choiceOverlay';
				wrap.style.cssText = `
		position:fixed;inset:0;display:none;align-items:center;justify-content:center;
		background:rgba(0,0,0,.5);z-index:9999;`;

				wrap.innerHTML = `
		<div style="background:#fff;border-radius:14px;max-width:720px;width:90%;padding:16px 16px 20px;">
		<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
			<h3 style="margin:0;font-size:18px;">Chọn cách gọi món</h3>
			<button type="button" id="choiceClose"
					style="border:0;background:transparent;font-size:22px;cursor:pointer;line-height:1">×</button>
		</div>

		<div style="display:grid;gap:12px;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));">
			<button 
			type="button" 
			class="choice-card" 
			data-mode="datban"
			style="text-align:left;border:1px solid #eee;border-radius:10px;padding:12px;cursor:pointer;">
			<div style="font-size:22px">🗓️</div>
			<div style="font-weight:600;margin-top:6px">Đặt bàn</div>
			<div style="opacity:.8">Chỉ giữ chỗ, chọn món sau</div>
		</button>

			<button type="button" class="choice-card" data-mode="menu"
					style="text-align:left;border:1px solid #eee;border-radius:10px;padding:12px;cursor:pointer;">
			<div style="font-size:22px">📋</div>
			<div style="font-weight:600;margin-top:6px">Chọn món theo menu</div>
			<div style="opacity:.8">Chọn từ danh sách món sẵn có</div>
			</button>

			<button type="button" class="choice-card" data-mode="custom"
					style="text-align:left;border:1px solid #eee;border-radius:10px;padding:12px;cursor:pointer;">
			<div style="font-size:22px">✨</div>
			<div style="font-weight:600;margin-top:6px">Chọn món theo ý thích</div>
			<div style="opacity:.8">Tự nhập thực đơn mong muốn</div>
			</button>
		</div>
		</div>
	`;

				document.body.appendChild(wrap);

				// Close
				document.getElementById('choiceClose').addEventListener('click', hideChoiceOverlay);
				wrap.addEventListener('click', (e) => {
					if (e.target.id === 'choiceOverlay') hideChoiceOverlay();
				});

				// Gắn click 3 card
				wrap.querySelectorAll('.choice-card').forEach(btn => {
					const mode = btn.getAttribute('data-mode');

					// Đã xóa dòng if (mode === 'datban')...

					// Giờ nút nào cũng được phép chạy hàm gửi dữ liệu
					btn.addEventListener('click', () => chooseAndSubmit(mode));
				});
			}

			function showChoiceOverlay() {
				ensureChoiceOverlay();
				const ov = document.getElementById('choiceOverlay');
				ov.style.display = 'flex';
			}

			function hideChoiceOverlay() {
				const ov = document.getElementById('choiceOverlay');
				if (ov) ov.style.display = 'none';
			}

			// 3) Submit form + redirect với mode
			function chooseAndSubmit(mode) {
				const form = document.getElementById('bookingForm');
				const formData = new FormData(form);

				// Thêm các trường bị disabled (không tự động vào FormData)
				formData.set('customer_name', <?php echo json_encode($customer_name, JSON_UNESCAPED_UNICODE); ?>);
				formData.set('customer_phone', <?php echo json_encode($customer_phone, JSON_UNESCAPED_UNICODE); ?>);
				formData.set('customer_email', <?php echo json_encode($customer_email, JSON_UNESCAPED_UNICODE); ?>);
				formData.set('mode', mode);

				// Khoá các lựa chọn trong overlay
				document.querySelectorAll('#choiceOverlay .choice-card').forEach(b => b.disabled = true);

				fetch('process_booking.php', {
						method: 'POST',
						body: formData
					})
					.then(async (res) => {
						const raw = await res.text();
						let data;
						try {
							data = JSON.parse(raw);
						} catch {
							throw new Error('Response không phải JSON hợp lệ.');
						}
						if (!res.ok || !data.success) {
							throw new Error(data.message || ('HTTP ' + res.status));
						}
						// --- SỬA MỚI: Kiểm tra mode để chuyển trang đúng đích ---
						if (mode === 'datban') {
							// Nếu là đặt bàn thường -> Sang trang thanh toán/xác nhận
							window.location.href = 'vnpay_cre.php?mode=datban';
						} else {
							// Các trường hợp khác (Menu, Custom) -> Sang hợp đồng/chọn món
							let url = data.redirect_url || 'hopdong_menu.php';
							url += (url.includes('?') ? '&' : '?') + 'mode=' + encodeURIComponent(mode);
							window.location.href = url;
						}
						// -------------------------------------------------------
					})
					.catch((err) => {
						alert(err.message || 'Có lỗi khi xử lý đặt bàn!');
						document.querySelectorAll('#choiceOverlay .choice-card').forEach(b => b.disabled = false);
					});
			}

			/* ============ Khởi động ============ */
			document.addEventListener('DOMContentLoaded', function() {
				// thay nút submit ở bước 6 bằng nút Tiếp theo (mở overlay)
				replaceSubmitWithNext();

				// Gắn lại click cho bàn tĩnh (nếu có)
				bindTableClicks();

				// Progress bar ban đầu
				updateProgressBar();
			});
		</script>

	</body>

	</html>