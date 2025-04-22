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
    public function applyPreprocessing(Request $request, int $dataset_id): JsonResponse
    {
        $request->validate([
            'type' => 'required|string',
            'method' => 'nullable|string'
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

        $datasetPath = storage_path('app/private/' . $dataset->file_path);
        Log::debug('[PREPROCESSING] Dataset file path: ' . $datasetPath);

        try {
            $pythonPath = base_path('ml_python/venv/Scripts/python.exe');
            $scriptPath = base_path('ml_python/preprocessing_script.py');

            $command = [$pythonPath, $scriptPath, $datasetPath, $request->type];

            // Correction ici pour accepter "outliers" et "remove_outliers"
            if (in_array($request->type, ['fill', 'scaling', 'remove_duplicates', 'outliers'])) {
                if (!$request->has('method') && in_array($request->type, ['fill', 'scaling', 'outliers'])) {
                    Log::error('[PREPROCESSING] Missing method parameter for type: ' . $request->type);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'The "method" parameter is required for this preprocessing type.'
                    ], 400);
                }

                if ($request->type === 'fill') {
                    $request->validate(['method' => 'required|string|in:mean,median,mode']);
                } elseif ($request->type === 'scaling') {
                    $request->validate(['method' => 'required|string|in:normalization,standardization']);
                } elseif ($request->type === 'outliers') {
                    $request->validate(['method' => 'required|string|in:iqr,zscore']);
                }

                if ($request->has('method')) {
                    $command[] = $request->method;
                }
            }

            Log::info('[PREPROCESSING] Executing command: ' . implode(' ', $command));

            $process = new Process($command);
            $process->run();

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            Log::debug('[PREPROCESSING] Python stdout: ' . $output);
            Log::debug('[PREPROCESSING] Python stderr: ' . $errorOutput);

            $result = json_decode($output, true);

            if (!$process->isSuccessful()) {
                Log::error('[PREPROCESSING] Process failed with exit code ' . $process->getExitCode());

                if (!empty($errorOutput)) {
                    Log::error('[PREPROCESSING] Raw stderr: ' . $errorOutput);
                }

                $errorMessage = 'Une erreur est survenue pendant le prétraitement.';
                $errorData = json_decode($errorOutput, true);

                if (is_array($errorData) && isset($errorData['error'])) {
                    $errorMessage = $errorData['error'];
                }

                return response()->json([
                    'status' => 'error',
                    'message' => $errorMessage
                ], 500);
            }

            if (!is_array($result)) {
                Log::error('[PREPROCESSING] Output JSON could not be decoded. Raw output: ' . $output);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid JSON output from Python script.'
                ], 500);
            }

            $preprocessing = Preprocessing::create([
                'dataset_id' => $dataset->id,
                'name' => ucfirst(str_replace('_', ' ', $request->type)) . ($request->has('method') ? ' (' . $request->method . ')' : ''),
                'file_path' => $result['file_path'],
                'summary' => json_encode($result['summary'], JSON_UNESCAPED_UNICODE),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Preprocessing applied successfully',
                'preprocessing' => $preprocessing
            ], 200);

        } catch (ProcessFailedException $e) {
            Log::error('[PREPROCESSING] ProcessFailedException: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur d’exécution du script : ' . $e->getMessage()
            ], 500);
        } catch (\Throwable $e) {
            Log::critical('[PREPROCESSING] Unexpected exception: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur inattendue : ' . $e->getMessage()
            ], 500);
        }
    }

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
