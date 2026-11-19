<?php

declare(strict_types=1);

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ModerationCategoryController;
use App\Http\Controllers\ModerationCommentController;
use App\Http\Controllers\ModerationEventController;
use App\Http\Controllers\ModerationVenueController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/udalosti', [EventController::class, 'index'])->name('events.index');
Route::get('/mista', [VenueController::class, 'index'])->name('venues.index');
Route::get('/kategorie', [CategoryController::class, 'index'])->name('categories.index');

Route::middleware('guest')->group(function () {
    Route::get('/registrace', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/registrace', [AuthController::class, 'register'])->name('register.store');

    Route::get('/prihlaseni', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/prihlaseni', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/odhlaseni', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profil/heslo', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/moje-udalosti', [EventController::class, 'mine'])->name('events.mine');
    Route::get('/udalosti/nova', [EventController::class, 'create'])->name('events.create');
    Route::post('/udalosti/nova', [EventController::class, 'store'])->name('events.store');
    Route::get('/udalosti/{event}/upravit', [EventController::class, 'edit'])->name('events.edit');
    Route::post('/udalosti/{event}/upravit', [EventController::class, 'update'])->name('events.update');
    Route::post('/udalosti/{event}/odeslat', [EventController::class, 'submit'])->name('events.submit');
    Route::post('/udalosti/{event}/zrusit', [EventController::class, 'cancel'])->name('events.cancel');

    Route::get('/moje-registrace', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::post('/udalosti/{slug}/registrace', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::post('/registrace/{registration}/zrusit', [RegistrationController::class, 'cancel'])->name('registrations.cancel');
    Route::post('/registrace/{registration}/uhrazeno', [RegistrationController::class, 'claimPayment'])->name('registrations.claim-payment');
    Route::get('/udalosti/{event}/ucastnici', [RegistrationController::class, 'participants'])->name('registrations.participants');
    Route::post('/registrace/{registration}/potvrdit', [RegistrationController::class, 'confirmPayment'])->name('registrations.confirm-payment');
    Route::post('/registrace/{registration}/odmitnout', [RegistrationController::class, 'rejectPayment'])->name('registrations.reject-payment');

    Route::get('/muj-kalendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/muj-kalendar.ics', [CalendarController::class, 'export'])->name('calendar.export');

    Route::post('/udalosti/{slug}/hodnoceni', [FeedbackController::class, 'storeRating'])->name('events.ratings.store');
    Route::post('/udalosti/{slug}/komentar', [FeedbackController::class, 'storeComment'])->name('events.comments.store');
    Route::post('/komentare/{comment}/smazat', [FeedbackController::class, 'destroyComment'])->name('comments.destroy');

    Route::get('/mista/navrhnout', [VenueController::class, 'create'])->name('venues.create');
    Route::post('/mista/navrhnout', [VenueController::class, 'store'])->name('venues.store');
    Route::get('/kategorie/navrhnout', [CategoryController::class, 'create'])->name('categories.create');
    Route::post('/kategorie/navrhnout', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/moje-navrhy', [VenueController::class, 'myProposals'])->name('proposals.index');
});

Route::get('/udalosti/{slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/mista/{venue}', [VenueController::class, 'show'])->name('venues.show');

Route::middleware(['auth', 'role:moderator'])
    ->prefix('moderace/udalosti')
    ->name('moderation.events.')
    ->group(function () {
        Route::get('/', [ModerationEventController::class, 'index'])->name('index');
        Route::get('/{event}', [ModerationEventController::class, 'show'])->name('show');
        Route::post('/{event}/schvalit', [ModerationEventController::class, 'approve'])->name('approve');
        Route::post('/{event}/zamitnout', [ModerationEventController::class, 'reject'])->name('reject');
    });

Route::middleware(['auth', 'role:moderator'])
    ->prefix('moderace/mista')
    ->name('moderation.venues.')
    ->group(function () {
        Route::get('/', [ModerationVenueController::class, 'index'])->name('index');
        Route::get('/nove', [ModerationVenueController::class, 'create'])->name('create');
        Route::post('/nove', [ModerationVenueController::class, 'store'])->name('store');
        Route::get('/{venue}/upravit', [ModerationVenueController::class, 'edit'])->name('edit');
        Route::post('/{venue}/upravit', [ModerationVenueController::class, 'update'])->name('update');
        Route::post('/{venue}/schvalit', [ModerationVenueController::class, 'approve'])->name('approve');
        Route::post('/{venue}/zamitnout', [ModerationVenueController::class, 'reject'])->name('reject');
        Route::post('/{venue}/smazat', [ModerationVenueController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:moderator'])
    ->prefix('moderace/kategorie')
    ->name('moderation.categories.')
    ->group(function () {
        Route::get('/', [ModerationCategoryController::class, 'index'])->name('index');
        Route::get('/nova', [ModerationCategoryController::class, 'create'])->name('create');
        Route::post('/nova', [ModerationCategoryController::class, 'store'])->name('store');
        Route::get('/{category}/upravit', [ModerationCategoryController::class, 'edit'])->name('edit');
        Route::post('/{category}/upravit', [ModerationCategoryController::class, 'update'])->name('update');
        Route::post('/{category}/schvalit', [ModerationCategoryController::class, 'approve'])->name('approve');
        Route::post('/{category}/zamitnout', [ModerationCategoryController::class, 'reject'])->name('reject');
        Route::post('/{category}/smazat', [ModerationCategoryController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'role:moderator'])
    ->prefix('moderace/komentare')
    ->name('moderation.comments.')
    ->group(function () {
        Route::get('/', [ModerationCommentController::class, 'index'])->name('index');
        Route::post('/{comment}/skryt', [ModerationCommentController::class, 'hide'])->name('hide');
        Route::post('/{comment}/zobrazit', [ModerationCommentController::class, 'show'])->name('show');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin/uzivatele')
    ->name('admin.users.')
    ->group(function () {
        Route::get('/', [AdminUserController::class, 'index'])->name('index');
        Route::get('/novy', [AdminUserController::class, 'create'])->name('create');
        Route::post('/novy', [AdminUserController::class, 'store'])->name('store');
        Route::get('/{user}/upravit', [AdminUserController::class, 'edit'])->name('edit');
        Route::post('/{user}/upravit', [AdminUserController::class, 'update'])->name('update');
        Route::post('/{user}/heslo', [AdminUserController::class, 'resetPassword'])->name('reset-password');
        Route::post('/{user}/aktivita', [AdminUserController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{user}/smazat', [AdminUserController::class, 'destroy'])->name('destroy');
    });
