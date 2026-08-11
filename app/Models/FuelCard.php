<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FuelCard extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = ['card_number', 'card_type', 'acente_id', 'vehicule_id'];

    public function Acente()
    {
        return $this->belongsTo(Acente::class);
    }
    public function Vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function fuelPurchases()
    {
        return $this->hasMany(FuelPurchase::class);
    }
}
