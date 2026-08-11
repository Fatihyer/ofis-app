<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\GroupinvoiceController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\SirketController;
use App\Http\Controllers\AjaxController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\NotificationGroupController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransfersController;
use App\Http\Controllers\AcenteController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\KurController;
use App\Http\Controllers\KdvController;
use App\Http\Controllers\FirmaController;
use App\Http\Controllers\HareketController;
use App\Http\Controllers\VehiculeController;
use App\Http\Controllers\VehiclePriceRuleController;
use App\Http\Controllers\DepotController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\DriverUsageController;
use App\Http\Controllers\DriverVehicleOvernightController;
use App\Http\Controllers\KilometerController;
use App\Http\Controllers\VehicleMaintenanceController;
use App\Http\Controllers\SubcontractedVehicleController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\OffsetController;
use App\Http\Controllers\AcentemsgController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\DovizalController;
use App\Http\Controllers\ExcelimportController;
use App\Http\Controllers\EfaturaController;
use App\Http\Controllers\ListController;
use App\Http\Controllers\MissionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\GoogleDriveController;
use App\Http\Controllers\TwilioController;
use App\Http\Controllers\FuelController;
use App\Http\Controllers\OtherController;
use App\Http\Controllers\OfficeHourController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\TalepController;
use App\Http\Controllers\GmailController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\FichierController;

use App\Http\Controllers\HareketFileController;
use Illuminate\Http\Request;

use App\Http\Controllers\BankImportController;
use App\Http\Controllers\FuelBankImportController;

use App\Http\Controllers\StickyNoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HermesController;
use App\Http\Controllers\TachographController;
use App\Http\Controllers\GlobalAlertController;
use App\Http\Controllers\WhatsappInboxController;
use App\Http\Controllers\AiBotController;
use App\Http\Controllers\PennylaneController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\GoogleAdsDashboardController;
use App\Http\Controllers\TaskController;

use App\Http\Controllers\DriverConfirmController;
use App\Http\Controllers\DriverPlanningController;
use App\Http\Controllers\WhatsAppGroupController;
use App\Http\Controllers\WhatsAppGroupLeadController;



Route::get('/driver/confirm/{transfer}', [DriverConfirmController::class, 'confirm']);
Route::get('/driver-app', [HomeController::class, 'driverApp'])->name('driver.app');
Route::post('/driver-app/location', [HomeController::class, 'driverAppLocation'])->name('driver.app.location');



// Hermes Routes
Route::get('/hermes', [HermesController::class, 'getVehicles'])->name('hermes.index');
Route::get('/hermes/engine-alerts', [HermesController::class, 'engineAlerts'])->name('hermes.engine_alerts');
Route::get('/alerts/client-driver-details', [GlobalAlertController::class, 'clientDriverDetails'])->name('alerts.client_driver_details');
Route::get('/alerts/user-notifications', [GlobalAlertController::class, 'userNotifications'])->name('alerts.user_notifications');
Route::post('/alerts/user-notifications/{notification}/read', [GlobalAlertController::class, 'markUserNotificationRead'])->name('alerts.user_notifications.read');
Route::get('/hermes/location/{uid}', [HermesController::class, 'getVehicleLocation']);
Route::get('/acentes/{id}/hermes-driving-hours', [AcenteController::class, 'getHermesDrivingHours'])
    ->name('acentes.hermes.hours');

Route::get('/hermes/vehicles', [HermesController::class, 'getVehicles']); // view döndürür, istersen web.php'ye taşı
Route::get('/hermes/vehicle/{uid}/location', [HermesController::class, 'getVehicleLocation']);
Route::get('/hermes/fleet', [HermesController::class, 'fleetDayView'])->name('hermes.fleet.day');
// örnek: /hermes/fleet?day=2026-02-07

Route::get('/hermes/fleet/yesterday', [HermesController::class, 'fleetYesterdayView'])->name('hermes.fleet.yesterday');


Route::middleware('auth')->group(function () {
    Route::get('/hermes/debug/track-raw/{unitUid}', [HermesController::class, 'debugTrackRaw']);
    Route::get('/hermes/debug/units', [HermesController::class, 'debugUnitsList']);
    Route::get('/hermes/debug/unit/{unitUid}', [HermesController::class, 'debugUnit']);
    Route::get('/hermes/debug/track-info/{unitUid}', [HermesController::class, 'debugTrackInfo']);
});



Route::get('/hermes/resources', [HermesController::class, 'getResources'])->name('hermes.resources');;
Route::get('/hermes/resources/{resUid}/yesterday-working-time', [HermesController::class, 'resourceYesterdayWorkingTime']);
Route::post('/hermes/resources/link', [HermesController::class, 'linkHermesResource'])
    ->name('hermes.resources.link');

Route::post('/hermes/resources/unlink', [HermesController::class, 'unlinkHermesResource'])
    ->name('hermes.resources.unlink');


Route::get('/hermes/acentes/search', [HermesController::class, 'searchAcentes'])
    ->name('hermes.acentes.search');



Route::get('/hermes/debug/track-days/{unitUid}', [HermesController::class, 'debugTrackRaw'])
    ->name('hermes.debug.track_days')->middleware('auth');
// DB'den fleet raporu (örn: /hermes/fleet-db?day=2026-02-07)
Route::get('/hermes/fleet-db', [HermesController::class, 'fleetDbView'])
    ->name('hermes.fleet.db');

Route::get('/hermes/driver-vehicle-daily', [HermesController::class, 'driverVehicleDailyView'])
    ->name('hermes.driver_vehicle_daily');


// Notifications
Route::post('/notifications/mark-read', [NotificationController::class, 'markRead'])
    ->name('notifications.markRead');

Route::delete('/notifications/delete', [NotificationController::class, 'deleteAll'])
    ->name('notifications.delete');

