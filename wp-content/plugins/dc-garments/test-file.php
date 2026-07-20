<?php
// Test file to verify file paths
echo "Plugin URL: " . DC_PM_PLUGIN_URL . "<br>";
echo "Plugin Directory: " . DC_PM_PLUGIN_DIR . "<br>";
echo "CSS File Path: " . DC_PM_PLUGIN_DIR . 'assets/css/product-management.css' . "<br>";
echo "CSS File Exists: " . (file_exists(DC_PM_PLUGIN_DIR . 'assets/css/product-management.css') ? 'Yes' : 'No') . "<br>";
echo "JS File Path: " . DC_PM_PLUGIN_DIR . 'assets/js/product-management.js' . "<br>";
echo "JS File Exists: " . (file_exists(DC_PM_PLUGIN_DIR . 'assets/js/product-management.js') ? 'Yes' : 'No') . "<br>";
echo "Template File Path: " . DC_PM_PLUGIN_DIR . 'admin/partials/product-management.php' . "<br>";
echo "Template File Exists: " . (file_exists(DC_PM_PLUGIN_DIR . 'admin/partials/product-management.php') ? 'Yes' : 'No') . "<br>";
?> 