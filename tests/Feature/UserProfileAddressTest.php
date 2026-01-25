<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Identity\Models\User;
use App\Domain\Shipping\Models\Country;
use App\Domain\Shipping\Models\State;
use App\Domain\Shipping\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Log;

class UserProfileAddressTest extends TestCase
{
    /**
     * Test that user profile page loads successfully
     *
     * @return void
     */
    public function test_user_profile_page_loads()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $response = $this->actingAs($user)->get('/user/profile');

        $response->assertStatus(200);
        $response->assertViewIs('user.profile');
        $response->assertViewHas('user');
    }

    /**
     * Test that latitude and longitude columns exist in users table
     *
     * @return void
     */
    public function test_latitude_longitude_columns_exist()
    {
        $columns = \Schema::getColumnListing('users');

        $this->assertContains('latitude', $columns, 'latitude column does not exist in users table');
        $this->assertContains('longitude', $columns, 'longitude column does not exist in users table');
    }

    /**
     * Test that User model has latitude and longitude in fillable
     *
     * @return void
     */
    public function test_user_model_fillable_includes_coordinates()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('latitude', $fillable, 'latitude is not in User fillable array');
        $this->assertContains('longitude', $fillable, 'longitude is not in User fillable array');
        $this->assertContains('country', $fillable, 'country is not in User fillable array');
        $this->assertContains('state_id', $fillable, 'state_id is not in User fillable array');
        $this->assertContains('city_id', $fillable, 'city_id is not in User fillable array');
        $this->assertContains('address', $fillable, 'address is not in User fillable array');
    }

    /**
     * Test updating user profile with address data
     *
     * @return void
     */
    public function test_user_can_update_profile_with_address()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'country' => 'Saudi Arabia',
            'state_id' => 1,
            'city_id' => 1,
            'address' => 'Test Address, Riyadh, Saudi Arabia',
            'latitude' => 24.7135517,
            'longitude' => 46.6752957,
        ];

        $response = $this->actingAs($user)->post('/user/profile', $data);

        $response->assertRedirect('/user/profile');
        $response->assertSessionHas('success');

        // Verify data was saved
        $user->refresh();
        $this->assertEquals('Saudi Arabia', $user->country);
        $this->assertEquals(1, $user->state_id);
        $this->assertEquals(1, $user->city_id);
        $this->assertEquals('Test Address, Riyadh, Saudi Arabia', $user->address);
        $this->assertEquals(24.7135517, (float)$user->latitude);
        $this->assertEquals(46.6752957, (float)$user->longitude);
    }

    /**
     * Test that saved address data is displayed on profile page
     *
     * @return void
     */
    public function test_saved_address_displays_on_profile_page()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        // Set address data
        $user->update([
            'country' => 'Saudi Arabia',
            'state_id' => 1,
            'city_id' => 1,
            'address' => 'Test Display Address',
            'latitude' => 24.7135517,
            'longitude' => 46.6752957,
        ]);

        $response = $this->actingAs($user)->get('/user/profile');

        $response->assertStatus(200);
        $response->assertSee('Test Display Address');
        $response->assertSee('24.7135517', false); // false = don't escape
        $response->assertSee('46.6752957', false);
    }

    /**
     * Test that country dropdown has selected attribute for saved country
     *
     * @return void
     */
    public function test_country_dropdown_shows_selected()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $user->update(['country' => 'Saudi Arabia']);

        $response = $this->actingAs($user)->get('/user/profile');

        $response->assertStatus(200);
        $response->assertSee('selected', false);
    }

    /**
     * Test profile update logs data correctly
     *
     * @return void
     */
    public function test_profile_update_logs_data()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        Log::shouldReceive('info')
            ->once()
            ->with('User Profile Update - Received Data:', \Mockery::any());

        Log::shouldReceive('info')
            ->once()
            ->with('User Profile Update - Data Saved Successfully:', \Mockery::any());

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'country' => 'Saudi Arabia',
            'state_id' => 1,
            'city_id' => 1,
            'address' => 'Test Address',
            'latitude' => 24.7135517,
            'longitude' => 46.6752957,
        ];

        $response = $this->actingAs($user)->post('/user/profile', $data);
    }

    /**
     * Test handling null coordinates
     *
     * @return void
     */
    public function test_can_save_profile_with_null_coordinates()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $data = [
            'name' => $user->name,
            'email' => $user->email,
            'country' => 'Saudi Arabia',
            'state_id' => 1,
            'city_id' => 1,
            'address' => 'Test Address',
            'latitude' => null,
            'longitude' => null,
        ];

        $response = $this->actingAs($user)->post('/user/profile', $data);

        $response->assertRedirect('/user/profile');
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->latitude);
        $this->assertNull($user->longitude);
    }

    /**
     * Test that Google Maps modal is included in the page
     *
     * @return void
     */
    public function test_google_maps_modal_is_included()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $response = $this->actingAs($user)->get('/user/profile');

        $response->assertStatus(200);
        $response->assertSee('Select Location from Map');
        $response->assertSee('mapModal', false);
    }

    /**
     * Test that NiceSelect initialization script exists
     *
     * @return void
     */
    public function test_niceselect_initialization_exists()
    {
        $user = User::first();
        if (!$user) {
            $this->markTestSkipped('No users in database');
        }

        $response = $this->actingAs($user)->get('/user/profile');

        $response->assertStatus(200);
        $response->assertSee('Initializing NiceSelect for saved values', false);
        $response->assertSee('NiceSelect.bind', false);
    }
}
