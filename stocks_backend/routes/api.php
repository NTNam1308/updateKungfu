<?php

use Illuminate\Http\Request;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'namespace' => 'Auth',
    'middleware' => 'api',
    'prefix' => 'password'
], function () {
    Route::post('request', 'PasswordResetController@create');
    Route::post('reset', 'PasswordResetController@reset');
});
Route::get('mobile/version', 'Mobile\AppVersionController@index');

Route::group(['middleware' => 'api'], function ($router) {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('/send-contact-feedback', 'ContactController@saveContactFeedBack');
    Route::post('register', 'AuthController@register');
    Route::get('plans', 'AuthController@plans');
    Route::post('verify', 'AuthController@verify');
    Route::get('updateUser', 'UsersController@scheduleUser');
    Route::get('check-token', 'UsersController@checkToken');
    
    Route::get('check-maintenance', 'UsersController@checkMaintenance');
    Route::post('update-fcm-token', 'UsersController@updateFcmToken');
    Route::get('get-notify-finance', 'UsersController@getNotify');
    Route::get('user-is-read', 'UsersController@userCallBackIsRead');
    Route::get('user-is-new', 'UsersController@userCallBackIsNew');

    //api trading chart
    Route::prefix('trading')->group(function () {
        Route::get('/history', 'TradeController@getHistory');
        Route::get('/config', 'TradeController@getConfig');
        Route::get('/symbols', 'TradeController@getSymbol');
        Route::get('/search', 'TradeController@getSearch');
        Route::get('/timescale_marks', 'TradeController@getTimescaleMarks');
        Route::get('/indexinfo', 'TradeController@getIndexInfo');
        Route::get('/dailyprice', 'TradeController@getDailyPrice');
        Route::get('/topstocks', 'TradeController@getTopStocks');
    });
    Route::prefix('guest')->group(function () {
        Route::prefix('news')->group(function () {
            Route::get('/get-category-news', 'DashBoardController@getAllCategoryNews');
            Route::get('/get-all-news-category', 'DashBoardController@getAllDataNewsCategory');
            Route::get('/get-list-news-from-category', 'DashBoardController@getListNewsFromCategory');
            Route::get('/get-content-news', 'DashBoardController@getContentNews');
        });
    });
    Route::group(['middleware' => ['auth.jwt']], function () {
        Route::get('menu', 'MenuController@index');
        Route::prefix('mobile')->group(function () {
            Route::post('version', 'Mobile\AppVersionController@store');
            Route::prefix('stocks')->group(function () {
                Route::get('/get-data-table', 'Mobile\MobileStockDataRawController@getDataTableRaw')->middleware(['auth.jwt', 'multidevice']);
                Route::get('/compare', 'Mobile\MobileCompareStockController@getData')->middleware(['auth.jwt', 'multidevice']);
                Route::get('/financial-ratios', 'Mobile\MobileFinancialRatiosController@getData')->middleware(['auth.jwt', 'multidevice']);
                Route::get('/common-info', 'Mobile\MobileCommonInfoController@getCommonInfo')->middleware(['auth.jwt', 'multidevice']);
            });
        });

        //CTP TODO, PUT IN ADMIN SECTION IMPORTANT
        Route::prefix('stocks')->group(function () {
            Route::get('/', 'StocksController@index');
            Route::get('/check-quarter', 'StocksController@checkQuarterAll');
            Route::get('/filter-company-group', 'StocksController@filterCompanyGroup')->middleware(['admin']);
            Route::get('/financial-ratios', 'FinancialRatiosController@getData');
            Route::get('/common-info', 'CommonInfoController@getCommonInfo')->middleware(['auth.jwt', 'multidevice']);
            Route::get('/search', 'StocksController@searchInfo');
            Route::get('/get-all-mack', 'StocksController@getAllMack');
            Route::get('/compare', 'CompareController@getData');
            Route::group(['prefix' => 'canslim-fourm', 'middleware' => 'multidevice'], function () {
                Route::get('/all', 'CanslimFourmController@canslimFourmAll');
                Route::get('/by-mack', 'CanslimFourmController@canslimFourmByMack');
                Route::get('/count-stock', 'CanslimFourmController@countStock');
                Route::get('/count-stock-by-mack', 'CanslimFourmController@countStockByMack');
                Route::get('/get-data-compare-all', 'CanslimFourmController@getDataCompareAll');
                Route::prefix('save_filter_group')->group(function () {
                    Route::get('/get-all', 'CanslimFourmController@index');
                    Route::post('/store', 'CanslimFourmController@store');
                    Route::put('/update', 'CanslimFourmController@update');
                    Route::delete('/{id}', 'CanslimFourmController@destroy');
                });
            });
            Route::prefix('save-setting')->group(function () {
                Route::get('/get-all', 'SaveSettingController@getAll');
                Route::post('/backup-old-data', 'SaveSettingController@backupOldData');
                Route::post('/delete', 'SaveSettingController@destroy');
                Route::put('/update', 'SaveSettingController@update');
            });
            Route::prefix('compare')->group(function () {
                Route::get('/', 'CompareController@getData');
            });
            Route::prefix('market_pulse')->group(function () {
                Route::get('/getContent', 'MarketPulseController@getContent');
                Route::post('/updateContent', 'MarketPulseController@updateContent')->middleware(['admin']);
            });
            Route::prefix('orderboard')->group(function () {
                Route::get('/', 'StocksController@getDataOrderBoard');
            });
            Route::prefix('margin_safety')->group(function () {
                Route::get('/', 'MarginSafetyController@getData');
                Route::get('/get-all', 'MarginSafetyController@index');
                Route::post('/store', 'MarginSafetyController@store');
                Route::put('/update', 'MarginSafetyController@update');
                Route::delete('/{id}', 'MarginSafetyController@destroy');
            });
            Route::prefix('ta_watchlist')->group(function () {
                Route::get('/get-list-category', 'TAWatchlistController@getListCategory');
                Route::get('/get-list-mack-by-category', 'TAWatchlistController@getListMackByCategory');
            });
            Route::prefix('chart')->group(function () {
                Route::get('/nonbank', 'ChartController@getAllChartNonbank');
                Route::get('/bank', 'ChartController@bankChart');
                Route::get('/bank/scatter', 'ChartController@bankChartScatter');
                Route::get('/fourm-canslim-point-chart', 'ChartController@fourmCanslimPointChart');
                Route::get('/fourm-canslim-point-chart-by-time', 'ChartController@fourmCanslimPointChartByTime');
            });
            Route::prefix('trading_log')->group(function () {
                Route::get('/getCurrentPrice', 'TradingLogController@getCurrentPrice');
                Route::get('/getAll', 'TradingLogController@index');
                Route::post('/store', 'TradingLogController@store');
                Route::put('/update', 'TradingLogController@update');
                Route::delete('/{id}', 'TradingLogController@destroy');
            });
            Route::prefix('category')->group(function () {
                Route::get('/get-all', 'CategoryController@index');
                Route::post('/store', 'CategoryController@store');
                Route::put('/update', 'CategoryController@update');
                Route::delete('/{id}', 'CategoryController@destroy');
            });
            Route::prefix('stock_suggestion')->group(function () {
                Route::get('/get-all', 'StockSuggestionController@index');
            });
            Route::prefix('profit_loss')->group(function () {
                Route::get('/get-all', 'ProfitLossController@getAllItemTrading');
            });
            Route::prefix('document')->group(function () {
                Route::get('/get-document', 'DocumentController@getListDocument');
                Route::get('/get-year', 'DocumentController@getListYear');
                Route::get('/download-document/{id}', 'DocumentController@downloadDocument');
            });
            Route::prefix('report_analysis')->group(function () {
                Route::get('/get-report', 'ReportAnalysisController@getListReport');
                Route::get('/download-report/{id}', 'ReportAnalysisController@downloadReport');
            });
            Route::prefix('news')->group(function () {
                Route::get('/get-count-page-news', 'NewsController@getCountPage');
                Route::get('/get-list-news', 'NewsController@getListNews');
                Route::get('/get-list-news-related', 'NewsController@getListNewsRelated')->middleware(['auth.jwt', 'multidevice']);
                Route::get('/get-content-news', 'NewsController@getContentNews');
            });
            Route::prefix('dashboard')->group(function () {
                Route::get('/get-data-eod', 'DashBoardController@getDataEOD');
                Route::get('/get-data-eod-by-mack', 'DashBoardController@getDataEODByMack');
                Route::get('/get-data-exchange-trade', 'DashBoardController@getDataTradeExchange');
                Route::get('/get-data-itd-index', 'DashBoardController@getDataIntradayIndex');
                Route::get('/get-list-mack-market-volatility', 'DashBoardController@getListMackMarketVolatility');
                Route::get('/get-top-buy-sell', 'DashBoardController@getListTopBuySell');
                Route::get('/get-top-up-down-percent', 'DashBoardController@getListTopUpDownPercent');
                Route::get('/get-all-data-dashboard-interval', 'DashBoardController@getAllDataDashboard');
                Route::get('/get-content-market-pulse', 'DashBoardController@getContentMarketPulse');
                Route::prefix('news')->group(function () {
                    Route::get('/get-category-news', 'DashBoardController@getAllCategoryNews');
                    Route::get('/get-all-news-category', 'DashBoardController@getAllDataNewsCategory');
                    Route::get('/get-list-news-from-category', 'DashBoardController@getListNewsFromCategory');
                    Route::get('/get-list-news-from-categorys', 'DashBoardController@getListNewsFromCategorys');
                    Route::get('/get-content-news', 'DashBoardController@getContentNews');
                });
            });
            Route::prefix('top400stock')->group(function () {
                Route::get('/index', 'Top400StockController@getDataIndex');
                Route::get('/chart', 'Top400StockController@getDataChart');
                Route::get('/generate', 'Top400StockController@generateDataToDB');
            });
            Route::get('/generate', 'GenerateController@generate');
        });

        // Watchlist 
        Route::get('/watchlist/get-all-delete', 'WatchlistController@indexDelete');
        Route::delete('/watchlist/deleteWatchlist/{id}', 'WatchlistController@deleteWatchlist');
        Route::resource('watchlist', 'WatchlistController');
        Route::get('/mack-by-my-watchlist/{id}', 'WatchlistController@macksByMyWatchlist');
        Route::post('/watchlist/add-many-item', 'WatchlistController@addManyRow');
        Route::post('/watchlist-update-index', 'WatchlistController@updateIndex');

        Route::resource('my-watchlist', 'MyWatchlistController');

        Route::resource('user-mack-note', 'UserMackNoteController');

        Route::resource('notes', 'NotesController');

        Route::resource('resource/{table}/resource', 'ResourceController');

        Route::post('users/{id}/passwd',   'UsersController@passwd')->name('users.passwd');

        Route::post('users/{id}/chavatar',   'UsersController@changeAvatar')->name('users.chavatar');
        Route::post('users/update-info', 'UsersController@updateInfo');
        Route::post('preview-image',   'MediaController@previewImage')->name('preview-imager');

        Route::group(['middleware' => 'moderator'], function ($router) {
            Route::resource('users', 'UsersController')->except(['create', 'store']);
            Route::resource('kungfu-news',  'KungfuNewsController');   //create KungfuNew (resource)
            Route::resource('category-news',  'CategoryNewsController');   //create CategoryNews (resource)
            Route::get('users/{id}/reference-log','UserLogController@getAllReferenceLog');

            Route::prefix('contact-feedback')->group(function(){
                Route::get('/', 'ContactController@index');
                // Route::put('/call', 'ContactController@called');
                Route::get('/{id}/edit', 'ContactController@edit');
                Route::get('/{id}/show', 'ContactController@show');
                Route::post('/update', 'ContactController@update');
            });
	    
            Route::prefix('mobile-version')->group(function () {
                Route::get('', 'Mobile\AppVersionController@getAllVersion');
                Route::post('/store','Mobile\AppVersionController@store');
                Route::delete('/{id}','Mobile\AppVersionController@destroy');
                Route::post('/update','Mobile\AppVersionController@update');
            });
            
        });

        Route::group(['middleware' => 'admin'], function ($router) {
            Route::prefix('stocks')->group(function () {
                Route::prefix('stock_suggestion')->group(function () {
                    Route::post('/store', 'StockSuggestionController@store');
                    Route::put('/update', 'StockSuggestionController@update');
                    Route::post('/close-suggestion', 'StockSuggestionController@closeSuggestion');
                    Route::delete('/{id}', 'StockSuggestionController@destroy');
                });
            });

            Route::resource('mail',        'MailController');
            Route::get('prepareSend/{id}', 'MailController@prepareSend')->name('prepareSend');
            Route::post('mailSend/{id}',   'MailController@send')->name('mailSend');

            Route::resource('bread',  'BreadController');   //create BREAD (resource)

            Route::prefix('menu/menu')->group(function () {
                Route::get('/',         'MenuEditController@index')->name('menu.menu.index');
                Route::get('/create',   'MenuEditController@create')->name('menu.menu.create');
                Route::post('/store',   'MenuEditController@store')->name('menu.menu.store');
                Route::get('/edit',     'MenuEditController@edit')->name('menu.menu.edit');
                Route::post('/update',  'MenuEditController@update')->name('menu.menu.update');
                Route::get('/delete',   'MenuEditController@delete')->name('menu.menu.delete');
            });
            Route::prefix('menu/element')->group(function () {
                Route::get('/',             'MenuElementController@index')->name('menu.index');
                Route::get('/move-up',      'MenuElementController@moveUp')->name('menu.up');
                Route::get('/move-down',    'MenuElementController@moveDown')->name('menu.down');
                Route::get('/create',       'MenuElementController@create')->name('menu.create');
                Route::post('/store',       'MenuElementController@store')->name('menu.store');
                Route::get('/get-parents',  'MenuElementController@getParents');
                Route::get('/edit',         'MenuElementController@edit')->name('menu.edit');
                Route::post('/update',      'MenuElementController@update')->name('menu.update');
                Route::get('/show',         'MenuElementController@show')->name('menu.show');
                Route::get('/delete',       'MenuElementController@delete')->name('menu.delete');
            });
            Route::prefix('media')->group(function ($router) {
                Route::get('/',                 'MediaController@index')->name('media.folder.index');
                Route::get('/folder/store',     'MediaController@folderAdd')->name('media.folder.add');
                Route::post('/folder/update',   'MediaController@folderUpdate')->name('media.folder.update');
                Route::get('/folder',           'MediaController@folder')->name('media.folder');
                Route::post('/folder/move',     'MediaController@folderMove')->name('media.folder.move');
                Route::post('/folder/delete',   'MediaController@folderDelete')->name('media.folder.delete');;

                Route::post('/file/store',      'MediaController@fileAdd')->name('media.file.add');
                Route::get('/file',             'MediaController@file');
                Route::post('/file/delete',     'MediaController@fileDelete')->name('media.file.delete');
                Route::post('/file/update',     'MediaController@fileUpdate')->name('media.file.update');
                Route::post('/file/move',       'MediaController@fileMove')->name('media.file.move');
                Route::post('/file/cropp',      'MediaController@cropp');
                Route::get('/file/copy',        'MediaController@fileCopy')->name('media.file.copy');

                Route::get('/file/download',    'MediaController@fileDownload');
            });

            Route::resource('roles',        'RolesController');
            Route::get('/roles/move/move-up',      'RolesController@moveUp')->name('roles.up');
            Route::get('/roles/move/move-down',    'RolesController@moveDown')->name('roles.down');
        });

        Route::prefix('user-logs')->group(function(){
            Route::get('/reference',  'UserLogController@getReferenceLog')->name('user_log.getReferenceLog');
            Route::get('/alert-reference',  'UserLogController@alertRefernceLog')->name('user_log.getReferenceLog');
        });
    });

    //api trading chart
});
