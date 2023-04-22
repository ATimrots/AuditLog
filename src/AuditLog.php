<?php

namespace Atimrots\AuditLog;

use Illuminate\Support\Facades\Http;

class AuditLog {
	public function justDoIt(): string {
		$response = Http::get('https://inspiration.goprogram.ai/');

		return $response['quote'] . ' -' . $response['author'];
	}
}