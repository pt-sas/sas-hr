<?php
function apiResponse(
    bool $status,
    string $message,
    array $data = [],
    array $errors = [],
    array $meta = []
) {
    $response = [
        'status' => $status,
        'message' => $message
    ];

    if (!empty($data)) $response['data'] = $data;
    if (!empty($errors)) $response['errors'] = $errors;
    if (!empty($meta)) $response['meta'] = $meta;

    return $response;
}
