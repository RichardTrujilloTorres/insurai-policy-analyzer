# InsurAI Policy Analyzer

A production-grade **serverless API** that analyzes insurance policy text using OpenAI.  
Built with **Symfony 7**, **PHP 8.2**, **Bref (AWS Lambda)** and a fully automated **GitHub Actions CI**.

[![CI](https://github.com/RichardTrujilloTorres/insurai-policy-analyzer/workflows/CI/badge.svg)](https://github.com/RichardTrujilloTorres/insurai-policy-analyzer/actions)
[![codecov](https://codecov.io/gh/RichardTrujilloTorres/insurai-policy-analyzer/branch/main/graph/badge.svg)](https://codecov.io/gh/RichardTrujilloTorres/insurai-policy-analyzer)
[![PHP Version](https://img.shields.io/badge/php-8.2-blue.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/symfony-7.2-black.svg)](https://symfony.com/)

---

## 🚀 What It Does

**InsurAI** exposes a single HTTP endpoint that:

- Accepts raw insurance policy text + context (type, jurisdiction, language, metadata)
- Sends it to OpenAI with a **strict function-calling schema** (Structured Outputs)
- Returns a **deterministic JSON** structure describing:
    - Coverage details (type, amount, breakdown)
    - Deductibles
    - Exclusions
    - Overall risk level (low/medium/high)
    - Recommended follow-up actions
    - Compliance / review flags

It's designed as a **backend microservice**, not a UI app.

---

## 🧱 Architecture Overview

The core flow looks like this:

1. **Request enters `/analyze`**
    - JSON body → `PolicyAnalysisRequest` DTO (Symfony Serializer)
    - DTO validated (Symfony Validator)
    - Correlation ID attached to logs (X-Correlation-ID)
    - Rate limiting applied (cache-based sliding window)

2. **PolicyAnalyzerService** orchestrates the AI call
    - Builds system + user prompts (`PolicyPromptBuilder`)
    - Builds OpenAI tool schema (`OpenAiToolSchemaFactory`)
    - Calls OpenAI with Structured Outputs (`OpenAiClient`)
    - Normalizes structured JSON → `PolicyAnalysisResponse` (`PolicyResponseNormalizer`)

3. **Response is returned as JSON**
    - Strict, predictable shape
    - Safe to consume from other services / frontends
    - Includes correlation ID for request tracing

**Privacy-First**: Policy text is never logged, only metadata (type, jurisdiction, language).

---

## 📊 Test Coverage

**211 tests, 643 assertions**
- **Lines**: 97.68% (337/345)
- **Methods**: 95.83% (46/48)
- **Classes**: 90.00% (18/20)

### Test Suite Breakdown:
- **Unit Tests** (199 tests): Core business logic, fully mocked
    - AI Services: OpenAiClient, ModelConfig, ToolSchemaFactory
    - Policy Services: PromptBuilder, Normalizer, AnalyzerService
    - Event Subscribers: CorrelationId, RateLimiter
    - Logging & Monitoring: RequestLogger, MetricsRecorder
    - Rate Limiting: RateLimiter with cache
    - Validation: RequestValidator

- **Integration Tests** (2 tests): End-to-end API testing
    - POST /analyze endpoint validation
    - HTTP method restrictions

All tests run in CI with automated coverage reporting.

---

## 🛠 Tech Stack

- **PHP 8.2** - Modern PHP with readonly classes, enums, types
- **Symfony 7.2** - Web framework, DI, validation, serialization
- **Bref** - Serverless deployment on AWS Lambda
- **OpenAI API** - GPT-4o-mini with Structured Outputs (function calling)
- **Symfony Cache** - Rate limiting (Redis/DynamoDB in production)
- **Monolog** - Structured logging with correlation IDs
- **PHPUnit 11** - Testing framework with 97%+ coverage
- **PHP-CS-Fixer** - Code style enforcement
- **PHPStan** - Static analysis (level 6+)

---

## 🚦 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- OpenAI API key

### Installation

```bash
# Clone repository
git clone https://github.com/RichardTrujilloTorres/insurai-policy-analyzer.git
cd insurai-policy-analyzer

# Install dependencies
composer install

# Configure environment variables (see Configuration section)

# Run development server
symfony server:start
```

### Testing the API

```bash
# Health check
curl http://localhost:8000/health

# Analyze a policy
curl -X POST http://localhost:8000/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "policyText": "Comprehensive health insurance covering medical expenses up to $100,000 annually with a $1,000 deductible.",
    "policyType": "health",
    "jurisdiction": "US",
    "language": "en"
  }'
```

**Response:**
```json
{
  "coverage": {
    "coverageType": "comprehensive",
    "coverageAmount": "$100,000",
    "coverageBreakdown": [...]
  },
  "deductibles": [
    {"type": "annual", "amount": "$1,000"}
  ],
  "exclusions": [...],
  "riskLevel": "medium",
  "requiredActions": [...],
  "flags": {
    "needsLegalReview": false,
    "inconsistentClausesDetected": false
  }
}
```

---

## 🧪 Running Tests

```bash
# Run all tests
composer test

# Run unit tests only
php bin/phpunit tests/Unit

# Run with coverage
composer test:coverage

# Code style check
composer cs:check

# Static analysis
composer stan
```

---

## 📝 API Documentation

### POST /analyze

Analyzes an insurance policy document.

**Request:**
```json
{
  "policyText": "string (required)",
  "policyType": "string (required) - health|auto|life|home|travel",
  "jurisdiction": "string (required) - US|CA|UK|EU|etc",
  "language": "string (required) - en|fr|es|de|it|etc",
  "metadata": "object (optional) - custom metadata"
}
```

**Response:** `200 OK`
```json
{
  "coverage": {
    "coverageType": "string",
    "coverageAmount": "string|number",
    "coverageBreakdown": "array"
  },
  "deductibles": "array",
  "exclusions": "array<string>",
  "riskLevel": "low|medium|high",
  "requiredActions": "array<string>",
  "flags": {
    "needsLegalReview": "boolean",
    "inconsistentClausesDetected": "boolean"
  }
}
```

**Error Responses:**
- `400 Bad Request` - Invalid input, validation failed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error

**Headers:**
- `X-Correlation-ID` - Request tracking ID (auto-generated or from request)

---

## 🔒 Security & Privacy

- **No Policy Text Logging**: Policy content is never logged, only metadata
- **Rate Limiting**: 5 requests per 60 seconds per client (configurable)
- **Correlation IDs**: All requests tracked with unique IDs
- **Input Validation**: Strict DTO validation before processing
- **Exception Handling**: Sensitive data never exposed in error messages

---

## 🚀 Deployment (AWS Lambda)

This project is designed for serverless deployment on AWS Lambda using Bref.

### Deploy to AWS:

```bash
# Install Bref
composer require bref/bref --dev

# Configure serverless.yml (see Bref docs)
# Deploy
serverless deploy
```

**Environment Variables:**
- `OPENAI_API_KEY` - Your OpenAI API key (required)
- `APP_ENV` - production|staging|development
- `LOG_LEVEL` - debug|info|warning|error

---

## 🔧 Configuration

### Rate Limiting
Configured in `src/Service/RateLimit/RateLimiter.php`:
- Max requests: 5 (default)
- Time window: 60 seconds (default)
- Customizable per environment

### OpenAI Model
Configured in `src/Service/Ai/OpenAiModelConfig.php`:
- Model: `gpt-4o-mini` (default)
- Temperature: 0.0 (deterministic)
- Max tokens: 2000
- Structured Outputs: Enabled

---

## 📊 Monitoring

### Metrics (CloudWatch)
- `metrics.policy_analysis.success` - Successful analyses
- `metrics.policy_analysis.failure` - Failed analyses
- Duration tracking for all requests

### Logging
- Structured JSON logs
- Correlation ID in every log entry
- Privacy-aware (no policy text)
- Request metadata logged
- OpenAI call success/failure tracked

---

## 🤝 Contributing

```bash
# Fork the repo, create a branch
git checkout -b feature/my-feature

# Make changes, run tests
composer test
composer cs:check
composer stan

# Commit with conventional commits
git commit -m "feat: add new feature"

# Push and create PR
git push origin feature/my-feature
```

**Commit Convention:**
- `feat:` - New features
- `fix:` - Bug fixes
- `test:` - Test additions/changes
- `docs:` - Documentation
- `style:` - Code style (formatting)
- `refactor:` - Code refactoring
- `chore:` - Maintenance tasks

---

## 📄 License

MIT License - see LICENSE file for details

---

## 🙏 Acknowledgments

- Built with [Symfony](https://symfony.com/)
- Powered by [OpenAI](https://openai.com/)
- Serverless with [Bref](https://bref.sh/)

---
