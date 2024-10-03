<?php
function handleError($errorCode, $errorMessage, $response) {
    $response["result"] = "error";
    $response["errCode"] = $errorCode;
    $response["errMsg"] = $errorMessage;
    // Trả về phản hồi JSON và thoát
    header('Content-Type: application/json');
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}
?>