Route::get('/hermes/driver-working-daily', [HermesController::class, 'driverWorkingDailyView'])
    ->name('hermes.driver_working_daily');
    
Route::get('/hermes/drivers/working/daily',   [HermesController::class, 'driverWorkingDailyView'])->name('hermes.drivers.working.daily');
Route::get('/hermes/drivers/working/weekly',  [HermesController::class, 'driverWorkingWeeklyView'])->name('hermes.drivers.working.weekly');
Route::get('/hermes/drivers/working/monthly', [HermesController::class, 'driverWorkingMonthlyView'])->name('hermes.drivers.working.monthly');

Route::middleware('auth')->group(function () {
    Route::get('/hermes/tachograph', [TachographController::class, 'index'])->name('tachograph.index');
    Route::post('/hermes/tachograph/import', [TachographController::class, 'store'])->name('tachograph.import');
    Route::post('/hermes/tachograph/drive-settings', [TachographController::class, 'updateDriveSettings'])->name('tachograph.drive.settings');
    Route::post('/hermes/tachograph/drive-sync', [TachographController::class, 'syncDrive'])->name('tachograph.drive.sync');
    Route::get('/hermes/tachograph/google/redirect', [TachographController::class, 'redirectGoogleDrive'])->name('tachograph.drive.redirect');
    Route::get('/hermes/tachograph/google/callback', [TachographController::class, 'callbackGoogleDrive'])->name('tachograph.drive.callback');
    Route::post('/hermes/tachograph/google/disconnect', [TachographController::class, 'disconnectGoogleDrive'])->name('tachograph.drive.disconnect');
    Route::post('/hermes/tachograph/cards/{card}/map', [TachographController::class, 'mapCard'])->name('tachograph.cards.map');
});



// Authentication
Auth::routes();

///mesai
Route::get('/office-hours', [OfficeHourController::class, 'index'])->name('office_hours.index');
    Route::post('/office-hours/start', [OfficeHourController::class, 'startWork'])->name('office_hours.start');
    Route::post('/office-hours/end', [OfficeHourController::class, 'endWork'])->name('office_hours.end');
    Route::get('/office-hours/{id}/edit', [OfficeHourController::class, 'edit'])->name('office_hours.edit');
    Route::put('/office-hours/{id}', [OfficeHourController::class, 'update'])->name('office_hours.update');
    Route::delete('/office-hours/{id}', [OfficeHourController::class, 'destroy'])->name('office_hours.destroy');


// Language
Route::post('/language', [LanguageController::class, 'change'])->name('language.change');

