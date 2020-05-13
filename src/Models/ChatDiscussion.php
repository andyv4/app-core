<?php

namespace Andiwijaya\AppCore\Models;

use Andiwijaya\AppCore\Models\Traits\FilterableTrait;
use Andiwijaya\AppCore\Models\Traits\LoggedTraitV3;
use Andiwijaya\AppCore\Events\ChatEvent;
use App\Mail\ChatDiscussionCustomerNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class ChatDiscussion extends Model
{
  use LoggedTraitV3, FilterableTrait;

  protected $table = 'chat_discussion';

  protected $filter_searchable = [
    'id:=',
    'key:like',
    'name:like',
  ];

  protected $fillable = [ 'status', 'avatar_image_url', 'key', 'name', 'title', 'extra', 'unreplied_count', 'last_replied_at' ];

  protected $attributes = [
    'status'=>self::STATUS_OPEN,
    'unreplied_count'=>0
  ];

  protected $casts = [
    'extra'=>'array'
  ];

  const STATUS_OPEN = 1;
  const STATUS_CLOSED = -1;

  public function messages()
  {
    return $this->hasMany('Andiwijaya\AppCore\Models\ChatMessage', 'discussion_id', 'id');
  }

  public function getLatestMessagesAttribute(){

    return ChatMessage::whereDiscussionId($this->id)
      ->orderBy('created_at', 'desc')
      ->limit(5)
      ->get()
      ->reverse();

  }

  public function getLatestMessageAttribute(){

    return ChatMessage::whereDiscussionId($this->id)->orderBy('created_at', 'desc')->first();

  }


  public function end(){

    if($this->status == self::STATUS_CLOSED)
      exc(__('models.chat-discussion-already-closed'));

    try{
      $this->status = self::STATUS_CLOSED;

      parent::save();
    }
    catch(\Exception $ex){
      exc(__('models.chat-discussion-error', [ 'message'=>$ex->getMessage() ]));
    }

  }

  public function postSave()
  {
    event(new ChatEvent($this->wasRecentlyCreated ? ChatEvent::TYPE_NEW_CHAT : ChatEvent::TYPE_UPDATE_CHAT, $this));
  }

  public function calculate()
  {
    $last_replied = ChatMessage::where([
      'discussion_id'=>$this->id,
      'direction'=>ChatMessage::DIRECTION_OUT
    ])
      ->orderBy('created_at', 'desc')
      ->first();

    $this->unreplied_count = isset($last_replied->id) ?
      ChatMessage::whereDiscussionId($this->id)->where('id', '>', $last_replied->id)->where('direction', ChatMessage::DIRECTION_IN)->count() :
      ChatMessage::whereDiscussionId($this->id)->where('direction', ChatMessage::DIRECTION_IN)->count();

    parent::save();
  }

  public function sendEmailNotification(){

    if(filter_var($this->key, FILTER_VALIDATE_EMAIL)){

      if(isset($this->latest_message->id) && $this->latest_message->direction == ChatMessage::DIRECTION_OUT){

        Mail::to($this->key)
          ->bcc(env('MAIL_BCC'))
          ->queue(new ChatDiscussionCustomerNotification($this->id));
      }
    }

  }


  public static function notifyUnsent($callback = null){

    $discussions = ChatDiscussion::whereExists(function($query){
      $query->select(DB::raw(1))
        ->from('chat_message')
        ->whereRaw('chat_message.discussion_id = chat_discussion.id AND unsent = 1 AND notified <> 1');
    })
      ->get();

    if(is_callable($callback))
      call_user_func_array($callback, [ "Discussions require notification: " . count($discussions) ]);

    foreach($discussions as $discussion){

      $offline = count(Redis::pubsub('channels', "customer-discussion-{$discussion->id}")) <= 0;

      if(is_callable($callback))
        call_user_func_array($callback, [ "{$discussion->key}, " . ($offline ? 'offline' : 'online') ]);

      if($offline){
        $discussion->sendEmailNotification();

        ChatMessage::where([
          'discussion_id'=>$discussion->id,
          'unsent'=>1
        ])
          ->update([ 'notified'=>1 ]);

        if(is_callable($callback))
          call_user_func_array($callback, [ "Notification to {$discussion->key} sent." ]);
      }
    }
  }

}
