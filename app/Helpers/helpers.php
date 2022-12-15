<?php

use Illuminate\Support\Facades\Storage;
use App\Models\Message;

function prepareApiResponse($message, $code, $data = array())
{
    return array("message" => $message, "status" => $code, "data" => $data);
}

function uploadFiles($data, $file_name = "image", $path = "public/photos", $type = 0)
{
    if ($type) { //In case where direct file coming
        $path = Storage::putFile($path, $data);
    } else {
        $path = Storage::putFile($path, $data->file($file_name));
    }

    return $path;
}

function generateUniqueCodeForMessage()
{
    $code = rand(10000, 99999);
    $countForExistingCode = Message::where("local_message_id", $code)->count();
    if ($countForExistingCode) {
        generateUniqueCodeForMessage();
    }
    return $code;
}

function areaname($id)
{
    $area = Area::where('id', $id)->first();
    if ($area) {
        return ucfirst($area->title);
    } else {
        return false;
    }
}

function categoryname($id)
{
    $Category = Category::where('id', $id)->first();
    if ($Category) {
        return ucfirst($Category->title);
    } else {
        return false;
    }
}

function subcategoryname($id)
{
    $SubCategory = SubCategory::where('id', $id)->first();
    if ($SubCategory) {
        return $SubCategory->title;
    } else {
        return false;
    }
}

function treatmentsname($id)
{
    $Treatment = Treatment::where('id', $id)->first();
    if ($Treatment) {
        return $Treatment->title;
    } else {
        return false;
    }
}
