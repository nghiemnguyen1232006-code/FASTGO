<?php

function logActivity(
    $conn,
    $userId,
    $userName,
    $role,
    $action,
    $description
){

    $stmt = $conn->prepare("
        INSERT INTO activity_logs(
            user_id,
            user_name,
            role,
            action,
            description
        )
        VALUES(?,?,?,?,?)
    ");

    $stmt->execute([
        $userId,
        $userName,
        $role,
        $action,
        $description
    ]);
}