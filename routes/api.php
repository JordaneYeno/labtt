<?php

use App\Http\Controllers\AdminAbonnementController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\Api\FileController;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AssistanceController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\ClientMessagesController;
use App\Http\Controllers\ClientTemplateController;
use App\Http\Controllers\FacebookPageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentConroller;
use App\Http\Controllers\SocialiteController;
use App\Http\Controllers\TarificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WaGroupController;
use App\Models\Abonnement;
use App\Models\Param;
use Illuminate\Support\Facades\Route;



Route::get('dec/recep/outfiles/{encrypted_id}', [ExportController::class, 'downloadEncryptedFile']);

Route::get('export', [ExportController::class, 'exportToExcel']);

Route::post('add', [WaGroupController::class, 'addMembers']);
Route::get('oauth', [SocialiteController::class, 'authenticate']);
Route::post('/facebook/page-information', [FacebookPageController::class, 'getPageInformation']);

// Route::group([
//     'middleware' => 'api',
//     'prefix' => 'v1'
// ], function () {
//     // Route::post('/login', [AuthController::class, 'login']);
//     // Route::post('/register', [AuthController::class, 'register']);
//     // Route::get('/data',[ProfileController::class,'getData']);
// });

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::group([
    // 'middleware' => 'outbound',
    'middleware' => 'jwt.verify',
    'prefix' => 'outbound/v1'
], function () {
    Route::get('/outfiles/{folder}', [FileController::class, 'getImagesInFolder']);
});

Route::post('connexion', [ApiController::class, 'authenticate']);
Route::get('chrone', [NotificationController::class, 'sendChrone']);
Route::get('chroneNotif', [NotificationController::class, 'sendNotif']);
Route::get('verify_reception', [NotificationController::class, 'verify_reception']);
Route::get('relancer', [NotificationController::class, 'relancer']);


Route::post('register', [ApiController::class, 'registerUser']);
Route::post('otp_verify', [ApiController::class, 'otp_verify']);
Route::post('createpwd', [ApiController::class, 'createpwd_verify']);
Route::post('recovery/password', [ApiController::class, 'recoveryPassword']);

Route::post('init_token', [ApiController::class, 'init_token']);


Route::post('paiement/callback', [PaymentConroller::class, 'receiveCallback']);

