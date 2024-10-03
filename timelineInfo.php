<?php
include_once 'mysqlConnect.php';
include_once 'errorMsgs.php';

$response = [
    "whisperList"    => [], 
    "mywhisperList"    => [], 
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    handleError("001", "データベース処理が異常終了しました", $response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postData = json_decode(file_get_contents('php://input'), true);

    if (empty($postData['userId'])) {
        handleError("006", "ユーザIDが指定されていません", $response);
        exit; 
    }

    $userId = $postData["userId"];
    try {
        // 	★ユーザがフォローしているユーザのささやき情報																
        $sql = "
            select 
                w.whisperNo,
                COALESCE(uf.userId,0) as userId ,
                COALESCE(uf.userName,0) as userName,
                COALESCE(w.postDate,0) as postDate,
                COALESCE(w.content,0) as content,
            if(g.whisperNo is not null,true,false) as goodFlg 
            from user as u 
            left join follow as f on f.userId = u.userId 
            left join user as uf on uf.userId = f.followUserId 
            left join whisper as w on w.userId = uf.userId 
            left join goodinfo as g on g.whisperNo = w.whisperNo and g.userId = u.userId
            where w.whisperNo IS NOT NULL AND u.userId = :userId
            ORDER BY w.postDate DESC;
            ";
        $stm = $pdo->prepare($sql); 
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["whisperList"]= $result; 

         //★自ユーザのささやき情報								
         $sql = "
            SELECT 
                COALESCE(w.whisperNo,0) as whisperNo,
                COALESCE(g.userId,0) as userId,
                COALESCE(ug.userName,0) as userName,
                COALESCE(w.postDate,0) as postDate,
                COALESCE(w.content,0) as content,
                CASE WHEN g.userId IS NOT NULL THEN TRUE ELSE FALSE END AS goodFlg
            FROM user AS u
            LEFT JOIN whisper AS w ON u.userId = w.userId
            LEFT JOIN goodinfo AS g ON g.whisperNo = w.whisperNo
            left join goodinfo as gflg on gflg.whisperNO = w.whisperNO and gflg.userId = g.userId
            left join user as ug on ug.userId = g.userId
            where u.userId = :userId
            ORDER BY w.postDate DESC
            ;
        ";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["mywhisperList"]= $result;	
        
    } catch (PDOException $e) {
        handleError("001", "データベース処理中にエラーが発生しました: " . $e->getMessage(), $response);
    }

    $pdo = null; 

    header('Content-Type: application/json'); 
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
}
?>