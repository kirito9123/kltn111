import face_recognition
from scipy.spatial import distance as dist
import numpy as np
import cv2

def eye_aspect_ratio(eye):
    """
    Tính toán tỷ lệ khung hình mắt (EAR - Eye Aspect Ratio).
    Dựa trên khoảng cách Euclid giữa các điểm mốc của mắt.
    """
    # Tính khoảng cách Euclid giữa các điểm mốc dọc của mắt
    A = dist.euclidean(eye[1], eye[5])
    B = dist.euclidean(eye[2], eye[4])

    # Tính khoảng cách Euclid giữa các điểm mốc ngang của mắt
    C = dist.euclidean(eye[0], eye[3])

    # Tính EAR
    ear = (A + B) / (2.0 * C)
    return ear

def mouth_aspect_ratio(top_lip, bottom_lip):
    """
    Tính toán tỷ lệ khung hình miệng (MAR - Mouth Aspect Ratio).
    Dùng để phát hiện nụ cười hoặc mở miệng.
    """
    # MAR: Khoảng cách dọc / Khoảng cách ngang
    
    # Tính trung bình tọa độ của môi trên và môi dưới
    t_mean = np.mean(top_lip, axis=0)
    b_mean = np.mean(bottom_lip, axis=0)
    
    # Tính khoảng cách dọc (chiều cao miệng)
    height = dist.euclidean(t_mean, b_mean)
    
    # Tính khoảng cách ngang (chiều rộng miệng)
    # top_lip[0] là khóe miệng trái, top_lip[6] là khóe miệng phải (theo chuẩn 68 điểm)
    width = dist.euclidean(top_lip[0], top_lip[6])
    
    if width == 0: return 0
    return height / width

def get_head_pose(landmarks):
    """
    Ước tính tư thế đầu (quay trái/phải) dựa trên vị trí mũi so với chiều rộng khuôn mặt.
    Trả về: 'turn_left' (quay trái), 'turn_right' (quay phải), 'center' (nhìn thẳng)
    """
    # Các điểm cằm: 0-16 (17 điểm)
    # 0 là tai trái (bên trái ảnh), 16 là tai phải (bên phải ảnh)
    # Mũi: lấy điểm đầu tiên của nose_tip
    
    chin = landmarks['chin']
    nose_tip = landmarks['nose_tip'][0]
    
    left_edge = chin[0]  # Cạnh trái ảnh (Má phải người dùng)
    right_edge = chin[16] # Cạnh phải ảnh (Má trái người dùng)
    
    # Tính khoảng cách từ mũi đến hai bên má
    dist_left = dist.euclidean(nose_tip, left_edge)
    dist_right = dist.euclidean(nose_tip, right_edge)
    
    total_width = dist_left + dist_right
    if total_width == 0: return 'center'
    
    # Tính tỷ lệ vị trí mũi
    ratio = dist_left / total_width
    
    # Nếu nhìn thẳng, ratio ~ 0.5
    # Nếu quay TRÁI (người dùng quay sang trái của họ) -> Mặt hướng về bên PHẢI ảnh -> Mũi gần right_edge hơn
    # -> dist_right NHỎ, dist_left LỚN -> ratio > 0.5
    
    # Nếu quay PHẢI (người dùng quay sang phải của họ) -> Mặt hướng về bên TRÁI ảnh -> Mũi gần left_edge hơn
    # -> dist_left NHỎ -> ratio < 0.5
    
    # Ngưỡng xác định (Thresholds)
    # Ngưỡng xác định (Thresholds) - Đã nới lỏng để dễ nhận diện hơn
    if ratio < 0.40: # Mũi gần cạnh trái -> Người dùng quay PHẢI
        return 'turn_right'
    elif ratio > 0.60: # Mũi gần cạnh phải -> Người dùng quay TRÁI
        return 'turn_left'
    else:
        return 'center'

