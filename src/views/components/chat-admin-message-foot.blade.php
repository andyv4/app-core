@if($discussion->handled_by == $user->id)

  @php $chat_message_templates = App\Models\ChatMessageTemplate::whereDirection(\Andiwijaya\AppCore\Models\ChatMessage::DIRECTION_OUT)->orderBy('id')->get() @endphp

  @if(count($chat_message_templates) > 0)
    <div class="chat-message-templates nowrap hmar-1">
      @foreach($chat_message_templates as $chat_message_template)
        <span class="chat-message-template" onclick="chatadmin_set_template.apply(this)">{{ $chat_message_template->text }}</span>
      @endforeach
    </div>
  @endif

  <div class="srow pad-1 vpadt-0">
    <div>
      <div class="textarea" style="height:2.78rem">
        <textarea rows="1" name="text"></textarea>
      </div>
    </div>
    <span>
      <input type="hidden" name="last_id" />
      <button type="button" class="hmarl-05" onclick="$('.chat-admin').chatadmin_add_file()"><label><span class="fa fa-image"></span></label></button>
      <button class="hpad-1 btn-send-message"><label>Kirim</label></button><button class="hpad-1 btn-send-whatsapp" name="action" value="send-whatsapp"><label><span class="fab fa-whatsapp"></span></label></button>
    </span>
  </div>
  <div class="images repeater hpad-1">
    <div class="template">
      <!--
      <span class='item recently-created'>
        <input type="file" class="hidden" name="images[][file]" accept="image/*" />
        <input type="hidden" class="image-name" name="images[][name]" />
        <span class="img" style="width:24px;height:24px"></span></span>
      </span>
      -->
    </div>
    <div></div>
  </div>
@else
  <div class="align-center">
    <button class="block less" name="action" value="begin-chat"><label>Mulai Chat</label></button>
  </div>
@endif

<input type="hidden" name="id" value="{{ $discussion->id }}" />

