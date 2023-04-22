<?php

namespace Atimrots\AuditLog;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Exception;
use JsonException;

class Client
{
	private string $base_url;
	private HttpClient $client;
	public bool $error;
	private array $config;

	/**
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	public function __construct() {
		$this->config = config('services.log');

		$this->error = !$this->config['url'] || !$this->config['username'] || !$this->config['password'];

		if ($this->error) {
			return;
		}

		$this->base_url = $this->config['url'];

		$this->client = new HttpClient([
			'headers' => $this->headers(),
			'verify' => !config('app.debug'),
		]);
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param array $data
	 *
	 * @return array|null[]
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	public function makeRequest(string $method, string $path, array $data = []): array {
		$url = $this->base_url.$path;

		$options = $method === 'GET' ? ['query' => $data] : ['body' => json_encode($data, JSON_THROW_ON_ERROR)];

		$response = null;
		$exception = null;

		try {
			$response = $this->client->request($method, $url, $options);
		} catch (Exception $e) {
			//if ($e->getCode() === 403 && str_contains($e->getMessage(), 'Invalid token or expired token')) {
			//	$this->requestJwtToken();
			//
			//	return $this->makeRequest($method, $path, $data);
			//}

			if (method_exists($e, 'getResponse')) {
				$response = $e->getResponse();
			}

			$exception = $e->getMessage();
		}

		if ($response) {
			$response_body = (string)$response->getBody();
			try {
				$final_response = json_decode($response_body, true, 512, JSON_THROW_ON_ERROR);
			} catch (JsonException $e) {
				$final_response = [
					'exception' => 'Failed to parse JSON',
					'response' => $response_body,
					'original_exception' => $e->getMessage(),
				];
			}
		} else {
			$final_response = ['exception' => $exception];
		}

		return $final_response;
	}

	/**
	 * @return string[]
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	private function headers(): array {
		$headers =  [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json',
		];

		return array_merge($headers, ['Authorization' => 'Bearer '.$this->getToken()]);
	}

	/**
	 * @return string
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	private function getToken(): string {
		$token = session('log_api_access_token');

		if (!$token) {
			$token = $this->requestJwtToken();
		}

		return $token ?? '';
	}

	/**
	 * @return string|null
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	private function requestJwtToken(): string|null {
		$client = new Client([
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			],
			'verify' => !config('app.debug'),
		]);

		$data = [
			'email' => $this->config['username'],
			'password' => $this->config['password'],
		];

		$response = $client->post($this->base_url.'/client/login', ['body' => json_encode($data, JSON_THROW_ON_ERROR)]);
		$response = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

		if ($response['access_token'] ?? null) {
			$token = $response['access_token'];

			session()->put('log_api_access_token', $token);

			return $token;
		} else {
			// Log response to debug
		}

		return null;
	}
}