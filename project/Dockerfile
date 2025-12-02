FROM python:3.10-slim

# Cài các công cụ cần thiết để build dlib
RUN apt-get update && apt-get install -y \
    build-essential \
    cmake \
    g++ \
    libopenblas-dev \
    liblapack-dev \
    libx11-dev \
    libgtk-3-dev \
    && rm -rf /var/lib/apt/lists/*

# Tạo thư mục làm việc
WORKDIR /app

# Copy toàn bộ mã nguồn vào container
COPY . .

# Cài các thư viện Python cần thiết
RUN pip install --no-cache-dir \
    flask \
    opencv-python \
    face_recognition \
    mysql-connector-python \
    numpy \
    pickle-mixin

# Mở port Flask
EXPOSE 5000

# Tạo thư mục lưu dữ liệu nếu cần
RUN mkdir -p face_data

# Chạy ứng dụng
CMD ["python", "app.py"]
