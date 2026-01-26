{{-- Global SEO Schemas - Organization & WebSite --}}
{{-- These schemas appear on every page --}}

{{-- Organization Schema --}}
@if(isset($organizationSchema))
{!! $organizationSchema->toScript() !!}
@elseif(isset($gs))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": {{ json_encode($gs->site_name ?? config('app.name')) }},
    "url": {{ json_encode(url('/')) }},
    "logo": {{ json_encode(asset('assets/images/' . ($gs->logo ?? 'logo.png'))) }}
    @if(isset($seo) && !empty($seo->meta_description))
    ,"description": {{ json_encode($seo->meta_description) }}
    @endif
    ,"contactPoint": {
        "@type": "ContactPoint",
        "contactType": "customer service",
        "availableLanguage": ["Arabic", "English"]
    }
    @if(!empty($socialLinksArray))
    ,"sameAs": {!! json_encode($socialLinksArray) !!}
    @endif
}
</script>
@endif

{{-- WebSite Schema with SearchAction --}}
@if(isset($websiteSchema))
{!! $websiteSchema->toScript() !!}
@elseif(isset($gs))
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": {{ json_encode($gs->site_name ?? config('app.name')) }},
    "url": {{ json_encode(url('/')) }},
    "potentialAction": {
        "@type": "SearchAction",
        "target": {
            "@type": "EntryPoint",
            "urlTemplate": {{ json_encode(url('/category?search={search_term_string}')) }}
        },
        "query-input": "required name=search_term_string"
    }
}
</script>
@endif