// Home
Route::get('/ev', [HomeController::class, 'index'])->name('ev');
Route::get('/ev/timeline', [HomeController::class, 'timeline'])->name('ev.timeline');
Route::get('/pennylane', [PennylaneController::class, 'index'])->name('pennylane.index')->middleware('auth');
Route::post('/pennylane/customer-mappings', [PennylaneController::class, 'saveCustomerMappings'])->name('pennylane.customer-mappings')->middleware('auth');
Route::post('/pennylane/supplier-mappings', [PennylaneController::class, 'saveSupplierMappings'])->name('pennylane.supplier-mappings')->middleware('auth');
Route::post('/pennylane/suppliers/import', [PennylaneController::class, 'importSupplier'])->name('pennylane.suppliers.import')->middleware('auth');
Route::get('/google-ads', [GoogleAdsDashboardController::class, 'index'])->name('google-ads.index')->middleware('auth');
Route::post('/google-ads/refresh', [GoogleAdsDashboardController::class, 'refresh'])->name('google-ads.refresh')->middleware('auth');
Route::post('/google-ads/upload-form-leads', [GoogleAdsDashboardController::class, 'uploadFormLeads'])->name('google-ads.upload-form-leads')->middleware('auth');
Route::post('/google-ads/shared-negative-list/setup', [GoogleAdsDashboardController::class, 'setupSharedNegativeList'])->name('google-ads.shared-negative-list.setup')->middleware('auth');
Route::post('/google-ads/negative-keywords', [GoogleAdsDashboardController::class, 'addNegativeKeyword'])->name('google-ads.negative-keywords.store')->middleware('auth');
Route::post('/google-ads/negative-keywords/apply-candidates', [GoogleAdsDashboardController::class, 'applyNegativeCandidates'])->name('google-ads.negative-keywords.apply-candidates')->middleware('auth');
Route::post('/google-ads/negative-keywords/clean', [GoogleAdsDashboardController::class, 'cleanRedundantNegatives'])->name('google-ads.negative-keywords.clean')->middleware('auth');
Route::get('/', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::post('/home/message', [PostController::class, 'message'])->name('message.kayit');
Route::post('/home/messageedit', [PostController::class, 'messageedit'])->name('message.edit');
Route::post('/home/selectAjaxFirma', [AjaxController::class, 'selectAjaxFirma'])
    ->name('selectAjaxFirma')
    ->middleware(['web', 'auth']); // Assuming 'web' and 'auth' middleware groups are required
// Users
Route::get('/users-online', [UserController::class, 'online'])->name('users.online');
Route::resource('users', UserController::class);
Route::get('/password/{id}', [UserController::class, 'changepass'])->name('users.pass');
Route::post('/password', [UserController::class, 'passwordupdate'])->name('users.passwordupdate');

// Roles
Route::put('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('roles.permissions.update');
Route::put('/roles/{role}/users', [RoleController::class, 'updateUsers'])->name('roles.users.update');
Route::resource('roles', RoleController::class);

// Invoices
Route::get('/invoices/pennylane-compare', [InvoiceController::class, 'pennylaneCompare'])->name('invoices.pennylane.compare');
Route::get('/invoices/provider-pennylane-compare', [InvoiceController::class, 'providerPennylaneCompare'])->name('invoices.provider-pennylane.compare');
Route::post('/invoices/{invoice}/pennylane-draft', [InvoiceController::class, 'sendPennylaneDraft'])->name('invoices.pennylane.draft');
Route::post('/groupinvoices/{groupinvoice}/pennylane-draft', [GroupinvoiceController::class, 'sendPennylaneDraft'])->name('groupinvoices.pennylane.draft');
Route::resource('invoices', InvoiceController::class);
Route::resource('groupinvoices', GroupinvoiceController::class);
Route::get('/invois/pdf/{id}/{type?}', [InvoiceController::class, 'showpdf'])->name('invoicetopdf');
Route::get('/grpinvois/pdf/{id}/{type?}', [GroupinvoiceController::class, 'grppdf'])->name('grinvoicetopdf');
Route::get('/invois/auto/{id}', [InvoiceController::class, 'autofacture'])->name('autofacture');
Route::get('/voucher/pdf/{id}/{hotel}/{type?}', [InvoiceController::class, 'voucherpdf'])->name('vouchertopdf');

// Options
Route::resource('options', OptionController::class);
Route::get('/vehicle-price-rules/export', [VehiclePriceRuleController::class, 'export'])->name('vehicle-price-rules.export');
Route::post('/vehicle-price-rules/date-adjustments', [VehiclePriceRuleController::class, 'storeDateAdjustment'])->name('vehicle-price-rules.date-adjustments.store');
Route::put('/vehicle-price-rules/date-adjustments/{adjustment}', [VehiclePriceRuleController::class, 'updateDateAdjustment'])->name('vehicle-price-rules.date-adjustments.update');
Route::delete('/vehicle-price-rules/date-adjustments/{adjustment}', [VehiclePriceRuleController::class, 'destroyDateAdjustment'])->name('vehicle-price-rules.date-adjustments.destroy');
Route::resource('vehicle-price-rules', VehiclePriceRuleController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('/depots/vehicle', [DepotController::class, 'updateVehicle'])->name('depots.vehicle.update');
Route::resource('depots', DepotController::class)->only(['index', 'store', 'update', 'destroy']);
Route::post('/options/guncel', [OptionController::class, 'guncel'])->name('option.guncel');

// Sirkets
Route::resource('sirkets', SirketController::class);
Route::post('/sirkets/guncel', [SirketController::class, 'guncel'])->name('sirket.guncel');

// Ajax Routes
Route::post('/home/selectInvoiceAcente', [AjaxController::class, 'selectInvoiceAcente'])->name('selectInvoiceAcente');
Route::post('/home/selectaccount', [AjaxController::class, 'selectaccount'])->name('selectaccount');
Route::get('/events', [AjaxController::class, 'getEvents'])->name('events');
Route::get('/files', [AjaxController::class, 'getFiles'])->name('files');

Route::middleware('auth')->group(function () {
    Route::post('/tasks/{task}/accept', [TaskController::class, 'accept'])->name('tasks.accept');
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
    Route::resource('tasks', TaskController::class);
});

// Permissions
Route::resource('permissions', PermissionController::class);
Route::get('/notification-groups', [NotificationGroupController::class, 'index'])->name('notification-groups.index');
Route::put('/notification-groups/{group}/users', [NotificationGroupController::class, 'updateUsers'])->name('notification-groups.users');
Route::put('/notification-groups/alert-types/update', [NotificationGroupController::class, 'updateAlertTypes'])->name('notification-groups.alert-types');


// Clients
Route::resource('clients', ClientController::class);

// Accounts
Route::resource('accounts', AccountController::class);
Route::post('/accounts/guncel', [AccountController::class, 'guncel'])->name('accounts.guncel');

// Payments
Route::resource('payments', PaymentController::class);
Route::post('/payments/guncel', [PaymentController::class, 'guncel'])->name('payments.guncel');

// Posts
Route::post('/posts/comment/update', [PostController::class,'updateComment'])->name('posts.comment.update');
Route::get('/posts/user-file-stats', [PostController::class, 'userFileStats'])->name('posts.userFileStats');
Route::middleware('isAdmin')->group(function () {
    Route::get('/posts/uninvoiced', [PostController::class, 'uninvoiced'])->name('posts.uninvoiced');
    Route::post('/posts/uninvoiced/bulk-exclude', [PostController::class, 'bulkExcludeFromUninvoiced'])->name('posts.uninvoiced.bulk-exclude');
    Route::post('/posts/{post}/uninvoiced-exclude', [PostController::class, 'excludeFromUninvoiced'])->name('posts.uninvoiced.exclude');
    Route::post('/posts/{post}/uninvoiced-restore', [PostController::class, 'restoreToUninvoiced'])->name('posts.uninvoiced.restore');
});
Route::get('/posts/mismatch-transfers', [PostController::class, 'mismatchTransfers'])->name('mismatchTransfers');
Route::patch('/posts/{post}/sync-dates-from-transfers', [PostController::class, 'syncDatesFromTransfers'])->name('posts.syncDatesFromTransfers');
Route::patch('/posts/{post}/vehicules/all', [PostController::class, 'updateAllTransferVehicules'])->name('posts.vehicules.updateAll');
Route::patch('/posts/{post}/billing', [PostController::class, 'updateBilling'])->name('posts.billing.update');
Route::resource('posts', PostController::class);



Route::get('/balancefile', [PostController::class, 'balancefile'])->name('balancefile'); 
Route::get('/createfromtransfert', [PostController::class, 'createfromtransfert'])->name('posts.createfromtransfert'); 
Route::post('/storefromtransfert', [PostController::class, 'storefromtransfert'])->name('posts.storefromtransfert'); 
Route::get('hareketexcel/{id}', [PostController::class, 'excelhareket'])->name('post.excellist'); 
Route::get('hareketbakiye/{id}', [HareketController::class, 'bakiye'])->name('hareket.bakiye'); 


Route::post('/harekets/{hareket}/files', [HareketFileController::class, 'store'])
    ->name('harekets.files.store');

Route::delete('/hareket-files/{id}', [HareketFileController::class, 'destroy'])
    ->name('hareket-files.destroy');


// Transfers
Route::post('/planning-demain/assignment', [TransfersController::class, 'updatePlanningAssignment'])->name('planning.assignment.update');
Route::post('/transfers/{transfer}/driver-app-confirm', [TransfersController::class, 'driverAppConfirm'])->name('transfers.driverAppConfirm');
Route::post('/transfers/{transfer}/driver-app-refuse', [TransfersController::class, 'driverAppRefuse'])->name('transfers.driverAppRefuse');
Route::get('/transfers/vehicle-availability/check', [TransfersController::class, 'vehicleAvailability'])->name('transfers.vehicleAvailability');
Route::resource('transfers', TransfersController::class);


//sticky notes

Route::get('/sticky-notes', [StickyNoteController::class, 'index']);
Route::get('/sticky-notes/fetch', [StickyNoteController::class, 'fetch']);
Route::post('/sticky-notes', [StickyNoteController::class, 'store']);
Route::post('/sticky-notes/update-order', [StickyNoteController::class, 'updateOrder']);
Route::delete('/sticky-notes/{id}', [StickyNoteController::class, 'destroy']);



Route::get('transfershowdriver/{postId}', [TransfersController::class, 'showdriver'])->name('showdriver');
Route::get('createWithTrajets/{postId}', [TransfersController::class, 'createWithTrajets'])->name('createWithTrajets'); 
Route::put('updateWithTrajets/{transfer}', [TransfersController::class, 'updateWithTrajets'])->name('updateWithTrajets'); 
Route::post('/updateOfisStart', [TransfersController::class, 'ofisStart'])->name('updateofisstart');
Route::post('/updateOfisStartnull', [TransfersController::class, 'ofisStartnull'])->name('updateofisstartnull');
Route::get('transfershowpdf/{id}', [TransfersController::class, 'showtransferPDF'])->name('transfershowpdf');
Route::get('transferMissionshowpdf/{id}', [TransfersController::class, 'showMissionPDF'])->name('transferMissionshowpdf');
Route::get('/tranfersms/{id}', [TransfersController::class, 'smsgonder'])->name('transfers.sms');
Route::post('/transfers/voucherupload', [TransfersController::class, 'voucherupload'])->name('transfers.voucherupload');
Route::post('/transfers/{transfer}/driver-signature', [TransfersController::class, 'driverSignatureUpload'])->name('transfers.driverSignature');
Route::post('/transfers/{transfer}/driver-note', [TransfersController::class, 'driverNoteUpdate'])->name('transfers.driverNote');
Route::post('/transfers/guncel', [TransfersController::class, 'guncel'])->name('transfers.guncel');
Route::get('/transfer/clone/{id}', [TransfersController::class, 'clone'])->name('transfers.clone');
Route::post('/transfers/clientstatus/{id}', [TransfersController::class, 'updateClientStatus'])->name('transfers.updateClientStatus');
Route::get('transfers/addconge/{id}', [TransfersController::class, 'addconge'])->name('addconge');
Route::post('transfers/saveconge/{id}', [TransfersController::class, 'saveconge'])->name('saveconge');

Route::prefix('transferstable')->group(function () {
    // View sayfasını gösterir
    Route::get('/', [TransfersController::class, 'index'])->name('transferstable.index');

    // DataTables için AJAX veri kaynağı
    Route::get('/list', [TransfersController::class, 'list'])->name('transferstable.list');

    // Inline update işlemi (vehicule_id, driver_id, ofis_start vs.)
    Route::post('/update', [TransfersController::class, 'updatetable'])->name('transferstable.update');
});
Route::get('/transferstable/{id}/trajets', [TransfersController::class, 'getTrajets'])->name('transferstable.trajets');

// web.php
Route::get('/confirm-transfer/{token}', function ($token) {
    $transfer = \App\Models\Transfer::where('confirmation_token', $token)->firstOrFail();
    $transfer->update(['is_confirmed' => true]);

    return "<h2>✅ Merci ! Vous avez confirmé votre transfert.</h2>";
})->name('transfer.confirm');
Route::post('/send-whatsapp', [WhatsAppController::class, 'send'])->name('send.whatsapp');

Route::post('/twilio-status', function (\Illuminate\Http\Request $request) {
    \App\Models\TransferMessage::where('sid', $request->MessageSid)->update([
        'status' => $request->MessageStatus
    ]);
    return response('OK', 200);
})->name('twilio.status');



// Acentes
Route::get('/acentes/{id}/detailled-export', [AcenteController::class, 'detailledExport'])->name('acentes.detailled.export');
Route::resource('acentes', AcenteController::class);
Route::get('/balanceprovider', [AcenteController::class, 'balanceprovider'])->name('balanceprovider'); 
Route::get('downloadExcel/{id}', [AcenteController::class, 'excellist'])->name('acente.excellist'); 

// Statuses
Route::resource('statuss', StatusController::class);
Route::post('/status/guncel', [StatusController::class, 'guncel'])->name('statuss.guncel');
Route::patch('/posts/{post}/status', [PostController::class, 'updateStatus'])
    ->name('posts.updateStatus');

// Binbir
Route::get('/kaptan/show', [HomeController::class, 'kaptanShow'])->name('kaptanshow');
Route::get('/planning-demain', [ChartController::class, 'planningDemain'])->name('planning.demain');
Route::post('/heuredetravail/{acente}/remove-suivi', [DriverUsageController::class, 'removeSuivi'])->name('heuredetravail.remove-suivi');
Route::get('/heuredetravail/{weekOffset?}', [DriverUsageController::class, 'heuredetravail'])->name('heuredetravail');
Route::get('/monthly-calendar', [DriverUsageController::class, 'monthlyCalendar'])->name('monthlyCalendar');
Route::get('/driver-calendar', [DriverUsageController::class, 'showDriverCalendar'])->name('driver-calendar');

// Kurs
Route::resource('kurs', KurController::class);
Route::post('/kur/guncel', [KurController::class, 'guncel'])->name('kur.guncel');

// KDV
Route::resource('kdvs', KdvController::class);
Route::post('/kdv/guncel', [KdvController::class, 'guncel'])->name('kdv.guncel');

// Firmas
Route::resource('firmas', FirmaController::class);
Route::post('/firma/guncel', [FirmaController::class, 'guncel'])->name('firmas.guncel');

// Hareketler
Route::resource('harekets', HareketController::class);


//bankaları getir fransa

Route::get('/bank/import', [BankImportController::class, 'showImportForm'])->name('bank.import.form');
Route::post('/bank/import', [BankImportController::class, 'import'])->name('bank.import');
Route::post('/bank/import/pennylane', [BankImportController::class, 'importFromPennylane'])->name('bank.import.pennylane');
Route::post('/bank/import/pennylane-mappings', [BankImportController::class, 'savePennylaneMappings'])->name('bank.import.pennylane-mappings');
Route::delete('/bank/import/delete/{id}', [BankImportController::class, 'destroy'])->name('bank.import.delete');
Route::get('/bank/import/addoffset/{id}', function () {
    return redirect()->route('bank.import.form')->with('error', 'Session expiree ou ouverture directe du lien. Veuillez utiliser le bouton Creer ecriture depuis la page import bancaire.');
})->name('bank.import.addoffset.get');
Route::post('/bank/import/addoffset/{id}', [BankImportController::class, 'addoffset'])->name('bank.import.addoffset');

// Stripe payments
Route::get('/stripe/payments', [StripePaymentController::class, 'index'])->name('stripe.payments.index');
Route::post('/stripe/payments/sync', [StripePaymentController::class, 'sync'])->name('stripe.payments.sync');
Route::post('/stripe/payments/{stripePayment}/offset', [StripePaymentController::class, 'createOffset'])->name('stripe.payments.offset');
Route::post('/stripe/webhook', [StripePaymentController::class, 'webhook'])->name('stripe.webhook');
Route::post('/stripe/{account}/webhook', [StripePaymentController::class, 'webhook'])->whereIn('account', ['parisvia', 'francevia'])->name('stripe.webhook.account');

// Backward compatibility for old menu/bookmarks
Route::get('/fuel-bank/import', [BankImportController::class, 'showImportForm'])->name('fuel.bank.import.form');
Route::post('/fuel-bank/import', [BankImportController::class, 'import'])->name('fuel.bank.import');
Route::delete('/fuel/bank/delete/{id}', [BankImportController::class, 'destroy'])->name('fuel.bank.delete');
Route::post('/fuel/bank/addoffset/{id}', [BankImportController::class, 'addoffset'])->name('fuel.bank.addoffset');


// Vehicules
Route::resource('vehicules', VehiculeController::class);
Route::post('/vehicule/guncel', [VehiculeController::class, 'guncel'])->name('vehicule.guncel');
Route::post('/vehicules/{vehicule}/documents', [VehiculeController::class, 'storeDocument'])->name('vehicules.documents.store');
Route::delete('/vehicules/{vehicule}/documents/{filename}', [VehiculeController::class, 'destroyDocument'])->name('vehicules.documents.destroy');
Route::get('/vehicules/sous-traites', [SubcontractedVehicleController::class, 'index'])->name('vehicules.subcontracted');
Route::post('/vehicules/sous-traites/{transfer}/mouvement', [SubcontractedVehicleController::class, 'syncMovement'])->name('vehicules.subcontracted.movement');
Route::get('/planning-futur', [ChartController::class, 'planningFutur'])->name('planning.futur');
Route::post('/planning-futur/assign-vehicle', [TransfersController::class, 'assignPlanningFutureVehicle'])->name('planning.futur.assignVehicle');
Route::get('/driver-planning', [DriverPlanningController::class, 'index'])->name('driver-planning.index');
Route::put('/driver-planning/{driver}', [DriverPlanningController::class, 'update'])->name('driver-planning.update');
Route::get('/vehiculescontrol', [ChartController::class, 'vehiculescontrol'])->name('vehiculescontrol');
Route::post('/vehiculescontrol', [ChartController::class, 'viewByDate'])->name('vehiculescontrolviewByDate');
Route::get('/vehiculescontrol/{date}', [ChartController::class, 'viewBySpecificDate'])->name('vehiculescontrolviewBySpecificDate');
Route::get('/vehiculesusage', [VehiculeController::class, 'vehiculesusage'])->name('vehiculesusage');
Route::get('/vehicules-controle-docs', [VehiculeController::class, 'controleDocs'])->name('vehicules.controle-docs');


// Driver Usage
Route::resource('driver-vehicle-overnights', DriverVehicleOvernightController::class)->except(['show']);
Route::get('/driver-usage', [DriverUsageController::class, 'index'])->name('driverUsage.index');
Route::post('/driver-usage', [DriverUsageController::class, 'viewByDate'])->name('driverUsage.viewByDate'); 
Route::get('/driver-usage/{date}', [DriverUsageController::class, 'viewBySpecificDate'])->name('driverUsage.viewBySpecificDate'); 
Route::get('/getDriverWorkDays', [DriverUsageController::class, 'getDriverWorkDays'])->name('getDriverWorkDays'); 
Route::get('/get-driver-hours/{driverId}', [DriverUsageController::class, 'getDriverHours'])->name('getDriverHours');
Route::get('/get-driver-monthly-calendar/{driverId}/{year}/{month}', [DriverUsageController::class, 'getDriverMonthlyCalendar'])->name('getDriverMonthlyCalendar');
///Calışam tablosu
Route::get('/table-driver-usage', [DriverUsageController::class, 'boardindex'])->name('table-driverUsage.index');
Route::post('/table-driver-usage', [DriverUsageController::class, 'boardviewByDate'])->name('table-driverUsage.viewByDate'); 
Route::get('/table-driver-usage/{date}', [DriverUsageController::class, 'boardviewBySpecificDate'])->name('table-driverUsage.viewBySpecificDate'); 


// Kilometers
Route::post('kilometers', [KilometerController::class, 'store'])->name('kilometers.store');
Route::delete('/kilometers/{id}', [KilometerController::class, 'destroy'])->name('kilometers.destroy');

// Vehicle Maintenance
Route::get('vehicle-maintenance/monthly-costs', [VehicleMaintenanceController::class, 'monthlyCosts'])->name('vehicle_maintenance.monthly_costs');
Route::resource('vehicle_maintenance', VehicleMaintenanceController::class);

// Service Types
Route::resource('servicetype', ServiceTypeController::class);
Route::post('/servicetype/guncel', [ServiceTypeController::class, 'guncel'])->name('servicetype.guncel');


// Image Upload
Route::post('image-upload', [ImageUploadController::class, 'imageUploadPost'])->name('image.upload.post');
Route::post('image-upload-acente', [ImageUploadController::class, 'imageUploadAcente'])->name('image.upload.acente');

// Charts
Route::get('charts', [ChartController::class, 'file'])->name('charts');
Route::get('transfersday', [ChartController::class, 'transfer'])->name('transfersday');
Route::get('day/{day?}', [ChartController::class, 'day'])->name('day');

// Offsets
Route::resource('offsets', OffsetController::class);
Route::get('multioffsets', [OffsetController::class, 'multiCreate'])->name('multioffsets');
Route::post('multioffsetsstore', [OffsetController::class, 'multiStore'])->name('multioffsetsstore');

// Acente Messages
Route::resource('acentemsgs', AcentemsgController::class);

// Stocks
Route::resource('stocks', StockController::class);

// Hotels
Route::resource('hotels', HotelController::class);

// Doviz
Route::resource('dovizs', DovizalController::class);

// Manual
Route::get('/manuale', [HomeController::class, 'manual'])->name('manual');

// Logs
Route::get('logActivity', [HomeController::class, 'logActivity'])->name('logActivity');
Route::get('logActivity/{id}', [HomeController::class, 'PostActivity']);

// Files
Route::resource('fichier', FichierController::class);
Route::resource('others', OtherController::class);
Route::get('cloneothres/{id}',[OtherController::class,'clone'])->name('others.clone');
Route::post('fichier-upload', [FichierController::class, 'FichierUploadPost'])->name('fichier.upload.post');

// Email
Route::get('mail', [HomeController::class, 'mail']);

// Excel Import/Export
Route::get('export', [ExcelimportController::class, 'export'])->name('export');
Route::get('importExportView', [ExcelimportController::class, 'importExportView'])->name('importExportView');
Route::get('importExportcard', [ExcelimportController::class, 'importExportcard'])->name('listexcelcard');
Route::post('import', [ExcelimportController::class, 'import'])->name('import');
Route::post('importcard', [ExcelimportController::class, 'importcard'])->name('importcard');
Route::get('listexcel', [ExcelimportController::class, 'index'])->name('listexcel');

Route::delete('exceldestroy/{id}', [ExcelimportController::class, 'destroy'])->name('exceldestroy');
Route::post('addexceloffset/{id}', [ExcelimportController::class, 'addoffset'])->name('addexceloffset');




// E-Fatura

// List
Route::get('listegunluk/{tarih?}', [ListController::class, 'index'])->name('listegunluk');

// Missions
Route::get('m/t/{token}', [MissionController::class, 'publicIndex'])->name('mission.public');
Route::post('m/t/{token}/confirm', [MissionController::class, 'publicConfirm'])->name('mission.public.confirm');
Route::post('m/t/{token}/refuse', [MissionController::class, 'publicRefuse'])->name('mission.public.refuse');
Route::post('m/t/{token}/s', [MissionController::class, 'publicStart'])->name('mission.public.start');
Route::get('m/t/{token}/o/{mission}', [MissionController::class, 'publicOnplace'])->name('mission.public.onplace');
Route::get('m/t/{token}/b/{mission}', [MissionController::class, 'publicOnboard'])->name('mission.public.onboard');
Route::get('m/t/{token}/f/{mission}', [MissionController::class, 'publicFinish'])->name('mission.public.finish');
Route::post('m/t/{token}/depot/{mission}', [MissionController::class, 'publicFinishDepot'])->name('mission.public.finishDepot');
Route::post('m/t/{token}/kilometres/{mission}', [MissionController::class, 'publicUpdateKilometers'])->name('mission.public.kilometers');
Route::get('m/{transferid}', [MissionController::class, 'index'])->name('mission');
Route::post('m/s/{transferid}', [MissionController::class, 'start'])->name('startmission');
Route::get('m/o/{transferid}', [MissionController::class, 'onplace'])->name('onplacemission');
Route::get('m/b/{transferid}', [MissionController::class, 'onboard'])->name('onboardmission');
Route::get('m/f/{transferid}', [MissionController::class, 'finish'])->name('finishmission');
Route::post('/update_times', [MissionController::class, 'updateTimes'])->name('update_times');
Route::get('/missionslist', [MissionController::class, 'listm'])->name('missionlist');  
Route::post('/missions/by-date', [MissionController::class, 'missionsByDate'])->name('missions.byDate');
Route::post('/finishmissiondepot/{id}', [MissionController::class, 'finishMissionDepot'])->name('finishmissiondepot');
Route::post('/missions/{id}/kilometres', [MissionController::class, 'updateKilometers'])->name('missions.updateKilometers');

// Attendance
Route::post('/record-attendance', [AttendanceController::class, 'recordAttendance'])->name('permanence')->middleware('auth');
Route::post('/record-operation', [AttendanceController::class, 'recordOperation'])->name('operation')->middleware('auth');
Route::post('/record-parisgezgini', [AttendanceController::class, 'recordParisgezgini'])->name('parisgezgini')->middleware('auth');


Route::get('/last-attendance', [AttendanceController::class, 'getLastAttendance']);
Route::get('/last-operation', [AttendanceController::class, 'getLastOperation']);
Route::get('/last-parisgezgini', [AttendanceController::class, 'getLastparisgezgini']);

Route::get('/user-attendances', [AttendanceController::class, 'index'])->name('userattendances');
Route::post('/register-time', [MissionController::class, 'registerTime'])->name('registerTime');
Route::post('/register-timeend', [MissionController::class, 'registerTimeend'])->name('registerTimeend');

// Webhooks
Route::post('/webhook', [WebhookController::class, 'handleIncomingMessage']);
Route::post('/fallback', [WebhookController::class, 'handleFallback']);
Route::post('/status_callback', [WebhookController::class, 'handleStatusCallback']);


// Google Drive
Route::get('/drive/sheets', [GoogleDriveController::class, 'listGoogleSheets']);

// Twilio

Route::post('/twilio/whatsapp/webhook', [WhatsappInboxController::class, 'webhook'])->name('twilio.whatsapp.webhook');
Route::middleware('auth')->group(function () {
    Route::get('/ai-bot', [AiBotController::class, 'index'])->name('ai-bot');
    Route::post('/ai-bot/ask', [AiBotController::class, 'ask'])->name('ai-bot.ask');
    Route::get('/ai-bot/quick-transfer', [AiBotController::class, 'quickTransfer'])->name('ai-bot.quick-transfer');
    Route::post('/ai-bot/quick-transfer/xml', [AiBotController::class, 'quickTransferXml'])->name('ai-bot.quick-transfer.xml');
    Route::post('/ai-bot/quick-transfer/store', [AiBotController::class, 'quickTransferStore'])->name('ai-bot.quick-transfer.store');
});
Route::middleware('auth')->group(function () {
    Route::get('/whatsapp/inbox', [WhatsappInboxController::class, 'index'])->name('whatsapp.inbox');
    Route::get('/whatsapp/inbox/{message}', [WhatsappInboxController::class, 'show'])->name('whatsapp.inbox.show');
    Route::post('/whatsapp/inbox/{message}/reparse', [WhatsappInboxController::class, 'reparse'])->name('whatsapp.inbox.reparse');
    Route::post('/whatsapp/inbox/{message}/approve', [WhatsappInboxController::class, 'approve'])->name('whatsapp.inbox.approve');
    Route::post('/whatsapp/inbox/{message}/ignore', [WhatsappInboxController::class, 'ignore'])->name('whatsapp.inbox.ignore');
});
Route::get('/whatsapp-group-leads', [WhatsAppGroupLeadController::class, 'index'])->name('whatsapp-group-leads.index');
Route::get('/whatsapp-group-leads/{lead}', [WhatsAppGroupLeadController::class, 'show'])->name('whatsapp-group-leads.show');
Route::post('/whatsapp-group-leads/{lead}/status', [WhatsAppGroupLeadController::class, 'updateStatus'])->name('whatsapp-group-leads.status');
Route::post('/api/whatsapp/groups/{group}/send', [WhatsAppGroupLeadController::class, 'sendReply'])
    ->middleware('throttle:10,1')
    ->name('whatsapp-groups.send');
Route::get('/whatsapp-groups', [WhatsAppGroupController::class, 'index'])->name('whatsapp-groups.index');
Route::get('/whatsapp-groups/qr', [WhatsAppGroupController::class, 'qr'])->name('whatsapp-groups.qr');
Route::post('/whatsapp-groups/sync', [WhatsAppGroupController::class, 'sync'])->name('whatsapp-groups.sync');
Route::put('/whatsapp-groups/{group}', [WhatsAppGroupController::class, 'update'])->name('whatsapp-groups.update');
Route::post('/make-call', [TwilioController::class, 'makeCall'])->name('makecall');
Route::post('/twilio/webhook', [TwilioController::class, 'handleWebhook'])->name('handleWebhook');
Route::post('/twilio/voice-message', [TwilioController::class, 'voiceMessage'])->name('twilio.voice-message');
Route::get('/ara', [TwilioController::class, 'showCallForm']);



Route::get('/twilio-fallback', function () {
    return response('<?xml version="1.0"?><Response><Say>Le service est temporairement indisponible.</Say></Response>', 200)->header('Content-Type', 'text/xml');
});
Route::get('/twilio-status-callback', function () {
    return response('<?xml version="1.0"?><Response><Say>Le service est temporairement indisponible.</Say></Response>', 200)->header('Content-Type', 'text/xml');
});
Route::get('/twilio-call-status', function () {
    return response('<?xml version="1.0"?><Response><Say>Le service est temporairement indisponible.</Say></Response>', 200)->header('Content-Type', 'text/xml');
});
Route::post('/twilio-sms', function (Request $request) {
    \Log::info('📩 Gelen SMS:', $request->all());

    return response('<?xml version="1.0" encoding="UTF-8"?>
        <Response>
            <Message>Merci pour votre message. Nous vous contacterons bientôt.</Message>
        </Response>', 200)->header('Content-Type', 'text/xml');
});


// Fuel
Route::get('/fuel', [FuelController::class, 'index'])->name('fuel.index');
Route::get('/fuelcardlist', [FuelController::class, 'cardlist'])->name('fuel.cardlist');
Route::get('/fuellist', [FuelController::class, 'list'])->name('fuel.list');
Route::get('/fuel/create', [FuelController::class, 'create'])->name('fuel.create');
Route::get('/fuel/edit/{id}', [FuelController::class, 'edit'])->name('fuel.edit');
Route::put('/fuel/{id}', [FuelController::class, 'update'])->name('fuel.update');
Route::post('/fuel', [FuelController::class, 'store'])->name('fuel.store');
Route::get('/fuel/import', [FuelController::class, 'showForm'])->name('fuel.import.form');
Route::post('/fuel/import', [FuelController::class, 'import'])->name('fuel.import');
Route::delete('/fuel/import/delete/{id}', [FuelController::class, 'destroyexcel'])->name('fuel.import.delete');


Route::get('/fuel/import/review', [FuelController::class, 'excelindex'])->name('fuel.import.review');
Route::post('/fuel/import/save/{id}', [FuelController::class, 'excelstore'])->name('fuel.import.save');


// Fuel Purchase Routes
Route::get('/fuel/{fuelCard}/purchases', [FuelController::class, 'showPurchases'])->name('fuel.showPurchases');
Route::delete('/fuel/card/{id}', [FuelController::class, 'deleteCard'])->name('fuel.deleteCard');
Route::post('/fuel/purchase', [FuelController::class, 'addPurchase'])->name('fuel.addPurchase');
Route::get('/fuel/{fuelCard}/purchase/{id}/edit', [FuelController::class, 'editPurchase'])->name('fuel.editPurchase');
Route::post('/fuel/{fuelCard}/purchase/{id}', [FuelController::class, 'updatePurchase'])->name('fuel.updatePurchase');
Route::delete('/fuel/{fuelCard}/purchase/{id}', [FuelController::class, 'deletePurchase'])->name('fuel.deletePurchase');
Route::post('/fuel/{fuelCard}/addPurchasebycard', [FuelController::class, 'addPurchasebycard'])->name('fuel.addPurchasebycard');
Route::get('fuel/recover/{id}', [FuelController::class, 'recoverCard'])->name('fuel.recover');
Route::get('/fuel-purchases-chart', [FuelController::class, 'fuelPurchasesChart'])->name('fuel.chart');



Route::get('/fuel/add', [FuelController::class, 'createPurchase'])->name('fuel.createPurchase');
Route::get('/fuel/monthly-usage', [FuelController::class, 'monthlyFuelUsage'])->name('fuel.monthlyFuelUsage');

// Talepler için route grubu


Route::prefix('talepler')->name('talepler.')->group(function () {
    Route::get('/', [TalepController::class, 'index'])->name('index');
    Route::get('/create', [TalepController::class, 'create'])->name('create');
    Route::get('/options', [TalepController::class, 'options'])->name('options');
    Route::post('/options', [TalepController::class, 'updateOptions'])->name('options.update');
    Route::post('/', [TalepController::class, 'store'])->name('store');
    Route::post('/ai-xml', [TalepController::class, 'generateXmlFromText'])->name('aiXml');
    Route::post('/{talep}/recalculate-price', [TalepController::class, 'recalculatePrice'])->name('recalculatePrice');
    Route::post('/{talep}/ai-price-suggestion', [TalepController::class, 'aiPriceSuggestion'])->name('aiPriceSuggestion');
    Route::post('/{talep}/days/{day}/admin-note', [TalepController::class, 'updateOperationAdminNote'])->name('days.adminNote');
    Route::get('/{talep}', [TalepController::class, 'show'])->name('show');
    Route::get('/{talep}/edit', [TalepController::class, 'edit'])->name('edit');
    Route::put('/{talep}', [TalepController::class, 'update'])->name('update');
    Route::delete('/{talep}', [TalepController::class, 'destroy'])->name('destroy');

    Route::post('/route-preview', [TalepController::class, 'routePreview'])->name('routePreview');
});
Route::post('/talepler/{id}/konfirme-durumu', [TalepController::class, 'updateKonfirmeDurumu']);
Route::post('/talepler/{id}/update-user', [TalepController::class, 'updateUser']);
Route::post('/talepler/{id}/update-acente', [TalepController::class, 'updateAcente']);
Route::post('/talepler/{id}/update-fiyat', [TalepController::class, 'updateFiyat']);
Route::post('/talepler/{id}/operation-prices', [TalepController::class, 'updateOperationPrices'])->name('talepler.operationPrices');
Route::post('/talepler/{id}/update-relance', [TalepController::class, 'updateRelance']);
Route::post('/talepler/{id}/update-uzun-mesaj', [TalepController::class, 'updateUzunMesaj']);
Route::post('/talepler/{id}/update-internal-notes', [TalepController::class, 'updateInternalNotes'])->name('talepler.updateInternalNotes');


// Mail listesini getir
Route::get('/gmail-mails', [GmailController::class, 'listMails'])->name('gmail.mails');

// Bir mailden talep oluştur
Route::post('/gmail-create-talep', [GmailController::class, 'createTalep'])->name('gmail.create.talep');
Route::post('/gmail-link-talep', [GmailController::class, 'linkTalep'])->name('gmail.link.talep');

//////Cansu web worpress datblae al
use App\Http\Controllers\WpPostController;

Route::controller(WpPostController::class)->group(function () {
    Route::get('/cansu-requests', 'index')->name('cansu.index');         // liste + filtre
    Route::get('/cansu-requests/{id}', 'show')->name('cansu.show');      // tek kayıt
    Route::get('/cansu-requests-export', 'exportCsv')->name('cansu.csv'); // CSV dışa aktarım
});
