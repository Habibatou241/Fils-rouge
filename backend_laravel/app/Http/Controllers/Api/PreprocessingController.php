<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Preprocessing;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PreprocessingController extends Controller
{
    /**
     * Apply preprocessing to a dataset.
     *
     * @param Request $request
     * @param int $dataset_id
     * @return JsonResponse
     */
    public function applyPreprocessing(Request $request, int $dataset_id): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
        ]);

        $dataset = Dataset::where('id', $dataset_id)
                          ->where('user_id', auth()->id())
                          ->first();

        if (!$dataset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dataset not found or unauthorized'
            ], 404);
        }

        $filePath = storage_path('app/private/' . $dataset->file_path);

        try {
            $process = new Process(['python', base_path('ml_python/preprocessing_script.py'), $filePath, $request->type]);
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            $result = json_decode($output, true);

            if (!$process->isSuccessful() || !is_array($result)) {
                $errorMessage = 'Une erreur est survenue pendant le prétraitement.';

                $errorData = json_decode($errorOutput, true);
                if (is_array($errorData) && isset($errorData['error'])) {
                    $errorMessage = $errorData['error'];
                }

                Log::error('Erreur dans le script Python : ' . $errorMessage);

                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage
                ], 500, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            }

            $preprocessing = Preprocessing::create([
                'dataset_id' => $dataset->id,
                'name' => $request->type,
                'file_path' => $result['file_path'],
                'summary' => json_encode($result['summary'], JSON_UNESCAPED_UNICODE),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Preprocessing applied successfully',
                'preprocessing' => $preprocessing
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

        } catch (ProcessFailedException $exception) {
            Log::error('Exception lors de l’exécution du script Python : ' . $exception->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur d’exécution du script : ' . $exception->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
    }

    /**
     * Get preprocessing history for a specific dataset.
     *
     * @param int $dataset_id
     * @return JsonResponse
     */
    public function getPreprocessingHistoryByDataset(int $dataset_id): JsonResponse
    {
        $dataset = Dataset::where('id', $dataset_id)
                          ->where('user_id', auth()->id())
                          ->first();

        if (!$dataset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dataset not found or unauthorized'
            ], 404);
        }

        $preprocessings = Preprocessing::where('dataset_id', $dataset_id)->get();

        return response()->json([
            'status' => 'success',
            'preprocessings' => $preprocessings
        ]);
    }

    /**
     * Get all preprocessings for the authenticated user.
     *
     * @return JsonResponse
     */
    public function getAllPreprocessings(): JsonResponse
    {
        $preprocessings = Preprocessing::whereHas('dataset', function ($query) {
            $query->where('user_id', auth()->id());
        })->get();

        return response()->json([
            'status' => 'success',
            'preprocessings' => $preprocessings
        ]);
    }
}