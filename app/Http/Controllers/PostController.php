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
      
          $posts = Post::whereIn('status', ['Endorsed', 'In progress', ])->OrWhere('assess','unresolved')->paginate(10);

          

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

    public function analyze(Request $request)
    {
        try {
            // Validate incoming request data
            $validatedData = $request->validate([
                'posts_id' => 'required|integer|exists:posts,id',
                'tasks' => 'nullable|array',
                'tasks.*' => 'string|min:1', // Ensure each task is a valid string
                'removed_tasks' => 'nullable|array',
                'removed_tasks.*' => 'string|min:1', // Ensure removed tasks are valid strings
                'status' => 'required|string|in:Resolved,In Progress',
            ]);

           

            // Find the post by ID
            $post = Post::findOrFail($validatedData['posts_id']);
            
            // Decode existing tasks
            $existingTasks = $post->tasks ? json_decode($post->tasks, true) : [];

            // Merge new tasks
            if (!empty($validatedData['tasks'])) {
                $existingTasks = array_unique(array_merge($existingTasks, $validatedData['tasks']));
            }

            // Remove specified tasks
            if (!empty($validatedData['removed_tasks'])) {
                $existingTasks = array_filter($existingTasks, function ($task) use ($validatedData) {
                    return !in_array($task, $validatedData['removed_tasks']);
                });
            }

            // Update tasks
            $post->tasks = json_encode(array_values($existingTasks));

            // Handle status updates
            $currentTime = Carbon::now();
            if ($validatedData['status'] === 'Resolved' && $post->status === 'In Progress') {
                // Fetch logged user details from the session or API
                $fullname = session('logged_user_fullname'); // Assuming this is cached in session
                if (!$fullname) {
                    $token = session('token');
                    $response = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
                    $loggedUser = $response->json();
                    $fullname = $loggedUser['user']['fullname'] ?? 'Unknown';
                    session(['logged_user_fullname' => $fullname]);
                }

                $post->resolve_by = $fullname;
                $post->endorsed_date = $post->endorsed_date ?: $currentTime;
                $post->status = 'Resolved';
                $post->resolved_date = $currentTime;

                $endorsedDate = Carbon::parse($post->endorsed_date);
                $resolvedDays = $endorsedDate->diff($currentTime);

                $post->resolved_days = json_encode([
                    'total_difference' => $resolvedDays->format('%a days, %h hours, %i minutes, %s seconds'),
                    'days' => $resolvedDays->d,
                    'hours' => $resolvedDays->h,
                    'minutes' => $resolvedDays->i,
                    'seconds' => $resolvedDays->s,
                ]);
            } else {
                $post->status = 'In Progress';
            }

            // Save post
            $post->save();

            // Return success message
            $message = $post->status === 'In Progress'
                ? 'Progress saved successfully.'
                : 'Concern successfully resolved and archived.';

            return redirect()->route('posts.index')->with('success', $message);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
            FacadesLog::error('Post analyze error', [
                'exception' => $e,
                'post_id' => $request->input('posts_id'),
                'request_data' => $request->all(),
            ]);
             return redirect()->back()->with('error', 'An error occurred while processing your request.');
            // return redirect()->route('posts.index')->with('success', $message);

        }
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
    
    public function validateConcern(Request $request)
    {
        // Validate inputs
        $validatedData = $request->validate([
            'id' => 'required|exists:posts,id', 
            'assess' => 'required|string',
            'member_comments' => 'nullable|string|max:500',
        ]);
    
        // Retrieve the post by ID
        $post = Post::findOrFail($validatedData['id']);
    
        // Update the assess and member_comments fields
        $post->update([
            'assess' => $validatedData['assess'],
            'member_comments' => $validatedData['member_comments'],
        ]);
    
        // Archive the post if the assess value is 'Satisfied' or 'Unsatisfied'
        if (in_array($validatedData['assess'], ['Satisfied', 'Unsatisfied'])) {
            $post->update([
                'status' => 'Archived',
                'archived_at' => now(),
            ]);
        }
    
        // Return success response
        return back()->with('success', 'Member Feedback has been validated and archived successfully.');
    }

    // newly added code
    public function getStatusOverview(Request $request)
    {
       // Fetch the authenticated user's data from the external API
    $token = session('token');
    $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
    
    if ($response2->failed()) {
        return response()->json(['error' => 'Unable to fetch user data'], 500);
    }

    $authenticatedUser = $response2->json();

    if (!isset($authenticatedUser['user'])) {
        return response()->json(['error' => 'User data is missing'], 400);
    }

    $branchId = $authenticatedUser['user']['branch_id'] ?? null;
    $accountTypeId = $authenticatedUser['user']['account_type_id'] ?? null;

    if (!$branchId || !$accountTypeId) {
        return response()->json(['error' => 'Branch ID or Account Type ID is missing'], 400);
    }

        // Query the database to get counts based on the status
        $statusCounts = DB::table('posts') // Replace 'posts' with the actual table name
        ->select('status', DB::raw('COUNT(*) as count'), DB::raw('MAX(updated_at) as last_updated'))
        ->when($branchId == 23, function ($query) {
            $query->whereIn('status', ['Pending', 'Validate']);
        })
        ->when($accountTypeId == 7, function ($query) {
            $query->orWhereIn('status', ['Resolved']);
        })
        ->orWhere('status', 'Endorsed')
        ->orWhere('status', 'Unresolved')
        ->groupBy('status')
        ->get();

    return response()->json($statusCounts);
    }
           
}
