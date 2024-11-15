<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use App\Models\Contact;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ImportContact implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Contact([
            'nom' => $row['sms'],
            'numero' => $row['whatsapp'],
            'email' => $row['email'],
            'user_id' => auth()->user()->id
        ]);
    }
}
