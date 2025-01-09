<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as FacadesLog;

class PostController extends Controller
{
    //
    public function create()
    {
        $response = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $branches = $response->json();
        return view(
            'posts.create',
            [
                'branches' => $branches['branches'],
            ]
        );
    }

    public function store(Request $request)
    {

        //Validate the request
        $data = $request->validate([
            'post_id' => 'nullable|integer',
            'name' => 'required',
            'branch' => 'required|string',
            'contact_number' => 'required|regex:/^[0-9]+$/',
            'concern' => 'required|string',
            'message' => 'required|string',
            // Optional fields since they are nullable in the database
            'endorse_by' => 'nullable|string|max:255',
            'endorse_to' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
        ]);

        $post = POST::create($data);

        //redirect to index page
        // return redirect(route('posts.index'));
        return redirect()->route('posts.create')->with('success', '<span style="color: red;">Thank you for posting your concern. A Branch Representative will contact you soon.</span>');
    }

    public function update(Request $request)
    {
        $token = session('token');
        $req = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $user = $req->json();
        
        $post_id = $request->post_id;
        $endorse_to = $request->endorse_to;

        $branch_request = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $branches_json = $branch_request->json();
        $branches = $branches_json['branches'];
        foreach($branches as $branch){
            if($endorse_to == $branch['id']){
                $endorse_to = $branch['branch_manager']['id'];
            }
        }
        
       $endorse_by = $user['user']['oid'];
        $post = Post::find($post_id);
        $post->endorse_by = $endorse_by;
        $post->endorse_to = $endorse_to;
        $post->status = 'Endorsed';

        $post->save();

        return redirect()->route('concerns.list');
    }


    public function list()
    {
        // Fetch branch data from the external API
        $response1 = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $branches = $response1->json();
    
        // Get the authenticated user's data
        $token = session('token');
        $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $authenticatedUser = $response2->json();
    
        $posts = Post::where('status', 'pending')->orWhere('status', 'Endorsed')->paginate(10);
    
        // Return the view with the posts and other data
        return view('posts.member_concern', [
            'data' => $posts,
            'branches' => $branches['branches'],
            'authenticatedUser' => $authenticatedUser['user'],
        ]);
    }
    

 

    public function endorsebm()
    {
        
          // Fetch branch data from the external API
          $response1 = Http::get('https://loantracker.oicapp.com/api/v1/branches');
          $branches = $response1->json();
      
          // Get the authenticated user's data
          $token = session('token');
          $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
          $authenticatedUser = $response2->json();
      
          $posts = Post::where('status', 'Endorsed')->paginate(10);

        return view('posts.endorse_bm', [
            'data' => $posts,
            'branches' => $branches['branches'],
            'authenticatedUser' => $authenticatedUser['user'],
        ]);
    }

    public function resolvebm()
    {
          // Fetch branch data from the external API
          $response1 = Http::get('https://loantracker.oicapp.com/api/v1/branches');
          $branches = $response1->json();
      
          // Get the authenticated user's data
          $token = session('token');
          $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
          $authenticatedUser = $response2->json();
      
          $posts = Post::where('status', 'Resolved')->paginate(10);

        return view('posts.resolve_bm', [
            'data' => $posts,
            'branches' => $branches['branches'],
            'authenticatedUser' => $authenticatedUser['user'],
        ]);
    }

