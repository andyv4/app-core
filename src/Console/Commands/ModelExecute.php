<?php

namespace Andiwijaya\AppCore\Console\Commands;

use Andiwijaya\AppCore\Facades\WebCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

class ModelExecute extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'model {name} {method} {--instance-id=} {--param=} {--background=0}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Find model and execute method';

  /**
   * Create a new command instance.
   *
   * @return void
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Execute the console command.
   *
   * @return mixed
   */
  public function handle()
  {
    $namespace = 'App\\Models';
    $timeout = 600;
    $is_background = $this->option('background');
    $model = $this->argument('name');
    $method = $this->argument('method');
    $instance_id = $this->option('instance-id');
    $param = trim($this->option('param'));

    if($is_background){
      $process = new Process("php artisan model {$model} {$method} --instance-id={$instance_id} --param={$param} > /dev/null 2>&1 &", base_path());
      $process->setTimeout($timeout);
      $process->run();
      return;
    }

    $model = $namespace . "\\" . collect(explode('-', $model))->map(function($value, $key){
        return ucwords($value);
      })->implode('');
    $methodName = collect(explode('-', $method))->map(function($item, $key){ return $key > 0 ? ucwords($item) : strtolower($item); })->implode('');
    $param = in_array(substr($param, 0, 1), [ '{', '[' ]) ? json_decode($param, 1) : $param;

    try{

      if($instance_id > 0){

        $instance = $model::findOrFail($instance_id);
        call_user_func_array([ $instance, $methodName ], [ $param ]);
      }
      else{

        call_user_func_array([ $model, $methodName ], [ $param ]);
      }

    }
    catch(\Exception $ex){

      $this->error($ex->getMessage());
    }

    $this->info('Completed in ' . (microtime(1) - LARAVEL_START) . 's');
  }
}
