<?php
require_once __DIR__ . '/../p_manageDB.php';

cleanupExpiredRecords();
echo "Expired records cleaned.\n";