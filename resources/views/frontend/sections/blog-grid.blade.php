{{--
================================================================================
SECTION PARTIAL: Blog Grid
================================================================================
Receives: $blogs (collection of Blog models)
================================================================================
--}}

<div class="row">
    @foreach($blogs as $blog)
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="muaadh-blog-card">
            <div class="muaadh-blog-img">
                <a href="{{ route('front.blogshow', $blog->slug) }}">
                    <img src="{{ asset('assets/images/blogs/' . $blog->photo) }}"
                         alt="{{ $blog->title }}"
                         loading="lazy">
                </a>
            </div>
            <div class="muaadh-blog-content">
                <div class="muaadh-blog-meta">
                    <span class="muaadh-blog-date">
                        <i class="far fa-calendar-alt"></i>
                        {{ \Carbon\Carbon::parse($blog->created_at)->format('M d, Y') }}
                    </span>
                </div>
                <h5 class="muaadh-blog-title">
                    <a href="{{ route('front.blogshow', $blog->slug) }}">{{ $blog->title }}</a>
                </h5>
                <p class="muaadh-blog-excerpt">
                    {{ \Illuminate\Support\Str::limit(strip_tags($blog->details), 100) }}
                </p>
                <a href="{{ route('front.blogshow', $blog->slug) }}" class="muaadh-blog-link">
                    @lang('Read More') <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
