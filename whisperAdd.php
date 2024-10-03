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

    if (empty($postData['content'])) {
        handleError("005", "ささやき内容がありません ", $response);
    }
    
    $userId = $postData["userId"];
    $content = $postData["content"];
    
    try {
        $pdo->beginTransaction(); 
    
        
        $sql = "SELECT * FROM whisper WHERE userId = :userId AND content = :content";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->bindParam(":content", $content, PDO::PARAM_STR);
        $stm->execute();
    
        if ($stm->rowCount() === 0) {

            $sql = "INSERT INTO whisper (userId, content) VALUES (:userId, :content)";
            $stm = $pdo->prepare($sql);
            $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
            $stm->bindParam(":content", $content, PDO::PARAM_STR);
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