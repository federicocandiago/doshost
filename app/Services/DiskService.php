<?php

declare(strict_types=1);

namespace App\Services;

use ZipArchive;

/**
 * Each instance owns storage/disks/{uuid}/bundle.jsdos: a js-dos bundle (zip)
 * holding .jsdos/dosbox.conf plus the whole content of drive C:.
 * The browser sends back the full drive on every save, so the file is always
 * the current state of the disk and its size is the real disk usage.
 */
final class DiskService
{
    public const KEYBOARD_LAYOUTS = [
        'us' => 'English (US)',
        'uk' => 'English (UK)',
        'it' => 'Italiano',
        'de' => 'Deutsch',
        'fr' => 'Français',
        'es' => 'Español',
    ];

    /**
     * End of autoexec: the DOS shell runs inside a loop, so typing EXIT closes the shell
     * instead of killing DOSBox (which would lose the unsaved disk). The flag file tells
     * the player to save and go back to the dashboard.
     */
    public const EXIT_FLAG = 'EXIT.FLG';
    /**
     * DOS clock: the js-dos DOSBox starts with a fixed date (1998). The player writes C:\DHCLOCK.BAT
     * (DATE + TIME from the browser's local time) before booting; autoexec runs it and deletes it.
     */
    public const CLOCK_FILE = 'DHCLOCK.BAT';
    private const CLOCK = "rem doshost-clock\nif exist C:\\" . self::CLOCK_FILE . " call C:\\" . self::CLOCK_FILE . "\nif exist C:\\" . self::CLOCK_FILE . " del C:\\" . self::CLOCK_FILE;

    /** The user's own startup file on C:, run before the shell (PATH, drivers, menus...). */
    private const USER_AUTOEXEC = "rem doshost-user-autoexec\nif exist C:\\AUTOEXEC.BAT call C:\\AUTOEXEC.BAT";
    private const EXIT_LOOP = "rem doshost-exit-loop\n:doshost\ncommand\necho exit>C:\\" . self::EXIT_FLAG . "\ngoto doshost";

    private string $root;

    public function __construct(?string $root = null)
    {
        $this->root = $root ?? storage_path('disks');
    }

    public static function maxBytes(): int
    {
        return max(1, (int) env('DISK_MAX_MB', 64)) * 1024 * 1024;
    }

    public static function warnPercent(): int
    {
        return min(100, max(1, (int) env('DISK_WARN_PERCENT', 80)));
    }

    public function bundlePath(string $uuid): string
    {
        return $this->dir($uuid) . '/bundle.jsdos';
    }

    public function size(string $uuid): int
    {
        clearstatcache();
        $path = $this->bundlePath($uuid);

        return is_file($path) ? (int) filesize($path) : 0;
    }

    /**
     * Builds a new disk. $imports are paths of .zip files whose content is copied into C:.
     * Returns the disk size in bytes.
     */
    public function create(string $uuid, string $name, string $cycles, string $layout, array $imports = []): int
    {
        $dir = $this->dir($uuid);
        if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
            throw new \RuntimeException("Unable to create disk folder {$dir}");
        }

        $zip = new ZipArchive();
        if ($zip->open($this->bundlePath($uuid), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create the DOS bundle');
        }
        $zip->addEmptyDir('.jsdos');
        $zip->addFromString('.jsdos/dosbox.conf', $this->dosboxConf($cycles, $layout));
        $zip->addFromString('README.TXT', $this->readme($name));

        try {
            foreach ($imports as $path) {
                $this->import($zip, $path);
            }
        } finally {
            $zip->close();
        }

        $size = $this->size($uuid);
        if ($size > self::maxBytes()) {
            $this->destroy($uuid);
            throw new \LengthException('Disk too large');
        }

        return $size;
    }

    /** Replaces the disk with the archive sent by the browser. Returns its size. */
    public function save(string $uuid, string $data): int
    {
        if (strlen($data) > self::maxBytes()) {
            throw new \LengthException('Disk too large');
        }
        if (!str_starts_with($data, 'PK')) {
            throw new \InvalidArgumentException('Invalid disk archive');
        }

        $target = $this->bundlePath($uuid);
        $tmp = $target . '.tmp';
        file_put_contents($tmp, $data, LOCK_EX);

        // Must be a readable zip that still contains the DOSBox configuration
        $zip = new ZipArchive();
        $valid = $zip->open($tmp, ZipArchive::RDONLY) === true && $zip->locateName('.jsdos/dosbox.conf') !== false;
        if ($valid) {
            $zip->close();
        }
        if (!$valid) {
            unlink($tmp);
            throw new \InvalidArgumentException('Invalid disk archive');
        }

        if (is_file($target)) {
            copy($target, $target . '.bak'); // previous state, just in case
        }
        rename($tmp, $target);

        return strlen($data);
    }

