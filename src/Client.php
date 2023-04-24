<?php

namespace Atimrots\AuditLog;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Exception;
use Illuminate\Support\Facades\Cache;
use JsonException;

class Client
{
	private string $base_url;
	private HttpClient $client;
	public bool $error;
	private array $config;
	private int $ttl;
	private int $retries = 0;

	/**
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	public function __construct() {
		$this->config = config('auditlog.api');

		$this->error = !$this->config['url'] || !$this->config['username'] || !$this->config['password'];

		if ($this->error) {
			return;
		}

		$this->base_url = $this->config['url'];
		$this->ttl = (int)$this->config['session_time'];

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
			if ($this->retries < 3 && $e->getCode() === 403 && str_contains($e->getMessage(), 'Invalid token or expired token')) {
				$this->refreshJwtToken();
				$this->retries++;

				return $this->retryRequest($method, $path, $data);
			}

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
	 * @param string $method
	 * @param string $path
	 * @param array $data
	 *
	 * @return null[]
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	private function retryRequest(string $method, string $path, array $data = []): array {
		return (new Client())->makeRequest($method, $path, $data);
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
		$token = Cache::get('audit_log_api_token');

		if (!$token) {
			$token = $this->refreshJwtToken();
		}

		return $token ?? '';
	}

	/**
	 * @return string|null
	 * @throws GuzzleException
	 * @throws JsonException
	 */
	private function refreshJwtToken(): string|null {
		$client = new HttpClient([
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

			Cache::set('audit_log_api_token', $token, $this->ttl);

			return $token;
		} else {
			// TODO: Log response to debug
		}

		return null;
	}
}