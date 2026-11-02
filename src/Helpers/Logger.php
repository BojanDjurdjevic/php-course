<?php

namespace App\Helpers;

use RuntimeException;

final class Logger
{
    private string $logDir;

    public function __construct()
    {
        $this->logDir = dirname(__DIR__) . '/private/';

        if (!is_dir($this->logDir)) {
            if (
                !mkdir($this->logDir, 0700, true)
                && !is_dir($this->logDir)
            ) {
                throw new RuntimeException(
                    'Could not create log directory.'
                );
            }
        }

        if (!is_writable($this->logDir)) {
            throw new RuntimeException(
                'Log directory is not writable.'
            );
        }
    }

    private function writeLog(
        string $filename,
        string $message,
        string $level = 'INFO'
    ): void
    {
        $timestamp = gmdate('Y-m-d H:i:s');

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        // Sprečava ubacivanje lažnih log redova
        $clean = static fn(string $value): string =>
            str_replace(["\r", "\n"], ['\\r', '\\n'], $value);

        $logMessage = sprintf(
            "[%s UTC] [%s] [IP: %s] %s | User-Agent: %s\n",
            $timestamp,
            $level,
            $clean((string) $ip),
            $clean($message),
            $clean((string) $userAgent)
        );

        $filepath = $this->logDir . $filename;

        $result = file_put_contents(
            $filepath,
            $logMessage,
            FILE_APPEND | LOCK_EX
        );

        if ($result === false) {
            throw new RuntimeException(
                'Failed to write log file.'
            );
        }
    }

    public function info(string $message): void
    {
        $this->writeLog('info.log', $message, 'INFO');
    }

    public function error(string $message): void
    {
        $this->writeLog('errors.log', $message, 'ERROR');
    }
}