def check_liveness(images_list, challenge_type='blink'):
    """
    Kiểm tra tính thực thể (Liveness) dựa trên loại thử thách.
    challenge_type: 'blink' (chớp mắt), 'turn_left' (quay trái), 'turn_right' (quay phải), 'smile' (cười)
    """
    ears = []
    mars = []
    poses = []
    valid_frames = []

    for img_np in images_list:
        try:
            # Chuyển đổi màu BGR sang RGB (face_recognition dùng RGB)
            rgb_frame = cv2.cvtColor(img_np, cv2.COLOR_BGR2RGB)
            face_locations = face_recognition.face_locations(rgb_frame)
            
            if not face_locations:
                continue
                
            # Giả sử chỉ xử lý khuôn mặt đầu tiên tìm thấy
            landmarks = face_recognition.face_landmarks(rgb_frame, face_locations)
            if not landmarks:
                continue

            shape = landmarks[0]
            
            # 1. Tính EAR (Cho thử thách Chớp mắt)
            leftEye = shape['left_eye']
            rightEye = shape['right_eye']
            leftEAR = eye_aspect_ratio(leftEye)
            rightEAR = eye_aspect_ratio(rightEye)
            ear = (leftEAR + rightEAR) / 2.0
            ears.append(ear)
            
            # 2. Tính MAR (Cho thử thách Cười)
            topLip = shape['top_lip']
            bottomLip = shape['bottom_lip']
            mar = mouth_aspect_ratio(topLip, bottomLip)
            mars.append(mar)
            
            # 3. Tính Pose (Cho thử thách Quay đầu)
            pose = get_head_pose(shape)
            poses.append(pose)
            
            valid_frames.append(rgb_frame)

        except Exception as e:
            print(f"Lỗi xử lý frame: {e}")
            continue

    if len(valid_frames) < 2:
        return False, None, "Không đủ dữ liệu khuôn mặt để phân tích."

    # --- XÁC MINH THỬ THÁCH ---
    
    # Tìm frame tốt nhất (rõ nét nhất, nhìn thẳng nhất) để nhận diện khuôn mặt sau này
    # Thường là frame có EAR lớn nhất (mắt mở to)
    best_idx = np.argmax(ears) 
    best_frame = valid_frames[best_idx]

    if challenge_type == 'blink':
        # Kiểm tra sự thay đổi của EAR (có mở và nhắm)
        min_ear = min(ears)
        max_ear = max(ears)
        # Ngưỡng: chênh lệch > 0.05 và mắt mở > 0.25
        if max_ear - min_ear > 0.05 and max_ear > 0.25:
            return True, best_frame, "Đã phát hiện chớp mắt."
        else:
            return False, None, "Vui lòng chớp mắt để xác thực."

    elif challenge_type == 'turn_left':
        # Kiểm tra xem có frame nào quay trái không
        if 'turn_left' in poses:
             return True, best_frame, "Đã phát hiện quay trái."
        else:
             return False, None, "Vui lòng quay mặt sang TRÁI."

    elif challenge_type == 'turn_right':
        # Kiểm tra xem có frame nào quay phải không
        if 'turn_right' in poses:
             return True, best_frame, "Đã phát hiện quay phải."
        else:
             return False, None, "Vui lòng quay mặt sang PHẢI."

    elif challenge_type == 'smile':
        # Kiểm tra MAR có vượt ngưỡng cười không
        # Bình thường ngậm miệng MAR ~ 0.0 - 0.1
        # Cười/Mở miệng MAR > 0.3 (có thể cần tinh chỉnh)
        max_mar = max(mars)
        print(f"Max MAR: {max_mar}")
        if max_mar > 0.2: 
             return True, best_frame, "Đã phát hiện nụ cười."
        else:
             return False, None, "Vui lòng CƯỜI HỞ RĂNG hoặc MỞ MIỆNG."
             
    else:
        # Mặc định nếu loại thử thách không hợp lệ
        return False, None, f"Yêu cầu không hợp lệ: {challenge_type}"
