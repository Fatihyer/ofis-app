<?php
namespace App\Http\Controllers;

use App\Services\GoogleSheetService;

class GoogleSheetController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

    public function fetchData($spreadsheetId, $range)
    {
        $data = $this->googleSheetService->getSheetData($spreadsheetId, $range);
        return response()->json($data);
    }
    
}
