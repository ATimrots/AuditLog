<?php

namespace Atimrots\AuditLog;

use GuzzleHttp\Exception\GuzzleException;
use JsonException;

class AuditLog {
	private const POST = 'POST';
	private const GET = 'GET';
	private const STORE_BASE_PATH = '/log/';
	private const SHOW_BASE_PATH = '/select/';

	/**
	 * @param string $collection
	 * @param array $data
	 *
	 * @return void
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	public static function post(string $collection, array $data): void {
		$client = new Client();

		// TODO: validate if collection exists from cached values

		$client->makeRequest(self::POST, self::STORE_BASE_PATH.$collection, $data);
	}

	/**
	 * @param string $collection
	 * @param array $query
	 * @param array $body
	 *
	 * @return array
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	public static function get(string $collection, array $query = [], array $body = []): array {
		$client = new Client();

		// TODO: validate if collection exists using cached values

		$path = self::SHOW_BASE_PATH.$collection;

		if ($query) {
			$path .= '?'.http_build_query($query);
		}

		$response = $client->makeRequest(self::POST, $path, $body);

		$response['response']['result'] = json_decode($response['response']['result'], true, 512, JSON_THROW_ON_ERROR);

		return $response;
	}
}
