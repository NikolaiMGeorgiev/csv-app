<?php

use App\Jobs\ProcessCSV;
use App\Models\Uploads;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
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
        $uploads = Mockery::mock('overload:' . Uploads::class);
        $uploads->shouldReceive('save')
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
    
        $uploads = Mockery::mock('overload:' . Uploads::class);
        $uploads->shouldReceive('where')
            ->andReturnSelf();
        $uploads->shouldReceive('select')
            ->once()
            ->andReturnSelf();
        $uploads->shouldReceive('first')
            ->once()
            ->andReturn(null);
    
        $response = $this->get('/api/status/12');
        $response->assertStatus(404);
    }

    public function testStatus_ExistingUpload() {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    
        $uploads = Mockery::mock('overload:' . Uploads::class);
        $uploads->shouldReceive('where')
            ->andReturnSelf();
        $uploads->shouldReceive('select')
            ->once()
            ->andReturnSelf();
        $uploads->shouldReceive('first')
            ->once()
            ->andReturn((object) ['status' => 'completed']);
    
        $response = $this->get('/api/status/12');
        $response->assertStatus(200);
        $this->assertEquals('completed', $response['status']);
    }
}
