<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $project = Project::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Projet créé avec succès',
            'project' => $project
        ]);
    }

    public function index()
    {
        $projects = Project::where('user_id', auth()->id())
                           ->with('datasets')
                           ->get();

        return response()->json([
            'status' => 'success',
            'projects' => $projects
        ]);
    }

    public function update(Request $request, $id)
{
    // Valider les champs modifiables
    $request->validate([
        'name' => 'sometimes|required|string|max:255',
        'description' => 'sometimes|nullable|string',
    ]);

    // Récupérer le projet en s'assurant qu'il appartient à l'utilisateur
    $project = Project::where('id', $id)
                      ->where('user_id', auth()->id())
                      ->first();

    if (!$project) {
        return response()->json([
            'status' => 'error',
            'message' => 'Projet introuvable ou non autorisé'
        ], 404);
    }

    // Mettre à jour les champs reçus
    if ($request->has('name')) {
        $project->name = $request->name;
    }
    if ($request->has('description')) {
        $project->description = $request->description;
    }

    $project->save();

    return response()->json([
        'status' => 'success',
        'message' => 'Projet mis à jour avec succès',
        'project' => $project
    ]);
}

    public function destroy($id)
    {
        $project = Project::where('id', $id)
                          ->where('user_id', auth()->id())
                          ->first();

        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Projet introuvable ou non autorisé'
            ], 404);
        }

        $project->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Projet supprimé avec succès'
        ]);
    }
}
