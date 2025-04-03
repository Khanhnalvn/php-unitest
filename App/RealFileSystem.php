<?php
namespace App;

class RealFileSystem implements FileSystem {
    public function isDir(string $path): bool {
        return is_dir($path);
    }

    public function mkdir(string $path, int $permissions = 0777, bool $recursive = true): bool {
        return mkdir($path, $permissions, $recursive);
    }

    public function isWritable(string $path): bool {
        return is_writable($path);
    }

    public function fopen(string $path, string $mode) {
        return fopen($path, $mode);
    }

    public function fclose($handle): bool {
        return fclose($handle);
    }

    public function fputcsv($handle, array $fields): bool|int {
        return fputcsv($handle, $fields);
    }

    public function fflush($handle): bool {
        return fflush($handle);
    }
}