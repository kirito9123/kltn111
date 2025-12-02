from flask import Flask, render_template, request, jsonify
import os, mysql.connector, face_recognition, cv2, numpy as np, pickle
from train import retrain 
from datetime import datetime, timedelta, time
from flask_cors import CORS
import traceback 
import liveness_detection 
import math

app = Flask(__name__)
CORS(app) # Cho phép CORS

FACE_DIR = 'face_data' 
os.makedirs(FACE_DIR, exist_ok=True)

# --- KẾT NỐI DATABASE ---
def get_connection():
    try:
        conn = mysql.connector.connect(
            host="localhost",
            user="root",
            password="",
            database="gs_restaurant1", # Tên CSDL
            charset='utf8mb4'
        )
        return conn
    except mysql.connector.Error as err:
        print(f"Lỗi kết nối MySQL: {err}")
        return None

# --- HÀM HELPERS ---
def time_from_timedelta(td):
    """Chuyển đổi timedelta (từ MySQL) thành đối tượng time của Python."""
    if isinstance(td, time): return td
    if isinstance(td, timedelta):
        total_seconds = int(td.total_seconds())
        hours, remainder = divmod(total_seconds, 3600)
        minutes, seconds = divmod(remainder, 60)
        hours = hours % 24 
        return time(hours, minutes, seconds)
    return None

def combine_dt(date_obj, time_obj):
    return datetime.combine(date_obj, time_obj)
    
def is_blurry(image, threshold=60.0):
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    lap_var = cv2.Laplacian(gray, cv2.CV_64F).var()
    return lap_var < threshold

# --- ROUTES HTML ---
@app.route('/')
def index(): return render_template('index.html')

@app.route('/them_khuon_mat')
def them_khuon_mat_page(): return render_template('them_khuon_mat.html')

@app.route('/diem_danh')
def diem_danh_page(): return render_template('diem_danh.html')

@app.route('/check_out')
def check_out_page(): return render_template('checkout.html')

# --- API KIỂM TRA NHÂN SỰ ---
@app.route('/api/kiem_tra_nhansu_bang_mans', methods=['POST'])
def kiem_tra_nhansu_bang_mans():
    data = request.get_json()
    mans = data.get('mans')
    name_admin = data.get('Name_admin')
    if not mans or not name_admin: return jsonify({"exists": False, "message": "Thiếu Mã Nhân Sự hoặc Họ tên"})
    conn = get_connection(); cursor = conn.cursor() if conn else None
    if not cursor: return jsonify({"exists": False, "message": "Lỗi kết nối CSDL"})
    
    sql = "SELECT ns.id_admin, ta.Name_admin FROM nhansu ns JOIN tb_admin ta ON ns.id_admin = ta.id_admin WHERE ns.mans = %s AND ta.Name_admin = %s"
    result = None
    try: 
        cursor.execute(sql, (mans, name_admin))
        result = cursor.fetchone()
    except mysql.connector.Error as err: print(f"Lỗi SQL: {err}")
    finally: conn.close()
    
    if result: return jsonify({"exists": True, "id_admin": result[0], "Name_admin": result[1]})
    else: return jsonify({"exists": False, "message": "Thông tin không khớp"})

# --- API KIỂM TRA TÀI KHOẢN ADMIN ---
@app.route('/api/kiem_tra_taikhoan', methods=['POST'])
def kiem_tra_taikhoan():
    data = request.get_json(); user_id = data.get('id_admin'); hoten = data.get('Name_admin')
    if not user_id or not hoten: return jsonify({"exists": False, "message": "Thiếu id_admin hoặc Name_admin"})
    conn = get_connection(); cursor = conn.cursor() if conn else None
    if not cursor: return jsonify({"exists": False, "message": "Lỗi kết nối CSDL"})
    result = None
    try: 
        cursor.execute("SELECT * FROM tb_admin WHERE id_admin = %s AND Name_admin = %s", (user_id, hoten))
        result = cursor.fetchone()
    except mysql.connector.Error as err: print(f"Lỗi SQL: {err}")
    finally: conn.close()
    return jsonify({"exists": bool(result)})

