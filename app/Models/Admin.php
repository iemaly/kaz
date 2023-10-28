<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'access_token',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        // 'access_token',
        'remember_token',
        'reset_token',
        'auth_token',
        'updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    private function adminByTokenExists($token)
    {
        $admin =  $this->where('reset_token', $token);
        if ($admin->exists()) return $admin->first()->id;
        return false;
    }

    public function login($email, $password, $type)
    {
        $attempt = auth($type)->attempt(['email' => $email, 'password' => $password]);
        return ['status' => true, 'role' => $type, 'attempt' => $attempt];
    }

    function emailVerified($type, $id): bool
    {
        switch ($type) {
            case 'user':
                $user = User::find($id);
                if (!$user->email_verified) return false;
                return true;
                break;
            case 'professional':
                $professional = Professional::find($id);
                if (!$professional->email_verified) return false;
                return true;
                break;
            case 'business':
                $business = Business::find($id);
                if (!$business->email_verified) return false;
                return true;
                break;
            default:
                return false;
                break;
        }
    }

    public function checkSubadminApproveStatus($id): bool
    {
        $subadmin = Subadmin::find($id);
        if (!$subadmin->status) return false;
        return true;
    }

    public function checkUserApproveStatus($id): bool
    {
        $user = User::find($id);
        if (!$user->status) return false;
        return true;
    }

    public function checkCarehomeApproveStatus($id): bool
    {
        $carehome = CareHome::find($id);
        if (!$carehome->status) return false;
        return true;
    }

    public function checkProfessionalApproveStatus($id): bool
    {
        $professional = Professional::find($id);
        if (!$professional->status) return false;
        return true;
    }

    public function checkBusinessApproveStatus($id): bool
    {
        $business = Business::find($id);
        if (!$business->status) return false;
        return true;
    }

    public function setResetToken($email, $token, $type)
    {
        if ($this->adminExists($email, $type)) return $this->whereEmail($email)->update(['reset_token' => $token]);
    }

    public function resetPassword($token, $password)
    {
        $admin = $this->adminByTokenExists($token);
        if (!$admin) return false;

        $admin = $this->find($admin);
        $admin->reset_token = null;
        $admin->password = bcrypt($password);
        return $admin->update();
    }

    static function connectedAccountDeatils()
    {
        try {
            if (auth()->user()->paymentMethods->isEmpty()) {
                return ['status' => false, 'error' => 'Set payment method first'];
            } else {
                $stripe = new \Stripe\StripeClient(env('HARIS_STRIPE_SECRET'));
                $stripe = $stripe->accounts->retrieve(
                    auth()->user()->paymentMethods[0]->account_id,
                    []
                );
                // dd($stripe->capabilities);
                if ($stripe->capabilities->transfers == 'inactive');
                return ['status' => false, 'error' => 'Setup your stripe connect account for transfer capabilities first'];
                return ['status' => true];
            }
        } catch (\Throwable $th) {
            return ['status' => false, 'error' => $th->getMessage()];
        }
    }

    static function calculateRank($event, $songs)
    {
        $songs->each(function ($song) {
            $song->judgeSelectedSong->judgeSong->song->vote_count = $song->judgeSelectedSong->judgeSong->song->votes->count();
        });

        $songs = $songs->sortByDesc(function ($song) {
            return $song->judgeSelectedSong->judgeSong->song->vote_count;
        })->values();

        $totalRanks = 27; // Set the total number of ranks
        $rank = 1;

        $eventData = Event::find($event);

        if (!$eventData) {
            return response()->json(['status' => false, 'message' => 'Event not found']);
        }

        // Check if the current date is after the voter_end_date
        $currentDate = now()->format('Y-m-d'); // Adjust the date format based on your database format
        $voterEndDate = $eventData->voter_end_date;

        // Initialize an array to store the first 3 records from each genre grouped by genre name.
        $first9 = [];
        if ($currentDate > $voterEndDate) {
            foreach ($songs as $song) {
                if ($song->admin_is_published == 1) {
                    $songData = $song->judgeSelectedSong->judgeSong->song;

                    // Determine the rank index to use for percentage calculations
                    $rankIndex = min($rank, $totalRanks) - 1;

                    // Calculate the winning amount for the current rank
                    $singerVotingPoolPercentData = json_decode($songData->event->singer_voting_pool_percent);
                    $singerUploadPercentData = json_decode($songData->event->singer_upload_percent);

                    if ($rank <= 3) {
                        // For ranks 1 to 3, use the specified percentages
                        $votingPoolPercent = isset($singerVotingPoolPercentData[$rankIndex]) ? $singerVotingPoolPercentData[$rankIndex]->percent : 0;
                        $singerUploadPercent = isset($singerUploadPercentData[$rankIndex]) ? $singerUploadPercentData[$rankIndex]->percent : 0;
                    } else {
                        // For ranks 4 to 27, use the 4th rank's percentages
                        $votingPoolPercent = isset($singerVotingPoolPercentData[3]) ? $singerVotingPoolPercentData[3]->percent : 0;
                        $singerUploadPercent = isset($singerUploadPercentData[3]) ? $singerUploadPercentData[3]->percent : 0;
                    }

                    if ($song->judgeSelectedSong->judgeSong->song->vote_count > 0) {
                        // Convert 1 vote to 0.50 and calculate the winning amount
                        $winningAmount = ($song->judgeSelectedSong->judgeSong->song->vote_count * 0.50) * ($votingPoolPercent / 100) + ($singerUploadPercent / 100) * $songData->event->song_upload_fees;
                    } else {
                        // When there are zero votes, share singer_upload_percent among all 27 ranks
                        $winningAmount = ($singerUploadPercent / 100) * $songData->event->song_upload_fees;
                    }

                    // Include the capped amount (same capped amount for ranks 4 to 27)
                    $cappedAmountData = json_decode($eventData->singer_cap_amount);
                    if ($rank <= 3) {
                        $cappedAmount = isset($cappedAmountData[$rankIndex]) ? $cappedAmountData[$rankIndex]->amount : 0;
                    } else {
                        $cappedAmount = isset($cappedAmountData[3]) ? $cappedAmountData[3]->amount : 0;
                    }

                    // Limit the winning amount based on judge_cap_amount
                    $winningAmount = min($winningAmount, $cappedAmount);

                    // Calculate the judge_winning_amount based on total votes
                    $totalVotes = 0;

                    // Calculate the total number of votes for the event
                    foreach ($songs as $s) {
                        $totalVotes += $s->judgeSelectedSong->judgeSong->song->votes->count();
                    }
                    // Check judge_cap_amount from event
                    $judgeCapAmount = $eventData->judge_cap_amount;
                    $judgePoolPercent = $eventData->judge_pool_percent;
                    $judgeWinningAmount = ($totalVotes * 0.50) * ($judgePoolPercent / 100);
                    $judgeWinningAmount = min($judgeWinningAmount, $judgeCapAmount);

                    // Round the winning amount to two decimal places
                    $winningAmount = number_format($winningAmount, 2, '.', '');;

                    $songData->winning_amount = $winningAmount;
                    $songData->rank = $rank;
                    $rank++;
                }
            }

            // FIRST 9
            // Sort the songs by the rank of the judge-selected song.
            $songsSortedByRank = $songs->sortBy('judgeSelectedSong.judgeSong.song.rank');

            // Initialize an array to keep track of the rank within each genre group.
            $rankByGenre = [];

            // Iterate through the sorted songs.
            foreach ($songsSortedByRank as $song) {
                $genreName = $song['judgeSelectedSong']['judgeSong']['song']['genre']['name'];

                // If we haven't seen this genre yet, initialize its record array and rank.
                if (!isset($first9[$genreName])) {
                    $first9[$genreName] = [];
                    $rankByGenre[$genreName] = 1; // Initialize the rank to 1 for this genre.
                }

                // Check if we've already collected 3 records for this genre.
                if (count($first9[$genreName]) < 3) {
                    // Set the song's rank based on the current rank for this genre.
                    $song['judgeSelectedSong']['judgeSong']['song']['rank'] = $rankByGenre[$genreName];

                    // Increment the rank for the next song within this genre.
                    $rankByGenre[$genreName]++;

                    // Add the song to the genre's record array.
                    $first9[$genreName][] = $song;
                }
            }
        }
        return ['songs' => $songs, 'first9' => $first9];
    }

    static function calculateFinalRank($event, $songs)
    {
        $songs->each(function ($song) {
            $song->judgeSelectedSong->judgeSong->song->vote_count = $song->judgeSelectedSong->judgeSong->song->votes->count();
        });

        $songs = $songs->sortByDesc(function ($song) {
            return $song->judgeSelectedSong->judgeSong->song->vote_count;
        });

        $totalRanks = 9; // Set the total number of ranks
        $rank = 1;

        $eventData = Event::find($event);

        if (!$eventData) {
            return response()->json(['status' => false, 'message' => 'Event not found']);
        }

        // Check if the current date is after the voter_end_date
        $currentDate = now()->format('Y-m-d'); // Adjust the date format based on your database format
        $voterEndDate = $eventData->final_voter_end_date;

        if ($currentDate > $voterEndDate) {
            foreach ($songs as $song) {
                if ($song->admin_is_published == 1) {
                    $songData = $song->judgeSelectedSong->judgeSong->song;

                    // Determine the rank index to use for percentage calculations
                    $rankIndex = min($rank, $totalRanks) - 1;

                    // Calculate the winning amount for the current rank
                    $singerVotingPoolPercentData = json_decode($songData->event->final_singer_voting_pool_percent);

                    if ($rank <= 3) {
                        // For ranks 1 to 3, use the specified percentages
                        $votingPoolPercent = isset($singerVotingPoolPercentData[$rankIndex]) ? $singerVotingPoolPercentData[$rankIndex]->percent : 0;
                    } else {
                        // For ranks 4 to 27, use the 4th rank's percentages
                        $votingPoolPercent = isset($singerVotingPoolPercentData[3]) ? $singerVotingPoolPercentData[3]->percent : 0;
                    }

                    if ($song->judgeSelectedSong->judgeSong->song->vote_count > 0) {
                        // Convert 1 vote to 0.50 and calculate the winning amount
                        $winningAmount = ($song->judgeSelectedSong->judgeSong->song->vote_count * 0.50) * ($votingPoolPercent / 100);
                    } else {
                        // When there are zero votes, share singer_upload_percent among all 27 ranks
                        $winningAmount = 0;
                    }

                    // Include the capped amount (same capped amount for ranks 4 to 27)
                    $cappedAmountData = json_decode($eventData->final_singer_cap_amount);
                    if ($rank <= 3) {
                        $cappedAmount = isset($cappedAmountData[$rankIndex]) ? $cappedAmountData[$rankIndex]->amount : 0;
                    } else {
                        $cappedAmount = isset($cappedAmountData[3]) ? $cappedAmountData[3]->amount : 0;
                    }

                    // Limit the winning amount based on judge_cap_amount
                    $winningAmount = min($winningAmount, $cappedAmount);

                    // Calculate the judge_winning_amount based on total votes
                    $totalVotes = 0;

                    // Calculate the total number of votes for the event
                    foreach ($songs as $s) {
                        $totalVotes += $s->judgeSelectedSong->judgeSong->song->votes->count();
                    }
                    // Check judge_cap_amount from event
                    // $judgeCapAmount = $eventData->final_judge_cap_amount;
                    // $judgePoolPercent = $eventData->final_judge_pool_percent;
                    // $judgeWinningAmount = ($totalVotes * 0.50) * ($judgePoolPercent / 100);
                    // $judgeWinningAmount = min($judgeWinningAmount, $judgeCapAmount);

                    // Round the winning amount to two decimal places
                    $winningAmount = number_format($winningAmount, 2, '.', '');;

                    $songData->winning_amount = $winningAmount;
                    $songData->rank = $rank;
                    $rank++;
                }
            }
        }
        return $songs;
    }

    static function winners($event, $songs)
    {
        // Initialize an empty array to store the winners
        $winners = [];

        // Retrieve the event data
        $eventData = Event::find($event);

        if (!$eventData) {
            return response()->json(['status' => false, 'message' => 'Event not found']);
        }

        // Calculate the judge winning amount based on judge_pool_percent
        $judgePoolPercent = $eventData->judge_pool_percent;
        // Sort the songs by votes
        $songs = $songs->sortByDesc(function ($song) {
            return $song->judgeSelectedSong->judgeSong->song->votes->count();
        })->values();

        $totalRanks = 27; // Set the total number of ranks
        $rank = 1;

        foreach ($songs as $song) {
            $songData = $song->judgeSelectedSong->judgeSong->song;

            // Determine the rank index to use for percentage calculations
            $rankIndex = min($rank, $totalRanks) - 1;

            // Calculate the winning amount for the current rank
            $singerVotingPoolPercentData = json_decode($eventData->singer_voting_pool_percent);
            $singerUploadPercentData = json_decode($eventData->singer_upload_percent);

            if ($rank <= 3) {
                // For ranks 1 to 3, use the specified percentages
                $votingPoolPercent = isset($singerVotingPoolPercentData[$rankIndex]) ? $singerVotingPoolPercentData[$rankIndex]->percent : 0;
                $singerUploadPercent = isset($singerUploadPercentData[$rankIndex]) ? $singerUploadPercentData[$rankIndex]->percent : 0;
            } else {
                // For ranks 4 to 27, use the 4th rank's percentages
                $votingPoolPercent = isset($singerVotingPoolPercentData[3]) ? $singerVotingPoolPercentData[3]->percent : 0;
                $singerUploadPercent = isset($singerUploadPercentData[3]) ? $singerUploadPercentData[3]->percent : 0;
            }

            if ($songData->votes->count() > 0) {
                // Convert 1 vote to 0.50 and calculate the winning amount
                $winningAmount = ($songData->votes->count() * 0.50) * ($votingPoolPercent / 100) + ($singerUploadPercent / 100) * $eventData->song_upload_fees;
            } else {
                // When there are zero votes, share singer_upload_percent among all 27 ranks
                $winningAmount = ($singerUploadPercent / 100) * $eventData->song_upload_fees;
            }

            // Include the capped amount (same capped amount for ranks 4 to 27)
            $cappedAmountData = json_decode($eventData->singer_cap_amount);
            if ($rank <= 3) {
                $cappedAmount = isset($cappedAmountData[$rankIndex]) ? $cappedAmountData[$rankIndex]->amount : 0;
            } else {
                $cappedAmount = isset($cappedAmountData[3]) ? $cappedAmountData[3]->amount : 0;
            }

            // Limit the winning amount based on judge_cap_amount
            $winningAmount = min($winningAmount, $cappedAmount);

            // Calculate the judge_winning_amount based on total votes
            $totalVotes = 0;

            // Calculate the total number of votes for the event
            foreach ($songs as $s) {
                $totalVotes += $s->judgeSelectedSong->judgeSong->song->votes->count();
            }
            // Check judge_cap_amount from event
            $judgeCapAmount = $eventData->judge_cap_amount;
            $judgeWinningAmount = ($totalVotes * 0.50) * ($judgePoolPercent / 100);
            $judgeWinningAmount = min($judgeWinningAmount, $judgeCapAmount);


            // Round the winning amounts to two decimal places
            $winningAmount = number_format($winningAmount, 2, '.', '');;
            $judgeWinningAmount = number_format($judgeWinningAmount, 2, '.', '');;

            // Create the winner object for this song
            $winner = [
                'rank' => $rank,
                'singer_id' => $songData->singer_id,
                'judge_id' => $songData->judge->judge->id,
                'song_id' => $songData->id,
                'song_winning_amount' => $winningAmount,
                'judge_winning_amount' => $judgeWinningAmount,
            ];

            // Push the winner's data to the results array
            array_push($winners, $winner);

            $rank++;
        }
        return $winners;
    }

    static function finalWinners($event, $songs)
    {
        // Initialize an empty array to store the winners
        $winners = [];

        // Retrieve the event data
        $eventData = Event::find($event);

        if (!$eventData) {
            return response()->json(['status' => false, 'message' => 'Event not found']);
        }

        // Calculate the judge winning amount based on judge_pool_percent
        // $judgePoolPercent = $eventData->final_judge_pool_percent;
        // Sort the songs by votes
        $songs = $songs->sortByDesc(function ($song) {
            return $song->judgeSelectedSong->judgeSong->song->votes->count();
        })->values();

        $totalRanks = 9; // Set the total number of ranks
        $rank = 1;

        foreach ($songs as $song) {
            $songData = $song->judgeSelectedSong->judgeSong->song;

            // Determine the rank index to use for percentage calculations
            $rankIndex = min($rank, $totalRanks) - 1;

            // Calculate the winning amount for the current rank
            $singerVotingPoolPercentData = json_decode($eventData->final_singer_voting_pool_percent);

            if ($rank <= 3) {
                // For ranks 1 to 3, use the specified percentages
                $votingPoolPercent = isset($singerVotingPoolPercentData[$rankIndex]) ? $singerVotingPoolPercentData[$rankIndex]->percent : 0;
            } else {
                // For ranks 4 to 27, use the 4th rank's percentages
                $votingPoolPercent = isset($singerVotingPoolPercentData[3]) ? $singerVotingPoolPercentData[3]->percent : 0;
            }

            if ($songData->votes->count() > 0) {
                // Convert 1 vote to 0.50 and calculate the winning amount
                $winningAmount = ($songData->votes->count() * 0.50) * ($votingPoolPercent / 100);
            } else {
                // When there are zero votes, share singer_upload_percent among all 27 ranks
                $winningAmount = 0;
            }

            // Include the capped amount (same capped amount for ranks 4 to 27)
            $cappedAmountData = json_decode($eventData->final_singer_cap_amount);
            if ($rank <= 3) {
                $cappedAmount = isset($cappedAmountData[$rankIndex]) ? $cappedAmountData[$rankIndex]->amount : 0;
            } else {
                $cappedAmount = isset($cappedAmountData[3]) ? $cappedAmountData[3]->amount : 0;
            }

            // Limit the winning amount based on judge_cap_amount
            $winningAmount = min($winningAmount, $cappedAmount);

            // Calculate the judge_winning_amount based on total votes
            $totalVotes = 0;

            // Calculate the total number of votes for the event
            foreach ($songs as $s) {
                $totalVotes += $s->judgeSelectedSong->judgeSong->song->votes->count();
            }
            // Check judge_cap_amount from event
            // $judgeCapAmount = $eventData->final_judge_cap_amount;
            // $judgeWinningAmount = ($totalVotes * 0.50) * ($judgePoolPercent / 100);
            // $judgeWinningAmount = min($judgeWinningAmount, $judgeCapAmount);


            // Round the winning amounts to two decimal places
            $winningAmount = number_format($winningAmount, 2, '.', '');;
            // $judgeWinningAmount = number_format($judgeWinningAmount, 2, '.', '');;

            // Create the winner object for this song
            $winner = [
                'rank' => $rank,
                'singer_id' => $songData->singer_id,
                'judge_id' => $songData->judge->judge->id,
                'song_id' => $songData->id,
                'song_winning_amount' => $winningAmount,
                // 'judge_winning_amount' => $judgeWinningAmount,
            ];

            // Push the winner's data to the results array
            array_push($winners, $winner);

            $rank++;
        }
        return $winners;
    }

    // POLYMORPHIC RELATION
    function addedUsers()
    {
        return $this->morphMany(User::class, 'added_by');
    }

    // RELATIONS

    // ACCESSOR
    protected function image(): Attribute
    {
        return Attribute::make(
            fn ($value) => !empty($value) ? asset($value) : asset('assets/profile_pics/admin.jpg'),
        );
    }
}
