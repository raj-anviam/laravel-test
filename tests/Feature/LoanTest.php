<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Loan;

class LoanTest extends TestCase
{
    
    /**
     * A basic feature test example.
     *
     * @return void
     */

    public function createTestUser() {
        $user = User::whereEmail('test@gmail.com')->count();
        if(!$user) {
            $user = $this->post('/api/register', [
                'name' => 'user test',
                'email' => 'test@gmail.com',
                'password' => '123456789',
                'password_confirmation' => '123456789',
            ]);
        }
        else {
            $user = $this->post('/api/login', [
                'email' => 'test@gmail.com',
                'password' => '123456789',
            ]);
        }

        return $user;
    }

    public function adminLogin() {
        return $this->post('/api/login', [
            'email' => 'admin@gmail.com',
            'password' => '123456789',
        ]);
    }
    
    public function test_create_loan_application()
    {
        $user = $this->createTestUser();
        $response = $this->withHeader('Authorization', $user['data']['token'])
        ->json('post', '/api/loan', [
            'amount' => 100,
            'loan_term' => 4,
        ]);
        
        $response->assertStatus(201);
    }

    public function test_get_customer_loan_application()
    {
        $user = $this->createTestUser();
        $loanId = Loan::whereUserId($user['data']['id'])->value('id');
        $response = $this->withHeader('Authorization', $user['data']['token'])
        ->json('get', "/api/loan/$loanId");
        
        $response->assertStatus(200);
    }

    public function test_customer_cannot_access_other_loan_application()
    {
        $user = $this->createTestUser();
        $loan = Loan::where('user_id', '!=', $user['data']['id'])->first();
        if($loan) {
            $response = $this->withHeader('Authorization', $user['data']['token'])
            ->json('get', "/api/loan/$loan->id");
            $response->assertStatus(404);
        }
        else {
            $this->assertTrue(true);
            return;
        }
        
    }

    public function test_admin_approve_or_reject()
    {
        $user = $this->createTestUser();
        $admin = $this->adminLogin();
        $loanId = Loan::whereUserId($user['data']['id'])->whereStatus('PENDING')->value('id');
        $response = $this->withHeader('Authorization', $admin['data']['token'])
        ->json('post', '/api/loan/update-status', [
            'loan_id' => $loanId,
            'status' => 'APPROVED',
        ]);
        
        $response->assertStatus(200);
    }

    public function test_customer_repays_installments()
    {
        $user = $this->createTestUser();
        $loan = Loan::whereUserId($user['data']['id'])->whereStatus('PENDING')->first();

        if(is_null($loan)) {
            $this->assertTrue(true);
            return;
        }

        for($i = 0; $i < $loan->loan_term; $i++) {   
            $response = $this->withHeader('Authorization', $user['data']['token'])
            ->json('post', '/api/loan/payment', [
                'loan_id' => $loan->id,
                'amount' => 25
            ]);
        }
        
        $response->assertStatus(200);
    }
}