    /**
     * Brings the autoexec of older disks up to date (no-op when already there):
     * the DOS clock, the call to the user's C:\AUTOEXEC.BAT and the EXIT loop.
     */
    public function ensureExitLoop(string $uuid): void
    {
        $zip = new ZipArchive();
        if ($zip->open($this->bundlePath($uuid)) !== true) {
            return;
        }
        $original = $conf = (string) $zip->getFromName('.jsdos/dosbox.conf');
        $crlf = static fn (string $s): string => str_replace("\n", "\r\n", $s);

        if ($conf !== '' && !str_contains($conf, 'doshost-exit-loop')) {
            $conf = rtrim($conf) . "\r\n" . $crlf(self::EXIT_LOOP) . "\r\n";
        }
        if ($conf !== '' && !str_contains($conf, 'doshost-user-autoexec')) {
            $conf = str_replace('rem doshost-exit-loop', $crlf(self::USER_AUTOEXEC) . "\r\nrem doshost-exit-loop", $conf);
        }
        if ($conf !== '' && !str_contains($conf, 'doshost-clock')) {
            $conf = str_replace('rem doshost-user-autoexec', $crlf(self::CLOCK) . "\r\nrem doshost-user-autoexec", $conf);
        }
        if ($conf !== $original) {
            $zip->addFromString('.jsdos/dosbox.conf', $conf);
        }
        $zip->close();
    }

    /** Changes CPU speed and keyboard layout in the bundle's dosbox.conf. Returns the new disk size. */
    public function updateConfig(string $uuid, string $cycles, string $layout): int
    {
        $layout = array_key_exists($layout, self::KEYBOARD_LAYOUTS) ? $layout : 'us';
        $zip = new ZipArchive();
        if ($zip->open($this->bundlePath($uuid)) !== true) {
            throw new \RuntimeException('Unable to open the disk');
        }
        $conf = (string) $zip->getFromName('.jsdos/dosbox.conf');
        $conf = $this->setConfValue($conf, 'cpu', 'cycles', $cycles);
        $conf = $this->setConfValue($conf, 'dos', 'keyboardlayout', $layout);
        $zip->addFromString('.jsdos/dosbox.conf', $conf);
        $zip->close();

        return $this->size($uuid);
    }

    private function setConfValue(string $conf, string $section, string $key, string $value): string
    {
        $line = '/^(\s*' . preg_quote($key, '/') . '\s*=)[^\r\n]*/mi';
        if (preg_match($line, $conf)) {
            return (string) preg_replace($line, '${1}' . $value, $conf);
        }
        $header = '/^\[' . preg_quote($section, '/') . '\][^\r\n]*/mi';
        if (preg_match($header, $conf)) {
            return (string) preg_replace($header, "[{$section}]\r\n{$key}={$value}", $conf, 1);
        }

        return "[{$section}]\r\n{$key}={$value}\r\n\r\n" . $conf;
    }

