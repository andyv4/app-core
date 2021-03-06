@if(strlen($extends) > 0)
  @extends($extends)
@endif

@section('upper-options')
  @if($exportable)
    <span class="relative">
      <button class="max has-right" type="button" onclick="$.fetch('//{{ \Illuminate\Support\Facades\Request::getHost() . '/' .  \Illuminate\Support\Facades\Request::path() }}/create')"><label><span class="fa fa-plus selectable hmarr-05"></span> New...</label></button><button class="max has-left" type="button" data-click-popup=".action-popup">
        <label>&nbsp;<span class="fa fa-caret-down selectable"></span>&nbsp;</label>
      </button>
      <div class="action-popup popup" data-ref=".has-right">
        <button class="item min block" name="action" value="export" data-async="0"><span class="fa fa-cloud-download-alt hmarr-05"></span> Export...</button>
      </div>
    </span>
  @else
    <button class="max hpad-1" type="button" onclick="$.fetch('//{{ \Illuminate\Support\Facades\Request::getHost() . '/' .  \Illuminate\Support\Facades\Request::path() }}/create')"><label><span class="fa fa-plus hmarr-05"></span>New...</label></button>
  @endif
@endsection

@section('upper')

  <div class="hidden-sm head">

    <div class="pad-2">
      <div class="srow valign-middle">
        <span class="hpadl-1">
          <h1>{{ $title }}</h1>
        </span>
        <div class="align-right">
          <button class="hidden" name="action" value="fetch"></button>

          @yield('upper-options')

          <span class="textbox mw30v">
            <input type="text" class="list-search" name="search" value="{{ $search }}"/>
            <span class="icon fa fa-search"></span>
          </span>
          <button class="hpad-1" type="button" data-action="filterbar-toggle"><label><span class="fa fa-sliders-h"></span></label></button>
        </div>
      </div>
    </div>

    <div class="hpad-1 vmart-1">
      <div class="grid" data-grid-content=".grid-content" id="{{ $meta['id'] ?? '' }}-grid">
        <table>
          <thead class="grid-thead">
          @component($view_grid_head, [ 'sorts'=>$sorts, 'sortable'=>$sortable ])@endcomponent
          </thead>
        </table>
      </div>
    </div>

  </div>

@endsection

@section('desktop-list-items')

  @if(count($items) > 0)
    @foreach($items as $idx=>$item)
      @component($view_grid_item, [ 'item'=>$item, 'idx'=>$idx ])@endcomponent
    @endforeach
  @else
    <tr class="no-data">
      <th colspan="100" class="align-center"><strong>Tidak ada data</strong></th>
    </tr>
  @endif

@endsection

@section('desktop-list-load-more')

  @if($next_items_after > 0)
    <div class="pad-1 align-center"><button class="min load-more-btn" name="action" value="load-more|{{ $next_items_after }}"><label class="less">Load More</label></button></div>
  @endif

@endsection

@section('mobile-list')

  <div class="vpad-1 mobile-content">
    <div class="feed" id="{{ $meta['id'] ?? '' }}-feed">
      <div class="feed-content">
        @foreach($items as $idx=>$item)
          @component($view_feed_item, [ 'item'=>$item, 'idx'=>$idx ])@endcomponent
        @endforeach
      </div>
    </div>
    <div class="load-more-cont">
      @if($next_items_after > 0)
        <div class="pad-1 align-center"><button class="min load-more-btn" name="action" value="load-more|{{ $next_items_after }},sm"><label class="less">Load More</label></button></div>
      @endif
    </div>
  </div>

  <div class="main-btn">
    <div><span class="icon icon-plus" onclick="$.fetch('//{{ \Illuminate\Support\Facades\Request::getHost() . '/' .  \Illuminate\Support\Facades\Request::path() }}/create')"></span></div>
  </div>

@endsection

