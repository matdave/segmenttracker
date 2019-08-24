@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../segmentio/analytics-php/bin/analytics
php "%BIN_TARGET%" %*
