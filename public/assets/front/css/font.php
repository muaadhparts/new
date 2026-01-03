<?php
header("Content-type: text/css; charset: UTF-8");

// Whitelist of allowed fonts to prevent XSS
$allowed_fonts = [
    'Open Sans',
    'Roboto',
    'Lato',
    'Montserrat',
    'Poppins',
    'Raleway',
    'Ubuntu',
    'Nunito',
    'Cairo',
    'Tajawal',
    'Almarai',
    'IBM Plex Sans Arabic',
    'Noto Sans Arabic',
    'Arial',
    'Helvetica',
    'Georgia',
    'Times New Roman',
    'Verdana',
    'Tahoma',
];

$font_familly = 'Open Sans'; // Default font

if (isset($_GET['font_familly'])) {
    $requested_font = $_GET['font_familly'];
    // Only allow whitelisted fonts
    if (in_array($requested_font, $allowed_fonts, true)) {
        $font_familly = $requested_font;
    }
}

// Escape for extra safety
$font_familly = htmlspecialchars($font_familly, ENT_QUOTES, 'UTF-8');
?>

body {
    font-family: "<?php echo $font_familly; ?>", sans-serif;
}

h1,
h2,
h3,
h4,
h5,
h6 {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

p {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.template-btn {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.single-catalogItem .img-wrapper .catalogItem-badge,
.single-catalogItem-list-view .img-wrapper .catalogItem-badge {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.single-catalogItem .rating-title,
.single-catalogItem-list-view .rating-title {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-cart-section .gs-cart-container .gs-cart-row .cart-table .table thead th {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-cart-section .gs-cart-container .gs-cart-row .cart-summary .cart-summary-content .cart-summary-subtitle {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-reg-section .reg-area .form-group .login-or {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.dropdown-item {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.collapse-icon::before {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.language-dropdown > .dropdown .dropdown-item {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

table.gs-data-table thead tr th {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

table.gs-data-table tbody tr td {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.dt-layout-row label {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

div.dt-container .dt-paging .dt-paging-button {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.vendor-table-wrapper .user-table td .status .nice-select .current,
.user-table-wrapper .user-table td .status .nice-select .current {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-vendor-header .user-dropdown-wrapper .user-designation {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-vendor-header .user-dropdown-wrapper .dropdown-item {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.nice-select.nice-select-vendor::after {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-modal .modal-dialog .modal-content .transaction-info-wrapper .info-list .info-list-item .info-type {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-explore-catalogItem-section .explore-tab-navbar li .nav-link {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-latest-post-section.home-3 .poster .poster_name {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.gs-latest-post-section.home-3 .poster .post_date {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.tab-catalogItem-des-wrapper .nav-link {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}

.select-catalogItem-types-wrapper .inputs-wrapper .label {
  font-family: "<?php echo $font_familly; ?>", sans-serif;
}
