<?php
// Script to replace old file/controller references

$directories = [
    __DIR__ . '/app',
    __DIR__ . '/routes',
    __DIR__ . '/resources/views',
];

$replacements = [
    // Controller references
    "Admin\\MessageController" => "Admin\\SupportTicketController",
    "'Admin\\MessageController" => "'Admin\\SupportTicketController",
    "User\\MessageController" => "User\\ChatController",
    "'User\\MessageController" => "'User\\ChatController",
    "Api\\User\\MessageController" => "Api\\User\\ChatController",
    "'Api\\User\\MessageController" => "'Api\\User\\ChatController",

    // View references - Admin
    "admin.message.index" => "admin.support-ticket.index",
    "admin.message.create" => "admin.support-ticket.create",
    "admin.message.dispute" => "admin.support-ticket.dispute",
    "'admin.message." => "'admin.support-ticket.",

    // View references - User
    "user.message.index" => "user.chat.index",
    "user.message.create" => "user.chat.create",
    "'user.message." => "'user.chat.",

    // View references - Load
    "load.message" => "load.support-message",

    // View references - Transactions
    "user.transactions" => "user.wallet-logs",
    "'user.transactions'" => "'user.wallet-logs'",
    "load.transaction-details" => "load.wallet-log-details",

    // View references - Page
    "frontend.page" => "frontend.static-content",
    "'frontend.page'" => "'frontend.static-content'",
];

$count = 0;

foreach ($directories as $directory) {
    if (!is_dir($directory)) continue;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'blade'])) {
            $content = file_get_contents($file->getPathname());
            $original = $content;

            foreach ($replacements as $old => $new) {
                $content = str_replace($old, $new, $content);
            }

            if ($content !== $original) {
                file_put_contents($file->getPathname(), $content);
                echo "Updated: " . $file->getPathname() . "\n";
                $count++;
            }
        }
    }
}

echo "\nTotal files updated: $count\n";
