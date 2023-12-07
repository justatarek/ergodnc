<?php

namespace Controllers\Api;

use App\Models\Tag;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     */
    public function itListsTags(): void
    {
        Tag::factory(2)->create();

        $response = $this->getJson(route('api.tags.index'));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);
    }
}
