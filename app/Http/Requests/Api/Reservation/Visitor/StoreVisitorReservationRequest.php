<?php

namespace App\Http\Requests\Api\Reservation\Visitor;

use App\Enums\OfficeApprovalStatus;
use App\Models\Office;
use App\Models\Reservation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Validator;

class StoreVisitorReservationRequest extends FormRequest
{
    protected Office $office;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Reservation::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'office_id'  => ['required', 'integer'],
            'start_date' => ['required', 'date:Y-m-d', 'after:today'],
            'end_date'   => ['required', 'date:Y-m-d', 'after:start_date'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $office = Office::find($this->validated('office_id'));

                if (is_null($office)) {
                    $validator->errors()->add(
                        key: 'office_id',
                        message: 'Invalid office_id',
                    );

                    return;
                }

                if ($office->user_id === $this->user()->id) {
                    $validator->errors()->add(
                        key: 'office_id',
                        message: 'You cannot make a reservation on your own office',
                    );
                }

                if ($office->is_hidden || $office->approval_status === OfficeApprovalStatus::Pending) {
                    $validator->errors()->add(
                        key: 'office_id',
                        message: 'You cannot make a reservation on a hidden office',
                    );
                }

                $this->office = $office;
            }
        ];
    }

    public function getOffice(): Office
    {
        return $this->office;
    }
}
