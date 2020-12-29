<?php

namespace Andiwijaya\AppCore\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\View;

class HTMLResponse implements Responsable {

  protected $data;
  protected $status;
  protected $headers;

  public function __construct($data = [], $status = 200, array $headers = [])
  {
    $this->data = $data;
    $this->status = $status;
    $this->headers = $headers;

    $this->headers['Content-Type'] = 'application/json';
  }

  public function append($target, $html){

    $this->data[] = [ '_type'=>'html', 'html'=>$html, 'mode'=>'append', 'target'=>$target ];
    return $this;
  }

  public function prepend($target, $html){

    $this->data[] = [ '_type'=>'html', 'html'=>$html, 'mode'=>'prepend', 'target'=>$target ];
    return $this;
  }

  public function html($target, $html){

    $this->data[] = [ '_type'=>'html', 'html'=>$html, 'target'=>$target ];
    return $this;
  }

  public function replace($target, $html){

    $this->data[] = [ '_type'=>'html', 'html'=>$html, 'mode'=>'replace', 'target'=>$target ];
    return $this;
  }

  public function text($text, $expr, array $data = []){

    $this->data[] = [ '_type'=>'text', 'text'=>$text, 'target'=>$expr ];
    return $this;
  }

  public function script($script, $id = ''){

    $this->data[] = [ '_type'=>'script', 'script'=>$script, 'id'=>$id ];
    return $this;
  }


  public function alert($text, $type = 'error', $options = []){

    $title = $text['title'] ?? $text;
    $description = $text['description'] ?? '';

    $this->data[] = [
      '_type'=>'alert',
      'type'=>$type,
      'text'=>[ 'title'=>$title, 'description'=>$description ],
      'options'=>$options
    ];
    return $this;
  }

  public function chart($target, $type, array $labels, array $data, array $options = []){

    $colors = [
      '#4A89DC',
      '#E9573F',
      '#3BAFDA',
      '#37BC9B',
      '#F6BB42',
      '#E9573F',
      '#DA4453',
      '#967ADC',
      '#D770AD',
      '#434A54'
    ];

    $datasets = [];
    $counter = 0;
    foreach($data as $idx=>$arr){
      $dataset = [
        'label'=>$idx,
        'data'=>$arr,
        'fill'=>false,
        'borderColor'=>$colors[$counter] ?? 'rgba(0, 0, 0, 1)'
      ];
      $datasets[] = $dataset;

      $counter++;
    }

    $params = [
      '_type'=>$type,
      'data'=>[
        'labels'=>$labels,
        'datasets'=>$datasets
      ],
      'options'=>[
        'scales'=>[
          'yAxes'=>[
            [
              'ticks'=>[
                'beginAtZero'=>true,
                'display'=>false
              ]
            ]
          ]
        ]
      ]
    ];

    $id = 'chart' . uniqid();
    $html[] = "<canvas id='{$id}'></canvas>";
    $this->data[] = [ '_type'=>'html', 'html'=>implode('', $html), 'target'=>$target ];
    $this->data[] = [ '_type'=>'script', 'script'=>"new Chart('{$id}', " . json_encode($params) . ");" ];

    return $this;
  }

  public function grid($target, $data, $columns, array $options = []){

    $onitemclick = $options['onitemclick'] ?? '';

    $html_columns = [];
    foreach($columns as $key=>$column){

      $width = $column['width'] ?? '';
      $align = $column['align'] ?? '';
      $text = $column['text'] ?? '';

      $html_columns[] = "<th width='{$width}' align='{$align}'>{$text}</th>";
    }
    $html_columns[] = "<th></th>";
    $html_columns = implode('', $html_columns);

    $html_data = [];
    $html_data[] = "<tr>";
    foreach($columns as $key=>$column){
      $width = $column['width'] ?? '';
      $html_data[] = "<td width='{$width}'></td>";
    }
    $html_data[] = "<td></td>";
    $html_data[] = "</tr>";
    foreach($data as $obj){
      $html_data[] = "<tr onclick=\"{$onitemclick}\">";
      foreach($columns as $key=>$column){

        $align = $column['align'] ?? '';
        $value = $obj[$key] ?? '';

        if(isset($column['format']))
          $value = call_user_func_array($column['format'], [ $value, $obj ]);

        $html_data[] = "<td align='{$align}' data-key='{$key}'>{$value}</td>";
      }
      $html_data[] = "<td></td>";
      $html_data[] = "</tr>";
    }
    $html_data = implode('', $html_data);


    $html = <<<EOT
 <div data-type="grid">
        <div class="grid-head">
          <table>
            <tr>{$html_columns}</tr>
          </table>
        </div>
        <div class="grid-body">
          <table>
            {$html_data}
          </table>
        </div>
      </div>
EOT;

    $this->data[] = [ '_type'=>'html', 'html'=>$html, 'target'=>$target ];

    return $this;
  }

  public function popup($content, $ref, array $options = []){

    $html = [];

    $html[] = "<div class=\"popup\">";
    $html[] = $content;
    $html[] = "</div>";

    $this->data[] = [ '_type'=>'popup', 'ref'=>$ref, 'html'=>implode('', $html) ];

    return $this;
  }

  /**
   * @param $title
   * @param $target "<selector|top|top-right>"
   * @param array|string[] $options "{ id:<string>, system:<true|false>, description:<string> }"
   * @return $this
   */
  public function notify($title, $target, array $options = [ 'description' => '' ]){

    $this->data[] = [ '_type'=>'notify', 'title'=>$title, 'target'=>$target, 'options'=>$options ];
    return $this;
  }

  public function modal($id, $html, array $options = [ 'init'=>1 ]){

    $this->data[] = [ '_type'=>'modal', 'html'=>$html, 'id'=>$id, 'options'=>$options ];
    return $this;
  }

  public function redirect($url){

    $this->data[] = [ '_type'=>'redirect', 'target'=>$url ];
    return $this;
  }






  public function toResponse($request)
  {
    return response()->json($this->data, $this->status, $this->headers);
  }
}