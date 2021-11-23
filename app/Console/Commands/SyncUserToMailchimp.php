<?php

namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use App\Models\Personel;
use App\Models\StatusHistory;
use Illuminate\Console\Command;
use MailchimpMarketing\ApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SyncUserToMailchimp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:user-to-mailchimp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync user DTBS to Mailchimp';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try{
            $mailchimp = new ApiClient();

            $mailchimp->setConfig([
                'apiKey' => config('app.mailchimp_api_key'),
                'server' => config('app.mailchimp_server_prefix'),
            ]);

            $listId = config('app.mailchimp_list_id');

            // $response = $mailchimp->lists->getListInterestCategories($listId);
            // print_r($response);

            // $response = $mailchimp->lists->listInterestCategoryInterests(
            //     $listId,
            //     // TO DO
            // );
            // print_r($response);

            $pastoralGroupId = config('app.mailchimp_pastoral_group_id');
            
            $subQuery = StatusHistory::leftJoin('status_histories as temps', function ($leftJoin) {
                $leftJoin->on('temps.personel_id', 'status_histories.personel_id')
                    ->where(function ($innerQuery) {
                        $innerQuery->whereRaw('status_histories.date_status < temps.date_status')
                            ->orWhere(function ($deepestQuery) {
                                $deepestQuery->whereRaw('status_histories.date_status = temps.date_status')
                                    ->where('status_histories.id', '<', 'temps.id');
                            });
                    });
            })->whereNull('temps.id')
                ->join('account_status', 'account_status.id', 'status_histories.status_histories_id')
                ->select('status_histories.personel_id', 'account_status.acc_status');
            $chunkPersonels = Personel::leftJoinSub($subQuery, 'status_histories', function ($leftJoinSub) {
                $leftJoinSub->on('personels.id', 'status_histories.personel_id');
            })
            ->select('first_name', 'last_name', 'email', 'date_of_birth', 'language', DB::raw('IFNULL(status_histories.acc_status, "-") as acc_status'))
            ->cursor()->chunk(400);

            $emails = [];
            $offset = 0;
            $statuses = ["unsubscribed", "cleaned", "pending", "transactional", "archived"];
            do{
                $members = $mailchimp->lists->getListMembersInfo($listId, ['members.email_address', 'members.status'], null, 500, $offset, null, $statuses);
                $members = $members->members;
                $countMembers = count($members);
                $offset += $countMembers;
                foreach($members as $member){
                    $emails[$member->email_address] = $member->status;
                }
            }
            while($countMembers > 0);

            $memberTags = [];
            foreach($chunkPersonels as $personels){
                $members = [];
                foreach($personels as $personel){
                    if(filter_var($personel->email, FILTER_VALIDATE_EMAIL) && (!isset($emails[strtolower($personel->email)])
                     || $emails[strtolower($personel->email)] == "unsubscribed" || $emails[strtolower($personel->email)] == "cleaned" || $emails[strtolower($personel->email)] == "pending")){
                        $dateOfBirth = '';
                        if($personel->date_of_birth != null){
                            try{
                                $dateOfBirth = Carbon::parse($personel->date_of_birth)->format('m/d');
                            }
                            catch(Exception $e){
                                $dateOfBirth = '';
                            }
                        }
                        $members[] = [
                            "email_address" => $personel->email,
                            "status" => (strtolower($personel->acc_status) != 'active' ? "unsubscribed" : "subscribed"),
                            // ($emails[strtolower($personel->email)] ?? 'subscribed')),
                            "merge_fields" => [
                                "FNAME" => $personel->first_name ?? '',
                                "LNAME" => $personel->last_name ?? '',
                                "BIRTHDAY" => $dateOfBirth,
                            ],
                            'language' => isset($personel->language) ? strtolower($personel->language) : '',
                            "interests" => [
                                $pastoralGroupId => true
                            ]
                        ];
                        $memberTags[md5(strtolower($personel->email))] = collect(Personel::$arrayLanguage)->map(function($lang) use($personel){
                            return [
                                'name' => $lang,
                                'status' => isset($personel->language) && $personel->language == $lang ? 'active' : 'inactive'
                            ];
                        })->toArray();
                    }
                }
                if(count($members) > 0){
                   $response = $mailchimp->lists->batchListMembers($listId, ["members" => $members, "update_existing" => true]);
                   foreach($response->errors as $error){
                        unset($memberTags[md5($error->email_address)]);
                   }
                }
            }

            if(count($memberTags) > 0){
                foreach($memberTags as $hash => $tags){
                    $mailchimp->lists->updateListMemberTags($listId, $hash, [
                        "tags" => $tags
                    ]);
                }
            }
       
            Log::channel('mailchimp')->info('Sync DTBS user to Mailchimp success.');
        }
        catch(Exception $exception){
            print_r($exception->getMessage());
            Log::channel('mailchimp')->error('Sync DTBS user to Mailchimp failed. Error : ' . $exception);
            // Mail::send([], [], function ($message) use ($exception) {
            //     $text = '<h4>ERROR</h4>';
            //     $text .= '<p>' . ($exception->getMessage() ?? 'Unknown error') . '</p>';
            //     $message->subject('ERROR SYNC DTBS USER TO MAILCHIMP')
            //         ->to(config('app.email_developer'))
            //         ->setBody($text, 'text/html');
            // });
        }
    }
}
