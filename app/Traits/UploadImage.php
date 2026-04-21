<?php

namespace App\Traits;

trait UploadImage
{
    public function uploadImage($request, $folderName, $requestFile)
    {

        if ($request->hasFile($requestFile)) {
            $image = $request->file($requestFile)->getClientOriginalName();
            $path = $request->file($requestFile)->storeAs($folderName, $image, 'public');

            return ['success' => true, 'data' => $path];  // Changed to return an array
        } else {
            return ['success' => false, 'message' => 'No image provided', 'data' => null];  // Corrected the return format
        }
    }
}
