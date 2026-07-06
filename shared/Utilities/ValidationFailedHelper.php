<?php

namespace Shared\Utilities;

use Illuminate\Support\MessageBag;

class ValidationFailedHelper
{
    public static function validationFailed($validationFailed): array
    {
        if ($validationFailed instanceof MessageBag) {
            return self::flattenMessageBag($validationFailed);
        }

        if (is_array($validationFailed)) {
            return self::flattenArray($validationFailed);
        }

        if (is_string($validationFailed)) {
            return [$validationFailed];
        }

        return [];
    }

    private static function flattenMessageBag(MessageBag $messageBag): array
    {
        $flattened = [];
        foreach ($messageBag->toArray() as $messages) {
            foreach ($messages as $message) {
                $flattened[] = $message;
            }
        }
        return $flattened;
    }

    private static function flattenArray(array $errors): array
    {
        $flattened = [];
        foreach ($errors as $value) {
            if (is_array($value)) {
                $flattened = array_merge($flattened, self::extractMessagesFromArray($value));
            } elseif (is_string($value)) {
                $flattened[] = $value;
            }
        }
        return $flattened;
    }

    private static function extractMessagesFromArray(array $value): array
    {
        if (isset($value['error']) && is_string($value['error'])) {
            return [$value['error']];
        }

        $messages = [];
        foreach ($value as $message) {
            if (is_string($message)) {
                $messages[] = $message;
            }
        }
        return $messages;
    }
}
