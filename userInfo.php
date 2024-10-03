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

    $userId = $postData["userId"];
    
    try {

        $sql = "SELECT * FROM user WHERE userId = :userId";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);    
        $stm->execute();

         while ($row = $stm->fetch()) {
            if($row === false){
                handleError("004", "対象データが見つかりませんでした ", $response);
            }
            $data["userId"] = $row["userId"];
            $data["profile"] = $row["profile"];
            $data["iconPath"] = $row["iconPath"];
            $response['list'][] = $data;
        }
        $response["result"] = "success";
       
        

    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
    $pdo = null;
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>