<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TableExport implements FromCollection, WithTitle, WithHeadings
{
    protected $data;
    protected $sheetName;
    protected $type;

    public function __construct(array $data, string $sheetName, string $type = null)
    {
        $this->data = $data;
        $this->sheetName = $sheetName;
        $this->type = $type;
    }

    public function collection()
    {
        return new Collection($this->data);
    }

    public function headings(): array
    {
        return $this->type == 'dest' ? [['Titre', 'Canal', 'Message'],] : [];
    }
    public function title(): string
    {
        return $this->sheetName;
    }
}
