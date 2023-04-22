<?php

return [
	'api' => [
		'url' => env('AUDIT_LOG_API_URL'),
		'username' => env('AUDIT_LOG_API_USERNAME'),
		'password' => env('AUDIT_LOG_API_PASSWORD'),
		'session_time' => env('AUDIT_LOG_API_SESSION_TIME', 480)
	],
];