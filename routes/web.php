<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\GuestMiddleware;
use App\Http\Middleware\LoginMiddleware;

Route::get('/', function () {
    return redirect(route('login'));
});

Route::get('/home', function () {
    return view('dashboard');
})->middleware(LoginMiddleware::class)->name('dashboard');



Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login')->middleware(GuestMiddleware::class);

Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::resource('posts', PostController::class)->except(['index', 'create', 'update']);


Route::middleware(LoginMiddleware::class)->group(function () {

    Route::put('/posts/update', [PostController::class, 'update'])->name('posts.update');

    Route::post('/concerns/analyze', [PostController::class, 'analyze'])->name('posts.analyze');

    Route::post('/posts/save-progress', [PostController::class, 'saveProgress'])->name('posts.saveProgress');

    //newly added code 01162025
    Route::get('/status-overview', [PostController::class, 'getStatusOverview'])->name('status.overview');


    Route::prefix('/concerns')->name('concerns.')->group(function(){

        Route::get('/list', [PostController::class, 'list'])->name('list');
        Route::get('/resolvebm', [PostController::class, 'resolvebm'])->name('resolvebm');
        Route::get('/endorsebm', [PostController::class, 'endorsebm'])->name('endorsebm');
        Route::get('/resolveho', [PostController::class, 'resolveho'])->name('resolveho');
        Route::get('/reportHeadOffice',[PostController::class, 'reportho'])->name('reportHeadOffice');
        Route::get('/reportbm',[PostController::class, 'reportbm'])->name('reportbm');
        Route::get('/download/report/{type}', [PostController::class, 'download'])->name('download.report');
        Route::get('/download/reportho/{type}/{areas?}', [PostController::class, 'downloadho'])->name('download.reportho');


    });


    Route::get('/resolved-concerns', [PostController::class, 'resolved'])->name('posts.resolved');

    Route::get('/resolved-facilitate', [PostController::class, 'facilitate'])->name('posts.facilitate');

    // Route to handle validation of a concern
    Route::post('/concerns/validate', [PostController::class, 'validateConcern'])->name('validate.concern');

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

});

Route::post('/posts', [PostController::class, 'store'])->name('posts.store');
 //It defines a GET route to display the form for creating a new post
Route::get('/posts/create/concern', [PostController::class, 'create'])->name('posts.create');
Route::get('/posts/success', function () {
    return view('posts.thank_you');
});




Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


