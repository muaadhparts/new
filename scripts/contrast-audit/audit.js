/**
 * WCAG Contrast Ratio Audit Script
 * Uses Playwright to check color contrast across the site
 *
 * Usage:
 *   node audit.js                    # Run on default pages
 *   node audit.js --theme=nissan     # Specify theme
 *   node audit.js --url=http://...   # Custom base URL
 */

const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

// ============================================================
// WCAG Contrast Calculation Functions
// ============================================================

/**
 * Parse CSS color to RGB
 */
function parseColor(color) {
    if (!color || color === 'transparent' || color === 'rgba(0, 0, 0, 0)') {
        return null;
    }

    // Handle rgb/rgba
    const rgbaMatch = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
    if (rgbaMatch) {
        return {
            r: parseInt(rgbaMatch[1]),
            g: parseInt(rgbaMatch[2]),
            b: parseInt(rgbaMatch[3]),
            a: rgbaMatch[4] ? parseFloat(rgbaMatch[4]) : 1
        };
    }

    // Handle hex
    const hexMatch = color.match(/^#([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i);
    if (hexMatch) {
        return {
            r: parseInt(hexMatch[1], 16),
            g: parseInt(hexMatch[2], 16),
            b: parseInt(hexMatch[3], 16),
            a: 1
        };
    }

    return null;
}

/**
 * Calculate relative luminance (WCAG 2.1)
 */
function getLuminance(r, g, b) {
    const [rs, gs, bs] = [r, g, b].map(c => {
        c = c / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

/**
 * Calculate contrast ratio between two colors
 */
function getContrastRatio(color1, color2) {
    if (!color1 || !color2) return null;

    const l1 = getLuminance(color1.r, color1.g, color1.b);
    const l2 = getLuminance(color2.r, color2.g, color2.b);

    const lighter = Math.max(l1, l2);
    const darker = Math.min(l1, l2);

    return (lighter + 0.05) / (darker + 0.05);
}

/**
 * Check if text passes WCAG contrast requirements
 */
function checkWCAG(ratio, fontSize, fontWeight) {
    const size = parseFloat(fontSize);
    const weight = parseInt(fontWeight) || 400;

    // Large text: 18px+ OR 14px+ bold (700+)
    const isLargeText = size >= 18 || (size >= 14 && weight >= 700);

    // WCAG AA requirements
    const aaRequired = isLargeText ? 3.0 : 4.5;
    // WCAG AAA requirements
    const aaaRequired = isLargeText ? 4.5 : 7.0;

    return {
        aa: ratio >= aaRequired,
        aaa: ratio >= aaaRequired,
        aaRequired,
        aaaRequired,
        isLargeText
    };
}

/**
 * Blend foreground color with background (for transparent colors)
 */
function blendColors(fg, bg) {
    if (!fg || !bg) return fg || bg;
    if (fg.a === 1) return fg;

    const alpha = fg.a;
    return {
        r: Math.round(fg.r * alpha + bg.r * (1 - alpha)),
        g: Math.round(fg.g * alpha + bg.g * (1 - alpha)),
        b: Math.round(fg.b * alpha + bg.b * (1 - alpha)),
        a: 1
    };
}

// ============================================================
// Main Audit Function
// ============================================================

async function auditPage(page, url, pageName) {
    const results = [];

    console.log(`  Auditing: ${pageName} (${url})`);

    try {
        await page.goto(url, { waitUntil: 'networkidle', timeout: 30000 });
    } catch (e) {
        console.log(`    WARNING: Could not load ${url} - ${e.message}`);
        return results;
    }

    // Wait for styles to apply
    await page.waitForTimeout(1000);

    // Get all interactive/text elements
    const elements = await page.evaluate(() => {
        const selectors = [
            // Buttons
            '.m-btn', '.btn', 'button', '[type="submit"]', '[type="button"]',
            // Badges
            '.badge', '.m-badge', '[class*="badge-"]',
            // Alerts
            '.alert', '.m-alert', '[class*="alert-"]',
            // Links
            'a',
            // Text utilities
            '[class*="text-"]', '[class*="bg-"]',
            // Inputs
            'input', 'select', 'textarea', '.form-control',
            // Labels
            'label', '.form-label',
            // Cards
            '.card', '.m-card', '.card-header', '.card-body',
            // Navigation
            '.nav-link', '.dropdown-item',
            // Text elements
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'li', 'td', 'th'
        ];

        const results = [];
        const seen = new Set();

        selectors.forEach(selector => {
            try {
                document.querySelectorAll(selector).forEach(el => {
                    // Skip hidden elements
                    if (el.offsetParent === null && !el.closest('nav')) return;

                    // Get unique identifier
                    const rect = el.getBoundingClientRect();
                    const key = `${rect.x}-${rect.y}-${rect.width}-${rect.height}`;
                    if (seen.has(key)) return;
                    seen.add(key);

                    const styles = window.getComputedStyle(el);
                    const text = (el.textContent || '').trim().substring(0, 50);

                    if (!text) return; // Skip empty elements

                    // Get element type
                    let elementType = 'text';
                    const classList = el.className.toString();
                    if (el.tagName === 'BUTTON' || classList.includes('btn')) elementType = 'button';
                    else if (classList.includes('badge')) elementType = 'badge';
                    else if (classList.includes('alert')) elementType = 'alert';
                    else if (el.tagName === 'A') elementType = 'link';
                    else if (el.tagName === 'INPUT' || el.tagName === 'SELECT') elementType = 'input';
                    else if (el.tagName === 'LABEL') elementType = 'label';
                    else if (classList.includes('card')) elementType = 'card';
                    else if (classList.includes('nav')) elementType = 'nav';

                    // Get background by traversing up the DOM
                    let bgColor = styles.backgroundColor;
                    let parent = el.parentElement;
                    while (parent && (bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)')) {
                        const parentStyles = window.getComputedStyle(parent);
                        bgColor = parentStyles.backgroundColor;
                        parent = parent.parentElement;
                    }

                    // Default to white if no background found
                    if (bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)') {
                        bgColor = 'rgb(255, 255, 255)';
                    }

                    results.push({
                        selector: el.tagName.toLowerCase() + (el.className ? '.' + el.className.toString().split(' ').slice(0, 3).join('.') : ''),
                        elementType,
                        textColor: styles.color,
                        bgColor,
                        fontSize: styles.fontSize,
                        fontWeight: styles.fontWeight,
                        text,
                        classes: classList.substring(0, 100)
                    });
                });
            } catch (e) {
                // Ignore selector errors
            }
        });

        return results;
    });

    // Calculate contrast ratios
    elements.forEach(el => {
        const textColor = parseColor(el.textColor);
        const bgColor = parseColor(el.bgColor);

        if (textColor && bgColor) {
            const blendedText = blendColors(textColor, bgColor);
            const ratio = getContrastRatio(blendedText, bgColor);
            const wcag = checkWCAG(ratio, el.fontSize, el.fontWeight);

            results.push({
                page: pageName,
                url,
                selector: el.selector,
                elementType: el.elementType,
                text: el.text,
                textColor: el.textColor,
                bgColor: el.bgColor,
                fontSize: el.fontSize,
                fontWeight: el.fontWeight,
                ratio: ratio ? ratio.toFixed(2) : 'N/A',
                wcagAA: wcag.aa ? 'PASS' : 'FAIL',
                wcagAAA: wcag.aaa ? 'PASS' : 'FAIL',
                aaRequired: wcag.aaRequired,
                isLargeText: wcag.isLargeText,
                classes: el.classes
            });
        }
    });

    return results;
}

// ============================================================
// Report Generation
// ============================================================

function generateCSV(results) {
    const headers = [
        'page', 'url', 'elementType', 'selector', 'text',
        'textColor', 'bgColor', 'ratio', 'wcagAA', 'wcagAAA',
        'fontSize', 'fontWeight', 'isLargeText', 'classes'
    ];

    let csv = headers.join(',') + '\n';

    results.forEach(r => {
        csv += headers.map(h => {
            const val = r[h] || '';
            // Escape quotes and wrap in quotes if contains comma
            const escaped = String(val).replace(/"/g, '""');
            return escaped.includes(',') ? `"${escaped}"` : escaped;
        }).join(',') + '\n';
    });

    return csv;
}

function generateSummary(results) {
    const total = results.length;
    const aaPass = results.filter(r => r.wcagAA === 'PASS').length;
    const aaFail = results.filter(r => r.wcagAA === 'FAIL').length;
    const aaaPass = results.filter(r => r.wcagAAA === 'PASS').length;

    const byType = {};
    results.forEach(r => {
        if (!byType[r.elementType]) {
            byType[r.elementType] = { total: 0, pass: 0, fail: 0 };
        }
        byType[r.elementType].total++;
        if (r.wcagAA === 'PASS') byType[r.elementType].pass++;
        else byType[r.elementType].fail++;
    });

    const violations = results.filter(r => r.wcagAA === 'FAIL');

    return {
        total,
        wcagAA: { pass: aaPass, fail: aaFail, rate: ((aaPass / total) * 100).toFixed(1) + '%' },
        wcagAAA: { pass: aaaPass, fail: total - aaaPass, rate: ((aaaPass / total) * 100).toFixed(1) + '%' },
        byType,
        topViolations: violations.slice(0, 20).map(v => ({
            page: v.page,
            type: v.elementType,
            selector: v.selector,
            ratio: v.ratio,
            required: v.aaRequired,
            colors: `${v.textColor} on ${v.bgColor}`
        }))
    };
}

// ============================================================
// Main Execution
// ============================================================

async function main() {
    const args = process.argv.slice(2);
    const theme = args.find(a => a.startsWith('--theme='))?.split('=')[1] || 'current';
    const customUrl = args.find(a => a.startsWith('--url='))?.split('=')[1];

    const pagesConfig = JSON.parse(fs.readFileSync(path.join(__dirname, 'pages.json'), 'utf-8'));
    const baseUrl = customUrl || pagesConfig.baseUrl;

    console.log('='.repeat(60));
    console.log(' WCAG Contrast Ratio Audit');
    console.log('='.repeat(60));
    console.log(`Theme: ${theme}`);
    console.log(`Base URL: ${baseUrl}`);
    console.log('');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    let allResults = [];

    for (const pageConfig of pagesConfig.pages) {
        const url = baseUrl + pageConfig.path;
        const results = await auditPage(page, url, pageConfig.name);
        allResults = allResults.concat(results);

        const failures = results.filter(r => r.wcagAA === 'FAIL').length;
        console.log(`    Found ${results.length} elements, ${failures} WCAG AA failures`);
    }

    await browser.close();

    // Generate reports
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);
    const reportDir = path.join(__dirname, 'reports');

    if (!fs.existsSync(reportDir)) {
        fs.mkdirSync(reportDir, { recursive: true });
    }

    // CSV Report
    const csvPath = path.join(reportDir, `contrast-audit-${theme}-${timestamp}.csv`);
    fs.writeFileSync(csvPath, generateCSV(allResults));

    // JSON Report
    const jsonPath = path.join(reportDir, `contrast-audit-${theme}-${timestamp}.json`);
    fs.writeFileSync(jsonPath, JSON.stringify({
        meta: { theme, baseUrl, timestamp, totalElements: allResults.length },
        summary: generateSummary(allResults),
        results: allResults
    }, null, 2));

    // Summary
    const summary = generateSummary(allResults);

    console.log('');
    console.log('='.repeat(60));
    console.log(' AUDIT SUMMARY');
    console.log('='.repeat(60));
    console.log(`Total Elements Checked: ${summary.total}`);
    console.log(`WCAG AA Pass Rate: ${summary.wcagAA.rate} (${summary.wcagAA.pass}/${summary.total})`);
    console.log(`WCAG AAA Pass Rate: ${summary.wcagAAA.rate}`);
    console.log('');
    console.log('By Element Type:');
    Object.entries(summary.byType).forEach(([type, stats]) => {
        const rate = ((stats.pass / stats.total) * 100).toFixed(0);
        console.log(`  ${type.padEnd(10)} ${stats.pass}/${stats.total} pass (${rate}%)`);
    });

    if (summary.topViolations.length > 0) {
        console.log('');
        console.log('Top Violations:');
        summary.topViolations.slice(0, 10).forEach((v, i) => {
            console.log(`  ${i + 1}. [${v.page}] ${v.type} - ratio ${v.ratio} (need ${v.required})`);
        });
    }

    console.log('');
    console.log(`Reports saved to:`);
    console.log(`  CSV:  ${csvPath}`);
    console.log(`  JSON: ${jsonPath}`);
}

main().catch(console.error);
