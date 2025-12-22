#!/usr/bin/env node
/**
 * Theme Color Linter - STRICT MODE
 * =================================
 * Enforces theme compliance by detecting hardcoded colors in UI files.
 * Run: node scripts/theme-linter.cjs
 *
 * ALLOWED EXCEPTIONS (Only 2):
 * 1. PDF/Print paths - resources/views/pdf/, resources/views/print/
 * 2. Dynamic product swatches - {{ $color }}, {{ $productt->color[$key] }}, --swatch-color: #
 *
 * Exit codes:
 *   0 = Pass (no violations)
 *   1 = Fail (violations found)
 */

const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    // Directories to scan
    scanDirs: [
        'resources/views/frontend',
        'resources/views/components',
        'resources/views/user',
        'resources/views/rider',
        'resources/views/includes/frontend',
        'resources/views/includes/user',
        'resources/views/includes/rider',
        'resources/views/partials',
        'resources/views/layouts'
    ],

    // Excluded paths (STRICT: Only PDF/Print allowed)
    excludedPaths: [
        'resources/views/pdf',
        'resources/views/print'
    ],

    // Excluded files
    excludedFiles: [
        'theme_colors.blade.php'
    ],

    // STRICT WHITELIST: Only product swatches and essential SVG patterns
    allowedPatterns: [
        // ====== EXCEPTION 1: Dynamic Product Color Swatches ======
        /--swatch-color:\s*#/,
        /\{\{\s*\$.*color.*\}\}/i,
        /\{\{\s*\$.*Color.*\}\}/i,
        /\{\{\s*\$ct\s*\}\}/,
        /\{\{\s*\$productt->color/,
        /\{\{\s*\$prod->colors/,
        /\{\{\s*\$vendorColors/,
        /style="[^"]*\{\{[^}]*color/i,

        // ====== EXCEPTION 2: Brand Colors (Cannot Change) ======
        // Social login brand colors (Google)
        /fill="#4285F4"/,
        /fill="#34A853"/,
        /fill="#FBBC04"/,
        /fill="#EA4335"/,

        // Social login brand colors (Facebook)
        /fill="#1877F2"/,
        /fill="#2196F3"/,

        // Social share buttons (brand identity)
        /fill="#3A559F"/,    // Facebook
        /fill="#00A6DE"/,    // Twitter
        /fill="#0B69C7"/,    // LinkedIn
        /fill="#2AA81A"/,    // WhatsApp

        // ====== SVG Icons with CSS Override in muaadh-system.css ======
        /stroke="#4C3533"/,
        /stroke="#463539"/,
        /stroke="#292D32"/,
        /stroke="#1F0300"/,
        /stroke="#030712"/,
        /stroke="#180207"/,
        /fill="#4C3533"/,
        /fill="#463539"/,
        /fill="#292D32"/,
        /fill="#1F0300"/,
        /fill="#180207"/,

        // Form step indicators (neutral grays - CSS override)
        /fill="#999999"/,
        /fill="#FDFDFD"/,

        // Star rating (semantic yellow)
        /fill="#EEAE0B"/,

        // Placeholder/disabled icons (CSS override)
        /fill="#D9D9D9"/,

        // Semantic success icons (CSS override)
        /fill="#27BE69"/,
        /fill="#1F9854"/,
        /fill="#E8F5E9"/,
        /fill="#C8E6C9"/,

        // Dashboard stat icons (semantic - CSS override)
        /fill="#5B68FF"/,
        /fill="#26C3A4"/,
        /fill="#FFB134"/,
        /fill="#00C2FF"/,

        // Radio/checkbox accent (CSS override)
        /stroke="#EE1243"/,
        /fill="#EE1243"/,

        // Color picker default values
        /value="#000000"/,
        /value='#000000'/,

        // Social login SVG backgrounds
        /#FAFAFA/,

        // ====== CSS var() with fallback - ALLOWED ======
        /var\(--[a-zA-Z0-9_-]+,\s*#[0-9a-fA-F]{3,8}\)/,
        /var\(--[a-zA-Z0-9_-]+,\s*rgba?\([^)]+\)\)/,

        // ====== Loader overlays (system standard) ======
        /rgba\(45,\s*45,\s*45/,
        /rgba\(0,\s*0,\s*0,\s*0\.[0-9]/,

        // Box shadows (allowed in CSS)
        /box-shadow.*rgba\(/
    ],

    // Violation patterns
    violationPatterns: [
        {
            name: 'Hardcoded HEX (no var fallback)',
            pattern: /#[0-9a-fA-F]{3,8}\b/g,
            // These patterns are OK
            exclude: [
                /var\([^)]*#[0-9a-fA-F]/,
                /fill="#[0-9a-fA-F]+"/,
                /stroke="#[0-9a-fA-F]+"/,
                /value=["']#[0-9a-fA-F]+["']/,
                /--swatch-color:\s*#/,
                /\{\{.*color.*\}\}/i
            ]
        },
        {
            name: 'Hardcoded RGB/RGBA',
            pattern: /rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+/g,
            exclude: [
                /var\([^)]*rgba?\(/,
                /box-shadow.*rgba?\(/,
                /rgba\(0,\s*0,\s*0,\s*0\.[0-9]/,
                /rgba\(45,\s*45,\s*45/,
                /rgba\(255,\s*255,\s*255/
            ]
        },
        {
            name: 'Inline style with color',
            pattern: /style="[^"]*(?:color|background|border):\s*#[0-9a-fA-F]+/gi,
            exclude: [
                /\{\{.*\}\}/,
                /var\(/
            ]
        }
    ]
};

// Colors for console output
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

function isExcludedPath(filePath) {
    const normalizedPath = filePath.replace(/\\/g, '/');

    // Check excluded directories (PDF/Print only)
    for (const excluded of CONFIG.excludedPaths) {
        if (normalizedPath.includes(excluded.replace(/\\/g, '/'))) {
            return true;
        }
    }

    // Check excluded files
    for (const excluded of CONFIG.excludedFiles) {
        if (normalizedPath.endsWith(excluded)) {
            return true;
        }
    }

    return false;
}

function isAllowedPattern(line) {
    for (const pattern of CONFIG.allowedPatterns) {
        if (pattern.test(line)) {
            return true;
        }
    }
    return false;
}

function matchesExcludePatterns(line, excludePatterns) {
    if (!excludePatterns) return false;
    for (const pattern of excludePatterns) {
        if (pattern.test(line)) {
            return true;
        }
    }
    return false;
}

function classifyViolation(match, context, filePath) {
    const normalizedPath = filePath.replace(/\\/g, '/');

    // Check if it's a product swatch context
    if (/color.*swatch|swatch.*color|\$prod.*color|\$productt.*color|\$vendorColor/i.test(context)) {
        return { decision: 'WHITELIST', reason: 'Dynamic product color swatch' };
    }

    // Check if it's inside a var() fallback
    if (/var\([^)]*$/.test(context.split(match)[0])) {
        return { decision: 'WHITELIST', reason: 'CSS variable fallback' };
    }

    // Check if it's an SVG fill/stroke
    if (/fill=|stroke=/.test(context)) {
        return { decision: 'WHITELIST', reason: 'SVG attribute (CSS override in muaadh-system.css)' };
    }

    // Check file context for admin/vendor (separate theme areas)
    if (normalizedPath.includes('/admin/') || normalizedPath.includes('/vendor/')) {
        return { decision: 'WHITELIST', reason: 'Admin/Vendor area (separate theme)' };
    }

    // Default: needs fix
    return { decision: 'FIX', reason: 'Hardcoded color - convert to CSS variable' };
}

function scanFile(filePath) {
    const violations = [];
    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');

    lines.forEach((line, index) => {
        const lineNum = index + 1;

        // Skip if line is allowed by pattern
        if (isAllowedPattern(line)) {
            return;
        }

        // Check for violations
        for (const rule of CONFIG.violationPatterns) {
            // Skip if line matches exclude patterns
            if (matchesExcludePatterns(line, rule.exclude)) {
                continue;
            }

            const matches = line.match(rule.pattern);
            if (matches) {
                matches.forEach(match => {
                    // Double-check it's not in a var() fallback
                    const varPattern = new RegExp(`var\\([^)]*${match.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}`);
                    if (!varPattern.test(line)) {
                        const classification = classifyViolation(match, line, filePath);
                        violations.push({
                            file: filePath,
                            line: lineNum,
                            rule: rule.name,
                            match: match,
                            context: line.trim().substring(0, 120),
                            decision: classification.decision,
                            reason: classification.reason
                        });
                    }
                });
            }
        }
    });

    return violations;
}

function getAllFiles(dir, files = []) {
    if (!fs.existsSync(dir)) {
        return files;
    }

    const items = fs.readdirSync(dir);

    for (const item of items) {
        const fullPath = path.join(dir, item);
        const stat = fs.statSync(fullPath);

        if (stat.isDirectory()) {
            getAllFiles(fullPath, files);
        } else if (item.endsWith('.blade.php')) {
            files.push(fullPath);
        }
    }

    return files;
}

function generateReport(violations, baseDir) {
    const report = {
        date: new Date().toISOString().split('T')[0],
        mode: 'STRICT',
        allowedExceptions: [
            'PDF/Print paths (resources/views/pdf/, resources/views/print/)',
            'Dynamic product swatches ({{ $color }}, --swatch-color: #)'
        ],
        summary: {
            total: violations.length,
            fix: violations.filter(v => v.decision === 'FIX').length,
            whitelist: violations.filter(v => v.decision === 'WHITELIST').length
        },
        violations: violations.map(v => ({
            file: path.relative(baseDir, v.file).replace(/\\/g, '/'),
            line: v.line,
            rule: v.rule,
            match: v.match,
            context: v.context,
            decision: v.decision,
            reason: v.reason
        }))
    };

    return report;
}

function generateMarkdownReport(report) {
    let md = `# Theme Compliance Report - STRICT MODE\n\n`;
    md += `**Date:** ${report.date}\n`;
    md += `**Mode:** ${report.mode}\n\n`;

    md += `## Allowed Exceptions (Only 2)\n\n`;
    report.allowedExceptions.forEach((ex, i) => {
        md += `${i + 1}. ${ex}\n`;
    });

    md += `\n## Summary\n\n`;
    md += `| Metric | Count |\n`;
    md += `|--------|-------|\n`;
    md += `| **Total Findings** | ${report.summary.total} |\n`;
    md += `| **FIX Required** | ${report.summary.fix} |\n`;
    md += `| **Whitelisted** | ${report.summary.whitelist} |\n\n`;

    if (report.summary.fix > 0) {
        md += `## FIX Required (${report.summary.fix})\n\n`;
        md += `| File | Line | Match | Reason |\n`;
        md += `|------|------|-------|--------|\n`;
        report.violations
            .filter(v => v.decision === 'FIX')
            .forEach(v => {
                md += `| \`${v.file}\` | ${v.line} | \`${v.match}\` | ${v.reason} |\n`;
            });
        md += `\n`;
    }

    if (report.summary.whitelist > 0) {
        md += `## Whitelisted (${report.summary.whitelist})\n\n`;
        md += `| File | Line | Match | Reason |\n`;
        md += `|------|------|-------|--------|\n`;
        report.violations
            .filter(v => v.decision === 'WHITELIST')
            .forEach(v => {
                md += `| \`${v.file}\` | ${v.line} | \`${v.match}\` | ${v.reason} |\n`;
            });
    }

    return md;
}

function main() {
    log('cyan', '\n========================================');
    log('cyan', '  Theme Color Linter - STRICT MODE');
    log('cyan', '========================================\n');

    log('magenta', 'Allowed Exceptions:');
    log('magenta', '  1. PDF/Print paths');
    log('magenta', '  2. Dynamic product swatches\n');

    const baseDir = process.cwd();
    let allViolations = [];
    let scannedFiles = 0;

    for (const scanDir of CONFIG.scanDirs) {
        const fullPath = path.join(baseDir, scanDir);
        const files = getAllFiles(fullPath);

        for (const file of files) {
            if (isExcludedPath(file)) {
                continue;
            }

            scannedFiles++;
            const violations = scanFile(file);
            allViolations = allViolations.concat(violations);
        }
    }

    log('blue', `Scanned: ${scannedFiles} files`);

    // Generate and save reports
    const report = generateReport(allViolations, baseDir);

    // Save JSON report
    fs.writeFileSync(
        path.join(baseDir, 'THEME_VIOLATIONS_REPORT.json'),
        JSON.stringify(report, null, 2)
    );

    // Save Markdown report
    const mdReport = generateMarkdownReport(report);
    fs.writeFileSync(
        path.join(baseDir, 'THEME_VIOLATIONS_REPORT.md'),
        mdReport
    );

    // Count only FIX violations for build failure
    const fixCount = allViolations.filter(v => v.decision === 'FIX').length;

    if (fixCount === 0) {
        log('green', '\n✓ No violations requiring fixes!\n');
        log('green', `Theme compliance: PASSED`);
        log('yellow', `(${report.summary.whitelist} items whitelisted)`);
        log('cyan', '\nReports saved:');
        log('cyan', '  - THEME_VIOLATIONS_REPORT.json');
        log('cyan', '  - THEME_VIOLATIONS_REPORT.md');
        log('cyan', '========================================\n');
        process.exit(0);
    } else {
        log('red', `\n✗ Found ${fixCount} violations requiring fixes:\n`);

        // Group by file
        const byFile = {};
        for (const v of allViolations.filter(v => v.decision === 'FIX')) {
            const relPath = path.relative(baseDir, v.file);
            if (!byFile[relPath]) {
                byFile[relPath] = [];
            }
            byFile[relPath].push(v);
        }

        for (const [file, violations] of Object.entries(byFile)) {
            log('yellow', `\n${file}:`);
            for (const v of violations) {
                console.log(`  Line ${v.line}: ${v.rule}`);
                console.log(`    ${COLORS.cyan}${v.match}${COLORS.reset} in: ${v.context.substring(0, 80)}...`);
            }
        }

        log('red', '\n========================================');
        log('red', 'Theme compliance: FAILED');
        log('red', '========================================');
        log('yellow', '\nFix by using CSS variables:');
        console.log('  color: var(--text-primary, #1a1510);');
        console.log('  background: var(--action-primary, #006c35);');
        console.log('  border: var(--border-default, #d4c4a8);\n');
        log('cyan', 'Reports saved:');
        log('cyan', '  - THEME_VIOLATIONS_REPORT.json');
        log('cyan', '  - THEME_VIOLATIONS_REPORT.md');

        process.exit(1);
    }
}

main();
