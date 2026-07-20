<?php

declare(strict_types=1);

namespace app\services;

use RuntimeException;

final class UrlGuard
{
    public function assertPublicHttpUrl(string $url): void
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['http', 'https'], true)) {
            throw new RuntimeException('Webhook URL должен использовать HTTP или HTTPS.');
        }
        $host = strtolower(trim((string)($parts['host'] ?? '')));
        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local')) {
            if (!$this->allowPrivate()) {
                throw new RuntimeException('Webhook URL не может указывать на локальный адрес.');
            }
            return;
        }

        $addresses = [];
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            $addresses[] = $host;
        } else {
            $addresses = gethostbynamel($host) ?: [];
            foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (isset($record['ipv6'])) {
                    $addresses[] = (string)$record['ipv6'];
                }
            }
        }
        if (!$addresses) {
            throw new RuntimeException('Не удалось определить адрес webhook-сервера.');
        }
        if ($this->allowPrivate()) {
            return;
        }
        foreach (array_unique($addresses) as $address) {
            if (!filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                throw new RuntimeException('Webhook URL указывает на внутренний или служебный адрес.');
            }
        }
    }

    private function allowPrivate(): bool
    {
        return filter_var(env('WEBHOOK_ALLOW_PRIVATE', false), FILTER_VALIDATE_BOOL);
    }
}
