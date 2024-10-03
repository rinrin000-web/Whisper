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
        // var_dump($postData); 
    if (empty($postData['userId'])) {
        handleError("006", "ユーザIDが指定されていません ", $response);
    }

    if (empty($postData['followUserId'])) {
        handleError("012", "フォロユーザIDが指定されていません", $response);
    } 

    if (empty($postData['followFlg'])) {
        handleError("013", "フォローフラグが指定されていません", $response);
    } 
    
    $userId = $postData["userId"];
    $followUserId = $postData["followUserId"];
    $followFlg = ($postData["followFlg"] === "true");
    
    try {
        $pdo->beginTransaction(); 

        $sql = "SELECT * FROM follow WHERE followUserId = :followUserId AND userId = :userId";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->bindParam(":followUserId", $followUserId, PDO::PARAM_STR);
        $stm->execute();

        if ($followFlg) {
            if ($stm->rowCount() === 0) {
                $sql = "INSERT INTO follow (userId,followUserId) VALUES (:userId, :followUserId)";
                $stm = $pdo->prepare($sql);
                $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
                $stm->bindParam(":followUserId", $followUserId, PDO::PARAM_STR);
                $stm->execute();
                $response["result"] = "success";
                $pdo->commit();
            }
        } 
        else {
            if ($stm->rowCount() > 0) {
                $sql = "DELETE FROM follow WHERE userId = :userId AND followUserId = :followUserId";
                $stm = $pdo->prepare($sql);
                $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
                $stm->bindParam(":followUserId", $followUserId, PDO::PARAM_STR);
                $stm->execute();

                $response["result"] = "success";
                $pdo->commit();
            }
        }

    } catch (PDOException $e) {
        $pdo->rollback(); 
        handleError("001", "データベース処理中にエラーが発生しました: " . $e->getMessage(), $response);
    }
    
    $pdo = null;
    
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>