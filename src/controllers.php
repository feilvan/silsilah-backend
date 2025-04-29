<?php
require_once __DIR__ . '/database.php';

function getUsers()
{
    $pdo = connectDatabase();

    if (isset($_GET['id'])) {
        $id = $_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Decode 'anak' field for each user
            if (!empty($user['anak'])) {
                $user['anak'] = json_decode($user['anak'], true); // Convert JSON string to array
            }
            echo json_encode($user);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "User not found"]);
        }
    } elseif (isset($_GET['nama'])) {
        $nama = $_GET['nama'];
        $stmt = $pdo->prepare("SELECT * FROM user WHERE nama LIKE ?");
        $stmt->execute(["%$nama%"]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($users) {
            // Decode 'anak' field for each user
            foreach ($users as &$u) {
                if (!empty($u['anak'])) {
                    $u['anak'] = json_decode($u['anak'], true); // Convert JSON string to array
                }
            }
            echo json_encode($users);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "No users found with the given name"]);
        }
    } else {
        $stmt = $pdo->query("SELECT * FROM user");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Decode 'anak' field for each user
        foreach ($users as &$user) {
            if (!empty($user['anak'])) {
                $user['anak'] = json_decode($user['anak'], true); // Convert JSON string to array
            }
        }
        echo json_encode($users);
    }
}

function getUserlist()
{
    $pdo = connectDatabase();
    $stmt = $pdo->query("SELECT id, nama, suami, istri, anak, 
                         (SELECT id FROM user WHERE nama = u.suami) AS suami_id, 
                         (SELECT id FROM user WHERE nama = u.istri) AS istri_id 
                         FROM user u");
    $userlist = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($userlist) {
        // Decode 'anak' field for each user
        foreach ($userlist as &$user) {
            if (!empty($user['anak'])) {
                $user['anak'] = json_decode($user['anak'], true); // Convert JSON string to array
            }
        }
        echo json_encode($userlist);
    } else {
        http_response_code(404);
        echo json_encode(["message" => "User not found"]);
    }
}

function createUser()
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['nama']) || !isset($data['jenisKelamin'])) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid input"]);
        return;
    }

    $createdAt = $updatedAt = (new DateTime('now', new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d H:i:s');

    $pdo = connectDatabase();
    $stmt = $pdo->prepare("INSERT INTO user (nama, jenisKelamin, anakKe, alamat, suami, istri, anak, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$data['nama'], $data['jenisKelamin'], $data['anakKe'], $data['alamat'], $data['suami'], $data['istri'], json_encode($data['anak']), $createdAt, $updatedAt]);

    http_response_code(201);
    echo json_encode(["message" => "User created"]);
}

function updateUser($id)
{
    $data = json_decode(file_get_contents("php://input"), true);

    $pdo = connectDatabase();
    $stmt = $pdo->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->execute([$id]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingUser) {
        http_response_code(404);
        echo json_encode(["message" => "User not found"]);
        return;
    }

    $updatedAt = (new DateTime('now', new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('Y-m-d H:i:s');

    $updatedData = [
        'nama' => $data['nama'] ?? $existingUser['nama'],
        'jenisKelamin' => $data['jenisKelamin'] ?? $existingUser['jenisKelamin'],
        'anakKe' => $data['anakKe'] ?? $existingUser['anakKe'],
        'alamat' => $data['alamat'] ?? $existingUser['alamat'],
        'suami' => $data['suami'] ?? $existingUser['suami'],
        'istri' => $data['istri'] ?? $existingUser['istri'],
        'anak' => isset($data['anak']) ? json_encode($data['anak']) : $existingUser['anak'],
        'updatedAt' => $updatedAt
    ];

    $stmt = $pdo->prepare("UPDATE user SET nama = ?, jenisKelamin = ?, anakKe = ?, alamat = ?, suami = ?, istri = ?, anak = ?, updatedAt = ? WHERE id = ?");
    $stmt->execute([
        $updatedData['nama'],
        $updatedData['jenisKelamin'],
        $updatedData['anakKe'],
        $updatedData['alamat'],
        $updatedData['suami'],
        $updatedData['istri'],
        $updatedData['anak'],
        $updatedData['updatedAt'],
        $id
    ]);

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode(["message" => "User updated"]);
    } else {
        http_response_code(400);
        echo json_encode(["message" => "No changes made"]);
    }
}