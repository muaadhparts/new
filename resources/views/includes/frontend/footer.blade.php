<footer class="creative-footer">
    <!-- Main Footer Content -->
    <div class="footer-main">
        <div class="container">
            <div class="footer-layout">
                <!-- Brand Column -->
                <div class="footer-brand-col">
                    <div class="brand-wrapper">
                        <img src="{{ asset('assets/images/' . $gs->footer_logo) }}" alt="{{ config('app.name') }}" class="footer-brand-logo">
                        <p class="brand-tagline">@lang('Your trusted source for genuine OEM parts. Quality guaranteed.')</p>
                    </div>

                    <!-- Quick Contact -->
                    <div class="quick-contact">
                        <a href="tel:{{ $ps->phone }}" class="contact-link">
                            <div class="contact-icon-box">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div class="contact-details">
                                <span class="contact-label">@lang('Call Us')</span>
                                <span class="contact-value">{{ $ps->phone }}</span>
                            </div>
                        </a>

                        <a href="mailto:{{ $ps->email }}" class="contact-link">
                            <div class="contact-icon-box">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="contact-details">
                                <span class="contact-label">@lang('Email Us')</span>
                                <span class="contact-value">{{ $ps->email }}</span>
                            </div>
                        </a>
                    </div>

                    <!-- Social Media -->
                    <div class="social-section">
                        <h6 class="social-heading">@lang('Follow Us')</h6>
                        <div class="social-buttons">
                            @foreach (DB::table('social_links')->where('user_id', 0)->where('status', 1)->get() as $link)
                                <a href="{{ $link->link }}" class="social-btn" target="_blank" rel="noopener">
                                    <i class="{{ $link->icon }}"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Quick Links Columns -->
                <div class="footer-links-grid">
                    <!-- Categories -->
                    <div class="footer-col">
                        <h5 class="footer-heading">@lang('Categories')</h5>
                        <ul class="footer-list">
                            @foreach ($categories->take(6) as $cate)
                                <li>
                                    <a href="{{ route('front.category', $cate->slug) }}">
                                        {{ $cate->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Quick Links -->
                    <div class="footer-col">
                        <h5 class="footer-heading">@lang('Quick Links')</h5>
                        <ul class="footer-list">
                            @if ($ps->home == 1)
                                <li><a href="{{ route('front.index') }}">{{ __('Home') }}</a></li>
                            @endif
                            @if ($ps->blog == 1)
                                <li><a href="{{ route('front.blog') }}">{{ __('Blog') }}</a></li>
                            @endif
                            @if ($ps->faq == 1)
                                <li><a href="{{ route('front.faq') }}">{{ __('FAQ') }}</a></li>
                            @endif
                            @if ($ps->contact == 1)
                                <li><a href="{{ route('front.contact') }}">{{ __('Contact Us') }}</a></li>
                            @endif
                        </ul>
                    </div>

                    <!-- Customer Care -->
                    <div class="footer-col">
                        <h5 class="footer-heading">@lang('Customer Care')</h5>
                        <ul class="footer-list">
                            @foreach (DB::table('pages')->where('footer', 1)->get() as $data)
                                <li><a href="{{ route('front.vendor', $data->slug) }}">{{ $data->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Newsletter -->
                    <div class="footer-col newsletter-col">
                        <h5 class="footer-heading">@lang('Newsletter')</h5>
                        <p class="newsletter-desc">@lang('Subscribe to get updates on new products')</p>
                        <form action="{{ route('front.subscribe') }}" method="POST" class="newsletter-form-modern">
                            @csrf
                            <div class="form-group-modern">
                                <input type="email" name="email" placeholder="@lang('Your Email')" required>
                                <button type="submit" class="submit-btn-modern">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-grid">
                <p class="copyright-text">{{ $gs->copyright }}</p>
                <div class="payment-icons">
                    <span class="payment-label">@lang('We Accept')</span>
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-cc-amex"></i>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
/* ========================================
   CREATIVE FOOTER DESIGN
   ======================================== */
.creative-footer {
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
    color: rgba(255, 255, 255, 0.9);
    margin-top: 5rem;
    position: relative;
}

.creative-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg,
        transparent 0%,
        rgba(240, 147, 251, 0.5) 20%,
        rgba(79, 172, 254, 0.5) 50%,
        rgba(240, 147, 251, 0.5) 80%,
        transparent 100%);
}

/* ========================================
   MAIN FOOTER
   ======================================== */
.footer-main {
    padding: 4rem 0 2rem;
}

.footer-layout {
    display: grid;
    grid-template-columns: 1.5fr 2fr;
    gap: 4rem;
}

/* Brand Column */
.footer-brand-col {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.brand-wrapper {
    padding-bottom: 1.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-brand-logo {
    max-height: 50px;
    margin-bottom: 1.25rem;
    filter: brightness(1.2);
}

.brand-tagline {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.95rem;
    line-height: 1.7;
    margin: 0;
}

/* Quick Contact */
.quick-contact {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.contact-link {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.contact-link:hover {
    background: rgba(240, 147, 251, 0.1);
    border-color: rgba(240, 147, 251, 0.3);
    transform: translateX(5px);
}

.contact-icon-box {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 10px;
    color: #fff;
    font-size: 1.125rem;
    flex-shrink: 0;
}

.contact-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.contact-label {
    font-size: 0.8rem;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 500;
}

.contact-value {
    color: #fff;
    font-weight: 600;
    font-size: 1rem;
}

/* Social Section */
.social-section {
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.social-heading {
    font-size: 1rem;
    color: #fff;
    margin-bottom: 1rem;
    font-weight: 700;
}

.social-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.social-btn {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.9);
    text-decoration: none;
    font-size: 1.125rem;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.social-btn:hover {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: #fff;
    border-color: transparent;
    transform: translateY(-5px) rotate(-5deg);
    box-shadow: 0 10px 25px rgba(79, 172, 254, 0.4);
}

/* Footer Links Grid */
.footer-links-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 2rem;
}

.footer-col {
    display: flex;
    flex-direction: column;
}

.footer-heading {
    font-size: 1.125rem;
    font-weight: 700;
    color: #fff;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.75rem;
}

.footer-heading::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    border-radius: 2px;
}

.footer-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.footer-list li a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    display: inline-block;
}

.footer-list li a:hover {
    color: #f093fb;
    transform: translateX(5px);
}

/* Newsletter */
.newsletter-col {
    grid-column: span 1;
}

.newsletter-desc {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin-bottom: 1.25rem;
    line-height: 1.6;
}

.newsletter-form-modern {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.form-group-modern {
    position: relative;
    display: flex;
}

.form-group-modern input {
    flex: 1;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 0.875rem 3.5rem 0.875rem 1rem;
    border-radius: 12px;
    color: #fff;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.form-group-modern input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-group-modern input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.12);
    border-color: #f093fb;
    box-shadow: 0 0 0 3px rgba(240, 147, 251, 0.1);
}

.submit-btn-modern {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    width: 42px;
    height: 42px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.submit-btn-modern:hover {
    transform: translateY(-50%) scale(1.05);
    box-shadow: 0 5px 20px rgba(245, 87, 108, 0.4);
}

/* ========================================
   FOOTER BOTTOM
   ======================================== */
.footer-bottom {
    background: rgba(0, 0, 0, 0.2);
    padding: 1.5rem 0;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.footer-bottom-grid {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.copyright-text {
    margin: 0;
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.9rem;
}

.payment-icons {
    display: flex;
    align-items: center;
    gap: 1rem;
    color: rgba(255, 255, 255, 0.6);
}

.payment-label {
    font-size: 0.875rem;
    font-weight: 600;
}

.payment-icons i {
    font-size: 1.75rem;
    opacity: 0.6;
    transition: all 0.3s ease;
}

.payment-icons i:hover {
    opacity: 1;
    transform: translateY(-3px);
}

/* ========================================
   RESPONSIVE DESIGN
   ======================================== */
@media (max-width: 1199px) {
    .footer-layout {
        grid-template-columns: 1fr;
        gap: 3rem;
    }

    .footer-links-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767px) {
    .footer-main {
        padding: 3rem 0 1.5rem;
    }

    .footer-layout {
        gap: 2rem;
    }

    .footer-links-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }

    .footer-heading::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .footer-col {
        text-align: center;
    }

    .footer-list {
        align-items: center;
    }

    .quick-contact {
        align-items: stretch;
    }

    .social-buttons {
        justify-content: center;
    }

    .footer-bottom-grid {
        flex-direction: column;
        text-align: center;
    }

    .payment-icons {
        flex-wrap: wrap;
        justify-content: center;
    }
}

@media (max-width: 575px) {
    .creative-footer {
        margin-top: 3rem;
    }

    .footer-brand-logo {
        max-height: 45px;
    }

    .contact-link {
        padding: 0.875rem;
    }

    .contact-icon-box {
        width: 40px;
        height: 40px;
        font-size: 1rem;
    }

    .social-btn {
        width: 42px;
        height: 42px;
        font-size: 1rem;
    }

    .payment-icons i {
        font-size: 1.5rem;
    }
}
</style>
