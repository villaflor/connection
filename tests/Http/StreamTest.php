<?php

use Villaflor\Connection\Http\Stream;

it('can create stream with content', function () {
    $stream = new Stream('test content');

    expect((string) $stream)->toBe('test content');
    expect($stream->getSize())->toBe(12);
});

it('can read from stream', function () {
    $stream = new Stream('test content');

    expect($stream->read(4))->toBe('test');
    expect($stream->read(8))->toBe(' content');
    expect($stream->eof())->toBeTrue();
});

it('can get remaining contents', function () {
    $stream = new Stream('test content');

    $stream->read(5); // Read 'test '
    expect($stream->getContents())->toBe('content');
});

it('can rewind stream', function () {
    $stream = new Stream('test content');

    $stream->read(4);
    expect($stream->tell())->toBe(4);

    $stream->rewind();
    expect($stream->tell())->toBe(0);
    expect($stream->read(4))->toBe('test');
});

it('can seek in stream', function () {
    $stream = new Stream('test content');

    $stream->seek(5);
    expect($stream->read(7))->toBe('content');

    $stream->seek(-7, SEEK_CUR);
    expect($stream->read(7))->toBe('content');

    $stream->seek(-7, SEEK_END);
    expect($stream->read(7))->toBe('content');
});

it('can write to stream', function () {
    $stream = new Stream('test');

    $bytesWritten = $stream->write(' content');

    expect($bytesWritten)->toBe(8);
    expect((string) $stream)->toBe('test content');
});

it('reports readable and writable', function () {
    $stream = new Stream('test');

    expect($stream->isReadable())->toBeTrue();
    expect($stream->isWritable())->toBeTrue();
    expect($stream->isSeekable())->toBeTrue();
});

it('can detach stream', function () {
    $stream = new Stream('test');

    $result = $stream->detach();

    expect($result)->toBeNull();
    expect((string) $stream)->toBe('');
});

it('can close stream', function () {
    $stream = new Stream('test');
    $stream->close();

    // Close is a no-op for string streams, content should still be accessible
    expect((string) $stream)->toBe('test');
});

it('can handle invalid seek whence parameter', function () {
    $stream = new Stream('test content');

    expect(fn () => $stream->seek(0, 999))->toThrow(RuntimeException::class);
});

it('can clamp position when seeking before start', function () {
    $stream = new Stream('test content');

    $stream->seek(-100, SEEK_SET);
    expect($stream->tell())->toBe(0);
});

it('can clamp position when seeking after end', function () {
    $stream = new Stream('test content');

    $stream->seek(1000, SEEK_SET);
    expect($stream->tell())->toBe(12); // Length of 'test content'
});

it('can get metadata', function () {
    $stream = new Stream('test');

    expect($stream->getMetadata())->toBe([]);
    expect($stream->getMetadata('key'))->toBeNull();
});
