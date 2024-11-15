<?php

use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\Auth\FacebookController;
use App\Http\Controllers\SocialiteController;
use App\Services\SendMailService;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('auth/facebook', [FacebookController::class, 'redirectToProvider']);
Route::get('auth/facebook/callback', [FacebookController::class, 'handleProviderCallback']);
Route::post('/send-message', [FacebookController::class, 'sendMessage'])->middleware('auth');

// outfiles
Route::get('/files', [FileController::class, 'index'])->name('files.all');
Route::get('/files/download/{path}', [FileController::class, 'download'])->where('path', '.*')->name('all.download');
// Route::get('/files/outbound/v1/{folder}/{filename}', [FileController::class, 'show'])->where('filename', '.*')->name('files.outbound.v1');
Route::get('/files/show/{folder}/{filename}', [FileController::class, 'show'])->where('filename', '.*')->name('files.show');
Route::get('files/logo/{id}/profile', [FileController::class, 'showLogo'])->where('filename', '.*')->name('users.profile');


Route::get('auth/google', [SocialiteController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [SocialiteController::class, 'handleGoogleCallback']);
Route::get('/', function () {
    echo 'welcome';
});
Route::get('/clear-cache', function() {
    $exitCode = Artisan::call('config:cache');
    $exitCode = Artisan::call('config:clear');
    $exitCode = Artisan::call('cache:clear');
    $exitCode = Artisan::call('view:clear');
    $exitCode = Artisan::call('route:clear');
    $exitCode = Artisan::call('clear-compiled');
    
     // Appel de la méthode dans FileController
    (new FileController)->deleteFolderContents();     
    return "Cache is cleared and optimise";
});
Route::get('emails', [SendMailService::class, 'submitMail']);
Route::get('/email' , function () {

    $title = "Bonjour\n";
    $body = "Bonjour <br><br> Suite à votre demande d'activation du service";
    return view('mail.notification', [ "body" => "$body", "title" => "$title", "data" => $data[] = ['title' => 'mon titire']]);
});

// Auth::routes();
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
