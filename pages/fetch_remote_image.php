<?php
if (VULN_SSRF) {
    $url = $_POST['url'];
    echo file_get_contents($url);
} else {
    echo "SSRF protection enabled.";
}
