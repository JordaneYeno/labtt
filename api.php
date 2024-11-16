<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\MessagesController;

Route::post('connexion', [ApiController::class, 'authenticate']);
Route::post('register', [ApiController::class, 'register']);
Route::get('chrone', [NotificationController::class, 'sendChrone']);


Route::group(['middleware' => ['jwt.verify']], function() {

    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('get_user', [ApiController::class, 'get_user']);
    Route::post('sendMessageSimple' ,[MessagesController::class, 'sendMessageSimple']);
    Route::post('send' ,[MessagesController::class, 'send']);
    Route::post('sendMessagerie' ,[MessagesController::class, 'sendSMS']);
    Route::post('sendMail' ,[MessagesController::class, 'sendMail']);
    Route::post('create_msg_masse', [NotificationController::class, 'create_msg_masse']);
    
});