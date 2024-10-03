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

    if (empty($postData['whisperNo'])) {
        handleError("008", "ささやき管理番号が指定されていません ", $response);
    } 

    if (empty($postData['goodFlg'])) {
        handleError("014", "イイねフラグが指定されていません", $response);
    } 
    
    $userId = $postData["userId"];
    $whisperNo = $postData["whisperNo"];
    $goodFlg = ($postData["goodFlg"] === "true");
    
    try {
        $pdo->beginTransaction(); 

        $sql = "SELECT * FROM goodinfo WHERE whisperNo = :whisperNo AND userId = :userId";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->bindParam(":whisperNo", $whisperNo, PDO::PARAM_STR);
        $stm->execute();

        // if ($goodFlg) {
            if ($stm->rowCount() === 0) {
                $sql = "INSERT INTO goodinfo (userId, whisperNo) VALUES (:userId, :whisperNo)";
                $stm = $pdo->prepare($sql);
                $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
                $stm->bindParam(":whisperNo", $whisperNo, PDO::PARAM_STR);
                $stm->execute();
                $response["result"] = "add";
                $pdo->commit();
            }
        // } 
        else {
            if ($stm->rowCount() > 0) {
                $sql = "DELETE FROM goodinfo WHERE userId = :userId AND whisperNo = :whisperNo";
                $stm = $pdo->prepare($sql);
                $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
                $stm->bindParam(":whisperNo", $whisperNo, PDO::PARAM_STR);
                $stm->execute();

                $response["result"] = "delete";
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