<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'profile_picture_url' => $this->profile_picture_path
                ? Storage::disk('public')->url($this->profile_picture_path)
                : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
