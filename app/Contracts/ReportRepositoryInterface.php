<?php

declare(strict_types=1);

namespace Noir\App\Contracts;

interface ReportRepositoryInterface
{
    public function summary(string $from, string $toExclusive): array;
    public function orderStatuses(string $from, string $toExclusive): array;
    public function dailySales(string $from, string $toExclusive): array;
    public function applicationStatuses(string $from, string $toExclusive): array;
    public function recentActivity(string $from, string $toExclusive, int $limit = 12): array;
    public function roleBreakdown(): array;
    public function paymentRates(string $from, string $toExclusive): array;
    public function observabilitySummary(string $from, string $toExclusive): array;
}
