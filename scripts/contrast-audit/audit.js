/**
 * WCAG Contrast Ratio Audit Script - Enhanced
 * Uses Playwright to check color contrast across the site
 *
 * Features:
 * - State checking: default, hover, focus, disabled
 * - Inherited background calculation (traverses DOM + computed styles)
 * - Screenshots for violations
 * - JSON/CSV reports
 *
 * Usage:
 *   node audit.js                    # Run on all pages
 *   node audit.js --theme=nissan     # Specify theme name
 *   node audit.js --url=http://...   # Custom base URL
 *   node audit.js --screenshot       # Take screenshots of violations
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
// Element State Checking
// ============================================================

/**
 * Get element styles in different states
 */
async function getElementStates(page, selector, index) {
    return await page.evaluate(({ selector, index }) => {
        const elements = document.querySelectorAll(selector);
        const el = elements[index];
        if (!el) return null;

        const getStyles = () => {
            const styles = window.getComputedStyle(el);

            // Get effective background by traversing up
            let bgColor = styles.backgroundColor;
            let parent = el.parentElement;
            let depth = 0;

            while (parent && depth < 20 && (bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)')) {
                const parentStyles = window.getComputedStyle(parent);
                bgColor = parentStyles.backgroundColor;

                // Also check for background-image (gradients)
                if (bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)') {
                    const bgImage = parentStyles.backgroundImage;
                    if (bgImage && bgImage !== 'none') {
                        // Try to extract first color from gradient
                        const gradientMatch = bgImage.match(/rgba?\([^)]+\)|#[a-f0-9]{3,8}/i);
                        if (gradientMatch) {
                            bgColor = gradientMatch[0];
                        }
                    }
                }

                parent = parent.parentElement;
                depth++;
            }

            // Default to white if no background found
            if (!bgColor || bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)') {
                bgColor = 'rgb(255, 255, 255)';
            }

            return {
                color: styles.color,
                backgroundColor: bgColor,
                fontSize: styles.fontSize,
                fontWeight: styles.fontWeight
            };
        };

        return {
            default: getStyles(),
            isDisabled: el.disabled || el.hasAttribute('disabled') || el.classList.contains('disabled'),
            rect: el.getBoundingClientRect()
        };
    }, { selector, index });
}

/**
 * Get hover state styles
 */
async function getHoverState(page, selector, index) {
    try {
        const elements = await page.$$(selector);
        if (!elements[index]) return null;

        await elements[index].hover();
        await page.waitForTimeout(100); // Wait for CSS transition

        return await page.evaluate(({ selector, index }) => {
            const el = document.querySelectorAll(selector)[index];
            if (!el) return null;

            const styles = window.getComputedStyle(el);
            let bgColor = styles.backgroundColor;

            // Check parent backgrounds for hover too
            if (bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)') {
                let parent = el.parentElement;
                while (parent) {
                    const parentStyles = window.getComputedStyle(parent);
                    if (parentStyles.backgroundColor !== 'transparent' && parentStyles.backgroundColor !== 'rgba(0, 0, 0, 0)') {
                        bgColor = parentStyles.backgroundColor;
                        break;
                    }
                    parent = parent.parentElement;
                }
            }

            if (!bgColor || bgColor === 'transparent') bgColor = 'rgb(255, 255, 255)';

            return {
                color: styles.color,
                backgroundColor: bgColor
            };
        }, { selector, index });
    } catch (e) {
        return null;
    }
}

/**
 * Get focus state styles
 */
async function getFocusState(page, selector, index) {
    try {
        const elements = await page.$$(selector);
        if (!elements[index]) return null;

        await elements[index].focus();
        await page.waitForTimeout(100);

        return await page.evaluate(({ selector, index }) => {
            const el = document.querySelectorAll(selector)[index];
            if (!el) return null;

            const styles = window.getComputedStyle(el);
            let bgColor = styles.backgroundColor;
            if (bgColor === 'transparent' || bgColor === 'rgba(0, 0, 0, 0)') {
                bgColor = 'rgb(255, 255, 255)';
            }

            return {
                color: styles.color,
                backgroundColor: bgColor
            };
        }, { selector, index });
    } catch (e) {
        return null;
    }
}

