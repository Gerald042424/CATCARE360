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

require dirname(__DIR__) . '/public/index.php';
