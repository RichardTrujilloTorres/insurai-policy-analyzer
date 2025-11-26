<?php

namespace App\Infrastructure\Aws;

use Bref\Context\Context;

/**
 * Extracts Lambda runtime metadata and adapts it into an internal structure
 * that the application can use.
 */
final class LambdaContextFactory
{
    public function createFromBref(Context $context): LambdaExecutionContext
    {
        return new LambdaExecutionContext(
            requestId: $context->getAwsRequestId(),
            functionArn: $context->getInvokedFunctionArn(),
            deadlineMs: $context->getDeadline(),
            memoryLimitMb: $context->getMemoryLimit(),           // PhpStorm false-positive
            remainingTimeMs: $context->getRemainingTimeInMillis()
        );
    }
}
