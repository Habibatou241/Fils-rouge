<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Preprocessing;
use App\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

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
        // Validate the preprocessing type
        $request->validate([
            'type' => 'required|string', // Type of preprocessing (e.g., 'cleaning', 'scaling')
        ]);

        // Retrieve the dataset and ensure the user is authorized
        $dataset = Dataset::where('id', $dataset_id)
                          ->where('user_id', auth()->id())
                          ->first();

        if (!$dataset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dataset not found or unauthorized'
            ], 404);
        }

        // Prepare the file path for processing
        $filePath = storage_path('app/' . $dataset->file_path);

        // Call the Python script to apply preprocessing
        try {
            // Run the Python script with the file path and preprocessing type
            $process = new Process(['python3', base_path('ml-python/preprocessing_script.py'), $filePath, $request->type]);
            $process->mustRun();

            // Get the output from the Python script
            $output = $process->getOutput();
            $result = json_decode($output, true); // Assuming the script returns JSON output

            // Save the preprocessing result in the database
            $preprocessing = Preprocessing::create([
                'dataset_id' => $dataset->id,
                'name' => $request->type,
                'file_path' => $result['file_path'], // Path to the resulting file
                'summary' => json_encode($result['summary']), // Summary statistics
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Preprocessing applied successfully',
                'preprocessing' => $preprocessing
            ]);
        } catch (ProcessFailedException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error applying preprocessing: ' . $exception->getMessage()
            ], 500);
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
        // Check if the dataset belongs to the authenticated user
        $dataset = Dataset::where('id', $dataset_id)
                          ->where('user_id', auth()->id())
                          ->first();

        if (!$dataset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dataset not found or unauthorized'
            ], 404);
        }

        // Retrieve the preprocessing history for this dataset
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
        // Retrieve all preprocessings for the authenticated user
        $preprocessings = Preprocessing::whereHas('dataset', function ($query) {
            $query->where('user_id', auth()->id());
        })->get();

        return response()->json([
            'status' => 'success',
            'preprocessings' => $preprocessings
        ]);
    }
}
