<div align="center">

# InsurAI 🛡️

**AI-Powered Insurance Policy Analyzer**

*Enterprise-grade serverless microservice that extracts structured data from insurance policies in seconds*

[![CI](https://github.com/RichardTrujilloTorres/insurai-policy-analyzer/workflows/CI/badge.svg)](https://github.com/RichardTrujilloTorres/insurai-policy-analyzer/actions)
[![codecov](https://codecov.io/gh/RichardTrujilloTorres/insurai-policy-analyzer/branch/main/graph/badge.svg)](https://codecov.io/gh/RichardTrujilloTorres/insurai-policy-analyzer)
[![PHP Version](https://img.shields.io/badge/php-8.2+-blue.svg)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/symfony-7.2-black.svg)](https://symfony.com/)
[![Test Coverage](https://img.shields.io/badge/coverage-97.68%25-brightgreen.svg)](https://codecov.io/gh/RichardTrujilloTorres/insurai-policy-analyzer)

[Features](#-features) • [Demo](#-demo) • [Quick Start](#-quick-start) • [Tech Stack](#-tech-stack) • [Architecture](#-architecture)

</div>

---

## 💡 The Problem

Insurance policies are dense, 50+ page legal documents. Manual analysis is:
- ⏱️ **Time-consuming** - Hours per document
- 🎯 **Error-prone** - Easy to miss critical clauses
- 💰 **Expensive** - Requires specialized expertise

## ✨ The Solution

**InsurAI** analyzes any insurance policy in **< 2 seconds** and extracts:

- 📋 **Coverage details** - Type, amount, breakdown
- 💵 **Deductibles** - All cost obligations
- ⚠️ **Exclusions** - What's NOT covered
- 📊 **Risk assessment** - Low/Medium/High rating
- 🚩 **Legal flags** - Needs review, inconsistent clauses
- ✅ **Action items** - Recommended next steps

Perfect for: Insurance agents, legal teams, policy comparison platforms, InsurTech startups.

---

## 🎯 Demo

```bash
POST /analyze
Content-Type: application/json

{
  "policyText": "Comprehensive health insurance covering medical expenses...",
  "policyType": "health",
  "jurisdiction": "US"
}
```

**Response (< 2s):**
```json
{
  "coverage": {
    "coverageType": "comprehensive",
    "coverageAmount": "$100,000",
    "coverageBreakdown": {
      "medical": true,
      "dental": false,
      "pharmacy": true
    }
  },
  "riskLevel": "medium",
  "flags": {
    "needsLegalReview": true
  },
  "requiredActions": [
    "Review exclusions carefully",
    "Verify deductible terms"
  ]
}
```

---

## 🌟 Features

### Core Capabilities
- 🤖 **AI-Powered Analysis** - OpenAI GPT-4o-mini with Structured Outputs
- ⚡ **Serverless Architecture** - AWS Lambda via Bref (< 200ms cold start)
- 🔒 **Privacy-First** - Policy text never logged (GDPR compliant)
- 📊 **97.68% Test Coverage** - 211 tests, production-ready
- 🎯 **Type-Safe** - Strict DTOs with Symfony validation
- 🔄 **Request Tracing** - Correlation IDs throughout
- 🚦 **Rate Limiting** - Built-in abuse prevention
- 📈 **CloudWatch Ready** - Metrics & structured logging

### Technical Highlights
- **OpenAI Structured Outputs** - Guaranteed valid JSON responses
- **Modern PHP 8.2** - Readonly classes, enums, named arguments, strict types
- **Zero API Calls in Tests** - Stub pattern for fast CI/CD
- **Clean Architecture** - DTOs, Services, Infrastructure separation
- **Static Analysis** - PHPStan level 8, PHP-CS-Fixer
- **CI/CD Pipeline** - Automated tests, coverage, linting

---

## 🏗️ Architecture

```
┌─────────────┐      ┌──────────────────┐      ┌──────────────┐
│   Client    │─────▶│   AWS Lambda     │─────▶│   OpenAI     │
│  (REST API) │      │  (Symfony/Bref)  │      │  GPT-4 API   │
└─────────────┘      └──────────────────┘      └──────────────┘
                              │
                              ├─▶ Rate Limiter (Cache)
                              ├─▶ Request Validator (Symfony)
                              ├─▶ Policy Analyzer (Business Logic)
                              ├─▶ Response Normalizer (DTOs)
                              └─▶ CloudWatch (Logs + Metrics)
```

**Request Flow:**
1. JSON request → `PolicyAnalysisRequest` DTO (auto-deserialized)
2. Validation via Symfony Validator
3. Rate limiting check (cache-based)
4. Prompt built with context (type, jurisdiction, language)
5. OpenAI call with function schema (Structured Outputs)
6. Response normalized to `PolicyAnalysisResponse` DTO
7. JSON response with correlation ID

---

## 📊 Project Stats

| Metric | Value |
|--------|-------|
| **Tests** | 211 (100% passing) |
| **Coverage** | 97.68% (643 assertions) |
| **Classes** | 23 (fully documented) |
| **PHPStan Errors** | 0 (level 8) |
| **Response Time** | < 2 seconds (avg) |
| **Cold Start** | < 200ms (Lambda) |
| **Cost per Analysis** | ~$0.02 (OpenAI) |

### Test Suite Breakdown
- **Unit Tests** (199): Core logic fully mocked
    - AI Services (OpenAiClient, ToolSchemaFactory)
    - Policy Services (Analyzer, Normalizer, PromptBuilder)
    - Infrastructure (RateLimiter, RequestValidator)
    - Logging & Monitoring (RequestLogger, MetricsRecorder)

- **Integration Tests** (12): End-to-end API flows
    - POST /analyze with mocked OpenAI
    - Input validation scenarios
    - Rate limiting behavior
    - Correlation ID tracking

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| **Language** | PHP 8.2 (readonly, enums, strict types) |
| **Framework** | Symfony 7.2 (DI, validation, serialization) |
| **AI** | OpenAI API (GPT-4o-mini, Structured Outputs) |
| **Deployment** | AWS Lambda + Bref (serverless) |
| **Testing** | PHPUnit 11 (97.68% coverage) |
| **Quality** | PHPStan (level 8), PHP-CS-Fixer |
| **Cache** | Symfony Cache (Redis/DynamoDB ready) |
| **Logging** | Monolog (structured JSON) |
| **CI/CD** | GitHub Actions (tests, coverage, linting) |

---

## 🚀 Quick Start

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

# Copy environment file
cp .env .env.local

# Configure your OpenAI key
# Edit .env.local:
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o-mini

# Run development server
symfony server:start
```

### Test the API

```bash
# Health check
curl http://localhost:8000/health

# Analyze a policy
curl -X POST http://localhost:8000/analyze \
  -H "Content-Type: application/json" \
  -d '{
    "policyText": "Comprehensive health insurance covering medical expenses up to $100,000 annually with a $1,000 deductible. Pre-existing conditions excluded.",
    "policyType": "health",
    "jurisdiction": "US",
    "language": "en"
  }'
```

---

## 🧪 Running Tests

```bash
# Run all tests
composer test

# Run with coverage report
composer test:coverage

# Run only unit tests
php bin/phpunit tests/Unit

# Run only integration tests
php bin/phpunit tests/Integration

# Code style check
composer cs:check

# Fix code style
composer cs:fix

# Static analysis
composer stan
```

---

## 📡 API Documentation

### POST /analyze

Analyzes an insurance policy document.

**Request Body:**
```typescript
{
  policyText: string;      // Required - The policy text to analyze
  policyType?: string;     // Optional - health|auto|life|home|travel
  jurisdiction?: string;   // Optional - US|CA|UK|EU|etc
  language?: string;       // Optional - en|fr|es|de|it (default: en)
  metadata?: object;       // Optional - Custom metadata
}
```

**Response:** `200 OK`
```typescript
{
  coverage: {
    coverageType: string;
    coverageAmount: string;
    coverageBreakdown: object;
  };
  deductibles: array;
  exclusions: string[];
  riskLevel: "low" | "medium" | "high";
  requiredActions: string[];
  flags: {
    needsLegalReview: boolean;
    inconsistentClausesDetected: boolean;
  };
}
```

**Error Responses:**
- `422 Unprocessable Entity` - Validation failed
- `429 Too Many Requests` - Rate limit exceeded (5 req/60s)
- `500 Internal Server Error` - Processing error

**Headers:**
- `X-Correlation-ID` - Request tracking ID (auto-generated or from request)
- `Content-Type: application/json`

---

## 🔐 Security & Privacy

### Privacy-First Design
- ✅ **Never logs policy text** - Only metadata (type, jurisdiction, language)
- ✅ **GDPR compliant** - No PII stored or logged
- ✅ **Correlation IDs** - Request tracing without sensitive data
- ✅ **Sanitized errors** - No policy content in error messages

### Security Features
- 🚦 **Rate Limiting** - 5 requests per 60 seconds per client
- ✅ **Input Validation** - Strict DTO validation via Symfony Validator
- 🔒 **Type Safety** - PHP 8.2 strict types throughout
- 🛡️ **Exception Handling** - Global error handler prevents data leaks
- 🔑 **API Key Security** - Environment variables, never hardcoded

---

## 🎓 Why I Built This

This project demonstrates my expertise in:

### Backend Development
- ✅ **Modern PHP** - 8.2+ features (readonly, enums, named args)
- ✅ **Symfony Framework** - DI, validation, serialization, events
- ✅ **RESTful APIs** - Clean endpoints, proper status codes, DTOs
- ✅ **Error Handling** - Graceful failures, correlation IDs

### AI Integration
- ✅ **OpenAI API** - Function calling, Structured Outputs
- ✅ **Prompt Engineering** - Context-aware, domain-specific prompts
- ✅ **Response Normalization** - Reliable JSON → DTO mapping
- ✅ **Cost Optimization** - Efficient token usage

### Software Quality
- ✅ **97.68% Test Coverage** - Unit + integration tests
- ✅ **Clean Architecture** - Separation of concerns, SOLID principles
- ✅ **Static Analysis** - PHPStan level 8, no errors
- ✅ **CI/CD Pipeline** - Automated testing, linting, coverage

### Cloud Architecture
- ✅ **Serverless Design** - AWS Lambda ready (Bref)
- ✅ **Observability** - Structured logging, metrics, tracing
- ✅ **Scalability** - Stateless, cache-backed rate limiting
- ✅ **Cost-Efficient** - Pay-per-use, no idle servers

**Key Challenge Solved**: Getting reliable, structured data from OpenAI's API while maintaining strict type safety, 97%+ test coverage, and production-grade error handling.

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

```bash
# 1. Fork the repo and create a feature branch
git checkout -b feature/amazing-feature

# 2. Make your changes and run tests
composer test
composer cs:check
composer stan

# 3. Commit with conventional commits
git commit -m "feat: add amazing feature"

# 4. Push and create a Pull Request
git push origin feature/amazing-feature
```

### Commit Convention
- `feat:` - New features
- `fix:` - Bug fixes
- `test:` - Test additions/changes
- `docs:` - Documentation updates
- `style:` - Code formatting
- `refactor:` - Code improvements
- `chore:` - Maintenance tasks

---

## 📝 License

MIT License - see [LICENSE](LICENSE) file for details.

---

## 🙏 Acknowledgments

Built with:
- [Symfony](https://symfony.com/) - The PHP framework
- [OpenAI](https://openai.com/) - AI-powered analysis
- [Bref](https://bref.sh/) - Serverless PHP on AWS Lambda
- [PHPUnit](https://phpunit.de/) - Testing framework

---

<div align="center">

**⭐ If this project helped you, consider giving it a star!**

Built with ❤️ by [Richard Trujillo Torres](https://github.com/RichardTrujilloTorres)

[Report Bug](https://github.com/RichardTrujilloTorres/insurai-policy-analyzer/issues) • [Request Feature](https://github.com/RichardTrujilloTorres/insurai-policy-analyzer/issues)

</div>