@section('filter')

  <div class="filterbar">
    <div class="pad-lg-1 hpad-sm-1">

      <div class="row2 valign-middle">
        <div class="col-10">
          <h5>Edit View</h5>
        </div>
        <div class="col-2 align-right">
          <span class="fa fa-times selectable pad-1" data-action="filterbar-close"></span>
        </div>
      </div>

      @if(count($filterable) > 0)
        <div class="vmart-1">
          <div class="row">

            <div class="col-12">
              <div class="pad-1 vmarb-1 bordered-bottom">
                <strong class="less">Filters</strong>
              </div>
            </div>

            @foreach($filterable as $key=>$param)
              {!!  list_page_filter_item($key, $param) !!}
            @endforeach

          </div>
        </div>
      @endif

      @if(count($sortable) > 0)
        <div class="row vmart-1 hidden-lgx">
          <div class="col-12">

            <div class="pad-1 vmarb-2 bordered-bottom">
              <strong class="less">Sorts</strong>
            </div>

            @foreach($sortable as $key=>$value)
              @if(is_array($value))
                <strong class="hpad-1">{{ $value['text'] ?? $key }}</strong>
                <div class="vmarb-1">
                <span class="choice">
                  <input id="list-sc-{{ $key }}-asc" class="sort-control" type="radio" name="sorts[]" value="{{ $key }},asc" onchange="$('button[value=view]', $(this).closest('form')).click();"/>
                  <label for="list-sc-{{ $key }}-asc"><span class="checker"><span></span></span> Asc</label>
                </span>
                  <span class="choice">
                  <input id="list-sc-{{ $key }}-desc" class="sort-control" type="radio" name="sorts[]" value="{{ $key }},desc" onchange="$('button[value=view]', $(this).closest('form')).click();"/>
                  <label for="list-sc-{{ $key }}-desc"><span class="checker"><span></span></span> Desc</label>
                </span>
                </div>
              @elseif(is_scalar($value))
                <strong class="hpad-1">{{ $value }}</strong>
                <div class="vmarb-1">
                <span class="choice">
                  <input id="list-sc-{{ $value }}-asc" class="sort-control" type="radio" name="sorts[]" value="{{ $value }},asc" onchange="$('button[value=view]', $(this).closest('form')).click();"/>
                  <label for="list-sc-{{ $value }}-asc"><span class="checker"><span></span></span> Asc</label>
                </span>
                  <span class="choice">
                  <input id="list-sc-{{ $value }}-desc" class="sort-control" type="radio" name="sorts[]" value="{{ $value }},desc" onchange="$('button[value=view]', $(this).closest('form')).click();"/>
                  <label for="list-sc-{{ $value }}-desc"><span class="checker"><span></span></span> Desc</label>
                </span>
                </div>
              @endif
            @endforeach

          </div>
        </div>
      @endif

      <div class="row vmart-1">

        <div class="col-12">
          <div class="vmart-3">
            <button class="hpad-1 max apply-filter" name="action" value="view"><label>Apply</label></button>
            <button class="hpad-1" name="action" value="reset"><label>Reset</label></button>
          </div>
        </div>

      </div>

      <br /><br />

    </div>
  </div>

@endsection

@section('content')

  <form method="get" class="async">

    <div class="list-page" id="{{ $meta['id'] ?? '' }}-page">

      <div>
        @yield('upper')

        <div class="body hidden-sm desktop-list-cont v-scrollable">
          <div class="pad-1">
            <div class="grid grid-content" id="{{ $meta['id'] ?? '' }}-grid-content">
              <table>
                <thead></thead>
                <tbody class="grid-content-tbody">
                @yield('desktop-list-items')
                </tbody>
              </table>
              <div class="load-more-cont">
                @yield('desktop-list-load-more')
              </div>
            </div>
          </div>
        </div>

        <div class="body hidden-lg hidden-xl after-header mobile-list-cont">
          @yield('mobile-list')
        </div>

        <div class="foot">
          @yield('list-page-foot')
        </div>
      </div>

      <div class="filter-cont">
        @yield('filter')
      </div>

      <div class="content-board-popup-cont"></div>

    </div>

  </form>

  @if(isset($channel) && strlen($channel) > 0)
    <script>
      window.scriptBuffer.push(function(){
        $.wsListen('{{ $channel }}', '{{ env('UPDATER_HOST') }}');
      });
    </script>
  @endif

@endsection

@if(isset($channel) && strlen($channel) > 0)
  @push('head')
    <script type="text/javascript" src="/js/socket.io.js" defer></script>
  @endpush
@endif

@push('body-pre')
  <script type="text/javascript" src="/js/tinymce/tinymce.min.js"></script>
  {{--  <script src="https://cdn.tiny.cloud/1/k53ts3nziyi7rlczx4mysphbzjfojzqb2w3dwt8p0ttjx894/tinymce/5/tinymce.min.js" referrerpolicy="origin"/></script>--}}
@endpush