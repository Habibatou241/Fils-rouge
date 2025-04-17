<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DataImportController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xlsx|max:10240',
            'name' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id' // 👈 validation du projet
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $fileType = $file->getClientOriginalExtension();
        
        // Store file in storage/app/datasets
        $path = $file->store('datasets');
        
        if (!$path) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store the file'
            ], 500);
        }

        $dataset = Dataset::create([
            'user_id' => auth()->id(),
            'project_id' => $request->project_id, // 👈 association au projet
            'name' => $request->name,
            'original_filename' => $originalName,
            'file_path' => $path,
            'file_type' => $fileType,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dataset uploaded successfully',
            'dataset' => $dataset
        ], 201);
    }

    public function list(): JsonResponse
    {
        $datasets = Dataset::where('user_id', auth()->id())->get();

        return response()->json([
            'status' => 'success',
            'datasets' => $datasets
        ]);
    }
}
