<?php
require __DIR__ . '/config.php';
if (VULN_DEBUG_ENDPOINTS) {
    phpinfo();
} else {
    http_response_code(404);
}
