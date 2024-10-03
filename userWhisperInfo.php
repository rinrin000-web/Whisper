<?php
include_once 'mysqlConnect.php';
include_once 'errorMsgs.php';

$response = [
    "profile" => [], 
    "userFollowFlg" => [], 
    "whisperList" => [],
    "goodList"    => [], 
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

    if (empty($postData['loginUserId'])) {
        handleError("015", "ログインユーザIDが指定されていません", $response);
    }

    $userId= $postData["userId"];
    $loginUserId = $postData["loginUserId"];
    
    try {
        $pdo->beginTransaction(); 

        // ３．ユーザ情報を取得するSQL文を実行する。			
        $sql = "
            SELECT 
                u.userId ,
                u.profile,
                COALESCE(f.cnt,0) AS followcnt,
                COALESCE(fw.cnt,0) AS followercnt 
            FROM user as u 
            LEFT JOIN followcntview AS f 
            ON u.userId = f.userId 
            LEFT JOIN followercntview AS fw 
            ON u.userId = fw.followUserId 
            WHERE u.userId = :loginUserId;
        ";
        
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":loginUserId", $loginUserId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["profile"]= $result;

        if(!$result){
            handleError("004", "対象データが見つかりませんでした ", $response);
        }

        // ６．フォロー中情報を取得するSQL文を実行する。			
        $sql = "SELECT * FROM follow WHERE userId = :loginUserId";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":loginUserId", $loginUserId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["userFollowFlg"]= $result;

        if(!$result){
            handleError("004", "対象データが見つかりませんでした ", $response);
        }

        // ９．ささやきリストを取得するSQL文を実行する。
        $sql = "
            SELECT 
                w.whisperNo,
                u.userId,
                u.userName,
                w.postDate,
                w.content,
                IF(g.whisperNo IS NOT NULL, TRUE, FALSE) AS goodFlg
            FROM 
                whisper AS w
            LEFT JOIN 
                user AS u ON w.userId = u.userId
            LEFT JOIN 
                goodinfo AS g ON w.whisperNo = g.whisperNo AND g.userId = :loginUserId
            WHERE 
                u.userId = :userId 
            ORDER BY 
                w.postDate DESC;
        ";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->bindParam(":loginUserId", $loginUserId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["whisperList"]= $result;	

        // １２．イイねリストを取得するSQL文を実行する。									
        $sql = "
            SELECT 
                w.whisperNo,
                u.userId,
                u.userName,
                w.postDate,
                w.content,
                IF(g.whisperNo IS NOT NULL, TRUE, FALSE) AS goodFlg
            FROM 
                whisper AS w
            LEFT JOIN 
                user AS u ON w.userId = u.userId
            LEFT JOIN 
                goodinfo AS g ON w.whisperNo = g.whisperNo AND g.userId = :loginUserId
            WHERE 
                u.userId = :userId 
            ORDER BY 
                w.postDate DESC;
        ";
        $stm = $pdo->prepare($sql);
        $stm->bindParam(":userId", $userId, PDO::PARAM_STR);
        $stm->bindParam(":loginUserId", $loginUserId, PDO::PARAM_STR);
        $stm->execute();
        $result = $stm->fetchAll();
        $response["goodList"]= $result;	
        
        

    } catch (PDOException $e) {
        handleError("001", "データベース処理中にエラーが発生しました: " . $e->getMessage(), $response);    
    }
    $pdo = null;
    
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>