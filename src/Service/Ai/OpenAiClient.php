<?php

namespace App\Service\Ai;

use App\Exception\ExternalApiException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiClient
{
    private string $apiKey;
    private HttpClientInterface $http;
    private LoggerInterface $logger;
    private OpenAiModelConfig $config;

    // OpenAI v1 base URL
    private const OPENAI_API_URL = 'https://api.openai.com/v1/responses';

    // Number of retries when OpenAI fails
    private const MAX_RETRIES = 2;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        OpenAiModelConfig $config,
        string $openAiApiKey
    ) {
        $this->http   = $httpClient;
        $this->logger = $logger;
        $this->config = $config;
        $this->apiKey = $openAiApiKey;
    }

    /**
     * Perform a function-calling request to OpenAI.
     *
     * @param array $messages Chat messages (system + user)
     * @param array $tools    The schema for function calling
     */
    public function run(array $messages, array $tools): array
    {
        $attempt = 0;

        while (true) {
            try {
                return $this->executeRequest($messages, $tools);
            } catch (\Throwable $exception) {
                $attempt++;

                $this->logger->warning('OpenAI request failed.', [
                    'attempt' => $attempt,
                    'error'   => $exception->getMessage(),
                ]);

                if ($attempt > self::MAX_RETRIES) {
                    throw new ExternalApiException('OpenAI API failed after retries.', previous: $exception);
                }

                usleep(150_000); // 150ms delay before retry
            }
        }
    }

    private function executeRequest(array $messages, array $tools): array
    {
        // Build request payload
        $payload = [
            'model'       => $this->config->getModel(),
            'temperature' => $this->config->getTemperature(),
            'max_output_tokens' => $this->config->getMaxTokens(),
            'messages'    => $messages,
            'tools'       => $tools,
            'tool_choice' => 'auto',
        ];

        $this->logger->info('Calling OpenAI…', [
            'model' => $this->config->getModel(),
        ]);

        $response = $this->http->request('POST', self::OPENAI_API_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'json' => $payload,
            'timeout' => 25, // must be < Lambda timeout
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new ExternalApiException(
                sprintf('OpenAI returned non-200: %s', $response->getStatusCode())
            );
        }

        $data = $response->toArray(false); // false => do not throw on JSON errors

        $this->logger->debug('OpenAI raw response', ['response' => $data]);

        // Extract function call result
        $output = $data['output'] ?? null;

        if (!$output || !isset($output[0]['content'][0]['text'])) {
            throw new ExternalApiException('Malformed OpenAI response.');
        }

        // Content from v1 Responses API
        $json = $output[0]['content'][0]['text'];

        // Parse JSON output from the model
        try {
            return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new ExternalApiException('Failed to decode OpenAI JSON output.', previous: $e);
        }
    }
}
