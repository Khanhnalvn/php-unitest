<?php

use App\RealFileSystem;

beforeEach(function() {
    $this->fs = new RealFileSystem();
    $this->testDir = sys_get_temp_dir() . '/test_' . uniqid();
    $this->testFile = $this->testDir . '/test.csv';
    mkdir($this->testDir);
});

afterEach(function() {
    if (file_exists($this->testFile)) {
        unlink($this->testFile);
    }
    if (is_dir($this->testDir)) {
        rmdir($this->testDir);
    }
});

it('checks if directory exists', function() {
    expect($this->fs->isDir($this->testDir))->toBeTrue();
    expect($this->fs->isDir($this->testDir . '/nonexistent'))->toBeFalse();
});

it('creates directory', function() {
    $newDir = $this->testDir . '/new';
    expect($this->fs->mkdir($newDir))->toBeTrue();
    expect(is_dir($newDir))->toBeTrue();
    rmdir($newDir);
});

it('checks if path is writable', function() {
    expect($this->fs->isWritable($this->testDir))->toBeTrue();
});

it('opens and closes file', function() {
    $handle = $this->fs->fopen($this->testFile, 'w');
    expect($handle)->toBeResource();
    expect($this->fs->fclose($handle))->toBeTrue();
});

it('writes CSV data', function() {
    $handle = $this->fs->fopen($this->testFile, 'w');
    $result = $this->fs->fputcsv($handle, ['test', 'data']);
    expect($result)->toBeGreaterThan(0);
    $this->fs->fclose($handle);
    
    $content = file_get_contents($this->testFile);
    expect($content)->toContain('test,data');
});

it('flushes file buffer', function() {
    $handle = $this->fs->fopen($this->testFile, 'w');
    fwrite($handle, 'test');
    expect($this->fs->fflush($handle))->toBeTrue();
    $this->fs->fclose($handle);
});