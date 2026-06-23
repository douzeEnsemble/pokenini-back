<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Utils\JsonDecoder;

class GetActionLogsApiService extends AbstractApiService
{
    /**
     * @return array<int, array{
     *   action_type: string,
     *   current: array{
     *     created_at: string,
     *     done_at: null|string,
     *     execution_time: null|int,
     *     details: array<string, int>,
     *     error_trace: null|string,
     *   },
     *   last: null|array{
     *     created_at: string,
     *     done_at: null|string,
     *     execution_time: null|int,
     *     details: array<string, int>,
     *     error_trace: null|string,
     *   },
     * }>
     */
    public function get(): array
    {
        $json = $this->requestContent('GET', '/action_logs');

        /** @var array<int, array{action_type: string, current: array{created_at: string, done_at: null|string, execution_time: null|int, details: array<string, int>, error_trace: null|string}, last: null|array{created_at: string, done_at: null|string, execution_time: null|int, details: array<string, int>, error_trace: null|string}}> */
        return JsonDecoder::decode($json);
    }
}
