@extends($extends)

@section('message-list')

  @if(isset($chat))

    <div class="message-list-head pad-1">
      <div class="rowg">
        <div class="col-ft">
          <span class="img unloaded message-chat-image-url" style="width:48px;height:48px" data-src="{{ $chat->avatar_image_url }}"></span>
        </div>
        <div class="col-st">
          <h3>{{ $chat->name ?? $chat->key }}</h3><br />
          <label>{{ $chat->name ? $chat->key : '' }}</label>
        </div>
      </div>
    </div>

    <div class="message-list-body v-scrollable pad-2 vpadt-0 chat-{{ $chat->id }}">
      <form method="get" class="async">
        <div class="message-list-content">
          @component('andiwijaya::components.chat-admin-message-list', [ 'items'=>$chat->latest_messages ])@endcomponent
        </div>
      </form>
    </div>

    <div class="message-list-foot pad-1">

      @if($chat->status == \Andiwijaya\AppCore\Models\ChatDiscussion::STATUS_OPEN)
        <form method="post" class="async" action="{{ $path }}" data-onsuccess="$('input[name=message]', this).val('');$('.images-cont', this).html('')">
          @csrf

          <input type="hidden" name="id" value="{{ $chat->id }}" />

          <div class="row nowrap valign-middle">
            <div class="col-ft images-cont"></div>
            <div class="col-st">
              <div class="textbox">
                <input type="text" name="text" placeholder="Ketik pesan disini..."/>
              </div>
            </div>
            <div class="col-ft">
              <button type="button" onclick="$(this).closest('.chat').chat_attach_image()"><label>&nbsp;<span class="icon fa fa-image"></span>&nbsp;</label></button>
              <button class="hpad-1" name="action" value="send-message"><label>Kirim</label></button>
            </div>
          </div>
        </form>
      @else
        <div class="align-center pad-1">
          <h5 class="less">Pesan ini sudah ditutup.</h5>
        </div>
      @endif

    </div>

  @else

    <div class="pad-2"><label>Tidak ada chat yang dipilih</label></div>

  @endif

@endsection

@section('chat-list')

  @if(isset($chats))
    @if(count($chats) > 0)
      @foreach($chats as $idx=>$item)
        @component('andiwijaya::components.chat-item', [ 'idx'=>$idx, 'item'=>$item, 'path'=>$path ])@endcomponent
        @if($idx < count($chats) - 1)<div style="height:1px;background:#eee"></div>@endif
      @endforeach
    @else
      <div class="pad-1 no-chat-item"><label>Tidak ada chat</label></div>
    @endif
  @endif

@endsection

@section('info')

  <div class="row0">

    <div class="col-12 align-center">
      <div class="info-head pad-1">
        <span class="tabs less" data-cont=".tab-cont">
          <span class="item active">Informasi</span><span class="item">Histori</span>
        </span>
      </div>
    </div>

    <div class="col-12 tab-cont">

      <div class="info-tab-cont hpad-3 v-scrollable">
      </div>

      <div class="info-tab-cont pad-2 v-scrollable">

      </div>

    </div>

  </div>

@endsection

@section('content')

  @if(isset($chats))

    <div class="chat" style="height:{{ $height }}">

      <div class="row chat-head">
        <div class="col-12">
          <div class="row valign-middle">
            <div class="col-8">
              <h3>Pesan</h3>
            </div>
            <div class="col-4 align-right">
              <form method="get" action="{{ $path }}">
                <button class="min" name="action" value="download">
                  <label class="more">
                    <span class="fa fa-download"></span>
                    Download
                  </label>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row0 chat-body" style="background:#fff">

        <div class="col-3">
          <form method="get" class="async" action="{{ $path }}">
            <div class="chat-list" style="border-right:solid 1px #f5f5f5;">
              <div class="chat-list-head align-center pad-1">
                <span class="tabs less" data-cont=".tab-cont">
                  <span class="item{{ \Illuminate\Support\Facades\Session::get('chat.display') == 'unreplied' ? ' active' : '' }}" onclick="window.location = '?display=unreplied'">Belum Dibalas
                  </span><span class="item{{ \Illuminate\Support\Facades\Session::get('chat.display') != 'unreplied' ? ' active' : '' }}" onclick="window.location = '?display=all'">Semua</span>
                </span>
              </div>
              <div class="chat-list-body pad-1 v-scrollable">
                @yield('chat-list')
              </div>
            </div>
          </form>
        </div>

        <div class="col-5">
          <div class="message-list">

            @yield('message-list')

          </div>
        </div>

        <div class="col-4">
          <div class="info-card" style="border-left:solid 1px #f5f5f5;height:60vh">

            @yield('info')

          </div>
        </div>

      </div>

    </div>
    <script>

      var channels = [
        'chat-list'
      ];
      @if(isset($chat->id)) channels[1] = 'discussion-{{ $chat->id }}'; @endif

      var socket = $.wsConnect('{{ env('UPDATER_HOST') }}',
          function(){
            var socket = this;
            $(channels).each(function(){
              socket.emit('join', this);
            })
          },
          function(channel, message){
            $.process_xhr_response(eval('(' + message + ')'));
          }
        );

    </script>

  @endif

@endsection