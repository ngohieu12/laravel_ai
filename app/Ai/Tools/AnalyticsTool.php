<?php

namespace App\Ai\Tools;

use App\Models\User;
use App\Services\AnalyticsService;
use Laravel\Ai\Contracts\Tool;

/**
 * Base class for the analytics tools.
 *
 * Engagement analytics (views / shares / favorites, hot topics & keywords) is
 * an admin-only capability: the chatbot politely declines for other roles.
 */
abstract class AnalyticsTool implements Tool
{
    public const ACCESS_DENIED_MESSAGE = '🔒 Số liệu phân tích tương tác (lượt xem, lượt chia sẻ, lượt yêu thích, chủ đề/từ khoá hot) chỉ dành cho quản trị viên. Tài khoản hiện tại không có quyền xem dữ liệu này.';

    protected function analytics(): AnalyticsService
    {
        return new AnalyticsService;
    }

    protected function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    protected function isAdmin(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Normalise a period coming from the model (7/30/90/365/all).
     */
    protected function resolvePeriod(mixed $period): string
    {
        $period = is_string($period) || is_int($period) ? trim((string) $period) : '';

        if ($period === '' || in_array($period, ['all', 'tat-ca', 'tất cả'], true)) {
            return 'all';
        }

        $days = (int) preg_replace('/\D/', '', $period);

        foreach (array_keys(AnalyticsService::PERIODS) as $key) {
            if ($key !== 'all' && (int) $key === $days) {
                return $key;
            }
        }

        return AnalyticsService::DEFAULT_PERIOD;
    }

    protected function clampLimit(mixed $limit, int $default = 5, int $max = 20): int
    {
        $limit = (int) $limit;

        if ($limit < 1) {
            $limit = $default;
        }

        return min($limit, $max);
    }

    protected function number(int|float $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    protected function medal(int $rank): string
    {
        return match ($rank) {
            1 => '🥇',
            2 => '🥈',
            3 => '🥉',
            default => "{$rank}.",
        };
    }
}