// ============================================================
// Main Audit Function
// ============================================================

async function auditPage(page, url, pageName, options) {
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

    // Define selectors to check
    const selectors = [
        // Buttons (high priority)
        { selector: '.m-btn', type: 'button', priority: 'high' },
        { selector: '.btn', type: 'button', priority: 'high' },
        { selector: 'button:not([type="hidden"])', type: 'button', priority: 'high' },
        { selector: '[type="submit"]', type: 'button', priority: 'high' },
        { selector: '.add-to-cart', type: 'button', priority: 'high' },
        { selector: '.cart-btn', type: 'button', priority: 'high' },
        { selector: '.template-btn', type: 'button', priority: 'high' },

        // Badges
        { selector: '.badge', type: 'badge', priority: 'high' },
        { selector: '.m-badge', type: 'badge', priority: 'high' },

        // Alerts
        { selector: '.alert', type: 'alert', priority: 'medium' },

        // Links
        { selector: 'a:not(.btn):not(.nav-link)', type: 'link', priority: 'medium' },

        // Navigation
        { selector: '.nav-link', type: 'nav', priority: 'medium' },
        { selector: '.dropdown-item', type: 'nav', priority: 'medium' },

        // Form elements
        { selector: '.form-control', type: 'input', priority: 'medium' },
        { selector: 'label', type: 'label', priority: 'low' },

        // Text elements
        { selector: 'h1, h2, h3, h4', type: 'heading', priority: 'medium' },
        { selector: 'p', type: 'text', priority: 'low' }
    ];

    for (const { selector, type, priority } of selectors) {
        const elements = await page.$$(selector);
        const maxElements = priority === 'high' ? 20 : priority === 'medium' ? 10 : 5;

        for (let i = 0; i < Math.min(elements.length, maxElements); i++) {
            try {
                const states = await getElementStates(page, selector, i);
                if (!states || !states.default) continue;

                const text = await elements[i].textContent();
                const trimmedText = (text || '').trim().substring(0, 50);
                if (!trimmedText) continue;

                // Check default state
                const defaultResult = checkContrast(states.default, {
                    page: pageName,
                    selector,
                    index: i,
                    text: trimmedText,
                    elementType: type,
                    state: 'default',
                    isDisabled: states.isDisabled
                });
                if (defaultResult) results.push(defaultResult);

                // Check hover state for interactive elements
                if (['button', 'link', 'nav'].includes(type) && options.checkHover) {
                    const hoverStyles = await getHoverState(page, selector, i);
                    if (hoverStyles) {
                        const hoverResult = checkContrast({
                            ...states.default,
                            ...hoverStyles
                        }, {
                            page: pageName,
                            selector,
                            index: i,
                            text: trimmedText,
                            elementType: type,
                            state: 'hover',
                            isDisabled: states.isDisabled
                        });
                        if (hoverResult) results.push(hoverResult);
                    }
                }

                // Check focus state for interactive elements
                if (['button', 'input'].includes(type) && options.checkFocus) {
                    const focusStyles = await getFocusState(page, selector, i);
                    if (focusStyles) {
                        const focusResult = checkContrast({
                            ...states.default,
                            ...focusStyles
                        }, {
                            page: pageName,
                            selector,
                            index: i,
                            text: trimmedText,
                            elementType: type,
                            state: 'focus',
                            isDisabled: states.isDisabled
                        });
                        if (focusResult) results.push(focusResult);
                    }
                }

            } catch (e) {
                // Skip problematic elements
            }
        }
    }

    return results;
}

/**
 * Check contrast and return result object
 */
