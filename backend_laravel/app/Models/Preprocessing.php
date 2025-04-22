<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preprocessing extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'name',        // nom du prétraitement (ex: "Remplissage des valeurs manquantes")
        'file_path',   // chemin du fichier résultant
        'summary',     // statistiques résumées du prétraitement
    ];

    protected $casts = [
        'summary' => 'array', // on le cast en tableau PHP/JSON automatiquement
    ];

    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    public function analysis()
    {
        return $this->hasOne(Analysis::class);
    }
}
