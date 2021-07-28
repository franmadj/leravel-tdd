<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\AppRequest;

class RequestTest extends TestCase {

    use DatabaseMigrations;

    /**
     * @test
     * @group AppRequest
     */
    public function userCanCreateGetFeedbackLink() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGetFeedback($user);
        $response
                ->assertStatus(200)
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" => [
                        'url'
                    ]
                        ]
        );
        $request = AppRequest::first();


        $this->assertCount(1, AppRequest::all());
        $this->assertEquals($request->user_recipient_id, $user->id);
        $this->assertEquals($request->recipient_name, $user->name);
        $this->assertEquals($request->token, md5(time() . $user->name));
    }

    /**
     * 
     * @group AppRequest
     */
    public function unknownUserCantCreateGetFeedbackLink() {
        $this->withoutExceptionHandling();
        $this->expectException(\Exception::class);

        $data = [];
        $data['provider_name'] = 'Jose';

        $response = $this->json('POST', '/api/request/get-feedback?device_id=9999xx', $data);
    }

    /**
     * @test
     * @group AppRequest
     */
    public function userCanGiveFeedbackAndCreateLink() {
        $this->registerUser();
        $this->seed('CategoriesSeeder');
        $user = User::first();
        $response=$this->creteGiveFeedback($user);
        //dd($response);
        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" => [
                        'url'
                    ]
                        ]
        );
        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());
        $this->assertEquals($request->user_recipient_id, NULL);
        $this->assertEquals($request->recipient_name, 'Jose');
        $this->assertEquals($request->token, md5(time() . $user->name));
    }

    /**
     * @test
     * @group AppRequest
     */
    public function pastRequestsCanBeQueried() {
        $this->registerUser();
        $user = User::first();

        $response=$this->creteGetFeedback($user);

        $response = $this->get('/api/request/past-requests?device_id=' . $user->device_id);

        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"])
                ->assertJsonFragment(["id" => 1, "status" => "open", "date_sent" => date('Y-m-d'), "provider_name" => "Jose"]);
    }

    /**
     * @test
     * @group AppRequest
     */
    public function a_request_can_be_deleted() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGetFeedback($user);
        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"]);

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());

        $response = $this->delete('/api/request/' . $request->id . '?device_id=' . $user->device_id);
        $this->assertCount(0, AppRequest::all());
    }

    /**
     * @test
     * @group AppRequest
     */
    public function a_request_can_be_resend() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGetFeedback($user);

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());

        $response = $this->get('/api/request/re-send/' . $request->id . '?device_id=' . $user->device_id);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" => ['url']]
        );
    }
    
    
    /**
     * @test
     * @group AppRequest
     */
    public function a_request_can_be_queried_by_token() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGetFeedback($user);

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());

        $response = $this->get('/api/request/by-token/' . $request->token . '?device_id=' . $user->device_id);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" => ['provider_name','recipient_name','app_request_id','thumbnails']]
        );
    }
    
    /**
     * @test
     * @group AppRequest
     */
    public function a_request_can_be_declined() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGetFeedback($user);

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());

        $response = $this->get('/api/request/decline/' . $request->id);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"
                    ]);
    }
    
    /**
     * @test
     * @group AppRequest
     */
    public function current_user_can_be_set_as_recipient() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGiveFeedback($user);

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());

        $response = $this->json('POST','/api/request/add-recipient?device_id=' . $user->device_id,['token'=>$request->token]);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"
                    ]);

    }
    
    /**
     * @test
     * @group AppRequest
     */
    public function current_user_can_be_set_as_provider() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGetFeedback($user);

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());

        $response = $this->json('POST','/api/request/add-provider?device_id=' . $user->device_id,['token'=>$request->token]);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"
                    ]);

    }
    
    /**
     * @test
     * @group AppRequest
     */
    public function apprequest_status_can_be_updated() {
        $this->registerUser();
        $user = User::first();
        $response=$this->creteGetFeedback($user);
        

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());
        $this->assertEquals('open', $request->status);

        $response = $this->put('/api/request/set-status/1?device_id=' . $user->device_id,['status'=>'cancelled']);

        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"
                    ]);
        $request = AppRequest::first();
        $this->assertEquals('cancelled', $request->status);
        
        $response = $this->put('/api/request/set-status/1?device_id=' . $user->device_id,['status'=>'completed']);
        $request = AppRequest::first();
        $this->assertEquals('completed', $request->status);

    }

}
