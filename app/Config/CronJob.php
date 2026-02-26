<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use Daycry\CronJob\Scheduler;
use Daycry\CronJob\Loggers\Database as DatabaseLogger;
use Daycry\CronJob\Loggers\File as FileLogger;

class CronJob extends \Daycry\CronJob\Config\CronJob
{
    /**
     * Set true if you want save logs
     */
    public bool $logPerformance = true;

    /*
    |--------------------------------------------------------------------------
    | Log Saving Method
    |--------------------------------------------------------------------------
    |
    | Set to specify the REST API requires to be logged in
    |
    | 'file'   Save in files
    | 'database'  Save in database
    |
    */
    public string $logSavingMethod = 'database';

    public array $logSavingMethodClassMap = [
        'file' => FileLogger::class,
        'database' => DatabaseLogger::class
    ];

    /**
     * Directory
     */
    public string $filePath = WRITEPATH . 'cronJob/';

    /**
     * File Name in folder jobs structure
     */
    public string $fileName = 'jobs';

    /**
     * --------------------------------------------------------------------------
     * Maximum performance logs
     * --------------------------------------------------------------------------
     *
     * The maximum number of logs that should be saved per Job.
     * Lower numbers reduced the amount of database required to
     * store the logs.
     *
     * If you write 0 it is unlimited
     */
    public int $maxLogsPerJob = 3;

    /*
    |--------------------------------------------------------------------------
    | Database Group
    |--------------------------------------------------------------------------
    |
    | Connect to a database group for logging, etc.
    |
    */
    public ?string $databaseGroup = 'default';

    /*
    |--------------------------------------------------------------------------
    | Cronjob Table Name
    |--------------------------------------------------------------------------
    |
    | The table name in your database that stores cronjobs
    |
    */
    public string $tableName = 'sys_cronjob';

    /*
    |--------------------------------------------------------------------------
    | Cronjob Notification
    |--------------------------------------------------------------------------
    |
    | Notification of each task
    |
    */
    public bool $notification = false;
    public string $from = 'your@example.com';
    public string $fromName = 'CronJob';
    public string $to = 'your@example.com';
    public string $toName = 'User';

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | Notification of each task
    |
    */
    public array $views = [
        'login'                       => '\Daycry\CronJob\Views\login',
        'dashboard'                   => '\Daycry\CronJob\Views\dashboard',
        'layout'                      => '\Daycry\CronJob\Views\layout',
        'logs'                        => '\Daycry\CronJob\Views\logs'
    ];

    /*
    |--------------------------------------------------------------------------
    | Dashboard login
    |--------------------------------------------------------------------------
    */
    public bool $enableDashboard = false;
    public string $username = 'admin';
    public string $password = 'admin';

    /*
    |--------------------------------------------------------------------------
    | Cronjobs
    |--------------------------------------------------------------------------
    |
    | Register any tasks within this method for the application.
    | Called by the TaskRunner.
    |
    | @param Scheduler $schedule
    */
    public function init(Scheduler $schedule)
    {
        // $schedule->command('foo:bar')->everyMinute();

        // $schedule->shell('cp foo bar')->daily( '11:00 pm' );

        // $schedule->call( function() { do something.... } )->everyMonday()->named( 'foo' )

        // $schedule->url(env("app.baseURL") . "cron-not-approved")->named("CronNotApproved")->daily();
        $schedule->url(env("app.baseURL") . "cron-not-approved")->named("CronNotApproved")->daily("11:55 pm");
        // $schedule->url(env("app.baseURL") . "cron-update-employee")->named("CronUpdateEmployee")->daily("11:50 pm");
        $schedule->url(env("app.baseURL") . "cron-approved-realization")->named("CronApprovedRealization")->daily("11:59 pm");
        $schedule->url(env("app.baseURL") . "cron-absent-alert")->named("CronAbsentAlert")->daily("08:20 am");
        $schedule->url(env("app.baseURL") . "cron-send-absent-summary")->named("CronAbsentSummary")->daily("12:01");
        $schedule->url(env("app.baseURL") . "cron-delete-attendance-summary")->named("CronDeleteAttSummary")->sundays("11:40 pm");

        // Proxy
        $schedule->url(env("app.baseURL") . "cron-proxy-reguler")->named("CronProxyReguler")->daily("10:00 am");
        $schedule->url(env("app.baseURL") . "cron-return-proxy")->named("CronReturnProxy")->daily("11:50 pm");

        // Emp Delegation
        $schedule->url(env("app.baseURL") . "cron-delegation-absent")->named("CronDelegationAbsent")->daily("10:05 am");
        $schedule->url(env("app.baseURL") . "cron-delegation-transfer")->named("CronDelegationTransfer")->daily("11:45 pm");

        // Broadcast
        $schedule->url(env("app.baseURL") . "/cron-update-broadcast")->named("CronUpdateBroadcast")->everyMinute(1);

        // Promosi/Demosi
        $schedule->url(env("app.baseURL") . "/cron-update-promodemo")->named("CronUpdatePromoDemo")->daily("00:30");
    }
}