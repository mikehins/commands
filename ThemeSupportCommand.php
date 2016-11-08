<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ThemeSupportCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'krobar:theme';
	
	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add Theme support for frontend and backend';
	
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
		//$this->app['view']->setFinder($this->app['theme.finder']);
		$this->makeViewFinder()
			->setFinderInAppServiceProvider()
			->generateConfigFile();
		
		$this->makeDirectory(base_path('/public/themes'));
		$this->makeDirectory(base_path('/public/themes/admin'));
		$this->makeDirectory(base_path('/public/themes/default'));
		$this->makeDirectory(base_path('/public/themes/admin/assets'));
		$this->makeDirectory(base_path('/public/themes/admin/views'));
		$this->makeDirectory(base_path('/public/themes/default/assets'));
		$this->makeDirectory(base_path('/public/themes/default/views'));;
		
		if(file_exists(base_path('resources/views/welcome.blade.php'))) {
			rename(base_path('resources/views/welcome.blade.php'), base_path('public/themes/default/views/welcome.blade.php'));
		}
	}
	
	protected function setFinderInAppServiceProvider()
	{
		$provider = file(app_path() . '/Providers/AppServiceProvider.php');
		
		if(preg_grep('/theme\.finder/', $provider)){
			return $this;
		}
	
		array_splice($provider, 4, 0, 'use App\View\ThemeViewFinder;' . "\n");
		
		$addToBootMethod = '$this->app[\'view\']->setFinder($this->app[\'theme.finder\']);';
		
		array_splice($provider, key(preg_grep('/public function boot/', $provider)) + 2, 0, "\t\t" . $addToBootMethod . "\n");
		
		$register = <<<EOF
		\$this->app->singleton('theme.finder', function (\$app) {
			\$finder = new ThemeViewFinder(\$app['files'], \$app['config']['view.paths']);
			\$config = \$app['config']['theme'];
			\$finder->setBasePath(\$app['path.public'] . '/' . \$config['folder']);
			\$finder->setActiveTheme(\$config['active']);
			return \$finder;
		});
EOF;
		
		array_splice($provider, key(preg_grep('/public function register/', $provider)) + 2, 0, $register . "\n");
			
		file_put_contents(app_path() . '/Providers/AppServiceProvider.php', implode("", $provider));
		
		return $this;
	}
	
	protected function makeViewFinder()
	{
		$destination = app_path() . '/View';
		
		$this->makeDirectory($destination)->makeFile($destination . '/ThemeViewFinder.php', $this->generateThemeViewFinder());
		
		return $this;
	}
	
	protected function makeDirectory($path)
	{
		if (is_dir($path) && !$this->option('force')) {
			return $this;
		}
		
		mkdir($path);
		
		return $this;
	}
	
	protected function makeFile($path, $content)
	{
		if (file_exists($path) && !$this->option('force')) {
			return $this;
		}
		
		file_put_contents($path, $content);
		
		return $this;
	}
	
	protected function generateConfigFile()
	{
		$config = <<<EOF
<?php

return [
	'folder' => 'themes',
	'active' => 'default',
    'admin' => [
	    'assets' => '/themes/admin/assets/'
    ]
];
EOF;
		$this->makeFile(base_path('config/theme.php'), $config);
		
		return $this;
	}
	
	protected function generateThemeViewFinder()
	{
		return <<<EOF
<?php

namespace App\View;

use Illuminate\View\FileViewFinder;

class ThemeViewFinder extends FileViewFinder
{
	protected \$activeTheme;

	protected \$basePath;

	public function setBasePath(\$path)
	{
		\$this->basePath = \$path;
	}

	public function setActiveTheme(\$theme)
	{
		\$this->activeTheme = \$theme;

		array_unshift(\$this->paths, \$this->basePath . '/admin/views');
		array_unshift(\$this->paths, \$this->basePath . '/' . \$theme . '/views');
	}
}
EOF;
		
	}
	
}
