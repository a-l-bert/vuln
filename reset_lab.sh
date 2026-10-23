#!/bin/bash
# Reset script for the vulnerability lab

echo "Resetting lab environment..."

# 1. Reset database
rm -f /home/king/Vulnurable-apps/vulnapp-php/vulnapp.db
# Trigger database re-creation by hitting a page
curl -s http://localhost:8001/products.php > /dev/null

# 2. Clear uploads
rm -rf /home/king/Vulnurable-apps/vulnapp-php/uploads/*

# 3. Reset application log
echo "" > /home/king/Vulnurable-apps/vulnapp-php/app.log

echo "Lab reset complete."
