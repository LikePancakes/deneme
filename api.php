<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$host = 'localhost';
$dbname = 'dert_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

switch ($action) {
    case 'get_dertler':
        try {
            // Dertleri ve yorumlarını getir
            $stmt = $pdo->query("SELECT * FROM dertler ORDER BY tarih DESC");
            $dertler = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($dertler as &$dert) {
                $stmt = $pdo->prepare("SELECT * FROM yorumlar WHERE dert_id = ? ORDER BY tarih");
                $stmt->execute([$dert['id']]);
                $dert['yorumlar'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            echo json_encode($dertler);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error fetching data']);
        }
        break;
        
    case 'add_dert':
        $data = json_decode(file_get_contents('php://input'), true);
        $baslik = $data['baslik'] ?? '';
        $icerik = $data['icerik'] ?? '';
        
        if (empty($baslik) || empty($icerik)) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO dertler (baslik, icerik, tarih) VALUES (?, ?, NOW())");
            $stmt->execute([$baslik, $icerik]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding dert']);
        }
        break;
        
    case 'add_yorum':
        $data = json_decode(file_get_contents('php://input'), true);
        $dert_id = $data['dert_id'] ?? 0;
        $yorum = $data['yorum'] ?? '';
        
        if (empty($dert_id) || empty($yorum)) {
            echo json_encode(['success' => false, 'message' => 'Missing fields']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO yorumlar (dert_id, yorum, tarih) VALUES (?, ?, NOW())");
            $stmt->execute([$dert_id, $yorum]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error adding comment']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
