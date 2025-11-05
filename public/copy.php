<?php
session_start();

// CORS Headers
// header("Access-Control-Allow-Origin: https://poem.aallem.com");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json; charset=utf-8');


// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// Database Configuration
$servername = "localhost";
// $user = "u633387434_admin"; // ✅ غير هذا لاسم المستخدم الفعلي
// $pass = "Q1W2e#r@";
// $dbname = "u633387434_poems";
// $user = "admin"; // ✅ غير هذا لاسم المستخدم الفعلي
$user = "root"; // ✅ غير هذا لاسم المستخدم الفعلي
$pass = "";
$dbname = "poems";

$dsn = "mysql:host={$servername};dbname={$dbname}";

$conn = new mysqli($servername, $user, $pass, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode([
        'success' => false,
        'error' => 'فشل الاتصال بقاعدة البيانات',
        'details' => $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");


$option = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

function isAdmin() {
    return isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true;
}

try {
    $con = new PDO($dsn, $user, $pass, $option);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($_GET['action'] === 'login') {
// ====== Lgoin view functionality ======

        $password = $_POST['password'] ?? '';

        $stmt = $con->prepare("SELECT password FROM admin WHERE ID = 1");
        $stmt->execute();
        $hashedPasswordFromDB = $stmt->fetchColumn();

        if (password_verify($password, $hashedPasswordFromDB)) {
            $_SESSION['admin_authenticated'] = true;
            echo json_encode(['success' => true]);

        } else {
            echo json_encode(['success' => false, 'message' => 'كلمة مرور خاطئة']);
        }
        exit;
    } else if ($_GET['action'] === 'check') {
        echo json_encode([
            'authenticated' => isset($_SESSION['admin_authenticated']) && $_SESSION['admin_authenticated'] === true
        ]);
        exit; 
    } else if ($_GET['action'] === 'logout') {
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    } else if (isset($_GET['action']) && $_GET['action'] === 'getAllPoems') {
// ====== main functionality ======

        if (isset($_GET['s']) && isset($_GET['id']) && $_GET['id'] == 1) {
            $stmt = $con->prepare("SELECT content, ID FROM mainTable WHERE status = :status");
            $stmt->bindValue(':status', $_GET['s'], PDO::PARAM_INT);
            $stmt->execute();
            $poems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $con->prepare("SELECT content FROM mainTable WHERE status = 1");
            $stmt->execute();
            $poems = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['poems' => $poems], JSON_UNESCAPED_UNICODE);
        exit;
    } else if (isset($_GET['action']) && $_GET['action'] === 'checkOneLine' && isset($_GET['line'])) {
        $line = $_GET['line'];
        $stmt = $con->prepare("SELECT COUNT(*) FROM mainTable WHERE content LIKE :line AND status = 1");
        $stmt->bindValue(':line', '%' . $line . '%', PDO::PARAM_STR);
        $stmt->execute();
        $count = $stmt->fetchColumn();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exists' => $count > 0]);
        exit;
    } else if (isset($_GET['action']) && $_GET['action'] === 'approve' && isset($_GET['line'])) {
        if (isset($_GET['type']) && $_GET['type'] === 'admin') {
            if (!isAdmin()) {
                http_response_code(401);
                echo json_encode(['error' => 'غير مصرح', 'authenticated' => false]);
                exit;
            } else {
                $line = $_GET['line'];
                $stmt = $con->prepare("INSERT INTO mainTable (content, status) VALUES (:line, 1)");
                $stmt->bindValue(':line', $line, PDO::PARAM_STR);
                $stmt->execute();
    
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
            }
        } else {
            $line = $_GET['line'];
            $stmt = $con->prepare("INSERT INTO mainTable (content) VALUES (:line)");
            $stmt->bindValue(':line', $line, PDO::PARAM_STR);
            $stmt->execute();

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true]);
        }

        exit;
    } else if (isset($_POST['action']) && $_POST['action'] === 'reportError') {
        $type = $_POST['type'] ?? '';
        $content = $_POST['content'] ?? '';

        $stmt = $con->prepare("SELECT ID FROM mainTable Where content = :content");
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        $stmt->execute();
        $existingId = $stmt->fetchColumn();

        $stmt = $con->prepare("INSERT INTO errors (type, content) VALUES (:type, :content)");
        $stmt->bindValue(':type', $type, PDO::PARAM_STR);
        $stmt->bindValue(':content', $existingId, PDO::PARAM_INT);
        $stmt->execute();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;

    } else if ($_GET['action'] === 'approveAdded') {
// ====== added view functionality ======
        if (!isAdmin()) {
            http_response_code(401);
            echo json_encode(['error' => 'غير مصرح', 'authenticated' => false]);
            exit;
        } 
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $content = $_GET['content'] ?? null; // ✅ استقبال المحتوى المعدل
            
            if ($content) {
                // ✅ تحديث المحتوى + تغيير الحالة إلى 1
                $stmt = $con->prepare("UPDATE mainTable SET status = 1, content = :content WHERE ID = :id");
                $stmt->bindValue(':content', $content, PDO::PARAM_STR);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            } else {
                // تغيير الحالة فقط (بدون تعديل المحتوى)
                $stmt = $con->prepare("UPDATE mainTable SET status = 1 WHERE ID = :id");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'missing ID parameter']);
        }
        exit;

    } else if ($_GET['action'] === 'deleteAdded') {
        if (!isAdmin()) {
            http_response_code(401);
            echo json_encode(['error' => 'غير مصرح', 'authenticated' => false]);
            exit;
        } else {
            if (isset($_GET['id'])) {
                $stmt = $con->prepare("DELETE FROM mainTable WHERE ID = :id");
                $stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);
                $stmt->execute();

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            } else {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'missing parameters.']);
                exit;
            }
        }
    } else if ($_GET['action'] === 'getReportedLines') {
// ====== Report view functionality ======
        if (!isAdmin()) {
            http_response_code(401);
            echo json_encode(['error' => 'غير مصرح', 'authenticated' => false]);
            exit;
        } 

        if (!isset($_GET['type'])) {
            echo json_encode(['success' => false, 'error' => 'missing type parameter']);
            exit;
        }

        $type = $_GET['type'];
        try {
            switch ($type) {
                case 'Dublicated':
                    // جلب جميع البلاغات عن التكرار
                    $stmt = $con->prepare("
                        SELECT m.content, e.errors as error_id, m.ID as poem_id
                        FROM errors e
                        LEFT JOIN mainTable m ON e.content = m.ID
                        WHERE e.type = 'duplicate'
                    ");
                    $stmt->execute();
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'reportedLines' => $data, 'total' => count($data)], JSON_UNESCAPED_UNICODE);
                    break;
                    
                case 'Syntax':
                    // جلب جميع البلاغات عن الأخطاء اللغوية
                    $stmt = $con->prepare("
                        SELECT m.content, e.errors as error_id, m.ID as poem_id
                        FROM errors e
                        LEFT JOIN mainTable m ON e.content = m.ID
                        WHERE e.type = 'syntax'
                    ");
                    $stmt->execute();
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'reportedLines' => $data, 'total' => count($data)], JSON_UNESCAPED_UNICODE);
                    break;
                    
                default:
                    // نوع غير صالح
                    echo json_encode(['success' => false, 'error' => 'Invalid type parameter: ' . $type]);
                    break;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;


    } else if ($_GET['action'] === 'ReportActions') {
        if (!isAdmin()) {
            http_response_code(401);
            echo json_encode(['error' => 'غير مصرح', 'authenticated' => false]);
            exit;
        } 
        
        if (!isset($_GET['subAction']) OR !isset($_GET['id'])) {
            echo json_encode(['success' => false, 'error' => 'missing type parameter']);
            exit;
        }
        
        $type = $_GET['subAction'];
        $id = $_GET['id'];

        try {
            switch ($type) {
                case 'Ignore':
                    $stmt = $con->prepare("DELETE FROM errors WHERE errors = :id");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $count = $stmt->rowCount(); // ✅ استخدام rowCount بدلاً من fetchColumn
                    echo json_encode(['success' => true, 'total' => (int)$count]);
                    break;
                    
                case 'delete':
                    $con->beginTransaction();
                    
                    

                    $stmt = $con->prepare("DELETE FROM mainTable WHERE ID = (SELECT content FROM errors WHERE errors = :id)");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $deletedPoems = $stmt->rowCount();
                    
                                            $stmt = $con->prepare("DELETE FROM errors WHERE errors = :id");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $deletedErrors = $stmt->rowCount();
                    
                    $con->commit();
                    echo json_encode([
                        'success' => true, 
                        'total' => (int)$deletedPoems,
                        'deletedErrors' => (int)$deletedErrors
                    ]);
                    break;
                    
                case 'check':
                    if (!isset($_GET['content'])) {
                        echo json_encode(['success' => false, 'error' => 'missing content parameter']);
                        exit;
                    }
                    
                    $content = $_GET['content'];
                    
                    $stmt = $con->prepare("SELECT COUNT(*) FROM mainTable WHERE content LIKE :line AND status = 1");
                    $stmt->bindValue(':line', '%' . $content . '%', PDO::PARAM_STR);
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    
                    echo json_encode(['exists' => $count > 1]);
                    break;
                    
                case 'reload':
                    if (!isset($_GET['content'])) {
                        echo json_encode(['success' => false, 'error' => 'missing content parameter']);
                        exit;
                    }
                    
                    $content = $_GET['content'];
                    
                    $con->beginTransaction();
                    
                    // تحديث المحتوى
                    $stmt = $con->prepare("UPDATE mainTable SET content = :content WHERE ID = (SELECT content FROM errors WHERE errors = :id)");
                    $stmt->bindValue(':content', $content, PDO::PARAM_STR); // ✅ استخدام $content مع PARAM_STR
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $updatedRows = $stmt->rowCount();
                    
                                            $stmt = $con->prepare("DELETE FROM errors WHERE errors = :id");
                    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                    $stmt->execute();
                    $deletedRows = $stmt->rowCount();
                    
                    $con->commit();
                    echo json_encode([
                        'success' => true, 
                        'total' => (int)$updatedRows,
                        'deleted' => (int)$deletedRows
                    ]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'error' => 'Invalid type parameter: ' . $type]);
                    break;
            }
        } catch (PDOException $e) {
            // ✅ إضافة rollback في حالة الخطأ
            if ($con->inTransaction()) {
                $con->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;

    } else if ($_GET['action'] === 'staticesView') {
// ====== statices view functionality ======
        if (!isAdmin()) {
            http_response_code(401);
            echo json_encode(['error' => 'غير مصرح', 'authenticated' => false]);
            exit;
        } 
        
        if (!isset($_GET['type'])) {
            echo json_encode(['success' => false, 'error' => 'missing type parameter']);
            exit;
        }
        
        $type = $_GET['type'];

        try {
            switch ($type) {
                case 'totalPoems':
                    // إجمالي الأبيات
                    $stmt = $con->prepare("SELECT COUNT(*) FROM mainTable");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    echo json_encode(['success' => true, 'total' => (int)$count]);
                    break;
                    
                case 'totalApproved':
                    // الأبيات الموثقة (status = 1)
                    $stmt = $con->prepare("SELECT COUNT(*) FROM mainTable WHERE status = 1");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    echo json_encode(['success' => true, 'total' => (int)$count]);
                    break;
                    
                case 'totalReported':
                    // إجمالي البلاغات
                    $stmt = $con->prepare("SELECT COUNT(*) FROM errors");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    echo json_encode(['success' => true, 'total' => (int)$count]);
                    break;
                    
                case 'DuplicateReported':
                    // بلاغات التكرار
                    $stmt = $con->prepare("SELECT COUNT(*) FROM errors WHERE type = 'duplicate'");
                    $stmt->execute();
                    $count = $stmt->fetchColumn();
                    echo json_encode(['success' => true, 'total' => (int)$count]);
                    break;
                    
                default:
                    // نوع غير صالح
                    echo json_encode(['success' => false, 'error' => 'Invalid type parameter: ' . $type]);
                    break;
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
        

    } else { 
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Invalid action or missing parameters.']);
        exit;
    }

}
catch (PDOException $e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Failed to Connect: ' . $e->getMessage()]);
}
?>