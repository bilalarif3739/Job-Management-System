<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfileResource;
use App\Models\UserToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return Response::success('Profile retrieved successfully.', [
            'user' => new ProfileResource($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        if (!$request->hasAny(['name', 'email'])) {
            return Response::error('Provide a name or email to update.', 422);
        }

        $user = $request->user();
        if ($request->has('name')) {
            $request->merge([
                'name' => preg_replace('/\s+/', ' ', trim((string) $request->input('name'))),
            ]);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'regex:/^[\pL\s]+$/u', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc',
                'max:255',
                'regex:/^\S+$/u',
                Rule::unique('users', 'email')->ignore($user->getKey()),
            ],
        ]);

        $user->fill($validated)->save();

        return Response::success('Profile updated successfully.', [
            'user' => new ProfileResource($user->fresh()),
        ]);
    }

    public function updatePicture(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'profile_picture' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = $request->user();
        $oldPath = $user->profile_picture_path;
        $newPath = $validated['profile_picture']->storePublicly('profile-pictures', 'public');

        if (!is_string($newPath) || $newPath === '') {
            Log::error('Unable to store a profile picture.', ['user_id' => $user->id]);

            return Response::error('Unable to save the profile picture.', 500);
        }

        $user->profile_picture_path = $newPath;
        $user->save();

        if ($oldPath && $oldPath !== $newPath && !Storage::disk('public')->delete($oldPath)) {
            Log::warning('Unable to remove a replaced profile picture.', [
                'user_id' => $user->id,
                'path' => $oldPath,
            ]);
        }

        return Response::success('Profile picture updated successfully.', [
            'user' => new ProfileResource($user->fresh()),
        ]);
    }

    public function deletePicture(Request $request): JsonResponse
    {
        $user = $request->user();
        $oldPath = $user->profile_picture_path;

        if (
            $oldPath && Storage::disk('public')->exists($oldPath)
            && !Storage::disk('public')->delete($oldPath)
        ) {
            Log::error('Unable to remove a profile picture.', ['user_id' => $user->id]);

            return Response::error('Unable to remove the profile picture.', 500);
        }

        $user->profile_picture_path = null;
        $user->save();

        return Response::success('Profile picture removed successfully.', [
            'user' => new ProfileResource($user->fresh()),
        ]);
    }

    public function destroyAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return Response::error('Current password is incorrect.', 422);
        }

        $picturePath = $user->profile_picture_path;

        if (
            $picturePath && Storage::disk('public')->exists($picturePath)
            && !Storage::disk('public')->delete($picturePath)
        ) {
            Log::error('Unable to remove profile picture during account deletion.', [
                'user_id' => $user->id,
            ]);

            return Response::error('Unable to delete the account right now.', 500);
        }

        DB::transaction(function () use ($user): void {
            $user->conversations()->with('messages')->get()->each(function ($conversation): void {
                $conversation->messages()->delete();
                $conversation->delete();
            });

            UserToken::where('user_id', $user->id)->delete();
            $user->forceFill([
                'remembertoken' => null,
                'profile_picture_path' => null,
            ])->save();
            $user->delete();
        });

        return Response::success('Account deleted successfully.');
    }
}
