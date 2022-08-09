<?php

namespace App\Http\Controllers\ApiControllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use App\Http\Requests\LoanApplicationRequest;
use App\Http\Requests\LoanRequest;
use App\Http\Requests\PaymentRequest;
use Auth;
use DB;
use Exception;
use Carbon\Carbon;
use App\Models\Loan;
use App\Models\Installment;

class LoanController extends Controller
{
    use ApiResponseTrait;
    
    public function show($loanId) {
        try {
            $data = Loan::with('installments')->findOrFail($loanId);

            if($data->user_id != Auth::user()->id)
                throw new Exception("You can only view your loan applications");

            return $this->successResponse($data);
        }
        catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    public function store(LoanApplicationRequest $request) {
        try {

            $data = $request->only('amount', 'loan_term');
            $data['user_id'] = Auth::user()->id;
            $data['status'] = 'PENDING';

            DB::beginTransaction();
            Loan::create($data);
            DB::commit();
            return $this->successResponse([], 201, true, 'Loan Request has been created');
        }
        catch (Exception $e) {
            DB::rollback();
            return $this->errorResponse($e->getMessage());
        }
    }

    public function updateStatus(LoanRequest $request) {
        try {

            $loan = Loan::findOrFail($request->loan_id);

            if(count($loan->installments)) {
                return $this->errorResponse("Already reviewed this request");
            }
            $data = [];

            Loan::whereId($loan->id)->update(['status' => $request->status]);

            // if admin rejects the application
            if($request->status == 'REJECTED') {
                $loan->status = $request->status;
                $loan->save();
            }

            else if($request->status == 'APPROVED') {
                $installmentAmount = $loan->amount / $loan->loan_term;
                
                $dueDate = Carbon::today();
                $test = [];

                $dueDates = [];
                for($i = 0; $i < $loan->loan_term; $i++) {

                    $dueDate = Carbon::parse($dueDate)->addWeek();

                    $data[] = [
                        'loan_id' => $loan->id,
                        'amount' => $installmentAmount,
                        'amount_paid' => 0,
                        'due_date' => $dueDate,
                        'status' => 'PENDING',
                    ];
                }

                // return $data;
                DB::beginTransaction();
                Installment::insert($data);
                DB::commit();
            }
            else {
                return $this->errorResponse("please check status");
            }

            return $this->successResponse(['installments' => $data], 200, true, "Loan Request has been $request->status");
        }
        catch (Exception $e) {
            DB::rollback();
            return $this->errorResponse($e->getMessage());
        }
    }

    public function payment(PaymentRequest $request) {
        
        try {

            $loan = Loan::findOrFail($request->loan_id);
            
            // check pending installments
            if(!$this->hasPendingInstallments($loan)) {
                return $this->errorResponse("You have paid back the loan");
            }
            
            $installment = $loan->installments()->whereStatus('PENDING')->where('due_date', '>=', Carbon::today())->first();
            
            if($installment->count()) {
                
                if($request->amount < $installment->amount)
                return $this->errorResponse("You need pay atleast $installment->amount USD for this installment");
                
                // get next pending installment
                $pendingInstallments = $loan->installments()->where('id', '>=', $installment->id)->whereStatus('PENDING');
                
                $totalAmount = $pendingInstallments->sum('amount');
                
                // check if user is paying more pending amount
                if($totalAmount - $request->amount < 0)
                return $this->errorResponse("You need pay maximum $totalAmount USD for this installment");
                
                $installment->status = 'PAID';
                $installment->amount_paid = $request->amount;
                $installment->save();
                
                // update next installment amount
                if($pendingInstallments->first())
                $pendingInstallments->first()->decrement('amount', ($request->amount - $installment->amount));
                else
                $loan->status = 'PAID';
                $loan->save();
                
                return $this->successResponse([], 200, true, "payment successfull");
            }
            else {
                return $this->successResponse([], 200, true, "payment successfull, you have paid back the loan");
            }
        }
        catch (Exception $e) {
            DB::rollback();
            return $this->errorResponse($e->getMessage());
        }
    }

    public function hasPendingInstallments(Loan $loan) {

        $count = $loan->installments()->whereStatus('PENDING')->count();
        return $count > 0;
    }
}
