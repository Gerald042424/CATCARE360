<?php

declare(strict_types=1);

// Vercel's PHP runtime invokes files in api/. Delegate every dynamic request
// to CodeIgniter's normal front controller while keeping the original project
// layout (app/, public/, writable/, and vendor/) intact.
// Keep previously generated /index.php/... bookmarks compatible while all new
// URLs are emitted without the front-controller segment.
if (isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = preg_replace('#^/index\.php(?=/|$)#', '', $_SERVER['REQUEST_URI']) ?: '/';
}

// Keep deployment health checks independent from database, session, mail, and
// object-storage credentials. This confirms that the PHP runtime itself booted.
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($requestPath === '/health/runtime') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status' => 'ok', 'service' => 'catcare360'], JSON_THROW_ON_ERROR);
    return;
}

require dirname(__DIR__) . '/public/index.php';
