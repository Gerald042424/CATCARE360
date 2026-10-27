<?php

declare(strict_types=1);

// Vercel's PHP runtime invokes files in api/. Delegate every dynamic request
// to CodeIgniter's normal front controller while keeping the original project
// layout (app/, public/, writable/, and vendor/) intact.
require dirname(__DIR__) . '/public/index.php';
