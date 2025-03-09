<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Retrieve a list of all departments.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $departments = Department::all();
        return response()->json($departments);
    }

    /**
     * Store a newly created department in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|exists:companies,id',
        ]);

        $department = Department::create($request->all());
        return response()->json($department, 201);
    }

    /**
     * Display the specified department.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $department = Department::findOrFail($id);
        return response()->json($department);
    }

    /**
     * Update the specified department in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'company_id' => 'sometimes|required|exists:companies,id',
        ]);

        $department = Department::findOrFail($id);
        $department->update($request->all());
        return response()->json($department);
    }

    /**
     * Remove the specified department from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $department = Department::findOrFail($id);
        $department->delete();
        return response()->json(null, 204);
    }
}