<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Validation;

use App\Service\Validation\RequestValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestValidatorTest extends TestCase
{
    private ValidatorInterface $validator;
    private RequestValidator $requestValidator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->requestValidator = new RequestValidator($this->validator);
    }

    public function testValidatePassesWhenNoViolations(): void
    {
        // Arrange
        $dto = new \stdClass();
        $emptyViolations = new ConstraintViolationList([]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($emptyViolations);

        // Act & Assert - Should not throw
        $this->requestValidator->validate($dto);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testValidateThrowsWhenViolationsExist(): void
    {
        // Arrange
        $dto = new \stdClass();

        $violation = new ConstraintViolation(
            'This field is required',
            'This field is required',
            [],
            $dto,
            'propertyPath',
            null
        );

        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->with($dto)
            ->willReturn($violations);

        // Assert
        $this->expectException(ValidationFailedException::class);

        // Act
        $this->requestValidator->validate($dto);
    }

    public function testValidateCallsSymfonyValidator(): void
    {
        // Arrange
        $dto = new \stdClass();
        $emptyViolations = new ConstraintViolationList([]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($dto)
            ->willReturn($emptyViolations);

        // Act
        $this->requestValidator->validate($dto);
    }

    public function testValidateThrowsWithMultipleViolations(): void
    {
        // Arrange
        $dto = new \stdClass();

        $violation1 = new ConstraintViolation(
            'Field 1 is required',
            'Field 1 is required',
            [],
            $dto,
            'field1',
            null
        );

        $violation2 = new ConstraintViolation(
            'Field 2 is invalid',
            'Field 2 is invalid',
            [],
            $dto,
            'field2',
            'invalid-value'
        );

        $violations = new ConstraintViolationList([$violation1, $violation2]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        // Assert
        $this->expectException(ValidationFailedException::class);

        // Act
        $this->requestValidator->validate($dto);
    }

    public function testValidateExceptionContainsViolations(): void
    {
        // Arrange
        $dto = new \stdClass();

        $violation = new ConstraintViolation(
            'Validation error',
            'Validation error',
            [],
            $dto,
            'field',
            null
        );

        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        // Act & Assert
        try {
            $this->requestValidator->validate($dto);
            $this->fail('Expected ValidationFailedException to be thrown');
        } catch (ValidationFailedException $e) {
            $this->assertSame($dto, $e->getValue());
            $this->assertSame($violations, $e->getViolations());
            $this->assertCount(1, $e->getViolations());
        }
    }

    public function testValidateWithDifferentDtoTypes(): void
    {
        // Test with different object types
        $dtos = [
            new \stdClass(),
            new class {},
            (object)['property' => 'value']
        ];

        foreach ($dtos as $dto) {
            // Arrange
            $validator = $this->createMock(ValidatorInterface::class);
            $requestValidator = new RequestValidator($validator);
            $emptyViolations = new ConstraintViolationList([]);

            $validator
                ->method('validate')
                ->with($dto)
                ->willReturn($emptyViolations);

            // Act & Assert
            $requestValidator->validate($dto);
            $this->assertTrue(true);
        }
    }

    public function testValidateDoesNotModifyDto(): void
    {
        // Arrange
        $dto = new \stdClass();
        $dto->property = 'original-value';

        $emptyViolations = new ConstraintViolationList([]);

        $this->validator
            ->method('validate')
            ->willReturn($emptyViolations);

        // Act
        $this->requestValidator->validate($dto);

        // Assert - DTO should remain unchanged
        $this->assertSame('original-value', $dto->property);
    }

    public function testValidatorIsReadonly(): void
    {
        // Assert - Class should be readonly
        $reflection = new \ReflectionClass(RequestValidator::class);
        $this->assertTrue(
            $reflection->isReadOnly(),
            'RequestValidator should be readonly'
        );
    }

    public function testValidateWithEmptyDto(): void
    {
        // Arrange
        $dto = new \stdClass(); // Empty DTO
        $emptyViolations = new ConstraintViolationList([]);

        $this->validator
            ->method('validate')
            ->with($dto)
            ->willReturn($emptyViolations);

        // Act & Assert - Should not throw
        $this->requestValidator->validate($dto);
        $this->assertTrue(true);
    }

    public function testValidateExceptionMessageContainsValidationInfo(): void
    {
        // Arrange
        $dto = new \stdClass();

        $violation = new ConstraintViolation(
            'Email is not valid',
            'Email is not valid',
            [],
            $dto,
            'email',
            'invalid-email@'
        );

        $violations = new ConstraintViolationList([$violation]);

        $this->validator
            ->method('validate')
            ->willReturn($violations);

        // Act & Assert
        try {
            $this->requestValidator->validate($dto);
            $this->fail('Expected ValidationFailedException to be thrown');
        } catch (ValidationFailedException $e) {
            // Check that exception contains violation information
            $violationsList = $e->getViolations();
            $this->assertCount(1, $violationsList);
            $this->assertSame('Email is not valid', $violationsList[0]->getMessage());
        }
    }
}
