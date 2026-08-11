<?php

namespace App\Http\Controllers;

use App\Services\GoogleSheetService;
use Illuminate\Http\Request;

class GoogleDriveController extends Controller
{
    protected $googleSheetService;

    public function __construct(GoogleSheetService $googleSheetService)
    {
        $this->googleSheetService = $googleSheetService;
    }

   

    public function listGoogleSheets()
    {
        $files = $this->googleSheetService->listGoogleSheets();

        if (empty($files)) {
          
         return response()->json(['message' => 'No Google Sheets files found'], 404);
        }

        return response()->json($files);
    }
}
