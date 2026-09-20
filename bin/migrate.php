<?php
require_once __DIR__ . '/../p_manageDB.php';

runMigrations();
echo "Migrations applied.\n";