# --- API VERIFY LIVENESS (BƯỚC 1) ---
@app.route('/api/verify_liveness', methods=['POST'])
def verify_liveness_api():
    images = request.files.getlist('images')
    if not images: return jsonify(success=False, message="❌ Không có ảnh nào được gửi!")
    
    try:
        img_list = []
        for img_file in images:
            img_file.stream.seek(0)
            img_np = np.frombuffer(img_file.read(), np.uint8)
            img = cv2.imdecode(img_np, cv2.IMREAD_COLOR)
            if img is not None: img_list.append(img)
            
        if not img_list: return jsonify(success=False, message="❌ Không đọc được dữ liệu ảnh!")

        challenge = request.form.get('challenge', 'blink')
        is_live, _, msg = liveness_detection.check_liveness(img_list, challenge_type=challenge)
        
        if is_live:
            return jsonify(success=True, message=msg)
        else:
            return jsonify(success=False, message=msg)
    except Exception as e:
        print(f"Lỗi verify_liveness: {e}")
        return jsonify(success=False, message=f"❌ Lỗi hệ thống: {e}")

# --- API ĐIỂM DANH (CHECK-IN) ---
@app.route('/api/diem_danh', methods=['POST'])
def diem_danh_api():
    if 'image' not in request.files and 'images' not in request.files: 
        return jsonify({"num_faces": -1, "message": "Không có file ảnh"})
    
    # Load model
    model_path = os.path.join(FACE_DIR, 'encodings.pkl')
    if not os.path.exists(model_path): return "❌ Chưa có dữ liệu huấn luyện (encodings.pkl)"
    with open(model_path, 'rb') as f: known_encodings, known_names = pickle.load(f)

    images = request.files.getlist('images')
    if not images: return "❌ Không có ảnh nào được gửi!"

    try:
        img_list = []
        for img_file in images:
            img_file.stream.seek(0)
            img_np = np.frombuffer(img_file.read(), np.uint8)
            img = cv2.imdecode(img_np, cv2.IMREAD_COLOR)
            if img is not None: img_list.append(img)
        
        if not img_list: return "❌ Không đọc được dữ liệu ảnh!"

        # Check Liveness
        challenge = request.form.get('challenge', 'blink')
        is_live, best_frame_rgb, msg = liveness_detection.check_liveness(img_list, challenge_type=challenge)
        if not is_live: return f"❌ Liveness Check Failed: {msg}"

        faces = face_recognition.face_encodings(best_frame_rgb)
        if not faces: return "😥 Không nhận diện được khuôn mặt!"

        distances = face_recognition.face_distance(known_encodings, faces[0])
        best_match_index = np.argmin(distances)
        if distances[best_match_index] >= 0.5: return "❌ Khuôn mặt không khớp!"
        
        info = known_names[best_match_index]
        user_id, hoten = info.split("_", 1)
        user_id = int(user_id)

        # Xử lý CSDL Check-in
        conn = get_connection(); cursor = conn.cursor(dictionary=True) if conn else None
        if not cursor: return "❌ Lỗi kết nối CSDL!"
        try:
            cursor.execute("SELECT mans FROM nhansu WHERE id_admin = %s", (user_id,))
            nhansu_row = cursor.fetchone()
            if not nhansu_row: return f"❌ Không tìm thấy hồ sơ nhân sự cho {hoten}!"
            mans = nhansu_row['mans']
            today = datetime.now().strftime('%Y-%m-%d')
            now_time_str = datetime.now().strftime('%H:%M:%S')

            # 1. Lấy tất cả các ca CHƯA CHẤM CÔNG trong ngày của nhân viên, sắp xếp theo giờ bắt đầu
            query_get_shifts = """
                SELECT dk.id_dangky, ca.ten_ca, ca.gio_bat_dau, ca.gio_ket_thuc
                FROM tbl_dangkylich dk JOIN tbl_ca ca ON dk.id_ca = ca.id_ca
                WHERE dk.mans = %s AND dk.ngay = %s AND dk.trang_thai_cham_cong = 'Chưa chấm công'
                ORDER BY ca.gio_bat_dau ASC
            """
            cursor.execute(query_get_shifts, (mans, today))
            shifts = cursor.fetchall()

            if not shifts:
                # Kiểm tra xem đã check-in ca nào chưa (để báo lỗi chính xác hơn)
                cursor.execute("SELECT ca.ten_ca FROM tbl_dangkylich dk JOIN tbl_ca ca ON dk.id_ca = ca.id_ca WHERE dk.mans = %s AND dk.ngay = %s AND dk.trang_thai_cham_cong = 'Đã check-in' LIMIT 1", (mans, today))
                already = cursor.fetchone()
                if already: return f"⚠️ Bạn đã check-in vào ca {already['ten_ca']} rồi."
                return "❌ Không tìm thấy ca đăng ký nào hôm nay (hoặc đã hoàn thành hết)."

            checked_in_shift = None
            missed_shifts = []
            
            # Chuyển đổi thời gian hiện tại sang timedelta để so sánh
            now_td = timedelta(hours=datetime.now().hour, minutes=datetime.now().minute, seconds=datetime.now().second)

            for shift in shifts:
                shift_start = shift['gio_bat_dau'] # timedelta
                shift_end = shift['gio_ket_thuc']   # timedelta
                
                # Logic so sánh thời gian
                # 1. Nếu hiện tại > Giờ kết thúc -> Vắng
                if now_td > shift_end:
                    cursor.execute("UPDATE tbl_dangkylich SET trang_thai_cham_cong = 'Vắng', tien_phat = 500000 WHERE id_dangky = %s", (shift['id_dangky'],))
                    missed_shifts.append(shift['ten_ca'])
                    continue
                
                # 2. Nếu hiện tại >= (Giờ bắt đầu - 15p) VÀ hiện tại <= Giờ kết thúc -> Check-in
                # start_window = shift_start - 15 mins
                start_window = shift_start - timedelta(minutes=15)
                
                if now_td >= start_window and now_td <= shift_end:
                    # Tính phạt đi trễ
                    di_tre_phut = 0
                    tien_phat = 0
                    
                    shift_start_seconds = shift_start.total_seconds()
                    now_seconds = now_td.total_seconds()
                    
                    if now_seconds > shift_start_seconds:
                        diff_seconds = now_seconds - shift_start_seconds
                        minutes_late = math.ceil(diff_seconds / 60)
                        
                        if minutes_late > 15:
                            di_tre_phut = minutes_late
                            hours_late = math.ceil(minutes_late / 60)
                            tien_phat = hours_late * 100000

                    cursor.execute("UPDATE tbl_dangkylich SET gio_cham_cong = %s, trang_thai_cham_cong = 'Đã check-in', di_tre_phut = %s, tien_phat = %s WHERE id_dangky = %s", (now_time_str, di_tre_phut, tien_phat, shift['id_dangky']))
                    checked_in_shift = shift['ten_ca']
                    break # Đã check-in thành công 1 ca, thoát vòng lặp (các ca sau chưa tới giờ)
                
                # 3. Nếu hiện tại < (Giờ bắt đầu - 15p) -> Ca tương lai, chưa tới giờ -> Dừng kiểm tra
                if now_td < start_window:
                    break

            conn.commit()

            msg = ""
            if missed_shifts:
                msg += f"⚠️ Đã đánh dấu VẮNG: {', '.join(missed_shifts)} (Quá giờ - Phạt 500k). "
            
            if checked_in_shift:
                msg += f"✅ Check-in thành công: {checked_in_shift} lúc {now_time_str}."
                if tien_phat > 0:
                    msg += f" (Trễ {di_tre_phut}p - Phạt {tien_phat:,}đ)"
                return msg
            elif missed_shifts:
                return msg + "❌ Không tìm thấy ca phù hợp để check-in (các ca trước đã quá giờ)."
            else:
                return "❌ Chưa tới giờ check-in cho ca tiếp theo (sớm hơn 15p)."

        except mysql.connector.Error as err: return f"❌ Lỗi CSDL: {err}"
        finally: 
            if conn: conn.close()
    except Exception as e: return f"❌ Lỗi hệ thống: {e}"

