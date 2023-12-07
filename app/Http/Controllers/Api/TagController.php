<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TagResource;
use App\Models\Tag;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TagController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        return TagResource::collection(
            Tag::all(),
        );
    }
}
