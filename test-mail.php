<?php

require_once 'includes/mailer.php';

$result = sendMail(
    'nghiemnguyen1232006@gmail.com',
    'FASTGO Test',
    '<h2>Xin chào FASTGO</h2><p>Gửi mail thành công.</p>'
);

echo $result ? 'OK' : 'FAIL';