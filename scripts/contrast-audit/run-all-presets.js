/**
 * Run WCAG Contrast Audit on All Theme Presets
 *
 * This script:
 * 1. Applies each preset using php artisan theme:apply
 * 2. Regenerates theme-colors.css
 * 3. Runs the contrast audit with screenshots
 * 4. Generates a combined report
 *
 * Usage:
 *   node run-all-presets.js
 *   node run-all-presets.js --url=http://new.test
 */

const { execSync, spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const PRESETS = [
    'nissan',   // Red - Classic auto parts
    'blue',     // Royal Blue - Tech/modern
    'green',    // Emerald - Nature/eco
    'purple',   // Elegant Purple - Creative
    'orange',   // Sunset Orange - Energy
    'gold'      // Gold Luxury - Premium
];

const args = process.argv.slice(2);
const customUrl = args.find(a => a.startsWith('--url='))?.split('=')[1] || 'http://new.test';
const projectRoot = path.resolve(__dirname, '../..');
const reportDir = path.join(__dirname, 'reports');
const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);

// Ensure reports directory exists
if (!fs.existsSync(reportDir)) {
    fs.mkdirSync(reportDir, { recursive: true });
}

console.log('='.repeat(70));
console.log(' WCAG CONTRAST AUDIT - ALL PRESETS');
console.log('='.repeat(70));
console.log(`Project Root: ${projectRoot}`);
console.log(`Base URL: ${customUrl}`);
console.log(`Presets: ${PRESETS.join(', ')}`);
console.log('');

const results = [];
let hasFailures = false;

// First, generate pages.json from routes
console.log('Step 1: Generating pages.json from routes...');
try {
    execSync('php artisan audit:generate-pages --base-url=' + customUrl, {
        cwd: projectRoot,
        stdio: 'inherit'
    });
    console.log('');
} catch (e) {
    console.log('WARNING: Could not generate pages - using existing pages.json');
}

// Run audit for each preset
for (const preset of PRESETS) {
    console.log('='.repeat(70));
    console.log(` PRESET: ${preset.toUpperCase()}`);
    console.log('='.repeat(70));

    // Step 1: Apply preset
    console.log(`\n[1/3] Applying preset: ${preset}...`);
    try {
        execSync(`php artisan theme:apply ${preset} --generate-css`, {
            cwd: projectRoot,
            stdio: 'inherit'
        });
    } catch (e) {
        console.error(`Failed to apply preset: ${preset}`);
        results.push({
            preset,
            status: 'ERROR',
            error: 'Failed to apply preset'
        });
        continue;
    }

    // Step 2: Clear cache
    console.log(`\n[2/3] Clearing cache...`);
    try {
        execSync('php artisan cache:clear && php artisan view:clear', {
            cwd: projectRoot,
            stdio: 'pipe'
        });
    } catch (e) {
        // Ignore cache clear errors
    }

    // Step 3: Run audit
    console.log(`\n[3/3] Running contrast audit...`);
    try {
        const auditResult = spawnSync('node', [
            'audit.js',
            `--theme=${preset}`,
            `--url=${customUrl}`,
            '--screenshot'
        ], {
            cwd: __dirname,
            stdio: 'pipe',
            encoding: 'utf8'
        });

        console.log(auditResult.stdout);
        if (auditResult.stderr) console.error(auditResult.stderr);

        // Parse results from JSON file
        const jsonFiles = fs.readdirSync(reportDir)
            .filter(f => f.startsWith(`contrast-audit-${preset}-`) && f.endsWith('.json'))
            .sort()
            .reverse();

        if (jsonFiles.length > 0) {
            const latestReport = JSON.parse(fs.readFileSync(path.join(reportDir, jsonFiles[0]), 'utf8'));
            results.push({
                preset,
                status: latestReport.summary.wcagAA.fail === 0 ? 'PASS' : 'FAIL',
                total: latestReport.summary.total,
                passRate: latestReport.summary.wcagAA.rate,
                failures: latestReport.summary.wcagAA.fail,
                topViolations: latestReport.summary.topViolations.slice(0, 5)
            });

            if (latestReport.summary.wcagAA.fail > 0) {
                hasFailures = true;
            }
        } else {
            results.push({
                preset,
                status: 'ERROR',
                error: 'No report generated'
            });
        }

    } catch (e) {
        console.error(`Audit failed for ${preset}: ${e.message}`);
        results.push({
            preset,
            status: 'ERROR',
            error: e.message
        });
    }

    console.log('');
}

// Generate combined report
console.log('='.repeat(70));
console.log(' COMBINED REPORT');
console.log('='.repeat(70));
console.log('');

console.log('Preset Results:');
console.log('-'.repeat(60));
console.log('Preset'.padEnd(15) + 'Status'.padEnd(10) + 'Pass Rate'.padEnd(15) + 'Failures');
console.log('-'.repeat(60));

results.forEach(r => {
    const status = r.status === 'PASS' ? '\x1b[32mPASS\x1b[0m' : r.status === 'FAIL' ? '\x1b[31mFAIL\x1b[0m' : '\x1b[33mERROR\x1b[0m';
    console.log(
        r.preset.padEnd(15) +
        r.status.padEnd(10) +
        (r.passRate || 'N/A').toString().padEnd(15) +
        (r.failures ?? r.error ?? 'N/A')
    );
});

console.log('-'.repeat(60));
console.log('');

// Save combined report
const combinedReport = {
    timestamp,
    baseUrl: customUrl,
    presets: results,
    summary: {
        total: PRESETS.length,
        passed: results.filter(r => r.status === 'PASS').length,
        failed: results.filter(r => r.status === 'FAIL').length,
        errors: results.filter(r => r.status === 'ERROR').length
    }
};

const combinedPath = path.join(reportDir, `combined-report-${timestamp}.json`);
fs.writeFileSync(combinedPath, JSON.stringify(combinedReport, null, 2));

console.log(`Combined report saved to: ${combinedPath}`);
console.log('');

// Print violations summary
const presetsWithViolations = results.filter(r => r.topViolations && r.topViolations.length > 0);
if (presetsWithViolations.length > 0) {
    console.log('Top Violations by Preset:');
    console.log('-'.repeat(60));

    presetsWithViolations.forEach(r => {
        console.log(`\n${r.preset.toUpperCase()}:`);
        r.topViolations.forEach((v, i) => {
            console.log(`  ${i + 1}. [${v.page}] ${v.type}:${v.state}`);
            console.log(`     Ratio: ${v.ratio} (need ${v.required})`);
            console.log(`     "${v.text}"`);
        });
    });
}

console.log('');
console.log('='.repeat(70));

if (hasFailures) {
    console.log('\x1b[31m[FAIL] Some presets have WCAG violations!\x1b[0m');
    console.log('Run individual fixes and re-test each preset.');
    process.exit(1);
} else if (results.every(r => r.status === 'PASS')) {
    console.log('\x1b[32m[SUCCESS] All presets pass WCAG AA requirements!\x1b[0m');
    process.exit(0);
} else {
    console.log('\x1b[33m[WARNING] Some presets encountered errors.\x1b[0m');
    process.exit(1);
}
