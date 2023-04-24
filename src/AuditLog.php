<?php

namespace Atimrots\AuditLog;

class AuditLog {
	private const POST = 'POST';
	private const GET = 'GET';
	private const POST_BASE_PATH = '/log/';
	private const GET_BASE_PATH = '/get/';

	public static function post(string $collection, array $data): void {
		$client = new Client();

		// TODO: validate if collection exists from cached values

		$response = $client->makeRequest(self::POST, self::POST_BASE_PATH.$collection, $data);

		dd($response);
	}

	public static function get(string $collection, array $query = []): array {
		$client = new Client();

		// TODO: validate if collection exists from cached values

		$response = $client->makeRequest(self::GET, self::GET_BASE_PATH.$collection, $query);

		dd(json_decode($response['response']['result'], true));

		return $response;
	}
}
