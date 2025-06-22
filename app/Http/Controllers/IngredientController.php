<?php

namespace App\Http\Controllers;

use App\Models\Ingredient;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IngredientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Collection
    {
        return Ingredient::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'details' => 'required|array',
        ]);

        return Ingredient::create($data);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ingredient $ingredient): Ingredient
    {
        return $ingredient;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ingredient $ingredient): Ingredient
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'details' => 'sometimes|array',
        ]);

        $ingredient->update($data);

        return $ingredient;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ingredient $ingredient): Response
    {
        $ingredient->delete();

        return response()->noContent();
    }
}
