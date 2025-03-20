<?php

use App\Jobs\ProcessCSV;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;

class UploadsTest extends TestCase {

    public function testUpload_InvalidFileType() {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Storage::fake('local');
        $file = UploadedFile::fake()->create('test.csv', 0, 'text/plain');
        
        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ]);
    
        $response->assertStatus(415);
    }

    public function testUpload_MissingFile() {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/upload', []);
    
        $response->assertStatus(415);
    }

    public function testUpload_ValidFile() {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        Queue::fake();
        Storage::fake('local');

        $file = UploadedFile::fake()->create('test.csv', 10, 'text/csv');

        DB::shouldReceive('table')
            ->once()
            ->with('uploads')
            ->andReturnSelf();
        DB::shouldReceive('insert')
            ->once()
            ->andReturn(true);

        
        $response = $this->postJson('/api/upload', [
            'file' => $file,
        ]);
        
        Queue::assertPushed(ProcessCSV::class, function ($processor) use ($file, $user) {
            return $processor->filename == $file->hashName() &&
                $processor->userId = $user->id &&
                $processor->userEmail = $user->email;
        });
    
        $response->assertStatus(200);
        $response->assertJson([
            'filename' => $file->hashName()
        ]);
    }

    public function testStatus_UnexistingUpload() {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    
        DB::shouldReceive('table')
            ->once()
            ->with('uploads')
            ->andReturnSelf();
        DB::shouldReceive('where')
            ->andReturnSelf();
        DB::shouldReceive('select')
            ->andReturnSelf();
        DB::shouldReceive('first')
            ->once()
            ->andReturn(null);
    
        $response = $this->get('/api/status/12');
        $response->assertStatus(404);
    }

    // public function testUpload_ValidFile() {
    //     $validator = $this->createMock(Validator::class);
    //     $validator->method('fails')->willReturn(false);
    //     Validator::shouldReceive('make')
    //         ->once()
    //         ->with([ 'file' => 'test_file.csv'])
    //         ->andReturn($validator);
    // }
    
    /*
    public function test_upload_valid_csv()
    {
        // Fake user authentication
        $user = User::factory()->create();
        $this->actingAs($user);

        // Mock the request
        $file = UploadedFile::fake()->create('test.csv', 100);
        $request = $this->json('POST', '/upload', [
            'file' => $file,
        ]);

        // Check for correct response (success)
        $request->assertStatus(200)
                ->assertJsonStructure(['filename']);
        
        // Assert the file was stored
        Storage::disk('local')->asser

        // Assert the database insert
        $this->assertDatabaseHas('uploads', [
            'users_id' => $user->id,
            'file_path' => 'test.csv',
            'status' => 'pending',
        ]);

        // Assert the job was dispatched
        Queue::assertPushed(ProcessCSV::class);
    }
    */
}
