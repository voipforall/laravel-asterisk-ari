<?php

namespace VoIPforAll\AsteriskAri\Concerns;

use InvalidArgumentException;

trait BuildsAriPaths
{
    /**
     * Encode a single identifier (channel, playback, application, module...) as one URL path segment.
     *
     * @throws InvalidArgumentException when the identifier is empty, a dot segment or contains control characters.
     */
    protected function segment(string $value): string
    {
        if ($value === '' || $value === '.' || $value === '..' || preg_match('/[\x00-\x1F\x7F]/', $value)) {
            throw new InvalidArgumentException('Invalid ARI identifier.');
        }

        return rawurlencode($value);
    }

    /**
     * Encode a recording name, which may contain sub-directories ("voicebot/turn-1"), rejecting path traversal.
     *
     * @throws InvalidArgumentException when the name is absolute, has empty or dot segments, or contains control characters.
     */
    protected function recordingPath(string $name): string
    {
        if ($name === '' || str_starts_with($name, '/') || str_contains($name, '\\')) {
            throw new InvalidArgumentException('Invalid recording name.');
        }

        return implode('/', array_map(fn (string $part) => $this->segment($part), explode('/', $name)));
    }

    /**
     * Join a value that ARI accepts as a comma-separated list.
     *
     * @param  string|array<int, string>  $values
     */
    protected function commaList(string|array $values): string
    {
        return is_array($values) ? implode(',', $values) : $values;
    }

    /**
     * Drop null values while keeping falsy ones such as 0 and false.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    protected function withoutNulls(array $params): array
    {
        return array_filter($params, fn ($value) => $value !== null);
    }
}