Route::group(['middleware' => ['jwt.verify']], function () {

    Route::group(['middleware' => ['subuser']], function () {
        Route::get('/sub-users', [UserController::class, 'index']);       // Liste des users secondaires
        Route::post('/sub-users', [UserController::class, 'store']);     // Créer un user secondaire
        Route::get('/sub-users/{user}', [UserController::class, 'show']); // Détail d’un user
        Route::put('/sub-users/{user}', [UserController::class, 'update']); // Mise à jour
        Route::delete('/sub-users/{user}', [UserController::class, 'destroy']); // Suppression


        Route::get('/sub-user/solde', [UserController::class, 'solde']);
        Route::prefix('sub-user/status')->group(function () {
            Route::get('/whatsapp', [UserController::class, 'sub_userStatusWhatsApp']);
            Route::get('/email', [UserController::class, 'sub_userStatusEmail']);
            Route::get('/sms', [UserController::class, 'sub_userStatusSms']);
            Route::get('/services', [UserController::class, 'sub_userStatusServices']);
            Route::get('/subscription', [UserController::class, 'sub_userStatusSubscription']);
        });

        Route::post('/sub-user/canal/messages', [UserController::class, 'getMessagesByCanal']);
        Route::get('/sub-user/all/messages', [UserController::class, 'getMessages']);
    });


    Route::post('recep/outfiles/{message_id}', [ExportController::class, 'exportNotificationsToExcel']); // new

    Route::prefix('service')->group(function () {
        Route::post('send', [NotificationController::class, 'gateway']);
        Route::post('message', [NotificationController::class, 'custumGateway']);

        Route::prefix('group')->group(function () {
            Route::get('mygroups', [NotificationController::class, 'getAllGroupInfo']);
            Route::post('send', [NotificationController::class, 'multiSendAtGroups']);
        });
        Route::get('search/ref', [MessagesController::class, 'getMessagesByReferenceId']);
    });

    // Route::get('export', [ExportController::class, 'storeExcel']);
    Route::post('import/contact', [ContactController::class, 'getContacts']);
    Route::post('verify', [NotificationController::class, 'verifySolde']);
    Route::prefix('password')->group(function () {
        Route::post('checkpass', [ApiController::class, 'checkPassword']);
        Route::put('upadate_pass', [ApiController::class, 'upadatePass']);
    });
    Route::put('reset', [AbonnementController::class, 'resetService']); // old
    Route::post('status/callback', [PaymentConroller::class, 'verifyPayment']);
    Route::get('logout', [ApiController::class, 'logout']);
    Route::get('authuser', [ApiController::class, 'getUserAuth']);
    Route::post('recipients', [ClientMessagesController::class, 'getRecipients']);
    Route::get('tarification/services', [TarificationController::class, 'getPricingClient']);

    Route::group(['middleware' => ['admin']], function () {

        Route::prefix('admin')->group(function () {
            Route::post('register/agent', [ApiController::class, 'registerAgent']);

            Route::prefix('assitance')->group(function () {
                Route::post('/creatwa-agent', [AssistanceController::class, 'addAgent']);
                Route::get('/agents', [AssistanceController::class, 'getAgentList']);
            });
            Route::prefix('tarification')->group(function () {
                Route::post('/', [TarificationController::class, 'addTarification']);
                Route::post('/asign', [TarificationController::class, 'asignTarification']);
                Route::get('/', [TarificationController::class, 'getTarification']);
                Route::put('/', [TarificationController::class, 'updateTarification']);

                Route::get('/sms/{id}', [TarificationController::class, 'getSmsPrice']);
                Route::get('/email/{id}', [TarificationController::class, 'getEmailPrice']);
                Route::get('/whatsapp/{id}', [TarificationController::class, 'getWhatsappPrice']);

                Route::put('/sms', [TarificationController::class, 'setSmsPrice']);
                Route::put('/email', [TarificationController::class, 'setEmailPrice']);
                Route::put('/whatsapp', [TarificationController::class, 'setWhatsappPrice']);
            });

            Route::prefix('messages')->group(function () {
                Route::get('/', [MessagesController::class, 'getAllMessages']);
                Route::post('/canal', [MessagesController::class, 'getMessagesByCanal']);
                Route::post('/period', [MessagesController::class, 'getMessagesByPeriod']);
                Route::post('/keyword', [MessagesController::class, 'getMessagesByKeywordCanal']);
            });

            Route::prefix('activation')->group(function () {
                Route::prefix('maintenance')->group(function () {
                    Route::prefix('status')->group(function () {
                        Route::get('sms', [AdminAbonnementController::class, 'getMaintenanceSms']);
                        Route::get('email', [AdminAbonnementController::class, 'getMaintenanceEmail']);
                        Route::get('whatsapp', [AdminAbonnementController::class, 'getMaintenanceWhatsapp']);
                    });
                    Route::prefix('disable')->group(function () {
                        Route::put('sms', [AdminAbonnementController::class, 'disableSms']);
                        Route::put('email', [AdminAbonnementController::class, 'disableEmail']);
                        Route::put('whatsapp', [AdminAbonnementController::class, 'disableWhatsapp']);
                    });
                    Route::prefix('enable')->group(function () {
                        Route::put('sms', [AdminAbonnementController::class, 'enableSms']);
                        Route::put('email', [AdminAbonnementController::class, 'enableEmail']);
                        Route::put('whatsapp', [AdminAbonnementController::class, 'enableWhatsapp']);
                    });
                });
                // mail accept and reject
                Route::prefix('service')->group(function () {
                    Route::get('/request', [AdminAbonnementController::class, 'listRequest']);
                    Route::put('accept/sms', [AdminAbonnementController::class, 'acceptSms']);
                    Route::put('accept/email', [AdminAbonnementController::class, 'acceptEmail']);
                    Route::put('accept/whatsapp', [AdminAbonnementController::class, 'acceptWhatsapp']);
                    Route::put('reject/sms', [AdminAbonnementController::class, 'rejectSms']);
                    Route::put('reject/email', [AdminAbonnementController::class, 'rejectEmail']);
                    Route::put('reject/whatsapp', [AdminAbonnementController::class, 'rejectWhatsapp']);
                    Route::delete('service', [AdminAbonnementController::class, 'deleteService']);


                    // new
                    Route::put('update', [AdminAbonnementController::class, 'updateServiceStatus']);
                    Route::put('accept/{service}/{userId}', [AdminAbonnementController::class, 'acceptService']);
                    Route::put('reject/{service}/{userId}', [AdminAbonnementController::class, 'rejectService']);
                    Route::put('reset/{service}/{userId}', [AdminAbonnementController::class, 'resetService']);

                    Route::get('user/{userId}/active-services', [AdminAbonnementController::class, 'getActiveServices']);

                });
            });

            Route::prefix('user')->group(function () {
                Route::post('statut', [ApiController::class, 'getUserStatut']);
                Route::put('activate', [ApiController::class, 'activateUser']);
                Route::put('disable', [ApiController::class, 'disableUser']);
                Route::put('delete', [ApiController::class, 'deleteUser']);
                Route::put('credit-account', [AbonnementController::class, 'creditAccount']);
                Route::get('clients', [ApiController::class, 'getClients']);
                Route::get('agents', [ApiController::class, 'getAgents'])->middleware('admin.super');
                Route::put('decredit-account', [AbonnementController::class, 'decreditAccount'])->middleware('admin.super');
            });

            Route::prefix('params')->group(function () {

                Route::prefix('client')->group(function () {
                        Route::get('cash/{id}', [Abonnement::class, 'isAdminGetCashClient_v1']); // new
                        Route::post('cash/{id}', [Abonnement::class, 'isAdminGetCashClientAppro_v1']); // new
                    Route::prefix('wa')->group(function () {
                        Route::get('device/{id}', [Abonnement::class, 'isAdminGetWaDeviceClient']);
                        Route::put('device/{id}', [Abonnement::class, 'isAdminSetWaDeviceClient']);
                        Route::get('cash/{id}', [Abonnement::class, 'isAdminGetCashClient']); // old
                        Route::put('cash/{id}', [Abonnement::class, 'isAdminSetCashClient']); // old
                    });
                });

                Route::get('location', [Param::class, 'getApp']);
                Route::put('location', [Param::class, 'setApp']);
                Route::get('address', [Param::class, 'apiUrl']);
                Route::put('address', [Param::class, 'setApiUrl']);

                Route::get('mineprice', [Param::class, 'getMinPrice']);
                Route::put('mineprice', [Param::class, 'setMinPrice']);
                Route::get('wassenger', [Param::class, 'getTokenWhatsapp']);
                Route::put('wassenger', [Param::class, 'setTokenWhatsapp']);
                Route::get('pvit', [Param::class, 'getTokenPvit']);
                Route::put('pvit', [Param::class, 'setTokenPvit']);
                Route::get('email/awt', [Param::class, 'getEmailAwt']);
                Route::put('email/awt', [Param::class, 'setEmailAwt']);
                Route::get('email/admin', [Param::class, 'getAdminEmail']);
                Route::put('email/admin', [Param::class, 'setAdminEmail']);

                Route::get('sms/sender', [Param::class, 'getSmsSender']);
                Route::put('sms/sender', [Param::class, 'setSmsSender']);

                Route::get('agent', [Param::class, 'getAgent']);
                Route::put('agent', [Param::class, 'setAgent']);
                Route::get('marchand/am', [Param::class, 'getMarchandAirtel']);
                Route::put('marchand/am', [Param::class, 'setMarchandAirtel']);
                Route::get('marchand/mc', [Param::class, 'getMarchandMoov']);
                Route::put('marchand/mc', [Param::class, 'setMarchandMoov']);
                Route::get('urlfront', [Param::class, 'getBaseurlFront']);
                Route::put('urlfront', [Param::class, 'setBaseurlFront']);
                Route::get('wid-device', [Param::class, 'getWassengerDevice']);
                Route::put('wid-device', [Param::class, 'setWassengerDevice']);
            });
            // Route::get('demandes', [AbonnementController::class, 'listDemande']); // old

            Route::get('demandes/{service}', [AbonnementController::class, 'getDemandesByService']); // new
            Route::get('demandes/{service}/{status}', [AbonnementController::class, 'getDemandesByService_v2']); // new

            Route::get('demandes/reject', [AbonnementController::class, 'listDemandeReject']);
        });
    });

    Route::prefix('/wagroup')->group(function () {
        Route::get('mywadevice', [Abonnement::class, 'getCurrentWassengerDevice']); //check device wa

        Route::get('getAssistance', [WaGroupController::class, 'getAssistance']);
        Route::post('crgroup', [WaGroupController::class, 'createGroup']);
        Route::get('allgroups', [WaGroupController::class, 'getAllGroups']);
        Route::get('allmembers', [WaGroupController::class, 'allmembers']);
        Route::get('getbywid', [WaGroupController::class, 'getGroupByWid']);
        Route::get('refreshgroups', [WaGroupController::class, 'storeAllGroups']);
        Route::put('changerole', [WaGroupController::class, 'switchStatus']);
        Route::delete('revoke', [WaGroupController::class, 'revokeMembers']);
        Route::get('wagroups', [WaGroupController::class, 'getStore']);
        Route::post('sendmessage', [WaGroupController::class, 'sendAtGroups']); //send message in a group

        Route::post('send', [NotificationController::class, 'multiSendAtGroups']); //bulk

        Route::get('searchbyname', [WaGroupController::class, 'getStoreByName']);
        Route::post('addxmembers', [WaGroupController::class, 'addMembers']);
    });

    Route::prefix('/messages')->group(function () {
        Route::post('/', [NotificationController::class, 'verifySolde'])->middleware('admin');
        Route::get('/', [ClientMessagesController::class, 'getAllMessagesByUser']);
        Route::post('/canal', [ClientMessagesController::class, 'getMessagesByCanalAndUser']);
        Route::post('/period', [ClientMessagesController::class, 'getMessagesByPeriodAndUser']);
        Route::post('/keyword', [ClientMessagesController::class, 'getMessagesByKeywordCanalAndUser']);
    });

    Route::prefix('service')->group(function () {
        Route::prefix('status')->group(function () {
            Route::get('/sms', [AbonnementController::class, 'smsStatus']);
            Route::get('/email', [AbonnementController::class, 'emailStatus']);
            Route::get('/whatsapp', [AbonnementController::class, 'whatsappStatus']);
        });
        Route::prefix('value')->group(function () {
            Route::prefix('sms')->group(function () {
                Route::get('/', [AbonnementController::class, 'getEnterpriseName']);
                Route::get('/code', [AbonnementController::class, 'getCampagnKey']);
            });
            Route::get('/email', [AbonnementController::class, 'getEmail']);
            Route::get('/whatsapp', [AbonnementController::class, 'getWhatsappNumber']);

            Route::prefix('template')->group(function () {
                Route::get('color', [AbonnementController::class, 'getThemeColor']);
                Route::put('color', [AbonnementController::class, 'setThemeColor']);
            });
        });


        Route::get('region', [AbonnementController::class, 'getInternational']);
        Route::put('region', [AbonnementController::class, 'setInternational']);

        Route::prefix('template')->group(function () {
            Route::get('origin/theme', [AbonnementController::class, 'getIsCustomTemplate']);
            Route::put('origin/theme', [AbonnementController::class, 'setIsCustomTemplate']);

            Route::post('upload', [ClientTemplateController::class, 'uploadTemplate']);
            // Route::get('client', [ClientTemplateController::class, 'getTemplateStatus']);
            Route::get('client', [ClientTemplateController::class, 'getClientTemplateStatus']);
            // Route::get('{id}/template-status', [ClientTemplateController::class, 'getTemplateStatus']);
        });
    });

    Route::prefix('abonnement')->group(function () {
        Route::get('status', [AbonnementController::class, 'status']);
        Route::get('solde', [AbonnementController::class, 'solde']);

        Route::get('transaction/debit', [AbonnementController::class, 'getDebit']);
        Route::get('transaction/credit', [AbonnementController::class, 'getCredit']);
        Route::post('logo', [AbonnementController::class, 'saveLogo']);
        // Route::put('logo', [AbonnementController::class, 'setLogo']);
        Route::get('logo', [AbonnementController::class, 'getLogo']);
        Route::put('logo', [FileController::class, 'setLogo']);

        Route::prefix('info')->group(function () {
            Route::get('contact', [AbonnementController::class, 'getContact']);
            Route::put('contact', [AbonnementController::class, 'setContact']);
            Route::get('location', [AbonnementController::class, 'getLocation']);
            Route::put('location', [AbonnementController::class, 'setLocation']);
            Route::get('ville', [AbonnementController::class, 'getCity']);
            Route::put('ville', [AbonnementController::class, 'setCity']);
        });
    });

    Route::prefix('slug')->group(function () {
        Route::get('generate', [ApiController::class, 'getSlugGenerate']);
        Route::put('generate', [ApiController::class, 'setSlugGenerate']);
    });

    Route::prefix('wa')->group(function () {

        Route::get('dw', [WaGroupController::class, 'out']);

        Route::get('device', [Abonnement::class, 'getWaDeviceClient']);
        Route::put('device', [Abonnement::class, 'setWaDeviceClient']);
    });

    Route::group(['middleware' => ['status.client']], function () {
        Route::post('paiement', [PaymentConroller::class, 'initializePayment']);
        Route::prefix('activation/service')->group(function () {
            Route::prefix('sms')->group(function () {
                Route::post('/', [AbonnementController::class, 'sendEnterpriseName']);
                Route::post('code', [AbonnementController::class, 'sendCampagnKey']);
            });
            Route::post('email', [AbonnementController::class, 'sendEmailAddress']);
            Route::post('whatsapp', [AbonnementController::class, 'sendWhatsappNumber']);
        });

        // new
        // Route::post('service/request/{service}', [AbonnementController::class, 'requestService']);
    });

    // Route::post('sendMessageSimple', [MessagesController::class, 'sendMessageSimple']);
    // Route::post('mail_all_busi', [NotificationController::class, 'mail_all_busi']);
    // Route::post('create_msg_masse', [NotificationController::class, 'create_msg_masse']);

});
