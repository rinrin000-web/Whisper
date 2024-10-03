<?php
include_once 'mysqlConnect.php';
include_once 'errorMsgs.php';

$response = [
    "list"    => [], 
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError("001", "データベース処理が異常終了しました", $response);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = json_decode(file_get_contents('php://input'), true);
    
    if (empty($postData['userId'])) {
        handleError("006", "ユーザIDが指定されていません ", $response);
    }

    if (empty($postData['userName'])) {
        handleError("011", "ユーザ名が指定されていません ", $response);
    }

    if (empty($postData['password'])) {
        handleError("007", "パスワードが指定されていません ", $response);
    }
    
    $userId = $postData["userId"];
    $userName = $postData["userName"];
    $password = $postData["password"];
    
    try {
        $pdo->beginTransaction(); 
    
        
        $sql = "SELECT * FROM user WHERE userId = :userId AND password = :password AND userName = :userName";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->bindParam(":userName", $userName, PDO::PARAM_STR);
        $stm->bindParam(":password", $password, PDO::PARAM_STR);
        $stm->execute();
    
        if ($stm->rowCount() === 0) {

            $sql = "INSERT INTO user (userId, userName, password) VALUES (:userId, :userName, :password)";
            $stm = $pdo->prepare($sql);
            $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
            $stm->bindParam(":userName", $userName, PDO::PARAM_STR);
            $stm->bindParam(":password", $password, PDO::PARAM_STR);
            $stm->execute();
        }
    
        $pdo->commit();
    
        $response["result"] = "success";
    
    } catch (PDOException $e) {
        $pdo->rollback(); 
        handleError("001", "データベース処理中にエラーが発生しました: " . $e->getMessage(), $response);
    }
    
    $pdo = null;
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>