function checkContrast(styles, meta) {
    const textColor = parseColor(styles.color);
    const bgColor = parseColor(styles.backgroundColor);

    if (!textColor || !bgColor) return null;

    const blendedText = blendColors(textColor, bgColor);
    const ratio = getContrastRatio(blendedText, bgColor);
    const wcag = checkWCAG(ratio, styles.fontSize, styles.fontWeight);

    return {
        page: meta.page,
        selector: meta.selector,
        index: meta.index,
        elementType: meta.elementType,
        state: meta.state,
        isDisabled: meta.isDisabled,
        text: meta.text,
        textColor: styles.color,
        bgColor: styles.backgroundColor,
        fontSize: styles.fontSize,
        fontWeight: styles.fontWeight,
        ratio: ratio ? ratio.toFixed(2) : 'N/A',
        wcagAA: wcag.aa ? 'PASS' : 'FAIL',
        wcagAAA: wcag.aaa ? 'PASS' : 'FAIL',
        aaRequired: wcag.aaRequired,
        isLargeText: wcag.isLargeText
    };
}

// ============================================================
// Report Generation
// ============================================================

function generateCSV(results) {
    const headers = [
        'page', 'elementType', 'state', 'selector', 'text',
        'textColor', 'bgColor', 'ratio', 'wcagAA', 'wcagAAA',
        'fontSize', 'fontWeight', 'isLargeText', 'isDisabled'
    ];

    let csv = headers.join(',') + '\n';

    results.forEach(r => {
        csv += headers.map(h => {
            const val = r[h] || '';
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
    const byState = {};

    results.forEach(r => {
        // By type
        if (!byType[r.elementType]) {
            byType[r.elementType] = { total: 0, pass: 0, fail: 0 };
        }
        byType[r.elementType].total++;
        if (r.wcagAA === 'PASS') byType[r.elementType].pass++;
        else byType[r.elementType].fail++;

        // By state
        if (!byState[r.state]) {
            byState[r.state] = { total: 0, pass: 0, fail: 0 };
        }
        byState[r.state].total++;
        if (r.wcagAA === 'PASS') byState[r.state].pass++;
        else byState[r.state].fail++;
    });

    const violations = results.filter(r => r.wcagAA === 'FAIL');

    return {
        total,
        wcagAA: { pass: aaPass, fail: aaFail, rate: total > 0 ? ((aaPass / total) * 100).toFixed(1) + '%' : '0%' },
        wcagAAA: { pass: aaaPass, fail: total - aaaPass, rate: total > 0 ? ((aaaPass / total) * 100).toFixed(1) + '%' : '0%' },
        byType,
        byState,
        topViolations: violations.slice(0, 20).map(v => ({
            page: v.page,
            type: v.elementType,
            state: v.state,
            selector: v.selector,
            ratio: v.ratio,
            required: v.aaRequired,
            colors: `${v.textColor} on ${v.bgColor}`,
            text: v.text
        }))
    };
}

// ============================================================
// Screenshot Functionality
// ============================================================

async function takeViolationScreenshots(page, violations, reportDir, theme) {
    const screenshotDir = path.join(reportDir, `screenshots-${theme}`);
    if (!fs.existsSync(screenshotDir)) {
        fs.mkdirSync(screenshotDir, { recursive: true });
    }

    console.log(`\nTaking screenshots of violations...`);

    // Group violations by page
    const byPage = {};
    violations.forEach(v => {
        if (!byPage[v.page]) byPage[v.page] = [];
        byPage[v.page].push(v);
    });

    for (const [pageName, pageViolations] of Object.entries(byPage)) {
        const filename = `${theme}-${pageName.replace(/[^a-z0-9]/gi, '_')}.png`;
        const filepath = path.join(screenshotDir, filename);

        try {
            await page.screenshot({ path: filepath, fullPage: true });
            console.log(`  Saved: ${filename}`);
        } catch (e) {
            console.log(`  Failed to save screenshot for ${pageName}`);
        }
    }
}

// ============================================================
// Main Execution
// ============================================================

async function main() {
    const args = process.argv.slice(2);
    const theme = args.find(a => a.startsWith('--theme='))?.split('=')[1] || 'current';
    const customUrl = args.find(a => a.startsWith('--url='))?.split('=')[1];
    const takeScreenshots = args.includes('--screenshot');
    const checkHover = !args.includes('--no-hover');
    const checkFocus = !args.includes('--no-focus');

    // Try to load generated pages first, fall back to static pages.json
    let pagesConfig;
    const generatedPath = path.join(__dirname, 'pages.generated.json');
    const staticPath = path.join(__dirname, 'pages.json');

    if (fs.existsSync(generatedPath)) {
        pagesConfig = JSON.parse(fs.readFileSync(generatedPath, 'utf-8'));
        console.log('Using pages.generated.json');
    } else if (fs.existsSync(staticPath)) {
        pagesConfig = JSON.parse(fs.readFileSync(staticPath, 'utf-8'));
        console.log('Using pages.json');
    } else {
        console.error('No pages config found. Run: php artisan audit:generate-pages');
        process.exit(1);
    }

    const baseUrl = customUrl || pagesConfig.baseUrl;

    console.log('='.repeat(60));
    console.log(' WCAG Contrast Ratio Audit - Enhanced');
    console.log('='.repeat(60));
    console.log(`Theme: ${theme}`);
    console.log(`Base URL: ${baseUrl}`);
    console.log(`Check Hover: ${checkHover}`);
    console.log(`Check Focus: ${checkFocus}`);
    console.log(`Screenshots: ${takeScreenshots}`);
    console.log('');

    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1920, height: 1080 }
    });
    const page = await context.newPage();

    let allResults = [];
    const options = { checkHover, checkFocus };

    for (const pageConfig of pagesConfig.pages) {
        // Skip auth-required pages for now
        if (pageConfig.requiresAuth) continue;

        const url = baseUrl + pageConfig.path;
        const results = await auditPage(page, url, pageConfig.name, options);
        allResults = allResults.concat(results);

        const failures = results.filter(r => r.wcagAA === 'FAIL').length;
        console.log(`    Found ${results.length} elements, ${failures} WCAG AA failures`);
    }

    // Generate reports
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-').substring(0, 19);
    const reportDir = path.join(__dirname, 'reports');

    if (!fs.existsSync(reportDir)) {
        fs.mkdirSync(reportDir, { recursive: true });
    }

    // Take screenshots of violations
    const violations = allResults.filter(r => r.wcagAA === 'FAIL');
    if (takeScreenshots && violations.length > 0) {
        await takeViolationScreenshots(page, violations, reportDir, theme);
    }

    await browser.close();

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
        const rate = stats.total > 0 ? ((stats.pass / stats.total) * 100).toFixed(0) : 0;
        console.log(`  ${type.padEnd(10)} ${stats.pass}/${stats.total} pass (${rate}%)`);
    });

    console.log('');
    console.log('By State:');
    Object.entries(summary.byState).forEach(([state, stats]) => {
        const rate = stats.total > 0 ? ((stats.pass / stats.total) * 100).toFixed(0) : 0;
        console.log(`  ${state.padEnd(10)} ${stats.pass}/${stats.total} pass (${rate}%)`);
    });

    if (summary.topViolations.length > 0) {
        console.log('');
        console.log('Top Violations:');
        summary.topViolations.slice(0, 10).forEach((v, i) => {
            console.log(`  ${i + 1}. [${v.page}] ${v.type}:${v.state} - ratio ${v.ratio} (need ${v.required})`);
            console.log(`     "${v.text}" - ${v.colors}`);
        });
    }

    console.log('');
    console.log(`Reports saved to:`);
    console.log(`  CSV:  ${csvPath}`);
    console.log(`  JSON: ${jsonPath}`);

    // Return exit code based on violations
    if (summary.wcagAA.fail > 0) {
        console.log(`\n[FAIL] ${summary.wcagAA.fail} WCAG AA violations found!`);
        process.exit(1);
    } else {
        console.log(`\n[PASS] No WCAG AA violations found!`);
        process.exit(0);
    }
}

main().catch(err => {
    console.error(err);
    process.exit(1);
});
