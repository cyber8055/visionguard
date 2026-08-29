<?php
/**
 * VisionGuard Background CRON Automation
 * 
 * This script runs automated data purging based on the policies defined
 * in data/retention_policy.json.
 * 
 * Usage: Execute via server cron job (e.g., `0 0 * * * php /path/to/scripts/cron_runner.php`)
 */

$dataDir = __DIR__ . '/../data';
$policyFile = $dataDir . '/retention_policy.json';

if (!file_exists($policyFile)) {
    echo "ERROR: Retention policy not found.\n";
    exit(1);
}

$policy = json_decode(file_get_contents($policyFile), true);

if (empty($policy['auto_purge_enabled'])) {
    echo "INFO: Auto-purge is disabled in the retention policy. Exiting.\n";
    exit(0);
}

$incidentDays = (int)($policy['incident_reports_days'] ?? 365);
$authDays = (int)($policy['auth_logs_days'] ?? 90);

$currentTime = time();
$logsPurged = 0;
$incidentsPurged = 0;

// Process Auth Logs
$authFile = $dataDir . '/auth_logs.json';
if (file_exists($authFile)) {
    $authLogs = json_decode(file_get_contents($authFile), true) ?? [];
    $filteredAuthLogs = [];
    foreach ($authLogs as $log) {
        if (isset($log['timestamp'])) {
            $logTime = strtotime($log['timestamp']);
            if (($currentTime - $logTime) <= ($authDays * 86400)) {
                $filteredAuthLogs[] = $log;
            } else {
                $logsPurged++;
            }
        }
    }
    file_put_contents($authFile, json_encode($filteredAuthLogs, JSON_PRETTY_PRINT));
}

// Process Incident Reports
$incidentFile = $dataDir . '/incidents.json';
if (file_exists($incidentFile)) {
    $incidents = json_decode(file_get_contents($incidentFile), true) ?? [];
    $filteredIncidents = [];
    foreach ($incidents as $inc) {
        if (isset($inc['timestamp'])) {
            $incTime = strtotime($inc['timestamp']);
            if (($currentTime - $incTime) <= ($incidentDays * 86400)) {
                $filteredIncidents[] = $inc;
            } else {
                $incidentsPurged++;
            }
        }
    }
    file_put_contents($incidentFile, json_encode($filteredIncidents, JSON_PRETTY_PRINT));
}

$summary = sprintf(
    "[%s] CRON JOB COMPLETED. Purged %d auth logs (older than %d days) and %d incidents (older than %d days).\n",
    date('Y-m-d H:i:s'),
    $logsPurged,
    $authDays,
    $incidentsPurged,
    $incidentDays
);

echo $summary;

// Append to a cron log file
file_put_contents($dataDir . '/cron.log', $summary, FILE_APPEND);
