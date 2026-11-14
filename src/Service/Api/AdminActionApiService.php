<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\Exception\ActionAlreadyRunningException;
use App\Exception\ModifyFailedException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class AdminActionApiService extends AbstractApiService
{
    public function update(string $type): void
    {
        $this->start('update', $type);
    }

    public function calculate(string $type): void
    {
        $this->start('calculate', $type);
    }

    private function start(string $action, string $type): void
    {
        try {
            $this->requestContent('POST', "/istration/{$action}/{$type}");
        } catch (ClientExceptionInterface $e) {
            if (Response::HTTP_CONFLICT === $e->getResponse()->getStatusCode()) {
                throw new ActionAlreadyRunningException(previous: $e);
            }

            throw new ModifyFailedException($e->getMessage(), previous: $e);
        } catch (ExceptionInterface $e) {
            throw new ModifyFailedException($e->getMessage(), previous: $e);
        }
    }
}
