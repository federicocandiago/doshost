<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AvatarService;
use App\Services\DiskService;

final class User extends Model
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?self
    {
        return self::first('email = ?', [mb_strtolower(trim($email))]);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Reads the column explicitly (not from SELECT *): if the migration that adds it hasn't run yet,
     * the query fails with "unknown column" and the app applies it automatically.
     */
    public function isVerified(): bool
    {
        return static::db()->scalar('SELECT email_verified_at FROM users WHERE id = ?', [$this->id]) !== null;
    }

    /** New confirmation link: only the hash of the token is stored. Returns the token for the email. */
    public function newVerificationToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['verification_token' => hash('sha256', $token), 'verification_sent_at' => date('Y-m-d H:i:s')]);

        return $token;
    }

    public static function findByVerificationToken(string $token): ?self
    {
        return preg_match('/^[a-f0-9]{64}$/', $token) ? self::first('verification_token = ?', [hash('sha256', $token)]) : null;
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /** Personal limit if set by an admin, otherwise the global one. */
    public function instanceLimit(): int
    {
        return $this->max_instances !== null ? (int) $this->max_instances : Setting::maxInstances();
    }

    /** @return Instance[] */
    public function instances(string $sort = 'recent'): array
    {
        return Instance::where('user_id = ?', [$this->id], Instance::SORTS[$sort] ?? Instance::SORTS['recent']);
    }

    public function instanceCount(): int
    {
        return Instance::count('user_id = ?', [$this->id]);
    }

    public function canCreateInstance(): bool
    {
        return $this->instanceCount() < $this->instanceLimit();
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim((string) $this->name)) ?: [''];

        return mb_strtoupper(mb_substr($words[0], 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
    }

    /** Deletes the account together with its disks and profile picture. */
    public function deleteWithData(): void
    {
        $disks = new DiskService();
        foreach ($this->instances() as $instance) {
            $disks->destroy($instance->uuid);
        }
        (new AvatarService())->delete($this->avatar);
        $this->delete(); // instance rows go away via ON DELETE CASCADE
    }
}
