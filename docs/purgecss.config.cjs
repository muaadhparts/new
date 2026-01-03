/**
 * PurgeCSS Configuration
 *
 * Removes unused CSS from muaadh-system.css
 * Run: npm run purge:css
 */

module.exports = {
    // Content files to scan for class usage
    content: [
        'resources/views/**/*.blade.php',
        'resources/views/**/*.php',
        'public/assets/front/js/**/*.js',
        'app/View/Components/**/*.php'
    ],

    // CSS files to purge
    css: [
        'public/assets/front/css/muaadh-system.css'
    ],

    // Output directory
    output: 'public/assets/front/css/',

    // Safelist - never remove these
    safelist: {
        // Standard patterns
        standard: [
            // Dynamic state classes
            /^is-/,
            /^has-/,

            // Theme variables (CSS custom properties)
            /^--/,

            // Bootstrap classes that might be added dynamically
            /^show$/,
            /^active$/,
            /^disabled$/,
            /^fade$/,
            /^collapse/,
            /^modal/,
            /^dropdown/,
            /^tooltip/,
            /^popover/,
            /^carousel/,
            /^tab-/,
            /^nav-/,

            // Slick slider
            /^slick/,

            // Nice select
            /^nice-select/,

            // DataTables
            /^dataTables/,
            /^dt-/,

            // Toastr
            /^toast/,

            // Magnific popup
            /^mfp-/,

            // Dropzone
            /^dz-/,
            /^dropzone/,

            // jQuery UI
            /^ui-/,

            // RTL
            /^rtl/,

            // Legacy classes that might be in JS
            /^gs-/,
            /^template-/,
            /^muaadh-/
        ],

        // Deep patterns (also check children)
        deep: [
            /^m-/, // All design system classes
            /modal/,
            /alert/,
            /btn/,
            /badge/,
            /card/,
            /form/,
            /input/,
            /table/
        ],

        // Greedy patterns
        greedy: [
            /^col-/,
            /^row/,
            /^container/,
            /^d-/,
            /^flex/,
            /^justify/,
            /^align/,
            /^text-/,
            /^bg-/,
            /^border/,
            /^rounded/,
            /^shadow/,
            /^p-/,
            /^m-/,
            /^py-/,
            /^px-/,
            /^my-/,
            /^mx-/,
            /^mt-/,
            /^mb-/,
            /^ms-/,
            /^me-/,
            /^pt-/,
            /^pb-/,
            /^ps-/,
            /^pe-/,
            /^w-/,
            /^h-/,
            /^gap-/
        ]
    },

    // Keyframes to keep
    keyframes: true,

    // CSS variables to keep
    variables: true,

    // Font faces to keep
    fontFace: true,

    // Rejected classes log (for debugging)
    rejected: false
};
