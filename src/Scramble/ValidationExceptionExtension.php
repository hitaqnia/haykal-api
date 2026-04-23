<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Scramble;

use Dedoc\Scramble\Extensions\ExceptionToResponseExtension;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Documents Laravel's `ValidationException` using the Haykal envelope (HTTP 422).
 */
final class ValidationExceptionExtension extends ExceptionToResponseExtension
{
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(ValidationException::class);
    }

    public function toResponse(Type $type): Response
    {
        return Response::make(422)
            ->description('Validation error')
            ->setContent(
                'application/json',
                Schema::fromType(EnvelopeResponseSchema::withErrorsObject(422)),
            );
    }

    public function reference(ObjectType $type): Reference
    {
        return new Reference('responses', Str::start($type->name, '\\'), $this->components);
    }
}
