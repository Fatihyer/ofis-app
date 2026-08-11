<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Acente;
use App\Models\FuelCard;
use App\Models\FuelPurchase;
use App\Models\Vehicule;
use App\Models\Firma;
use App\Models\FuelExcel;
use App\Models\Option;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\fuelImport;



class FuelController extends Controller
{
   
    public function __construct() {
        $this->middleware('auth');
        $this->middleware('permission:fuel.manage');
      }
   
   
   
    public function index()
    {
        $fuelCards = FuelCard::with('acente', 'fuelPurchases.vehicule')->get();
        $vehicules = Vehicule::all(); // Add this line
        return view('fuel.index', compact('fuelCards', 'vehicules')); 
    }

    public function cardlist()
    {
        $fuelCards = FuelCard::withTrashed()->get();
        return view('fuel.cardlist', compact('fuelCards'));  

    }
    public function deleteCard($id)
    {
        $fuelCard = FuelCard::findOrFail($id);
    $fuelCard->delete();

    return redirect()->route('fuel.cardlist')->with('success', 'Fuel card deleted successfully.');

    }

    public function recoverCard($id)
{
    $fuelCard = FuelCard::withTrashed()->findOrFail($id);
    $fuelCard->restore();

    return redirect()->route('fuel.cardlist')->with('success', 'Fuel card restored successfully.');
}



    public function list(Request $request)
    {
        // Get the selected date range, card type, and acente from the request
        

        $sortBy = $request->input('sort_by', 'purchase_date');
        $order = $request->input('order', 'desc');

        $startDate = $request->input('start_date', now()->subMonth()->toDateString());
        $endDate = $request->input('end_date', date('Y-m-d ', strtotime('tomorrow')));
        $endDate = date('Y-m-d 23:59:59', strtotime($endDate));
        $cardType = $request->input('card_type');
        $acenteId = $request->input('acente_id');
        $vehiculeId = $request->input('vehicule_id');
    
        // Query the fuel purchases based on the selected filters
        $fuelPurchasesQuery = FuelPurchase::with(['fuelCard.acente', 'vehicule'])
            ->whereHas('fuelCard', function($query) use ($cardType) {
                if ($cardType) { // Add this condition to check if $cardType is provided
                    $query->where('card_type', $cardType);
                }
            })
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->orderBy($sortBy, $order); // Take only the last 20 records
    
        if ($acenteId) {
            $fuelPurchasesQuery->whereHas('fuelCard', function($query) use ($acenteId) {
                $query->where('acente_id', $acenteId);
            });
        }
    
        if ($vehiculeId) {
            $fuelPurchasesQuery->where('vehicule_id', $vehiculeId);
        }
        
        $fuelPurchasesQuery = $fuelPurchasesQuery->orderBy($sortBy, $order);
        $fuelPurchases = $fuelPurchasesQuery->get();
    
        // Calculate the total amount
        $totalAmount = $fuelPurchases->sum('amount');
    
        // Get the list of unique acentes from the filtered fuel purchases
        $driverIds = Option::where('name', 'driverIds')->orderBy('name')->value('value'); // Retrieve the 'value' column as a string
        $driverIdsArray = explode(',', $driverIds); // Convert the string to an array
        $firma = Firma::whereIn('id', $driverIdsArray)->get(); 
        $acentes = $firma->pluck('acentes')->flatten();
        $vehicules = Vehicule::all();
    
        return view('fuel.list', compact('fuelPurchases', 'acentes', 'totalAmount', 'vehicules'));
    }
    
    public function create()
    {
       
      
        $driverIds = Option::where('name', 'driverIds')->orderBy('name')->value('value'); // Retrieve the 'value' column as a string
        $driverIdsArray = explode(',', $driverIds); // Convert the string to an array
        $firma = Firma::whereIn('id', $driverIdsArray)->get(); // Single $ sign here
        $drivers = $firma->isNotEmpty() ? $firma->pluck('acentes')->flatten() : collect();
          $vehicules = Vehicule::orderBy('name')->get();       
       
        return view('fuel.create', compact('drivers','vehicules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'card_number' => 'required|unique:fuel_cards,card_number',
            'card_type' => 'required',
               'acente_id'   => 'nullable|exists:acentes,id',
    'vehicule_id' => 'nullable|exists:vehicules,id',
        ]);
        
        
        $fuelCard = FuelCard::create($request->only('card_number', 'card_type', 'acente_id', 'vehicule_id'));
        return redirect()->route('fuel.index')->with('success', 'Fuel card added successfully');
    }

