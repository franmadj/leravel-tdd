<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\AppRequest;
use App\Feedback;

class FeedbackTest extends TestCase {

    use DatabaseMigrations;

    /**
     * @test
     * @group Feedbacks
     */
    public function feedbacks_can_be_queried_by_apprequest_token() {
        $this->registerUser();
        $user = User::first();

        $response = $this->creteGiveFeedback($user);

        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());

        $response = $this->get('/api/feedback/by-token/' . $request->token);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" => ['provider_name', 'recipient_name', 'feedbacks']]
        );
    }

    /**
     * @test
     * @group Feedbacks
     */
    public function a_feedback_can_be_sent_from_web_browser() {
        $this->seed('CategoriesSeeder');
        $this->registerUser();
        $user = User::first();
        $response = $this->creteGetFeedback($user);
        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());
        $data = [
            "app_request_id" => 1,
            "provider_name" => "Francisco",
            "feedbacks" => [
                ["category_id" => 3, "feedback" => "feedback content from Francisco to Himal.."]
            ]
        ];
        $response = $this->post('/api/feedback/web-send/', $data);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"]
        );
    }

    /**
     * @test
     * @group Feedbacks
     */
    public function a_feedback_can_be_sent_from_app() {
        $this->seed('CategoriesSeeder');
        $this->registerUser();
        $user = User::first();
        $response = $this->creteGetFeedback($user);
        $request = AppRequest::first();
        $this->assertCount(1, AppRequest::all());
        $this->assertCount(0, $request->feedbacks);

        $this->json('POST', '/api/profile/store', array_merge($this->data(), ['name' => "UserProvider", 'device_id' => "UserProvider", 'device_token' => "UserProvider"]));
        $userProvider = User::find(2);


        $data = [
            "app_request_id" => $request->id,
            "feedbacks" => [
                ["category_id" => 3, "feedback" => "feedback content from Francisco to Himal.."]
            ]
        ];
        $response = $this->post('/api/feedback/app-send?device_id=' . $userProvider->device_id, $data);
        $request = AppRequest::first();
        $this->assertCount(1, $request->feedbacks);
        $feedbackFirst = $request->feedbacks()->first();
        $this->assertEquals('open', $feedbackFirst->status);
        $this->assertEquals($userProvider->id, $feedbackFirst->user_provider_id);
        $this->assertEquals($userProvider->name, $feedbackFirst->provider_name);
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data"]
        );
    }

    /**
     * @test
     * @group Feedbacks
     */
    public function open_feedbacks_can_be_queried() {
        \Illuminate\Http\JsonResponse::class;
        $this->seed('CategoriesSeeder');
        $this->registerUser();
        $user = User::first();
        $response = $this->creteGiveFeedback($user);
        $request = AppRequest::first();
        $user2 = $this->json('POST', '/api/profile/store', array_merge($this->data(), ['name' => "Jose", 'device_id' => "user2", 'device_token' => "user2"]));
        $users = User::all();
        $this->assertCount(2, $users);
        $response = $this->json('POST', '/api/request/add-recipient?device_id=user2', ['token' => $request->token]);
        $response = $this->get('/api/feedback?device_id=user2');
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" =>
                    ["feedbacks"]
                ])
                ->assertJsonFragment([
                    "provider_name" => "Francisco",
                    "content" => [
                        ["id" => 1, "feedback" => "all good here", "thumbnailUrl" => "virtuoso.jpg", "thumbnailName" => "Virtuoso"],
                        ["id" => 2, "feedback" => "all regular here", "thumbnailUrl" => "rain-maker.jpg", "thumbnailName" => "Rain Maker"]
                    ]
        ]);


        foreach ($response->baseResponse->getData()->data->feedbacks as $feedback) {
            foreach ($feedback->content as $content) {
                $this->assertEquals(Feedback::find($content->id)->status, 'open');
            }
        }
    }

    /**
     * @test
     * @group Feedbacks
     */
    public function feedbacks_can_be_queried_grouped_by_provider() {
        $this->seed('CategoriesSeeder');
        $this->registerUser();
        $userProvider1 = User::first();
        $this->json('POST', '/api/profile/store', array_merge($this->data(), ['name' => "UserProvider2", 'device_id' => "UserProvider2", 'device_token' => "UserProvider2"]));
        $userProvider2 = User::find(2);
        $this->json('POST', '/api/profile/store', array_merge($this->data(), ['name' => "UserProvider3", 'device_id' => "UserProvider3", 'device_token' => "UserProvider3"]));
        $userProvider3 = User::find(3);
        $this->json('POST', '/api/profile/store', array_merge($this->data(), ['name' => "userRecipient", 'device_id' => "userRecipient", 'device_token' => "userRecipient"]));
        $userRecipient = User::find(4);

        $this->creteGiveFeedback($userProvider1);
        $request = AppRequest::first();
        $response = $this->json('POST', '/api/request/add-recipient?device_id=userRecipient', ['token' => $request->token]);

        $this->creteGiveFeedback($userProvider2);
        $request = AppRequest::find(2);
        $response = $this->json('POST', '/api/request/add-recipient?device_id=userRecipient', ['token' => $request->token]);

        $this->creteGiveFeedback($userProvider3);
        $request = AppRequest::find(3);
        $response = $this->json('POST', '/api/request/add-recipient?device_id=userRecipient', ['token' => $request->token]);
        $users = User::all();
        $this->assertCount(4, $users);
        $response = $this->get('/api/feedbacks-by-provider?device_id=userRecipient');
        $response
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" =>
                    ["feedbacks"]
                ])
                ->assertJsonFragment([
                    "provider_name" => "Francisco",
                    "content" => [
                        ["id" => 1, "feedback" => "all good here", "thumbnailUrl" => "virtuoso.jpg", "thumbnailName" => "Virtuoso"],
                        ["id" => 2, "feedback" => "all regular here", "thumbnailUrl" => "rain-maker.jpg", "thumbnailName" => "Rain Maker"]
                    ]
        ]);
    }

    /**
     * @test
     * @group AppRequest
     */
    public function feedback_status_can_be_updated() {
        $this->registerUser();
        $userProvider = User::first();
        $this->seed('CategoriesSeeder');
        $response = $this->creteGiveFeedback($userProvider);
        
        $request=AppRequest::first();

        $feedback = $request->feedbacks()->first();
        $this->assertCount(1, AppRequest::all());
        $this->assertEquals('open', $feedback->status);
        
        
        $this->json('POST', '/api/profile/store', array_merge($this->data(), ['name' => "userRecipient", 'device_id' => "userRecipient", 'device_token' => "userRecipient"]));
        $this->json('POST', '/api/request/add-recipient?device_id=userRecipient', ['token' => $request->token]);
        $userRecipient=User::find(2);

        $response = $this->put('/api/feedback/set-status/' . $feedback->id . '?device_id=' . $userRecipient->device_id, ['status' => 'cancelledd']);
        
        dd($response);


        $this->assertEquals('cancelled', Feedback::find($feedback->id)->status);

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

}
