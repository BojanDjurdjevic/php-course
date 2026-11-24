<?php

header('Content-Type: application/json; charset=utf-8');

$method = empty($_SERVER['REQUEST_METHOD']) ? null : $_SERVER['REQUEST_METHOD'];

$uri = empty($_SERVER['REQUEST_URI']) ? null : $_SERVER['REQUEST_URI'];

$queryString = empty($_SERVER['QUERY_STRING']) ? null : $_SERVER['QUERY_STRING'];

$contentType = empty($_SERVER['CONTENT_TYPE']) ? null : $_SERVER['CONTENT_TYPE'];

$userAgent = empty($_SERVER['HTTP_USER_AGENT']) ? null : $_SERVER['HTTP_USER_AGENT'];

$ip = empty($_SERVER['REMOTE_ADDR']) ? null : $_SERVER['REMOTE_ADDR'];

$query = empty($_GET) ? null : $_GET;

echo json_encode([

    'method' => $method,

    'uri' => $uri,

    'query_string' => $queryString,

    'query' => $query,

    'content_type' => $contentType,

    'user_agent' => $userAgent,

    'ip' => $ip,

], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);