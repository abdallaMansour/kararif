# Runs PHPUnit tests for recent chat-related changes.
# - ChatChangesRegressionTest + CustomGameFlowTest: use RefreshDatabase (isolated DB OK for migrate:fresh).
# - ChatChangesRealAccountTest: NO RefreshDatabase — logs in existing `adventurers` only (no registration).
#   Requires the same DB as your app with:
#     moamen.hamed33322@gmail.com / 6789
#     moamen.hamed3334422@gmail.com / 1234
#
# Usage (from project root):
#   .\scripts\test-chat-changes.ps1
# Real-accounts only:
#   php artisan test tests/Feature/ChatChangesRealAccountTest.php
# Optional: .\scripts\test-chat-changes.ps1 --filter draw

param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Passthrough
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$tests = @(
    "tests/Feature/ChatChangesRegressionTest.php",
    "tests/Feature/CustomGameFlowTest.php",
    "tests/Feature/ChatChangesRealAccountTest.php"
)

& php artisan test @tests @Passthrough
