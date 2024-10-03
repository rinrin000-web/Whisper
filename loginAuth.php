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

    if (empty($postData['password'])) {
        handleError("007", "パスワードが指定されていません ", $response);
    }

    $userId = $postData["userId"];
    $password = $postData["password"];
    
    try {

        $sql = "SELECT * FROM user WHERE userId = :userId AND password = :password";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->bindParam(":password", $password, PDO::PARAM_STR);
    
        $stm->execute();

        // $result = $stm->fetch(PDO::FETCH_ASSOC);

         while ($row = $stm->fetch()) {
            $data["userId"] = $row["userId"];
            $data["password"] = $row["password"];
            $response['list'][] = $data;
        }
        $response["result"] = "success";
       
        if(count($response['list']) !== 1){
            handleError("003", "ユーザIDまたはパスワードが違います",$response); // Trả về lỗi nếu xác thực thất bại
        }

    } catch (PDOException $e) {
        throw new PDOException($e->getMessage(), (int)$e->getCode());
    }
    $pdo = null;
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>