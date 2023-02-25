<?php

namespace Ashiful\Themes;

use Ashiful\Themes\Models\Theme;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ThemesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

        try{

            $this->loadViewsFrom(__DIR__.'/../resources/views', 'themes');

            $theme = '';

            if (Schema::hasTable('themes')) {
                $theme = $this->rescue(function () {
                    return Theme::where('active', '=', 1)->first();
                });
                if(Cookie::get('theme')){
                    $theme_cookied = Theme::where('folder', '=', Cookie::get('theme'))->first();
                    if(isset($theme_cookied->id)){
                        $theme = $theme_cookied;
                    }
                }
            }

            view()->share('theme', $theme);

            $this->themes_folder = config('themes.themes_folder', resource_path('views/themes'));

            $this->loadDynamicMiddleware($this->themes_folder, $theme);

            // Make sure we have an active theme
            if (isset($theme)) {
                $this->loadViewsFrom($this->themes_folder.'/'.@$theme->folder, 'theme');
            }
            $this->loadViewsFrom($this->themes_folder, 'themes_folder');

        } catch(\Exception $e){
            return $e->getMessage();
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('themes.php'),
            ], 'config');


        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        try {
            DB::connection()->getPdo();
            $this->addThemesTable();
        } catch (\Exception $e) {
            Log::error("Error connecting to database: ".$e->getMessage());
        }



//        $router = $this->app['router'];
//        $router->pushMiddlewareToGroup('web', ServiceMiddleware::class);
//
//        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
//        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
//        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'service');
//        $this->loadViewsFrom(resource_path('/views/vendors/service'), 'service');
//
//        $this->publishes([
//            __DIR__.'/../public' => public_path('vendor/spondonit'),
//            __DIR__.'/../resources/views' => resource_path('views/vendors/service'),
//        ], 'spondonit');
//
//        $this->commands([
//            MigrateStatusCommand::class,
//        ]);

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');



        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'themes');

        // Register the main class to use with the facade
        $this->app->singleton('themes', function () {
            return new Themes;
        });
        @include __DIR__ . '/Helper/helpers.php';
    }


    private function addThemesTable()
    {

        if (!Schema::hasTable('themes')) {
            Schema::create('themes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->string('folder', 191)->unique();
                $table->boolean('active')->default(false);
                $table->double('version',11,2)->default(1.00);
                $table->timestamps();
            });

            Schema::create('theme_options', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('theme_id')->unsigned()->index();
                $table->foreign('theme_id')->references('id')->on('themes')->onDelete('cascade');
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }
    }

    private function loadDynamicMiddleware($themes_folder, $theme){
        if (empty($theme)) {
            return;
        }
        $middleware_folder = $themes_folder . '/' . $theme->folder . '/middleware';
        if(file_exists( $middleware_folder )){
            $middleware_files = scandir($middleware_folder);
            foreach($middleware_files as $middleware){
                if($middleware != '.' && $middleware != '..'){
                    include($middleware_folder . '/' . $middleware);
                    $middleware_classname = 'Themes\\Middleware\\' . str_replace('.php', '', $middleware);
                    if(class_exists($middleware_classname)){
                        // Dynamically Load The Middleware
                        $this->app->make('Illuminate\Contracts\Http\Kernel')->prependMiddleware($middleware_classname);
                    }
                }
            }
        }
    }


    function rescue(callable $callback, $rescue = null)
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            report($e);
            return value($rescue);
        }
    }
}