# === API THÊM KHUÔN MẶT ===
@app.route('/api/them_khuon_mat', methods=['POST'])
def them_khuon_mat_api():
    user_id = request.form.get('id_admin')
    hoten = request.form.get('Name_admin')
    
    if not user_id or not hoten: 
        return jsonify(success=False, message="❌ Thiếu 'id_admin' hoặc 'Name_admin'!"), 400
    
    images = request.files.getlist('images')
    if not images: 
        return jsonify(success=False, message="❌ Không có ảnh nào được gửi!"), 400

    try:
        img_list = []
        for img_file in images:
            img_file.stream.seek(0)
            img_np = np.frombuffer(img_file.read(), np.uint8)
            img = cv2.imdecode(img_np, cv2.IMREAD_COLOR)
            if img is not None: img_list.append(img)
        
        if not img_list: return jsonify(success=False, message="❌ Không đọc được dữ liệu ảnh!"), 400

        # Check Liveness
        challenge = request.form.get('challenge', 'blink')
        is_live, best_frame_rgb, msg = liveness_detection.check_liveness(img_list, challenge_type=challenge)
        if not is_live:
            return jsonify(success=False, message=f"❌ Liveness Check Failed: {msg}"), 400

        # Check Face Count
        face_locations = face_recognition.face_locations(best_frame_rgb)
        if len(face_locations) != 1:
            return jsonify(success=False, message=f"❌ Ảnh phải chứa đúng 1 khuôn mặt (tìm thấy {len(face_locations)})."), 400

        # Lưu ảnh vào đĩa (folder face_data)
        best_frame_bgr = cv2.cvtColor(best_frame_rgb, cv2.COLOR_RGB2BGR)
        folder_name = f'{user_id}_{hoten}'
        folder_path = os.path.join(FACE_DIR, folder_name)
        os.makedirs(folder_path, exist_ok=True)
        
        image_path = os.path.join(folder_path, '1.jpg')
        cv2.imwrite(image_path, best_frame_bgr)
        print(f"Đã lưu ảnh file vào: {image_path}")

        # Encode ảnh thành bytes để lưu vào DB
        is_success, buffer = cv2.imencode(".jpg", best_frame_bgr)
        if not is_success: return jsonify(success=False, message="Lỗi nén ảnh"), 500
        image_data = buffer.tobytes()
        
        conn = get_connection()
        cursor = conn.cursor() if conn else None
        
        if not cursor: return jsonify(success=False, message="Lỗi kết nối CSDL"), 500
            
        try:
            # Chuyển đổi user_id sang int để tránh lỗi type
            try:
                user_id_int = int(user_id)
            except ValueError:
                user_id_int = user_id

            # Tạo tuple tham số rõ ràng
            sql = "UPDATE tb_admin SET anh_face = %s WHERE id_admin = %s AND Name_admin = %s"
            val = (image_data, user_id_int, hoten) # Tuple 3 phần tử: blob, int, string

            cursor.execute(sql, val)
            conn.commit()
            
            if cursor.rowcount == 0:
                print(f"Cảnh báo: Không tìm thấy ID {user_id} để cập nhật ảnh.")
            else:
                print(f"Đã cập nhật anh_face DB cho ID {user_id}")

        except mysql.connector.Error as err:
            if conn: conn.rollback() # Rollback nếu lỗi
            print(f"Lỗi SQL: {err}")
            return jsonify(success=False, message=f"Lỗi CSDL: {err}"), 500
        finally:
            if conn and conn.is_connected():
                conn.close()

        print("Bắt đầu huấn luyện lại...")
        if retrain(): 
            return jsonify(success=True, message=f"✅ Đã thêm/cập nhật khuôn mặt cho {hoten}")
        else: 
            return jsonify(success=False, message="⚠️ Đã lưu ảnh nhưng lỗi khi huấn luyện lại.")

    except Exception as e: 
        print(f"Lỗi thêm khuôn mặt: {e}")
        traceback.print_exc()
        return jsonify(success=False, message=f"❌ Lỗi hệ thống: {e}"), 500


