<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\User;
use App\AppRequest;

class GeneralTest extends TestCase {

    use DatabaseMigrations;

    /**
     * A basic test example.
     *
     * @test
     * @group general
     */
    public function testHomePageTest() {
        $this->assertGuest();
        $response = $this->get('/');
        $response->assertSee('<h3>Start your talent discovery journey<br> and download the Natably app now.</h3>');
        $main_text = "“Don't follow your passion. Instead, focus on your talent. Find out what you're good at and then invest 10,000 hours in it.”";
        $response->assertSee($main_text);
        $response->assertViewHasAll(['data' => '', 'pageTitle' => 'Natably', 'pageDescription' => 'Natably Home Page.']);
        $response->assertViewIs('home');
        $response->assertStatus(200);
    }
    
    
    /** 
     * @test
     * @group general
     */

    public function deviceCanBeRegistered() {
        $response=$this->registerUser();
        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
        ]);
        $this->assertCount(1, User::all());
        $user = User::first();
        $this->assertEquals('Francisco', $user->name);
        $this->assertEquals('3sdf546', $user->device_id);
        $this->assertEquals('254gs7', $user->device_token);
    }
    
    /** 
     * @test
     * @group general
     */

    public function categoriesCanBeQueried() {
        $response = $this->get('/api/categories');
        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
                ])->assertJsonStructure([
            'status',
            'success',
            'data',
        ]);
    }
    
    /** 
     * @test
     * @group general
     */

    public function aProfileCanBeEdited() {
        $response=$this->registerUser();
        $user = User::first();
        $response = $this->get('/api/profile?device_id=' . $user->device_id);
        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
                ])->assertJsonStructure([
            'status',
            'success',
            'data',
        ]);
    }
    
    /** 
     * @test
     * @group general
     */

    public function aProfileCanBeUpdated() {
        $response=$this->registerUser();
        $user = User::first();

        $response = $this->json('PUT', '/api/profile/update?device_id=' . $user->device_id,
                array_merge($this->data(), ['name' => 'Fran updated']));
        
        
        
        


        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
        ]);

        $this->assertCount(1, User::all());
        $user = User::first();
        
        
        
        

        $this->assertEquals('Fran updated', $user->name);
        $this->assertEquals('3sdf546', $user->device_id);
        $this->assertEquals('254gs7', $user->device_token);
    }
    
    
    /** 
     * @test
     * @group general
     */

    public function homeDataCanBeQueried() {
        $response=$this->registerUser();
        $user = User::first();
        $response = $this->get('/api/home-categories?device_id=' . $user->device_id);
        $response
                ->assertStatus(200)
                ->assertJson([
                    'status' => 200,
                ])
                ->assertJsonStructure([
                    "status",
                    "success",
                    "data" => [
                        "categories",
                        "feedbacks" => [
                            "feedbacks"
                        ]
                    ]
                        ]
        );
    }

    

    

    

}