    public function destroy(string $uuid): void
    {
        $dir = $this->dir($uuid);
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $file) {
            if (is_file("{$dir}/{$file}")) {
                unlink("{$dir}/{$file}");
            }
        }
        rmdir($dir);
    }

    /** Copies every entry of an uploaded zip into the bundle, refusing unsafe paths. */
    /**
     * Adds the content of uploaded zips to an existing disk, without touching what is there:
     * a file whose path already exists on C: (case-insensitive, like DOS) is skipped.
     * Works on a copy that replaces the disk only if everything went well.
     *
     * @return array{added: int, skipped: int, bytes: int}
     */
    public function addFiles(string $uuid, array $zips): array
    {
        $bundle = $this->bundlePath($uuid);
        $tmp = $bundle . '.adding';
        if (!copy($bundle, $tmp)) {
            throw new \RuntimeException('Unable to copy the disk');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp) !== true) {
            unlink($tmp);
            throw new \RuntimeException('Unable to open the disk');
        }

        $existing = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $existing[strtolower(rtrim((string) $zip->getNameIndex($i), '/'))] = true;
        }

        $added = $skipped = 0;
        try {
            foreach ($zips as $path) {
                $result = $this->import($zip, $path, $existing);
                $added += $result['added'];
                $skipped += $result['skipped'];
            }
        } catch (\Throwable $e) {
            $zip->close();
            unlink($tmp);
            throw $e;
        }
        $zip->close();

        clearstatcache();
        if (filesize($tmp) > self::maxBytes()) {
            unlink($tmp);
            throw new \LengthException('Disk too large');
        }
        copy($bundle, $bundle . '.bak');
        rename($tmp, $bundle);

        return ['added' => $added, 'skipped' => $skipped, 'bytes' => $this->size($uuid)];
    }

    /**
     * Copies the entries of an uploaded zip into the bundle, refusing unsafe paths.
     * With $existing (lowercased paths already on the disk) nothing is ever overwritten.
     *
     * @return array{added: int, skipped: int}
     */
    private function import(ZipArchive $target, string $path, ?array &$existing = null): array
    {
        $source = new ZipArchive();
        if ($source->open($path, ZipArchive::RDONLY) !== true) {
            throw new \InvalidArgumentException('Invalid zip file');
        }

        $total = $added = $skipped = 0;
        for ($i = 0; $i < $source->numFiles; $i++) {
            $stat = $source->statIndex($i);
            $name = $this->safeName((string) $stat['name']);
            if ($name === null) {
                continue;
            }
            // Guard against zip bombs: the uncompressed content is limited too
            $total += (int) $stat['size'];
            if ($total > self::maxBytes() * 4) {
                $source->close();
                throw new \LengthException('Disk too large');
            }

            $isDir = str_ends_with($name, '/');
            $key = strtolower(rtrim($name, '/'));
            if ($existing !== null && isset($existing[$key])) {
                $skipped += $isDir ? 0 : 1;
                continue;
            }

            if ($isDir) {
                $target->addEmptyDir(rtrim($name, '/'));
            } else {
                $target->addFromString($name, (string) $source->getFromIndex($i));
                $added++;
            }
            if ($existing !== null) {
                $existing[$key] = true;
            }
        }
        $source->close();

        return ['added' => $added, 'skipped' => $skipped];
    }

    private function safeName(string $name): ?string
    {
        $name = ltrim(str_replace('\\', '/', $name), '/');
        $parts = explode('/', $name);

        if ($name === '' || in_array('..', $parts, true) || in_array($parts[0], ['__MACOSX', '.jsdos'], true)
            || basename($name) === '.DS_Store') {
            return null;
        }

        return $name;
    }

    private function dir(string $uuid): string
    {
        if (!preg_match('/^[a-f0-9-]{36}$/', $uuid)) {
            throw new \InvalidArgumentException('Invalid instance id');
        }

        return $this->root . '/' . $uuid;
    }

    private function dosboxConf(string $cycles, string $layout): string
    {
        $layout = array_key_exists($layout, self::KEYBOARD_LAYOUTS) ? $layout : 'us';
        $conf = <<<CONF
        [sdl]
        autolock=false

        [dosbox]
        machine=svga_s3
        memsize=16

        [cpu]
        core=auto
        cputype=auto
        cycles={$cycles}

        [mixer]
        rate=44100

        [dos]
        keyboardlayout={$layout}

        [autoexec]
        @echo off
        mount c .
        c:
        CONF;
        $conf .= "\n" . self::CLOCK . "\ntype README.TXT\n" . self::USER_AUTOEXEC . "\n" . self::EXIT_LOOP;

        return str_replace("\n", "\r\n", $conf) . "\r\n";
    }

    private function readme(string $name): string
    {
        $name = (string) preg_replace('/[^\x20-\x7E]/', '?', $name);
        $lines = [
            '',
            "  Welcome to {$name}",
            '  ' . str_repeat('=', 11 + strlen($name)),
            '',
            '  This is your persistent C: drive.',
            '  Everything you create or copy here is saved automatically',
            '  and when you leave the page.',
            '',
            '  Tip: use the file manager in the js-dos side bar to upload',
            '  programs and games into C:.',
            '',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }
}
