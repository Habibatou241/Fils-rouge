<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Preprocessing;
use App\Models\Analysis; // Ajouté pour accéder aux analyses
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Http\JsonResponse;

class DataAnalysisController extends Controller
{
    /**
     * Analyse les données à partir d’un fichier prétraité.
     *
     * @param int $id  ID du preprocessing
     * @return JsonResponse
     */
    public function analyze(int $id): JsonResponse
    {
        $preprocessing = Preprocessing::where('id', $id)
            ->whereHas('dataset', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();

        if (!$preprocessing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fichier prétraité introuvable ou non autorisé.'
            ], 404);
        }

        $filePath = $preprocessing->file_path;

        if (!str_starts_with($filePath, storage_path())) {
            $filePath = storage_path('app/' . $filePath);
        }


        // Vérification si le fichier existe
        if (!file_exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Le fichier physique est introuvable: ' . $filePath
            ], 404);
        }

        try {
            $pythonPath = 'C:\Users\HP ELITEBOOK 1030 G2\AppData\Local\Programs\Python\Python311\python.exe';
            $scriptPath = base_path('ml_python/data_analysis_script.py');
            
            $process = new Process([$pythonPath, $scriptPath, $filePath]);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            Log::info('[ANALYSE PYTHON OUTPUT]', ['output' => $output, 'error' => $errorOutput]);

            if (!$process->isSuccessful()) {
                Log::error('Erreur analyse de données : ' . $errorOutput);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Erreur pendant l’analyse des données.'
                ], 500);
            }

            $result = json_decode($output, true);
            

            // Enregistrement en base de données
            $analysis = Analysis::updateOrCreate(
                ['preprocessing_id' => $preprocessing->id],
                ['summary' => $result]
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Analyse effectuée avec succès.',
                'analysis' => $analysis,
            ]);

        } catch (ProcessFailedException $e) {
            Log::error('Échec exécution script analyse : ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur d’exécution du script : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Affiche les résultats d’analyse d’un preprocessing.
     *
     * @param int $id ID du preprocessing
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $preprocessing = Preprocessing::where('id', $id)
            ->whereHas('dataset', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->first();

        if (!$preprocessing) {
            return response()->json([
                'status' => 'error',
                'message' => 'Fichier prétraité introuvable ou non autorisé.'
            ], 404);
        }

        $analysis = $preprocessing->analysis;

        if (!$analysis) {
            return response()->json([
                'status' => 'error',
                'message' => 'Aucune analyse trouvée pour ce fichier prétraité.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'analysis' => $analysis,
        ]);
    }

    /**
     * Liste toutes les analyses de l’utilisateur connecté.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $analyses = Analysis::whereHas('preprocessing.dataset', function ($query) {
            $query->where('user_id', auth()->id());
        })->with('preprocessing')->get();

        return response()->json([
            'status' => 'success',
            'analyses' => $analyses,
        ]);
    }
}
