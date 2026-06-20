<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\ActionLog;
use App\DTO\ActionLogData;
use App\Utils\JsonDecoder;

class GetActionLogsApiService extends AbstractApiService
{
    /**
     * @return array<string, ActionLogData>
     */
    public function get(): array
    {
        $json = $this->requestContent(
            'GET',
            '/action_logs'
        );

        /** @var array<int, array{
         *  action_type: string,
         *  current: array{
         *    created_at: string,
         *    done_at?: null|string,
         *    execution_time?: null|int|string,
         *    details?: int[],
         *    error_trace?: null|string,
         *  },
         *  last?: null|array{
         *    created_at: string,
         *    done_at?: null|string,
         *    execution_time?: null|int|string,
         *    details?: int[],
         *    error_trace?: null|string,
         *  },
         * }> */
        $actionLogsData = JsonDecoder::decode($json);

        $list = [];
        foreach ($actionLogsData as $entry) {
            $item = $entry['action_type'];

            $currentData = $entry['current'];

            $lastData = $entry['last'] ?? null;

            $list[$item] = new ActionLogData(
                $item,
                ActionLog::createFromArray($currentData),
                $lastData ? ActionLog::createFromArray($lastData) : null,
            );
        }

        return $list;
    }
}
