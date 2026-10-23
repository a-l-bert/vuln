<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $xml_data = file_get_contents($_FILES['feed']['tmp_name']);
    $dom = new DOMDocument();
    
    if (VULN_XXE_IMPORT) {
        // VULNERABLE: Enabled entity loading
        $dom->loadXML($xml_data, LIBXML_NOENT | LIBXML_DTDLOAD);
    } else {
        // SECURE: Disable DTDs and entity expansion
        $dom->loadXML($xml_data, LIBXML_NONET | LIBXML_DTDLOAD | LIBXML_DTDATTR);
        // Better: libxml_disable_entity_loader(true);
    }
    echo "Import processed.";
}
?>
<h3>Import Product Feed (XML)</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="feed">
    <button type="submit">Import</button>
</form>
