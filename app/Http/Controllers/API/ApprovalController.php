<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'policy_id' => 'required|exists:policies,id',
            'restriction' => 'required|in:none,out-of-policy,all',
            'approvers' => 'nullable|array',
            'approvers.*.name' => 'required|string',
            'approvers.*.role' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $approval = \App\Models\Approval::create([
            'policy_id' => $request->policy_id,
            'restriction' => $request->restriction,
            'approvers' => json_encode($request->approvers),
        ]);

        return response()->json($approval, 201);
    }

    public function update(Request $request, Approval $approval)
    {
        if ($approval->approver_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:approved,rejected',
            'comments' => 'nullable|string',
        ]);

        $approval->update([
            'status' => $request->status,
            'comments' => $request->comments,
        ]);

        $booking = $approval->booking;
        $booking->status = $request->status === 'approved' ? 'approved' : 'rejected';
        $booking->save();

        return response()->json($approval);
    }
}