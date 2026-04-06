<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\ActionLog;
use App\DTO\ActionLogData;
use App\Utils\JsonDecoder;

class GetActionLogsService extends AbstractApiService
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

        /** @var array<string, int[][]|int[][][]|string[][]> */
        $actionLogsData = JsonDecoder::decode($json);

        $list = [];
        foreach ($actionLogsData as $item => $data) {
            /** @var array{
             *  created_at: string,
             *  done_at?: null|string,
             *  execution_time?: null|int|string,
             *  details?: int[],
             *  error_trace?: null|string,
             * } $currentData
             */
            $currentData = $data['current'];

            /** @var ?array{
             *  created_at: string,
             *  done_at?: null|string,
             *  execution_time?: null|int|string,
             *  details?: int[],
             *  error_trace?: null|string,
             * } $lastData
             */
            $lastData = $data['last'] ?? null;

            $list[$item] = new ActionLogData(
                $item,
                ActionLog::createFromArray($currentData),
                $lastData ? ActionLog::createFromArray($lastData) : null,
            );
        }

        return $list;
    }
}
