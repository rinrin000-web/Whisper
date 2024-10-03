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

    if (empty($postData['section'])) {
        handleError("009", "検索区分が指定されていません ", $response);
    }

    if (empty($postData['string'])) {
        handleError("010", "検索文字列が指定されていません", $response);
    }

    $section = $postData["section"];
    $string = $postData["string"];
    
    try {
        $pdo->beginTransaction(); 

        if ($section < 1 || $section > 2) {
            handleError("016", "検索区分が不正です", $response); // Báo lỗi nếu section không hợp lệ
        }

        // ４．検索区分が１(ユーザ検索)の場合、以下の処理を行う。			
        if($section == 1){
            // $sql = "select * from user where userId LIKE :userId OR userName LIKE :userName";
            $stringLike = "%" . $string . "%";
            $sql = "
                SELECT 
                    u.userId,
                    u.userName,
                    COALESCE(w.cnt, 0) AS whisperCnt,
                    COALESCE(f.cnt, 0) AS followCnt,
                    COALESCE(fw.cnt, 0) AS followerCnt
                FROM 
                    user AS u
                LEFT JOIN 
                    whispercntview AS w 
                    ON u.userId = w.userId
                LEFT JOIN 
                    followcntview AS f 
                    ON u.userId = f.userId
                LEFT JOIN 
                    followercntview AS fw 
                    ON u.userId = fw.followUserId
                    WHERE u.userId LIKE :userId 
                    OR u.userName LIKE :userName 
            ";
            $stm = $pdo->prepare($sql);
            $stm->bindParam(":userId", $stringLike, PDO::PARAM_STR);
            $stm->bindParam(":userName", $stringLike, PDO::PARAM_STR);
            $stm->execute();

            while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                $response["list"][] = $row; 
            }

        // ５．検索区分が2(ささやき検索)の場合、以下の処理を行う。
        }elseif ($section == 2){
            $stringLike = "%" . $string . "%";
            $sql = "
                SELECT 
                    COALESCE(w.whisperNo,0) as whisperNo,
                    u.userId,
                    u.userName ,
                    COALESCE(w.postDate,0) as postDate,
                    COALESCE(w.content,0) as content,
                    COALESCE(g.cnt,0) as cnt  
                FROM user AS u 
                LEFT JOIN whisper AS w 
                ON u.userId = w.userId
                LEFT JOIN goodcntview AS g 
                ON w.whisperno = g.whisperNo
                WHERE w.content LIKE :string
            ";
            $stm = $pdo->prepare($sql);
            $stm->bindParam(":string", $stringLike, PDO::PARAM_STR);
            $stm->execute();

            while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
                $response["list"][] = $row; 
            }
        }
         
        $response["result"] = "success";

    } catch (PDOException $e) {
        handleError("001", "データベース処理中にエラーが発生しました: " . $e->getMessage(), $response);    
    }
    $pdo = null;
}

header('Content-Type: application/json');
echo json_encode($response, JSON_UNESCAPED_UNICODE);

?>