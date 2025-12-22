#!/usr/bin/env node
/**
 * CSS Purge Script
 *
 * Analyzes and removes unused CSS from muaadh-system.css
 * Run: npm run purge:css
 *
 * Options:
 *   --dry-run    Show what would be removed without changing files
 *   --analyze    Show detailed size analysis
 */

const { PurgeCSS } = require('purgecss');
const fs = require('fs');
const path = require('path');

const COLORS = {
    reset: '\x1b[0m',
    red: '\x1b[31m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    blue: '\x1b[34m',
    cyan: '\x1b[36m',
    magenta: '\x1b[35m'
};

function log(color, message) {
    console.log(`${COLORS[color]}${message}${COLORS.reset}`);
}

function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

async function main() {
    const args = process.argv.slice(2);
    const dryRun = args.includes('--dry-run');
    const analyze = args.includes('--analyze');

    log('cyan', '\n========================================');
    log('cyan', '  CSS Purge Tool');
    log('cyan', '========================================\n');

    if (dryRun) {
        log('yellow', 'DRY RUN MODE - No files will be modified\n');
    }

    const cssFile = 'public/assets/front/css/muaadh-system.css';
    const backupFile = cssFile + '.backup';

    // Check if file exists
    if (!fs.existsSync(cssFile)) {
        log('red', `Error: ${cssFile} not found`);
        process.exit(1);
    }

    // Get original size
    const originalContent = fs.readFileSync(cssFile, 'utf8');
    const originalSize = Buffer.byteLength(originalContent, 'utf8');

    log('blue', `Original file: ${cssFile}`);
    log('blue', `Original size: ${formatBytes(originalSize)}`);

    // Load config
    const config = require('../purgecss.config.cjs');

    try {
        // Run PurgeCSS
        log('cyan', '\nScanning content files...');

        const purgeCSSResult = await new PurgeCSS().purge({
            content: config.content,
            css: [{ raw: originalContent, extension: 'css' }],
            safelist: config.safelist,
            keyframes: config.keyframes,
            variables: config.variables,
            fontFace: config.fontFace
        });

        if (purgeCSSResult.length === 0) {
            log('red', 'Error: PurgeCSS returned no results');
            process.exit(1);
        }

        const purgedContent = purgeCSSResult[0].css;
        const purgedSize = Buffer.byteLength(purgedContent, 'utf8');
        const savedBytes = originalSize - purgedSize;
        const savedPercent = ((savedBytes / originalSize) * 100).toFixed(1);

        log('green', `\n✓ Purge complete!`);
        log('blue', `\nResults:`);
        console.log(`  Original:  ${formatBytes(originalSize)}`);
        console.log(`  Purged:    ${formatBytes(purgedSize)}`);
        console.log(`  Saved:     ${formatBytes(savedBytes)} (${savedPercent}%)`);

        if (analyze) {
            // Count selectors
            const originalSelectors = (originalContent.match(/\{/g) || []).length;
            const purgedSelectors = (purgedContent.match(/\{/g) || []).length;
            const removedSelectors = originalSelectors - purgedSelectors;

            log('magenta', `\nSelector Analysis:`);
            console.log(`  Original selectors:  ${originalSelectors}`);
            console.log(`  Purged selectors:    ${purgedSelectors}`);
            console.log(`  Removed selectors:   ${removedSelectors}`);
        }

        if (!dryRun) {
            // Create backup
            fs.writeFileSync(backupFile, originalContent);
            log('yellow', `\nBackup created: ${backupFile}`);

            // Write purged content
            fs.writeFileSync(cssFile, purgedContent);
            log('green', `Updated: ${cssFile}`);

            log('cyan', '\n========================================');
            log('green', '  CSS Purge: SUCCESS');
            log('cyan', '========================================\n');

            console.log('To restore backup:');
            console.log(`  copy "${backupFile}" "${cssFile}"\n`);
        } else {
            log('yellow', '\nDry run complete. No files were modified.');
            log('cyan', 'Run without --dry-run to apply changes.\n');
        }

    } catch (error) {
        log('red', `\nError: ${error.message}`);
        process.exit(1);
    }
}

main();
