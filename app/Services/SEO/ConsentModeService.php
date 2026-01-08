<?php

namespace App\Services\SEO;

/**
 * Google Consent Mode Service
 * يدير Consent Mode v2 للامتثال لـ GDPR/CCPA
 */
class ConsentModeService
{
    /**
     * Render Consent Mode initialization script
     * يجب أن يكون قبل GTM script
     */
    public static function renderConsentInit(): string
    {
        return <<<'HTML'
<script>
// Google Consent Mode v2 - Default settings (deny all until consent given)
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}

// Set default consent state - deny all by default for GDPR compliance
gtag('consent', 'default', {
    'ad_storage': 'denied',
    'ad_user_data': 'denied',
    'ad_personalization': 'denied',
    'analytics_storage': 'denied',
    'functionality_storage': 'granted',
    'personalization_storage': 'denied',
    'security_storage': 'granted',
    'wait_for_update': 500
});

// Enable URL passthrough for better conversion tracking without cookies
gtag('set', 'url_passthrough', true);

// Enable ads data redaction when consent denied
gtag('set', 'ads_data_redaction', true);
</script>
HTML;
    }

    /**
     * Render consent update function
     * يُستدعى عند موافقة المستخدم من Cookie Banner
     */
    public static function renderConsentUpdateScript(): string
    {
        return <<<'HTML'
<script>
// Function to update consent - call this when user accepts cookies
function updateGoogleConsent(consentOptions) {
    const defaults = {
        analytics: false,
        marketing: false,
        personalization: false
    };

    const options = {...defaults, ...consentOptions};

    gtag('consent', 'update', {
        'ad_storage': options.marketing ? 'granted' : 'denied',
        'ad_user_data': options.marketing ? 'granted' : 'denied',
        'ad_personalization': options.personalization ? 'granted' : 'denied',
        'analytics_storage': options.analytics ? 'granted' : 'denied',
        'personalization_storage': options.personalization ? 'granted' : 'denied'
    });

    // Store consent in localStorage
    localStorage.setItem('cookie_consent', JSON.stringify(options));

    // Push event to dataLayer
    dataLayer.push({
        'event': 'consent_update',
        'consent_analytics': options.analytics,
        'consent_marketing': options.marketing,
        'consent_personalization': options.personalization
    });
}

// Function to accept all cookies
function acceptAllCookies() {
    updateGoogleConsent({
        analytics: true,
        marketing: true,
        personalization: true
    });
    hideCookieBanner();
}

// Function to accept only necessary cookies
function acceptNecessaryCookies() {
    updateGoogleConsent({
        analytics: false,
        marketing: false,
        personalization: false
    });
    hideCookieBanner();
}

// Function to hide cookie banner
function hideCookieBanner() {
    const banner = document.getElementById('cookie-consent-banner');
    if (banner) {
        banner.style.display = 'none';
    }
}

// Check for existing consent on page load
document.addEventListener('DOMContentLoaded', function() {
    const storedConsent = localStorage.getItem('cookie_consent');
    if (storedConsent) {
        try {
            const consent = JSON.parse(storedConsent);
            updateGoogleConsent(consent);
            hideCookieBanner();
        } catch (e) {
            // Invalid stored consent, show banner
        }
    }
});
</script>
HTML;
    }

    /**
     * Render simple cookie consent banner
     */
    public static function renderCookieBanner(): string
    {
        $acceptAll = __('Accept All');
        $acceptNecessary = __('Necessary Only');
        $message = __('We use cookies to improve your experience. By continuing, you agree to our use of cookies.');
        $privacyLink = __('Privacy Policy');

        return <<<HTML
<div id="cookie-consent-banner" class="cookie-banner" style="display: none;">
    <div class="cookie-banner-content">
        <p>{$message} <a href="/page/privacy-policy">{$privacyLink}</a></p>
        <div class="cookie-banner-buttons">
            <button onclick="acceptNecessaryCookies()" class="btn btn-outline-secondary btn-sm">{$acceptNecessary}</button>
            <button onclick="acceptAllCookies()" class="btn btn-primary btn-sm">{$acceptAll}</button>
        </div>
    </div>
</div>
<script>
// Show banner if no consent stored
if (!localStorage.getItem('cookie_consent')) {
    document.getElementById('cookie-consent-banner').style.display = 'block';
}
</script>
<style>
.cookie-banner {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: var(--theme-bg-dark, #1a1a2e);
    color: var(--theme-text-light, #fff);
    padding: 1rem;
    z-index: 9999;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.2);
}
.cookie-banner-content {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.cookie-banner p {
    margin: 0;
    flex: 1;
}
.cookie-banner a {
    color: var(--theme-primary, #3498db);
}
.cookie-banner-buttons {
    display: flex;
    gap: 0.5rem;
}
@media (max-width: 768px) {
    .cookie-banner-content {
        flex-direction: column;
        text-align: center;
    }
}
</style>
HTML;
    }
}
