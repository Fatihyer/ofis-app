<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelPurchase extends Model
{
    use HasFactory;

    protected $fillable = ['fuel_card_id', 'amount', 'purchase_date','vehicule_id','comment','kilometer','volume','produit','acente_id'];

    public function fuelCard()
    {
        return $this->belongsTo(FuelCard::class);
    }
    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }
    public function acente()
    {
        return $this->belongsTo(Acente::class);
    }
}
