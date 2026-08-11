<?php

namespace App\Exports;

use App\Models\FuelExcel;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class FuelImport implements ToModel, WithHeadingRow
{
    private function get($row, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    private function parseNumber($value)
    {
        if ($value === null) return null;

        $value = str_replace([' ', ','], ['', '.'], $value);

        return is_numeric($value) ? $value : null;
    }

    public function model(array $row)
    {
        $dateRaw = $this->get($row, [
            'heure_dautorisation',
            'heure_d_authorisation',
            'temps_de_transaction',
        ]);

        try {
            $authorizedAt = is_numeric($dateRaw)
                ? Carbon::instance(Date::excelToDateTimeObject($dateRaw))
                : Carbon::parse($dateRaw);
        } catch (\Exception $e) {
            $authorizedAt = null;
        }

        return new FuelExcel([
            'vehicule_raw' => $this->get($row, [
                'n_dimmatriculation',
                'numero_dimmatriculation',
            ]),

            'card_raw' => $this->get($row, [
                'numero_de_lobjet_de_decompte_pan_carte_boitier',
                'numero_de_l_objet_de_decompte_pan_carte_boitier',
                'no_de_carteboite',
            ]),

            'authorized_at' => $authorizedAt,

            'location' => $this->get($row, [
                'localite',
            ]),

            'volume' => $this->parseNumber($this->get($row, [
                'volume',
            ])),

            'amount' => $this->parseNumber($this->get($row, [
                'montant_brut_de_lautorisation',
                'montant_brut_de_l_authorisation',
                'valeur_totale_brute',
            ])),

            'fuel_type' => $this->get($row, [
                'type_de_marchandise',
            ]),

            'kilometrage' => $this->parseNumber($this->get($row, [
                'kilometrage',
            ])),

            'original_data' => json_encode($row),
        ]);
    }
}