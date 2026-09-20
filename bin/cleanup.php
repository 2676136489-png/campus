<?php
require_once __DIR__ . '/../lib/manageDB.php';

cleanupExpiredRecords();
echo "Expired records cleaned.\n";