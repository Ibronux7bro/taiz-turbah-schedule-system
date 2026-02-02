<?php
// Test script to debug the Flask endpoint
$data = ['phone' => '967772006329'];
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => http_build_query($data),
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents('http://127.0.0.1:5000/send-schedule', false, $context);

if ($result === FALSE) {
    echo "Failed to get response<br>";
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "<br>";
    }
} else {
    echo "Response: " . $result . "<br>";
}
?>
