<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class JoinRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|size:6',
            'teamCode' => 'required|string|regex:/^K[1-9][0-9]*$/',
            'isLeader' => 'required|boolean',
        ];
    }
}
