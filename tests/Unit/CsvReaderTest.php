<?php

namespace Tests\Unit;

use Fieldnotes\CsvKit\Reader;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CsvReaderTest extends TestCase
{
    private array $files = [];

    private function csv(string $text): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv-test-');
        file_put_contents($path, $text);
        $this->files[] = $path;

        return $path;
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $path) {
            unlink($path);
        }parent::tearDown();
    }

    public function test_streams_bom_quotes_normalization_and_stable_cursor(): void
    {
        $path = $this->csv("\xEF\xBB\xBFemail\r\n\"ONE@example.test\"\r\ntwo@example.test\r\n");
        $reader = new Reader;
        $this->assertSame([1 => ['email' => 'one@example.test'], 2 => ['email' => 'two@example.test']], iterator_to_array($reader->rows($path)));
        $this->assertSame([2 => ['email' => 'two@example.test']], iterator_to_array($reader->rows($path, 1)));
    }

    public function test_rejects_wrong_header(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array((new Reader)->rows($this->csv("name\nAlex\n")));
    }

    public function test_rejects_malformed_rows_with_row_number(): void
    {
        $this->expectExceptionMessage('Row 2');
        iterator_to_array((new Reader)->rows($this->csv("email\none@example.test\nnot-an-email\n")));
    }

    public function test_rejects_extra_columns(): void
    {
        $this->expectException(InvalidArgumentException::class);
        iterator_to_array((new Reader)->rows($this->csv("email\none@example.test,extra\n")));
    }

    public function test_rejects_empty_file(): void
    {
        $this->expectExceptionMessage('CSV is empty.');
        iterator_to_array((new Reader)->rows($this->csv('')));
    }
}
