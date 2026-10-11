# CSV Kit

A small framework-independent PHP library extracted from Fieldnotes Academy's learner import workflow. Streams a one-column email CSV with stable, one-based data-row cursors.

## Standalone use

Requires PHP 8.4+ for the bundled development test runner; the reader itself supports PHP 8.3+.

```sh
composer install
composer test
```

```php
use Fieldnotes\CsvKit\Reader;

foreach ((new Reader)->rows('/private/learners.csv', after: $savedCursor) as $row => $data) {
    // In YOUR database transaction: apply the row and persist $row together.
    enroll($data['email']);
}
```

The file must have exactly one `email` header. UTF-8 BOM, CRLF, quoted fields, whitespace, and email case normalization are supported. Invalid headers, extra fields, blank records, and invalid email addresses throw `InvalidArgumentException` with the data-row number where applicable. The generator closes its file handle when finished or destroyed.

Resume uses a row cursor, not a byte offset: skipped rows are scanned again. Keep the input immutable across attempts. The library does not implement durable checkpoints, queues, rollback, account lookup, or exactly-once side effects; Fieldnotes Academy demonstrates those application responsibilities. It fails fast at the first malformed row. Memory usage is bounded by the largest row, not the full file.

This is the copy bundled with Fieldnotes Academy. The standalone package is published on [Packagist](https://packagist.org/packages/cynthiaowolabi/csv-kit):

```sh
composer require cynthiaowolabi/csv-kit:^1.0
```

See the [standalone repository](https://github.com/adebolaowolabi32/csv-kit) for releases and package development. Fieldnotes continues to use this bundled copy through a Composer path repository.

MIT © Cynthia Owolabi.
