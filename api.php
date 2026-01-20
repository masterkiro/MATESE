<?php
// FILE: api.php
// Fungsi: Menangani permintaan data, login, dan upload file dari index.html

session_start();
header('Content-Type: application/json'); // Memberi tahu browser bahwa ini adalah data JSON
header('Access-Control-Allow-Origin: *'); // Mengizinkan akses dari file HTML

$dataFile = 'matese_data.json';
$uploadDir = 'uploads/'; // Folder untuk menyimpan gambar

// 1. Inisialisasi Database JSON jika belum ada
if (!file_exists($dataFile)) {
    $defaultData = [
        'publications' => [
            ['id' => 1, 'title' => "Analisis Kualitas Air Sungai Brantas", 'author' => "Dr. Budi Santoso", 'year' => 2023, 'type' => "Jurnal", 'abstract' => "Studi parameter fisika kimia air."],
            ['id' => 2, 'title' => "Sistem Monitoring Suhu IoT", 'author' => "Siti Aminah", 'year' => 2024, 'type' => "Prosiding", 'abstract' => "Implementasi ESP32 untuk lab."]
        ],
        'gallery' => [
            ['id' => 1, 'title' => "Penelitian Lab", 'url' => "https://images.unsplash.com/photo-1581093458791-9f3c3900df4b?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"],
            ['id' => 2, 'title' => "Diskusi Tim", 'url' => "https://images.unsplash.com/photo-1517048676732-d65bc937f952?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"]
        ]
    ];
    file_put_contents($dataFile, json_encode($defaultData, JSON_PRETTY_PRINT));
}

// 2. Buat folder uploads jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// 3. Routing API
$action = isset($_GET['action']) ? $_GET['action'] : '';

// API: Ambil Data
if ($action === 'get_data') {
    $currentData = json_decode(file_get_contents($dataFile), true);
    $response = $currentData;
    $response['is_logged_in'] = isset($_SESSION['admin_logged_in']);
    echo json_encode($response);
    exit;
}

// API: Login
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input['username'] === 'admin' && $input['password'] === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Login gagal']);
    }
    exit;
}

// API: Logout
if ($action === 'logout') {
    session_destroy();
    echo json_encode(['status' => 'success']);
    exit;
}

// API: Upload Gambar
if ($action === 'upload_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['admin_logged_in'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['image']['tmp_name'];
        $name = basename($_FILES['image']['name']);
        $newName = time() . '_' . $name; 
        $targetPath = $uploadDir . $newName;

        $check = getimagesize($tmpName);
        if ($check !== false) {
            if (move_uploaded_file($tmpName, $targetPath)) {
                // Return path relatif agar bisa diakses oleh HTML
                $webPath = 'uploads/' . $newName;
                echo json_encode(['status' => 'success', 'url' => $webPath]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Gagal memindahkan file']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'File bukan gambar']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Tidak ada file diupload']);
    }
    exit;
}

// API: Simpan Data JSON
if ($action === 'save_data' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['admin_logged_in'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
    $currentData = json_decode(file_get_contents($dataFile), true);
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input) {
        $newData = [
            'publications' => $input['publications'] ?? $currentData['publications'],
            'gallery' => $input['gallery'] ?? $currentData['gallery']
        ];
        file_put_contents($dataFile, json_encode($newData, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success']);
    }
    exit;
}
?>