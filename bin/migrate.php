<?php
require_once __DIR__ . '/../lib/manageDB.php';

runMigrations();
echo "Migrations applied.\n";