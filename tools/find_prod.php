<?php
declare(strict_types=1);

$root = realpath(__DIR__ . '/..'); // tools/ داخل المشروع
if (!$root) {
    fwrite(STDERR, "Cannot resolve project root.\n");
    exit(1);
}

$excludeDirs = [
    $root . DIRECTORY_SEPARATOR . 'vendor',
    $root . DIRECTORY_SEPARATOR . 'storage',
    $root . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache',
    $root . DIRECTORY_SEPARATOR . 'node_modules',
];

$pattern = '/\$(prod)\b/'; // $prod فقط

$rii = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);

$hits = 0;

foreach ($rii as $file) {
    /** @var SplFileInfo $file */
    if (!$file->isFile()) continue;

    $path = $file->getPathname();
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') continue;

    // استبعاد المسارات
    foreach ($excludeDirs as $ex) {
        if (str_starts_with($path, $ex . DIRECTORY_SEPARATOR) || $path === $ex) {
            continue 2;
        }
    }

    $lines = @file($path);
    if ($lines === false) continue;

    foreach ($lines as $i => $line) {
        if (preg_match($pattern, $line)) {
            $hits++;
            $ln = $i + 1;
            $snippet = rtrim($line);
            echo $path . ":" . $ln . " | " . $snippet . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Total hits: {$hits}" . PHP_EOL;
