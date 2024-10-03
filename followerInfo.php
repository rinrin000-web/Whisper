<?php
include_once 'mysqlConnect.php';
include_once 'errorMsgs.php';

$response = [
    "followList" => [], 
    "followerList" => [], 
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError("001", "データベース処理が異常終了しました", $response);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = json_decode(file_get_contents('php://input'), true);
    // var_dump($postData); 

    if (empty($postData['userId'])) {
        handleError("006", "ユーザIDが指定されていません", $response);
    }

    $userId= $postData["userId"];
    
    try {
        $pdo->beginTransaction(); 

        // ３．フォローリストを取得するSQL文を実行する。												
        $sql = "
            SELECT 
                COALESCE(f.followUserID,0) AS userId ,
                COALESCE(uf.userName,0) AS userName ,
                COALESCE(fcnt.cnt,0) AS followcnt ,
                COALESCE(fwcnt.cnt,0) AS followercnt,
                COALESCE(w.cnt,0) AS whispercnt 
            FROM user AS u 
            LEFT JOIN follow AS f ON u.userId = f.userId 
            LEFT JOIN user AS uf ON f.followUserId = uf.userId 
            LEFT JOIN followcntview AS fcnt ON f.followUserId = fcnt.userId 
            LEFT JOIN followercntview AS fwcnt ON f.followUserId = fwcnt.followUserId
            LEFT JOIN whispercntview AS w ON f.followUserId = w.userId
            WHERE u.userId = :userId
        ";
        
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["followList"]= $result;

        
        // 	７－２．フォロワーリストの連想配列にデータを追加する。											
        $sql = "
            SELECT 
                COALESCE(u.userId,0) AS userId,
                COALESCE(uf.userName,0) AS followerUser ,
                COALESCE(fcnt.cnt,0) AS followcnt,
                COALESCE(fwcnt.cnt,0) AS followercnt,
                COALESCE(w.cnt,0) AS whispercnt 
            FROM follow AS f 
            LEFT JOIN user AS u ON f.userId = u.userId 
            LEFT JOIN user AS uf ON u.userId = uf.userId
            LEFT JOIN followcntview AS fcnt ON u.userId = fcnt.userId 
            LEFT JOIN followercntview AS fwcnt ON u.userId = fwcnt.followUserId 
            LEFT JOIN whispercntview AS w ON u.userId = w.userId
            WHERE f.followUserId = :userId
        ";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["followerList"]= $result;

    } catch (PDOException $e) {
        handleError("001", "データベース処理中にエラーが発生しました: " . $e->getMessage(), $response);    
    }
    $pdo = null;
    
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>