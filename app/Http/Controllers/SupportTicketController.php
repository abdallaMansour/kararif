<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Support\CreateSupportTicketRequest;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;

class SupportTicketController extends Controller
{
    public function store(CreateSupportTicketRequest $request): JsonResponse
    {
        $ticket = SupportTicket::create([
            'user_id' => auth()->id(),
            'email' => $request->input('email'),
            'category' => $request->input('category'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'success' => true,
            'data' => ['ticketId' => 'TKT-' . $ticket->id],
            'message' => 'تم إرسال التذكرة بنجاح',
        ], 201);
    }
}
