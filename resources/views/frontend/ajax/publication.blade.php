    <div class="row">
        @foreach($publications as $publication)

        <div class="col-lg-6 col-md-6 mycol">
            <div class="single-blog">
                <div class="img">
                <img src="{{  $publication->photo ? asset('assets/images/publications/'.$publication->photo):asset('assets/images/noimage.png') }}" alt="">
                <div class="date">
                {{ date('d M, Y',strtotime($publication->created_at)) }}
                </div>
                </div>
                <div class="content">
                <a href="{{ route('front.publicationshow',$publication->id) }}">
                    <h4 class="title">
                        {{ mb_strlen($publication->title,'UTF-8') > 200 ? mb_substr($publication->title,0,200,'UTF-8')."...":$publication->title }}
                    </h4>
                </a>
                <ul class="top-meta">
                    <li>
                    <a href="javascript:;"><i class="far fa-comments"></i> {{ $publication->source }} </a>
                    </li>
                    <li>
                    <a href="javascript:;">
                        <i class="far fa-eye"></i> {{ $publication->views }}
                    </a>
                    </li>
                </ul>
                </div>
            </div>
        </div>

        @endforeach

    </div>

    <div class="page-center">

        {!! $publications->links() !!}

    </div>
