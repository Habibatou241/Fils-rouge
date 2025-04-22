<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'preprocessing_id',
        'summary', // Résultats de l’analyse au format JSON
    ];

    protected $casts = [
        'summary' => 'array', // Convertit automatiquement en tableau JSON/PHP
    ];

    public function preprocessing()
    {
        return $this->belongsTo(Preprocessing::class);
    }
}
