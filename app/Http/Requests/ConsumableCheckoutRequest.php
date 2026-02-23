<?php

namespace App\Http\Requests;

use App\Models\Consumable;
use Illuminate\Support\Facades\Gate;

class ConsumableCheckoutRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('checkout', new Consumable);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'assigned_user' => 'required_without:assigned_asset|nullable|integer|exists:users,id',
            'assigned_asset' => 'required_without:assigned_user|nullable|integer|exists:assets,id',
            'checkout_to_type' => 'required|in:user,asset',
            'checkout_qty' => 'nullable|integer|min:1',
            'note' => 'nullable|string',
        ];
    }
}
