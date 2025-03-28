<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Filter by trip if provided
        if ($request->has('trip_id')) {
            $trip = Trip::findOrFail($request->trip_id);
            
            // Check if user has permission to view expenses for this trip
            if (!$user->hasRole('admin') && $trip->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            
            $expenses = Expense::where('trip_id', $request->trip_id)
                ->with('user')
                ->orderBy('date', 'desc')
                ->paginate(10);
        } else {
            // If admin, can see all expenses in their company
            if ($user->hasRole('admin')) {
                $expenses = Expense::whereHas('trip', function ($query) use ($user) {
                    $query->where('company_id', $user->company_id);
                })
                ->with('user', 'trip')
                ->orderBy('date', 'desc')
                ->paginate(10);
            } else {
                // If employee, can only see their own expenses
                $expenses = Expense::where('user_id', $user->id)
                    ->with('trip')
                    ->orderBy('date', 'desc')
                    ->paginate(10);
            }
        }
        
        return response()->json($expenses);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'trip_id' => 'required|exists:trips,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'date' => 'required|date',
            'category' => 'required|in:accommodation,transportation,food,entertainment,other',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $trip = Trip::findOrFail($request->trip_id);
        
        // Check if user owns this trip
        if ($trip->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }
        
        $expense = Expense::create([
            'trip_id' => $request->trip_id,
            'user_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'date' => $request->date,
            'category' => $request->category,
            'status' => 'pending',
            'receipt_path' => $receiptPath,
        ]);
        
        return response()->json([
            'message' => 'Expense created successfully',
            'expense' => $expense
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $expense = Expense::with('trip', 'user')->findOrFail($id);
        
        // Check if user has permission to view this expense
        if (!$user->hasRole('admin') && $expense->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'date' => 'sometimes|required|date',
            'category' => 'sometimes|required|in:accommodation,transportation,food,entertainment,other',
            'status' => 'sometimes|required|in:pending,approved,rejected',
            'receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $expense = Expense::findOrFail($id);
        
        // Only the expense owner can update basic details
        if ($expense->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admin can change status
        if (isset($request->status) && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Only admins can approve or reject expenses'], 403);
        }
        
        // Handle receipt upload
        if ($request->hasFile('receipt')) {
            // Delete old receipt if it exists
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
            $expense->receipt_path = $receiptPath;
        }
        
        $expense->update($request->except(['trip_id', 'user_id', 'receipt_path']));
        
        return response()->json([
            'message' => 'Expense updated successfully',
            'expense' => $expense->fresh()
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $expense = Expense::findOrFail($id);
        
        // Only the expense owner or admin can delete
        if ($expense->user_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Delete receipt file if it exists
        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }
        
        $expense->delete();
        
        return response()->json(['message' => 'Expense deleted successfully']);
    }
}