    public function resolveho()
    {
          // Fetch branch data from the external API
          $response1 = Http::get('https://loantracker.oicapp.com/api/v1/branches');
          $branches = $response1->json();
      
          // Get the authenticated user's data
          $token = session('token');
          $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
          $authenticatedUser = $response2->json();
      
          $posts = Post::where('status', 'Resolved')->paginate(10);

        return view('posts.resolve_ho', [
            'data' => $posts,
            'branches' => $branches['branches'],
            'authenticatedUser' => $authenticatedUser['user'],
        ]);
    }
    
    
    public function resolved()
    {
        // Fetch branch data from the API
        $response1 = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $branches = $response1->json(); // This will hold the branch data

        // Fetch authenticated user's information
        $token = session('token');
        $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $authenticatedUser = $response2->json();

        // Check if branch_id is 23 (admin or specific branch condition)
        if ($authenticatedUser['user']['branch_id'] === 23) {
            // If the branch is 23, fetch all posts, filtered by resolved_by to match the authenticated user
            $posts = Post::whereNotNull('created_at')
                ->whereNotNull('endorsed_date')
                ->where('status', 'Resolved')
                ->where('resolved_by', $authenticatedUser['user']['id']) // Filter by resolved_by
                ->get();
        } else {
            // Otherwise, fetch posts only for the authenticated user's branch and resolved by the user
            $posts = Post::whereNotNull('created_at')
                ->whereNotNull('endorsed_date')
                ->where('status', 'Resolved')
                ->where('branch', $authenticatedUser['user']['branch_id']) // Filter by branch
                ->where('resolved_by', $authenticatedUser['user']['id']) // Filter by resolved_by
                ->get();
        }

        // Group posts by branch
        $groupedPosts = $posts->groupBy('branch');

        // Initialize an array to hold average facilitation times for each branch and concern
        $averagesByBranch = [];

        // Loop through each branch
        foreach ($groupedPosts as $branch => $branchPosts) {
            // Fetch the branch name from the branch data using the branch_id
            $branchName = collect($branches['branches'])->firstWhere('id', $authenticatedUser['user']['branch_id'])['branch_name'] ?? 'Unknown Branch';

            // Group posts by concern within the branch
            $postsByConcern = $branchPosts->groupBy('concern');

            foreach ($postsByConcern as $concern => $concernPosts) {
                // Exclude posts with null or empty concern
                if (empty($concern)) {
                    continue;
                }

                $totalSeconds = 0;
                $totalPosts = count($concernPosts);

                foreach ($concernPosts as $post) {
                    // Calculate the difference in seconds between concern_received_date and endorsed_date
                    $receivedDate = \Carbon\Carbon::parse($post->concern_received_date);
                    $endorsedDate = \Carbon\Carbon::parse($post->endorsed_date);

                    $diffInSeconds = $endorsedDate->diffInSeconds($receivedDate);

                    $totalSeconds += $diffInSeconds;
                }

                // Calculate the average time in seconds for this concern
                if ($totalPosts > 0) {
                    $averageSeconds = $totalSeconds / $totalPosts;

                    // Convert seconds into days, hours, minutes, and seconds
                    $averageDays = floor($averageSeconds / 86400);
                    $averageSeconds %= 86400;
                    $averageHours = floor($averageSeconds / 3600);
                    $averageSeconds %= 3600;
                    $averageMinutes = floor($averageSeconds / 60);
                    $averageSeconds %= 60;

                    // Store the average facilitation time for the concern
                    $averagesByBranch[$branch]['branch_name'] = $branchName; // Store branch name
                    $averagesByBranch[$branch][$concern] = [
                        'days' => $averageDays,
                        'hours' => $averageHours,
                        'minutes' => $averageMinutes,
                        'seconds' => $averageSeconds
                    ];
                }
            }
        }

        // Return the view with both averagesByBranch and posts data
        return view('posts.resolved', compact('averagesByBranch', 'posts', 'branches'));
    }

    // public function facilitate()
    // {
    //     // Retrieve all concerns with a valid concern_received_date and endorsed_date
    //     $posts = Post::whereNotNull('created_at')
    //         ->whereNotNull('endorsed_date')
    //         ->get();

    //     // Group posts by branch
    //     $groupedPosts = $posts->groupBy('branch');

    //     // Initialize an array to hold average facilitation times for each branch and concern
    //     $averagesByBranch = [];

    //     // Loop through each branch
    //     foreach ($groupedPosts as $branch => $branchPosts) {
    //         // Group posts by concern within the branch
    //         $postsByConcern = $branchPosts->groupBy('concern');

    //         foreach ($postsByConcern as $concern => $concernPosts) {
    //             $totalSeconds = 0;
    //             $totalPosts = count($concernPosts);

    //             foreach ($concernPosts as $post) {
    //                 // Calculate the difference in seconds between concern_received_date and endorsed_date
    //                 $receivedDate = \Carbon\Carbon::parse($post->concern_received_date);
    //                 $endorsedDate = \Carbon\Carbon::parse($post->endorsed_date);

    //                 $diffInSeconds = $endorsedDate->diffInSeconds($receivedDate);

    //                 $totalSeconds += $diffInSeconds;
    //             }

    //             // Calculate the average time in seconds for this concern
    //             $averageSeconds = $totalSeconds / $totalPosts;

    //             // Convert seconds into days, hours, minutes, and seconds
    //             $averageDays = floor($averageSeconds / 86400);
    //             $averageSeconds %= 86400;
    //             $averageHours = floor($averageSeconds / 3600);
    //             $averageSeconds %= 3600;
    //             $averageMinutes = floor($averageSeconds / 60);
    //             $averageSeconds %= 60;

    //             // Store the average facilitation time
    //             $averagesByBranch[$branch][$concern] = [
    //                 'days' => $averageDays,
    //                 'hours' => $averageHours,
    //                 'minutes' => $averageMinutes,
    //                 'seconds' => $averageSeconds
    //             ];
    //         }
    //     }

    //     // Pass the data to the view
    //     return view('posts.resolved', compact('averagesByBranch'));
    // }


public function validateConcern(Request $request)
{
    // Retrieve tasks as a single string from the textarea
    $tasksString = $request->input('tasks');
    
    // Split the string into an array of tasks
    $tasks = array_map('trim', explode(',', $tasksString));

    // Process the tasks (e.g., save to the database or display on a view)
    foreach ($tasks as $task) {
        // Save or handle each task
        Task::create(['description' => $task]); // Example saving
    }

    return back()->with('success', 'Tasks have been processed successfully.');
}


}
