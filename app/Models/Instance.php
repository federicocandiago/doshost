<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\DiskService;

final class Instance extends Model
{
    protected static string $table = 'instances';

    public const SORTS = [
        'recent' => 'is_favorite DESC, COALESCE(last_played_at, created_at) DESC',
        'name' => 'is_favorite DESC, name ASC',
        'size' => 'is_favorite DESC, disk_bytes DESC',
    ];

    /** DOSBox "cycles" value => label */
    public static function cpuProfiles(): array
    {
        return [
            'auto' => __('Automatic'),
            '3000' => __('286 (slow)'),
            '10000' => __('386'),
            '25000' => __('486'),
            'max' => __('Max speed'),
        ];
    }

    public static function findByUuid(string $uuid): ?self
    {
        return self::first('uuid = ?', [$uuid]);
    }

    public function belongsTo(User $user): bool
    {
        return (int) $this->user_id === (int) $user->id;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function usagePercent(): int
    {
        return min(100, (int) floor((int) $this->disk_bytes / DiskService::maxBytes() * 100));
    }

    /** 'full' when saving is blocked, 'warning' above DISK_WARN_PERCENT, otherwise null. */
    public function diskState(): ?string
    {
        return match (true) {
            (bool) $this->disk_full => 'full',
            $this->usagePercent() >= DiskService::warnPercent() => 'warning',
            default => null,
        };
    }
}
