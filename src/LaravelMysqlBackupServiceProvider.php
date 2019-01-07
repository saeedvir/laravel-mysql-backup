<?php

namespace LaravelMysqlBackup;

use Illuminate\Support\ServiceProvider;

class LaravelMysqlBackupServiceProvider extends ServiceProvider
{
	protected $commands = [
        LaravelMysqlBackupCommand::class,
    ];

    public function boot()
    {
        # code...
    }

    public function register()
    {
		$this->commands($this->commands);

    }
}