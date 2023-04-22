<?php

namespace Atimrots\AuditLog\Providers;

use Illuminate\Support\ServiceProvider;

class AuditLogProvider extends ServiceProvider
{
	/**
	 * Bootstrap services.
	 *
	 * @return void
	 */
	public function boot(): void {
		$this->publishes([
			__DIR__.'/config/auditlog.php' => config_path('auditlog.php'),
		],'config');
	}
}