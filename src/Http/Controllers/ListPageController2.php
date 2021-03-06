<?php

namespace Andiwijaya\AppCore\Http\Controllers;

use Andiwijaya\AppCore\Events\ModelEvent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class ListPageController2 extends ActionableController{

  public $model = null;

  public $title = '';
  public $channel = '';

  public $extends = '';
  public $view = 'andiwijaya::list-page';
  public $view_grid_head = 'andiwijaya::components.list-page-grid-head';
  public $view_grid_item = 'andiwijaya::components.list-page-grid-item';
  public $view_feed_item = 'andiwijaya::components.list-page-feed-item';

  public $exportable = true;

  protected $meta;

  public $sortable = [
    //'name'=>[ 'text'=>'Project Name'],
  ];

  public $filterable = [
    //'is_active'=>[ 'text'=>'Active', 'type'=>'array', 'items'=>[ 0=>'Inactive', 1=>'Active' ] ],
    //'business_id'=>[ 'text'=>'Business Name', 'type'=>'builder', 'class'=>Business::class, 'item_text_key'=>'name' ],
    //'date'=>'Date|date-range'
  ];

  public function __construct()
  {
    $this->meta = [
      'id'=>Str::slug($this->title),
    ];

    View::share('meta', $this->meta);
  }

  public function applySorts($builder, array $sorts)
  {
    foreach($sorts as $sort){

      list($key, $type) = explode(',', $sort);

      $builder->orderBy($key, $type);

    }
  }

  public function getParams() : array
  {
    return [];
  }

  public function handle(ModelEvent $event){

    if($this->channel && $this->model && $this->model == $event->class){

      $updates = [];

      $online = count(Redis::pubsub('channels', $this->channel)) > 0;

      if($online) {

        if($event->type == ModelEvent::TYPE_REMOVE){

          $updates[] = [
            'type'=>'script',
            'script'=>implode(';', [
              "$('#" . Str::slug($this->title) . "-page .grid-content-tbody tr[data-id={$event->id}]').remove()",
              "$('#" . Str::slug($this->title) . "-page .feed-content .item[data-id={$event->id}]').remove()",
            ])
          ];
        }

        else{

          $model = $this->model::whereId($event->id)->first();

          $updates[] = [
            'type' => 'element',
            'html' => view($this->view_grid_item, ['item' => $model ])->render(),
            'parent' => '#' . Str::slug($this->title) . '-page .grid-content-tbody',
            'mode' => 'prepend'
          ];

          $updates[] = [
            'type' => 'element',
            'html' => view($this->view_feed_item, ['item' => $model ])->render(),
            'parent' => '#' . Str::slug($this->title) . '-page .feed-content',
            'mode' => 'prepend'
          ];
        }

        Redis::publish(
          Str::slug(env('APP_NAME')) . '-' . $this->channel,
          json_encode($updates)
        );
      }
    }

  }


  public function fetch(Request $request){

    $action = isset(($actions = explode('|', $request->get('action')))[0]) ? $actions[0] : '';

    $builder = $this->datasource($request);
    $row_per_page = 15;

    $items = $builder->limit($row_per_page + 1)->get();

    if(count($items) == $row_per_page + 1){
      $next_items_after = $items[count($items) - 2]->id;
      $items = $items->slice(0, $row_per_page);
    }
    else
      $next_items_after = 0;

    $params = [
      'extends'=>$this->extends,
      'title'=>$this->title,
      'view_grid_head'=>$this->view_grid_head,
      'view_grid_item'=>$this->view_grid_item,
      'view_feed_item'=>$this->view_feed_item,
      'items'=>$items,
      'next_items_after'=>$next_items_after,
      'search'=>$request->get('search'),
      'sorts'=>$request->get('sorts', []),
      'exportable'=>$this->exportable,
      'sortable'=>$this->sortable,
      'filterable'=>$this->filterable,
      'channel'=>$this->channel
    ];

    $params = array_merge($params, $this->getParams());

    if($action && $request->ajax()){

      $sections = view($this->view, $params)->renderSections();

      return [
        "#{$this->meta['id']}-grid .grid-thead"=>view($this->view_grid_head, [ 'sorts'=>$params['sorts'], 'sortable'=>$params['sortable'] ])->render(),
        "#{$this->meta['id']}-grid-content .grid-content-tbody"=>$sections['desktop-list-items'],
        "#{$this->meta['id']}-grid-content .load-more-cont"=>$sections['desktop-list-load-more'],
        "#{$this->meta['id']}-feed .mobile-list-cont"=>$sections['mobile-list'],
      ];
    }
    else{

      return view($this->view, $params);
    }
  }

  public function view(){

    return call_user_func_array([ $this, 'fetch' ], func_get_args());
  }

  public function loadMore(Request $request){

    $action = isset(($actions = explode('|', $request->get('action')))[0]) ? $actions[0] : '';

    $builder = $this->datasource($request);
    $row_per_page = 15;

    $after_id = ($load_more_params = explode(',', $actions[1]))[0] ?? 0;

    $device_type = $load_more_params[1] ?? '';
    $items = collect([]);
    $builder->chunk(1000, function($rows) use(&$items, $after_id, $row_per_page){

      foreach($rows as $row)
      {
        if($after_id > 0){
          if($after_id == $row->id)
            $after_id = null;
        }
        else
          $items->add($row);

        if(count($items) == $row_per_page + 1) break;
      }

      if(count($items) == $row_per_page + 1) return false;
    });

    if(count($items) == $row_per_page + 1){

      $next_items_after = $items[count($items) - 2]->id;

      $items = $items->slice(0, $row_per_page);
    }
    else
      $next_items_after = 0;

    if($action && $request->ajax()) {

      $return = [];

      if($device_type == 'sm'){
        $html = [];
        foreach($items as $idx=>$item){
          $html[] = view($this->view_feed_item, [ 'item'=>$item, 'idx'=>$idx ]);
        }

        $return["#{$this->meta['id']}-feed .feed-content"] = '>>' . implode('', $html);
      }
      else{
        $html = [];
        foreach($items as $idx=>$item){
          $html[] = view($this->view_grid_item, [ 'item'=>$item, 'idx'=>$idx ]);
        }

        $return['.grid-content tbody'] = '>>' . implode('', $html);
      }

      $load_more_html = $next_items_after > 0 ?
        "<div class=\"pad-1 align-center\"><button class=\"min load-more-btn\" name=\"action\" value=\"load-more|{$next_items_after},{$device_type}\"><label class=\"less\">Load More</label></button></div>" :
        '';
      $return["#{$this->meta['id']}-feed .load-more-cont"] = $load_more_html;
      $return["#{$this->meta['id']}-grid-content .load-more-cont"] = $load_more_html;

      return $return;
    }
  }

  public function reset(Request $request){

    $action = isset(($actions = explode('|', $request->get('action')))[0]) ? $actions[0] : '';

    $builder = $this->datasource($request);
    $row_per_page = 15;

    $items = $builder->limit($row_per_page + 1)->get();

    if(count($items) == $row_per_page + 1){

      $next_items_after = $items[count($items) - 2]->id;

      $items = $items->slice(0, $row_per_page);
    }
    else
      $next_items_after = 0;

    $params = [
      'extends'=>$this->extends,
      'title'=>$this->title,
      'view_grid_head'=>$this->view_grid_head,
      'view_grid_item'=>$this->view_grid_item,
      'view_feed_item'=>$this->view_feed_item,
      'items'=>$items,
      'next_items_after'=>$next_items_after,
      'search'=>$request->get('search'),
      'sorts'=>$request->get('sorts', []),
      'exportable'=>$this->exportable,
      'sortable'=>$this->sortable,
      'filterable'=>$this->filterable,
      'channel'=>$this->channel
    ];

    $params = array_merge($params, $this->getParams());

    $sections = view($this->view, $params)->renderSections();

    return [
      '.filter-cont'=>$sections['filter'],
      '.grid-thead'=>view($this->view_grid_head, [ 'sorts'=>$params['sorts'], 'sortable'=>$params['sortable'] ])->render(),
      '.grid-content-tbody'=>$sections['desktop-list-items'],
      '.load-more-cont'=>$sections['desktop-list-load-more'],
      '.mobile-list-cont'=>$sections['mobile-list'],
      'script'=>implode(';', [
        "$('.list-search').val('')"
      ])
    ];
  }

  public function datasource(Request $request){

    $builder = $this->model::select('*');

    if($request->get('action') != 'reset'){

      if (method_exists(new $this->model, 'scopeFilter'))
        $builder->filter($request->all());

      if (method_exists(new $this->model, 'scopeSearch') && strlen($request->get('search')) > 0)
        $builder->search($request->get('search'));

      $this->applySorts($builder, $request->get('sorts', [ 'updated_at,desc' ]));
    }

    return $builder;
  }

}