    public function edit($id)
    {
        $fuelCard = FuelCard::findOrFail($id);
        $driverIds = Option::where('name', 'driverIds')->orderBy('name')->value('value'); // Retrieve the 'value' column as a string
        $driverIdsArray = explode(',', $driverIds); // Convert the string to an array
        $firma = Firma::whereIn('id', $driverIdsArray)->get(); // Single $ sign here
        $drivers = $firma->isNotEmpty() ? $firma->pluck('acentes')->flatten() : collect();
        $vehicules = Vehicule::orderBy('name')->get();
                
        
        return view('fuel.update', compact('fuelCard', 'drivers', 'vehicules'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'card_number' => 'required|string|max:255',
            'card_type' => 'required|string',
            'acente_id'   => 'nullable|exists:acentes,id',
    'vehicule_id' => 'nullable|exists:vehicules,id',

        ]);

        $fuelCard = FuelCard::findOrFail($id);
        $fuelCard->update([
            'card_number' => $request->card_number,
            'card_type' => $request->card_type,
         
             'acente_id'   => $request->acente_id,
    'vehicule_id' => $request->vehicule_id,
        ]);

        return redirect()->route('fuel.index')->with('success', 'Fuel card updated successfully!');
    }
    public function showPurchases($fuelCardId)
    {
        $fuelCard = FuelCard::with('fuelPurchases.vehicule')->findOrFail($fuelCardId);
        return view('fuel.showPurchases', compact('fuelCard'));
    }
    public function createPurchase()
    
    {
      
        $driverIds = Option::where('name', 'driverIds')->orderBy('name')->value('value'); // Retrieve the 'value' column as a string
        $driverIdsArray = explode(',', $driverIds); // Convert the string to an array
        $firma = Firma::whereIn('id', $driverIdsArray)->get(); // Single $ sign here
        $drivers = $firma->isNotEmpty() ? $firma->pluck('acentes')->flatten() : collect();
                
     
     
        $fuelCards = FuelCard::all();
       
        $vehicules = Vehicule::all()->sortBy('name'); // Add this line
        return view('fuel.createPurchase', compact('fuelCards', 'drivers','vehicules'));
    }


