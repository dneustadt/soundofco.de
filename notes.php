<?php

require_once __DIR__ . '/Classes/GitHubApi.php';
require_once __DIR__ . '/Classes/WaveformBuilder.php';

header('Content-Type: application/json');

$owner = @$_POST['owner'];
$repo = @$_POST['repo'];
$branch = @$_POST['branch'];
$path = @$_POST['path'];

$api = new \Classes\GitHubApi($owner, $repo);

if (!empty($path)) {
    try {
        $content = $api->getContentByPath($path, $branch);

        $waveformBuilder = new \Classes\WaveformBuilder($content);
        $lines = $waveformBuilder->contentLinesToArray();
        $bars = $waveformBuilder->convertLinesToBars($lines);

        echo json_encode([
            'success' => true,
            'lines' => $lines,
            'notes' => $bars,
        ]);
    }
    catch (Exception $exception) {
        echo json_encode([
            'success' => false,
            'message' => $exception->getMessage(),
        ]);
    }
} else {
    try {
        $paths = $api->getPaths();

        echo json_encode([
            'success' => true,
            'branch' => $api->getBranch(),
            'tree' => $paths,
        ]);
    }
    catch (Exception $exception) {
        echo json_encode([
            'success' => false,
            'message' => $exception->getMessage(),
        ]);
    }
}