# === API CHECK OUT ===
@app.route('/api/check_out', methods=['POST'])
def check_out_api():
    # Load model
    model_path = os.path.join(FACE_DIR, 'encodings.pkl')
    if not os.path.exists(model_path): return "❌ Lỗi: Tệp encodings.pkl không tồn tại."
    with open(model_path, 'rb') as f: known_encodings, known_names = pickle.load(f)

    images = request.files.getlist('images')
    if not images: return "❌ Không có ảnh nào được gửi!"

    try:
        img_list = []
        for img_file in images:
            img_file.stream.seek(0)
            img_np = np.frombuffer(img_file.read(), np.uint8)
            img = cv2.imdecode(img_np, cv2.IMREAD_COLOR)
            if img is not None: img_list.append(img)
        
        if not img_list: return "❌ Không đọc được dữ liệu ảnh!"

        # Check Liveness
        challenge = request.form.get('challenge', 'blink')
        is_live, best_frame_rgb, msg = liveness_detection.check_liveness(img_list, challenge_type=challenge)
        if not is_live: return f"❌ Liveness Check Failed: {msg}"

        faces = face_recognition.face_encodings(best_frame_rgb)
        if not faces: return "😥 Không nhận diện được khuôn mặt!"
        
        distances = face_recognition.face_distance(known_encodings, faces[0])
        best_match_index = np.argmin(distances)
        if distances[best_match_index] >= 0.5: return "❌ Khuôn mặt không khớp!"
        
        info = known_names[best_match_index]
        user_id, hoten = info.split("_", 1)
        user_id = int(user_id)

        # Xử lý CSDL Check-out
        conn = get_connection(); cursor = conn.cursor(dictionary=True) if conn else None
        if not cursor: return "❌ Lỗi kết nối CSDL!"
        
        try:
            cursor.execute("SELECT ns.mans, ta.level FROM nhansu ns JOIN tb_admin ta ON ns.id_admin = ta.id_admin WHERE ns.id_admin = %s LIMIT 1", (user_id,))
            nhansu_info = cursor.fetchone()
            if not nhansu_info: return f"❌ Không tìm thấy hồ sơ nhân sự cho {hoten}!"
            mans = nhansu_info['mans']
            user_level = nhansu_info['level']
            
            today_str = datetime.now().strftime('%Y-%m-%d')
            now_time_str = datetime.now().strftime('%H:%M:%S')

            # 1. Tìm ca ĐẦU TIÊN đã check-in nhưng CHƯA check-out
            query_find_first = """
                SELECT dk.id_dangky, dk.id_ca, ca.ten_ca, ca.gio_bat_dau, dk.gio_cham_cong
                FROM tbl_dangkylich dk JOIN tbl_ca ca ON dk.id_ca = ca.id_ca
                WHERE dk.mans = %s AND dk.ngay = %s AND dk.trang_thai_cham_cong = 'Đã check-in'
                ORDER BY ca.gio_bat_dau ASC LIMIT 1
            """
            cursor.execute(query_find_first, (mans, today_str))
            first_shift = cursor.fetchone()

            if not first_shift:
                 cursor.execute("SELECT trang_thai_cham_cong FROM tbl_dangkylich WHERE mans = %s AND ngay = %s ORDER BY id_dangky DESC LIMIT 1", (mans, today_str))
                 other = cursor.fetchone()
                 if other and other['trang_thai_cham_cong'] == 'Đã hoàn thành': return "⚠️ Bạn đã check-out hết rồi."
                 return "⚠️ Bạn chưa check-in ca nào."

            # 2. Tìm ca MỤC TIÊU (target_ca) dựa trên GIỜ CHECK-OUT
            query_target = """
                SELECT * FROM tbl_ca 
                WHERE TIME(%s) >= SUBTIME(gio_ket_thuc, '00:10:00') 
                AND TIME(%s) <= ADDTIME(gio_ket_thuc, '00:30:00')
                ORDER BY gio_ket_thuc DESC LIMIT 1
            """
            cursor.execute(query_target, (now_time_str, now_time_str))
            target_ca = cursor.fetchone()

            if not target_ca:
                return f"❌ Check-out thất bại! Giờ {now_time_str} không khớp giờ ra ca nào."

            # 3. Lấy chuỗi ca để fill
            start_time = time_from_timedelta(first_shift['gio_bat_dau']).strftime('%H:%M:%S')
            end_time = time_from_timedelta(target_ca['gio_ket_thuc']).strftime('%H:%M:%S')

            cursor.execute("SELECT * FROM tbl_ca WHERE gio_bat_dau >= %s AND gio_ket_thuc <= %s ORDER BY gio_bat_dau ASC", (start_time, end_time))
            shifts_to_fill = cursor.fetchall()

            if not shifts_to_fill: return "❌ Lỗi logic: Không tìm thấy chuỗi ca."

            messages = []
            for i, shift in enumerate(shifts_to_fill):
                id_ca_curr = shift['id_ca']
                ten_ca_curr = shift['ten_ca']
                
                # Giờ vào
                if i == 0: t_in = time_from_timedelta(first_shift['gio_cham_cong']).strftime('%H:%M:%S')
                else: t_in = time_from_timedelta(shift['gio_bat_dau']).strftime('%H:%M:%S')

                # Giờ ra
                if i == len(shifts_to_fill) - 1: t_out = now_time_str
                else: t_out = time_from_timedelta(shift['gio_ket_thuc']).strftime('%H:%M:%S')

                # REPLACE INTO
                sql_replace = """
                    REPLACE INTO tbl_dangkylich (mans, ngay, id_ca, level, gio_cham_cong, gio_check_out, trang_thai_cham_cong) 
                    VALUES (%s, %s, %s, %s, %s, %s, 'Đã hoàn thành')
                """
                cursor.execute(sql_replace, (mans, today_str, id_ca_curr, user_level, t_in, t_out))
                messages.append(f"- {ten_ca_curr}: {t_in} -> {t_out}")

            conn.commit()
            return f"✅ Check-out thành công ({len(shifts_to_fill)} ca):\n" + "\n".join(messages)

        except mysql.connector.Error as err: 
            if conn: conn.rollback()
            return f"❌ Lỗi CSDL: {err}"
        finally: 
            if conn: conn.close()
    except Exception as e: 
        traceback.print_exc()
        return f"❌ Lỗi hệ thống: {e}"

# --- MAIN ---
if __name__ == '__main__':
    app.run(host="0.0.0.0", port=5000, debug=True)