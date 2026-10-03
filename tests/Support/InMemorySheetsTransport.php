<?php

namespace Tests\Support;

use AmazingBV\GoogleSheetsDatabaseDriver\Contracts\SheetsTransport;
use AmazingBV\GoogleSheetsDatabaseDriver\Exceptions\GoogleSheetsException;

/**
 * In-process fake for the google-sheets database driver, used in automated
 * tests so they never call the real Google Sheets API. Mirrors the shape of
 * the package's own test-only AmazingBV\GoogleSheetsDatabaseDriver\Tests\Support\InMemorySheetsTransport,
 * which is only autoloaded for that package's own test suite and is not
 * available to consuming applications.
 */
class InMemorySheetsTransport implements SheetsTransport
{
    /**
     * @var array<string, array<string, array{hidden: bool, sheetId: int, rows: array<int, array<int, mixed>>}>>
     */
    private static array $spreadsheets = [];

    private string $spreadsheetId;

    /**
     * @param  array{database?: string}  $config
     */
    public function __construct(array $config)
    {
        $this->spreadsheetId = (string) ($config['database'] ?? 'testing');

        self::$spreadsheets[$this->spreadsheetId] ??= [];
    }

    public static function reset(): void
    {
        self::$spreadsheets = [];
    }

    public function assertAccessible(): void
    {
        //
    }

    public function listSheets(): array
    {
        $sheets = [];

        foreach (self::$spreadsheets[$this->spreadsheetId] as $title => $sheet) {
            $sheets[$title] = [
                'hidden' => $sheet['hidden'],
                'sheetId' => $sheet['sheetId'],
            ];
        }

        return $sheets;
    }

    public function getSheetValues(string $title): array
    {
        return self::$spreadsheets[$this->spreadsheetId][$title]['rows'] ?? [];
    }

    public function setSheetValues(string $title, array $rows): void
    {
        if (! isset(self::$spreadsheets[$this->spreadsheetId][$title])) {
            $this->createSheet($title);
        }

        self::$spreadsheets[$this->spreadsheetId][$title]['rows'] = $rows;
    }

    public function createSheet(string $title, bool $hidden = false): void
    {
        if (isset(self::$spreadsheets[$this->spreadsheetId][$title])) {
            throw new GoogleSheetsException(sprintf('Sheet [%s] already exists.', $title));
        }

        self::$spreadsheets[$this->spreadsheetId][$title] = [
            'hidden' => $hidden,
            'sheetId' => count(self::$spreadsheets[$this->spreadsheetId]) + 1,
            'rows' => [],
        ];
    }

    public function deleteSheet(string $title): void
    {
        unset(self::$spreadsheets[$this->spreadsheetId][$title]);
    }

    public function renameSheet(string $from, string $to): void
    {
        if (! isset(self::$spreadsheets[$this->spreadsheetId][$from])) {
            throw new GoogleSheetsException(sprintf('Sheet [%s] does not exist.', $from));
        }

        self::$spreadsheets[$this->spreadsheetId][$to] = self::$spreadsheets[$this->spreadsheetId][$from];
        unset(self::$spreadsheets[$this->spreadsheetId][$from]);
    }

    public function setSheetHidden(string $title, bool $hidden): void
    {
        if (! isset(self::$spreadsheets[$this->spreadsheetId][$title])) {
            throw new GoogleSheetsException(sprintf('Sheet [%s] does not exist.', $title));
        }

        self::$spreadsheets[$this->spreadsheetId][$title]['hidden'] = $hidden;
    }

    public function renderDatabaseIndexSheet(string $title, array $entries): void
    {
        if (! isset(self::$spreadsheets[$this->spreadsheetId][$title])) {
            $this->createSheet($title);
        }

        self::$spreadsheets[$this->spreadsheetId][$title]['rows'] = [['Database Index']];
    }
}
