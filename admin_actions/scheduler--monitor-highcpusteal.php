<?php

// ---------------------------------------------------------
// CONFIGURATION
// ---------------------------------------------------------
$sampleCount     = 3;      // Number of mpstat samples to average
$sampleInterval  = 1;      // Seconds between samples
$warnThreshold   = 20.0;   // %steal that triggers a WARNING
$alertThreshold  = 50.0;   // %steal that triggers an ALERT
$minCpuUsage     = 10.0;   // If %idle > 10% AND steal high → real steal, not user CPU
// ---------------------------------------------------------

$stealValues = [];
$idleValues  = [];

for ($i = 0; $i < $sampleCount; $i++) {

    $cmd = "mpstat 1 1";
    $output = [];
    exec($cmd, $output);

    // Parse the line starting with "all"
    foreach ($output as $line) {
        if (preg_match('/\s+all\s+([\d\.]+)\s+[\d\.]+\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)\s+([\d\.]+)/', $line, $m)) {
            // $m[7] = %steal, $m[10] = %idle
            $stealValues[] = floatval($m[7]);
            $idleValues[]  = floatval($m[10]);
        }
    }

    sleep($sampleInterval);
}

if (count($stealValues) === 0) {
    echo "STEAL MONITOR ERROR: unable to read mpstat\n";
    exit(1);
}

$avgSteal = array_sum($stealValues) / count($stealValues);
$avgIdle  = array_sum($idleValues)  / count($idleValues);

// -----------------------------------------------
// LOGIC: Only alert if true steal, not real CPU usage
// -----------------------------------------------
if ($avgSteal >= $alertThreshold && $avgIdle > $minCpuUsage) {
    echo "HIGH STEAL ALERT: steal={$avgSteal}% idle={$avgIdle}%\n";
    exit(2);
}

if ($avgSteal >= $warnThreshold && $avgIdle > $minCpuUsage) {
    echo "HIGH STEAL WARNING: steal={$avgSteal}% idle={$avgIdle}%\n";
    exit(3);
}

// Normal condition
echo "STEAL NORMAL: steal={$avgSteal}% idle={$avgIdle}%\n";
exit(0);

?>
