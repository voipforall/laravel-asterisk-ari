<?php

namespace VoIPforAll\AsteriskAri\Contracts;

/**
 * A client that can also return a raw (binary) response body. Kept apart from
 * AriClientInterface so existing implementations of that contract keep working.
 */
interface RawAriClientInterface extends AriClientInterface
{
    /**
     * Perform a GET request and return the raw response body (e.g. a stored recording's audio).
     *
     * @param  array<string, mixed>  $query
     */
    public function getRaw(string $uri, array $query = []): string;
}
