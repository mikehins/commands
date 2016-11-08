# commands
Commands for Laravel

##ThemeSupportCommand

Add a theme folder inside the public folder to store your frontend and your backend views

```
php artisan krobar:theme
```

And add the command to the ```app/Console/Kernel.php```
```
protected $commands = [
	    \App\Console\Commands\ThemeSupportCommand::class,
    ];
```