    public function addPurchase(Request $request)
    {
        
      
        
        $request->validate([
            'fuel_card_id' => 'required|exists:fuel_cards,id',
            'driver_id' => 'required',
            'kilometer' => 'required',
            'amount' => 'required|numeric|min:0',
            'vehicule_id' => 'required|exists:vehicules,id',
        ]);

        // Assuming you have a Purchase model
        $purchase = new FuelPurchase();
        $purchase->fuel_card_id = $request->fuel_card_id;
        $purchase->acente_id = $request->driver_id;
        $purchase->purchase_date = $request->purchase_date;
        $purchase->vehicule_id=$request->vehicule_id;
        $purchase->amount = $request->amount;
        $purchase->kilometer = $request->kilometer;
        $purchase->save();

       return redirect()->route('fuel.list')->with('success', 'Purchase added successfully.');
   
    }
    public function addPurchasebycard(Request $request, $fuelCardId)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'kilometer' => 'required',
            'purchase_date' => 'required|date',
            'vehicule_id' => 'required|exists:vehicules,id',
        ]);

        $fuelCard = FuelCard::findOrFail($fuelCardId);
        $fuelCard->fuelPurchases()->create([
            'amount' => $request->input('amount'),
            'kilometer' => $request->input('kilometer'),
            'purchase_date' => $request->input('purchase_date'),
            'vehicule_id' => $request->input('vehicule_id'),
        ]);

        return redirect()->route('fuel.index')->with('success', 'Fuel purchase added successfully');
    }


    public function editPurchase($fuelCardId, $id)
    {
        $fuelCard = FuelCard::findOrFail($fuelCardId);
        $purchase = $fuelCard->fuelPurchases()->findOrFail($id);
        $vehicules = Vehicule::all();
        $driverIds = Option::where('name', 'driverIds')->orderBy('name')->value('value'); // Retrieve the 'value' column as a string
        $driverIdsArray = explode(',', $driverIds); // Convert the string to an array
        $firma = Firma::whereIn('id', $driverIdsArray)->get(); // Single $ sign here
        $drivers = $firma->isNotEmpty()
        ? $firma->pluck('acentes')->flatten()->sortBy('name')
        : collect();
     
        return view('fuel.edit', compact('fuelCard', 'purchase', 'vehicules', 'drivers'));
    }
    public function updatePurchase(Request $request, $fuelCardId, $id)
    {
        $request->validate([
            'amount' => 'required',
            'purchase_date' => 'required|date',
            'vehicule_id' => 'required|exists:vehicules,id',
            'kilometer' => 'required',
        ]);

        $fuelCard = FuelCard::findOrFail($fuelCardId);
        $purchase = $fuelCard->fuelPurchases()->findOrFail($id);
        $purchase->update([
            'amount' => $request->input('amount'),
            'purchase_date' => $request->input('purchase_date'),
            'vehicule_id' => $request->input('vehicule_id'),
            'kilometer' => $request->input('kilometer'),

        ]);

        return redirect()->route('fuel.list')->with('success', 'Fuel purchase updated successfully');
    }
    public function deletePurchase($fuelCardId, $id)
    {
        $fuelCard = FuelCard::findOrFail($fuelCardId);
        $purchase = $fuelCard->fuelPurchases()->findOrFail($id);
        $purchase->delete();

        return redirect()->route('fuel.list')->with('success', 'Fuel purchase deleted successfully');
    }


    public function monthlyFuelUsage(Request $request)
    {
        // Set default values for startDate and endDate
        $startDate = $request->input('start_date', '2024-01-01');
        $endDate = $request->input('end_date', '2024-12-31');
    
        // Now run the query with this date range
        $monthlyTotals = DB::table('fuel_purchases')
            ->select(
                'vehicule_id',
                DB::raw('DATE_FORMAT(purchase_date, "%Y-%m") as month'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->groupBy('vehicule_id', 'month')
            ->orderBy('vehicule_id')
            ->orderBy('month')
            ->get();
    
        // Convert data to JSON for the chart
        $chartData = [];
        foreach ($monthlyTotals as $total) {
            $vehiculeName = Vehicule::find($total->vehicule_id)->name;
            if (!isset($chartData[$vehiculeName])) {
                $chartData[$vehiculeName] = [];
            }
            $chartData[$vehiculeName][$total->month] = $total->total_amount;
        }
    
        $chartDataJson = json_encode($chartData);
    
        return view('fuel.monthly_usage', compact('chartDataJson', 'startDate', 'endDate'));
    }

        public function fuelPurchasesChart(Request $request)
    {
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = DB::table('fuel_purchases')
            ->join('vehicules', 'fuel_purchases.vehicule_id', '=', 'vehicules.id')
            ->select('vehicules.name as vehicle_name', DB::raw('SUM(fuel_purchases.amount) as total_amount'))
            ->groupBy('vehicules.name');

        if ($startDate) {
            $query->whereDate('fuel_purchases.purchase_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('fuel_purchases.purchase_date', '<=', $endDate);
        }

        $fuelData = $query->get();

        return view('fuel.fuel_purchases', compact('fuelData', 'startDate', 'endDate'));
    }


    public function showForm()
    {
        
        $rows = FuelExcel::orderBy('authorized_at', 'desc')->get();
        $vehicules = Vehicule::all();
        $fuelCards = FuelCard::all();
        $driverIds = Option::where('name', 'driverIds')->orderBy('name')->value('value'); // Retrieve the 'value' column as a string
        $driverIdsArray = explode(',', $driverIds); // Convert the string to an array
        $firma = Firma::whereIn('id', $driverIdsArray)->get(); // Single $ sign here
        $acenteler = $firma->isNotEmpty() ? $firma->pluck('acentes')->flatten() : collect();
        return view('fuel.import', compact('rows','acenteler','vehicules','fuelCards')); // Blade dosyası: resources/views/transfers/import.blade.php
    }

    public function import(Request $request)
    {
       

        try {
            Excel::import(new fuelImport, $request->file('file'));
            return back()->with('success', 'Veriler başarıyla içe aktarıldı!');
        } catch (\Exception $e) {
            return back()->with('error', 'Bir hata oluştu: ' . $e->getMessage());
        }
    }

    public function excelindex()
    {
        $rows = FuelExcel::orderBy('authorized_at', 'desc')->get();
        return view('fuel.review', compact('rows'));
    }
    public function excelstore(Request $request, $id)
    {
        $request->validate([
           
             'acente_id'    => 'required|integer|exists:acentes,id',
        'vehicule_id'  => 'required|integer|exists:vehicules,id',
        'fuel_card_id' => 'required|integer|exists:fuel_cards,id',
        'kilometrage'  => 'nullable|numeric',
        ]);

        $fuel = FuelExcel::findOrFail($id);

        // FuelPurchase tablosuna aktarım (örnek alanlar)
        FuelPurchase::create([
            'acente_id' => $request->acente_id,
            'vehicule_id' => $request->vehicule_id,
            'fuel_card_id' => $request->fuel_card_id,
            'purchase_date' => $fuel->authorized_at,
            'volume' => $fuel->volume,
            'amount' => $fuel->amount,
            'produit' => $fuel->fuel_type,
            'kilometer' => $request->kilometrage,
            'comment' => $fuel->location,
        ]);

        
         $fuel->delete();

        return redirect()->back()->with('success', 'C est bien enregister.');
    }

    public function destroyexcel($id)
{
    $row = FuelExcel::findOrFail($id);
    $row->delete();

    return redirect()->back()->with('error', 'Ligne Effacer.');
}


}
