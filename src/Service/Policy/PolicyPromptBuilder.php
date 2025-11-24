<?php

namespace App\Service\Policy;

use App\Dto\PolicyAnalysisRequest;

class PolicyPromptBuilder
{
    /**
     * Build the system + user messages array for OpenAI.
     * This does NOT include the tool schema — only the messages.
     */
    public function buildMessages(PolicyAnalysisRequest $request): array
    {
        $systemMessage = [
            'role' => 'system',
            'content' => $this->buildSystemContent($request),
        ];

        $userMessage = [
            'role' => 'user',
            'content' => $this->buildUserContent($request),
        ];

        return [$systemMessage, $userMessage];
    }

    private function buildSystemContent(PolicyAnalysisRequest $request): string
    {
        return <<<TXT
You are an expert insurance policy analysis engine.

Your job:
- extract coverage details
- identify exclusions
- extract deductibles
- assess risk level
- detect required follow-up actions
- flag inconsistencies or legal-review needs

Output MUST strictly follow the JSON schema provided via tool calling.
Do NOT include explanations, summaries, or reasoning outside the structured output.

Jurisdiction: {$request->jurisdiction}
Language: {$request->language}

TXT;
    }

    private function buildUserContent(PolicyAnalysisRequest $request): string
    {
        $meta = $request->metadata ? json_encode($request->metadata) : '{}';

        return <<<TXT
Analyze the following insurance policy text.

Policy Type: {$request->policyType}
Metadata: {$meta}

Policy Text:
{$request->policyText}
TXT;
    }
}
