<?php

namespace App\Service\Ai;

use App\Exception\ExternalApiException;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAiClient
{
    private string $apiKey;
    private HttpClientInterface $http;
    private OpenAiModelConfig $config;

    private const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        HttpClientInterface $httpClient,
        OpenAiModelConfig $config,
        string $openAiApiKey,
    ) {
        $this->http   = $httpClient;
        $this->config = $config;
        $this->apiKey = $openAiApiKey;
    }

    public function getModelName(): string
    {
        return $this->config->getModel();
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @return array<string, mixed>
     */
    public function run(array $messages, array $tools): array
    {
        try {
            return $this->executeRequest($messages, $tools);

        } catch (\Throwable $e) {
            throw new ExternalApiException('OpenAI request failed: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $messages
     * @param array<int, array<string, mixed>> $tools
     * @return array<string, mixed>
     * @throws JsonException
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function executeRequest(array $messages, array $tools): array
    {
        $payload = [
            'model' => $this->config->getModel(),
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => [
                'type' => 'function',
                'function' => [
                    'name' => 'analyze_insurance_policy',
                ],
            ],
            'temperature' => $this->config->getTemperature(),
            'max_tokens' => $this->config->getMaxTokens(),
        ];

        $response = $this->http->request('POST', self::OPENAI_API_URL, [
            'headers' => [
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new ExternalApiException('OpenAI returned non-200: '.$response->getStatusCode());
        }

        $data = $response->toArray(false);

        // OpenAI response structure: choices[0].message.tool_calls[0].function.arguments
        if (!isset($data['choices'][0]['message']['tool_calls'][0]['function']['arguments'])) {
            throw new ExternalApiException('Unexpected OpenAI response structure');
        }

        $json = $data['choices'][0]['message']['tool_calls'][0]['function']['arguments'];

        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
