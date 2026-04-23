<?php

declare(strict_types=1);

namespace HiTaqnia\Haykal\Api\Scramble;

use Dedoc\Scramble\Support\Generator\Types as OpenApiTypes;

/**
 * Builds the OpenAPI schema for the Haykal API envelope.
 *
 * Shared between every `ExceptionToResponseExtension` in this package so
 * failure responses document the exact same shape as success responses.
 */
final class EnvelopeResponseSchema
{
    public static function withErrorsObject(int $defaultCode): OpenApiTypes\ObjectType
    {
        return self::baseEnvelope($defaultCode)
            ->addProperty(
                'errors',
                (new OpenApiTypes\ObjectType)
                    ->setDescription('Field-level error details keyed by attribute name.')
                    ->additionalProperties((new OpenApiTypes\ArrayType)->setItems(new OpenApiTypes\StringType)),
            );
    }

    public static function withNullErrors(int $defaultCode): OpenApiTypes\ObjectType
    {
        return self::baseEnvelope($defaultCode)
            ->addProperty(
                'errors',
                (new OpenApiTypes\MixedType)
                    ->setDescription('Error details, or null when not applicable.')
                    ->nullable(true),
            );
    }

    private static function baseEnvelope(int $defaultCode): OpenApiTypes\ObjectType
    {
        return (new OpenApiTypes\ObjectType)
            ->addProperty(
                'success',
                (new OpenApiTypes\IntegerType)
                    ->setDescription('1 when the operation succeeded, 0 otherwise.')
                    ->default(0),
            )
            ->addProperty(
                'code',
                (new OpenApiTypes\IntegerType)
                    ->setDescription('The response code — an HTTP status code or a Haykal business error code.')
                    ->default($defaultCode),
            )
            ->addProperty(
                'message',
                (new OpenApiTypes\StringType)
                    ->setDescription('A human-readable description of the outcome.')
                    ->nullable(true),
            )
            ->addProperty(
                'data',
                (new OpenApiTypes\MixedType)
                    ->setDescription('The response payload, or null on failure.')
                    ->default(null),
            )
            ->setRequired(['success', 'code']);
    }
}
