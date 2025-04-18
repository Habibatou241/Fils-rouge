<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    // Méthode pour créer un projet
    public function store(Request $request): JsonResponse
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

    // Méthode pour récupérer la liste des projets
    public function index(): JsonResponse
    {
        $projects = Project::where('user_id', auth()->id())
                           ->with('datasets') // Charger les datasets associés
                           ->get();

        return response()->json([
            'status' => 'success',
            'projects' => $projects
        ]);
    }

    // Méthode pour mettre à jour un projet existant
    public function update(Request $request, int $id): JsonResponse
    {
        // Valider les champs modifiables
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
        ]);

        // Récupérer le projet, vérifier qu'il appartient à l'utilisateur
        $project = Project::where('id', $id)
                          ->where('user_id', auth()->id())
                          ->first();

        // Si le projet n'existe pas ou l'utilisateur n'est pas autorisé
        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Projet introuvable ou non autorisé'
            ], 404);
        }

        // Mettre à jour les champs reçus dans la requête
        if ($request->has('name')) {
            $project->name = $request->name;
        }
        if ($request->has('description')) {
            $project->description = $request->description;
        }

        // Sauvegarder les modifications
        $project->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Projet mis à jour avec succès',
            'project' => $project
        ]);
    }

    // Méthode pour supprimer un projet
    public function destroy(int $id): JsonResponse
    {
        // Récupérer le projet, vérifier qu'il appartient à l'utilisateur
        $project = Project::where('id', $id)
                          ->where('user_id', auth()->id())
                          ->first();

        // Si le projet n'existe pas ou l'utilisateur n'est pas autorisé
        if (!$project) {
            return response()->json([
                'status' => 'error',
                'message' => 'Projet introuvable ou non autorisé'
            ], 404);
        }

        // Supprimer le projet
        $project->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Projet supprimé avec succès'
        ]);
    }
}
