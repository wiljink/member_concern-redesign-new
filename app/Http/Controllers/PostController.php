<?php

namespace App\Http\Controllers;

use App\Exports\PostExport;
use App\Exports\PostExportHO;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

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
    public function dashboard(Request $request)
    {
        $token = session('token');

        if (!$token) {
            return back()->with('error', 'Session token is missing.');
        }

        // Fetch logged-in user details
        $response = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $user = $response->json();

        // Check if API call was successful and user data exists
        if (!$response->successful() || !isset($user['user'])) {
            return back()->with('error', 'Failed to fetch user data.');
        }

        // Determine statuses based on account type
        $statuses = [];
        if (isset($user['user']['branch_id'])) {
            if ($user['user']['branch_id'] == 23) {
                $statuses = ['Archived', 'Endorsed', 'Pending']; // ✅ Only these statuses
            } elseif ($user['user']['account_type_id'] == 7) {
                $statuses = ['In Progress', 'Resolved']; // ✅ Only these statuses
            }
        }

        // Fetch distinct concern types from posts
        $concernTypes = Post::select('concern')->distinct()->pluck('concern');

        // Fetch posts with filtered statuses and conditions
        $posts = Post::select(
            'concern',
            'status',
            DB::raw('COUNT(*) as count'),
            DB::raw('MAX(updated_at) as last_updated') // Get the latest update
        )
        ->where(function ($query) use ($user) {
            if (isset($user['user']['oid']) && $user['user']['oid'] == 23) {
                $query->where('oid', 23);
            }
        })
        ->whereIn('status', $statuses)
        ->where(function ($query) use ($user) {

            // If user has account_type_id = 7, filter by endorse_to = user ID
            if (isset($user['user']['account_type_id']) && $user['user']['account_type_id'] == 7) {
                $query->where('endorse_to', $user['user']['id']);
            }
        })
        ->groupBy('concern', 'status')
        ->get();

        return view('dashboard', compact('user', 'posts', 'concernTypes'));
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

        foreach ($branches as $branch) {
            if ($endorse_to == $branch['id']) {
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

        //dd($authenticatedUser);
        // Check if the user data was fetched successfully
        if (!isset($authenticatedUser['user'])) {
            return redirect()->back()->with('error', 'Unable to fetch authenticated user details.');
        }

        $userId = $authenticatedUser['user']['id']; // Replace 'id' with the actual key for the user's ID from the response

        // Query to get concerns endorsed to the logged user
        $posts = Post::where('endorse_to', $userId) // Filter by the logged-in user's ID
            ->whereIn('status', ['Endorsed', 'In progress'])
            ->orWhere('assess', 'unresolved')
            ->paginate(10);


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

        $userId = $authenticatedUser['user']['id'];

        $posts = Post::where('status', 'Resolved')
            ->where('endorse_to', $userId)
            ->paginate(10);

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
            'tasks.*' => 'string|min:1',
            'removed_tasks' => 'nullable|array',
            'removed_tasks.*' => 'string|min:1',
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
            $fullname = session('logged_user_fullname');
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

            // Calculate the difference between endorsed and resolved date
            $endorsedDate = Carbon::parse($post->endorsed_date);
            $resolvedDate = Carbon::parse($post->resolved_date);

            // Ensure the dates are valid before calculating the difference
            if ($endorsedDate && $resolvedDate) {
                $resolvedDays = $endorsedDate->diff($resolvedDate);

                // Store the resolved days difference
                $post->resolved_days = json_encode([
                    'total_difference' => $resolvedDays->format('%a days, %h hours, %i minutes, %s seconds'),
                    'days' => $resolvedDays->d,
                    'hours' => $resolvedDays->h,
                    'minutes' => $resolvedDays->i,
                    'seconds' => $resolvedDays->s,
                ]);
            } else {
                $post->resolved_days = json_encode([
                    'total_difference' => 'Invalid dates',
                    'days' => 0,
                    'hours' => 0,
                    'minutes' => 0,
                    'seconds' => 0,
                ]);
            }
        } else {
            $post->status = 'In Progress';
        }

        // Save the post
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

        // Fetch posts with both "Resolved" and "Archived" status
        $posts = Post::whereIn('status', ['Resolved'])->paginate(10);

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
            'assess' => 'required|string|in:satisfied,unsatisfied,unresolved',
            'member_comments' => 'nullable|string|max:500',
        ]);

        // Retrieve the post by ID
        $post = Post::find($validatedData['id']);
        if (!$post) {
            return back()->with('error', 'Post not found.');
        }

        // Get the authenticated user's data
        $token = session('token');
        if (!$token) {
            return back()->with('error', 'Authentication token missing.');
        }

        $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");

        if ($response2->failed()) {
            return back()->with('error', 'Failed to authenticate user.');
        }

        $authenticatedUser = $response2->json();
        $loggedInUser = $authenticatedUser['user']['fullname'] ?? null;

        if (!$loggedInUser) {
            return back()->with('error', 'Unable to retrieve logged-in user details.');
        }

        // Handle based on assess value
        if (in_array($validatedData['assess'], ['satisfied', 'unsatisfied'])) {
            // Update assess_date, assess, and member_comments, then archive the concern
            $post->update([
                'assess_date' => now(), // Update assess_date
                'assess' => $validatedData['assess'], // Update assess value
                'member_comments' => $validatedData['member_comments'], // Update member_comments
                'status' => 'Archived',
                'archived_at' => now(),
            ]);

            return back()->with([
                'success' => 'Concern has been validated successfully.',
                'alert_type' => 'success' // Green success message
            ]);
        } elseif ($validatedData['assess'] === 'unresolved') {
            // Set status back to "In Progress" but DO NOT update assess_date, assess, or member_comments
            $post->update([
                'status' => 'In Progress',
            ]);

            \Log::info('Concern status changed to In Progress', [
                'post_id' => $post->id,
                'reassigned_by' => $loggedInUser,
            ]);

            return back()->with([
                'success' => 'Concern has been sent back to the Manager who resolved it.',
                'alert_type' => 'danger' // Red alert for unresolved
            ]);
        }
    }






    public function reportho(Request $request)
    {
        // Get branches from API
        $branchRequest = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $branches = $branchRequest->json();
        $area = $request->area;
        $areas = [];

        if ($area != 'all' && isset($area)) {
            foreach ($branches['branches'] as $branch) {
                if ($branch['area'] == $area) {
                    $areas[] = $branch['id'];
                }
            }
        }

        // Authenticated user
        $token = session('token');
        $userResponse = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");

        if ($userResponse->failed()) {
            return response()->json(['error' => 'Unable to fetch user data'], 500);
        }

        $authenticatedUser = $userResponse->json();
        $userId = $authenticatedUser['user']['id'] ?? null;

        if (!$userId) {
            return response()->json(['error' => 'Invalid user data'], 500);
        }

        // Concern types
        $concernTypes = ['Loans', 'Deposit', 'Customer Service', 'General'];

        // Output arrays
        $avgResolveDays = [];
        $concernCounts = [];

        foreach ($concernTypes as $concern) {
            $query = Post::where('status', 'Archived')
                ->where('concern', $concern);

            if (!empty($areas)) {
                $query->whereIn('branch', $areas);
            }

            $posts = $query->get();
            $concernCounts[$concern] = $posts->count();

            $totalMinutes = 0;
            $validPosts = 0;

            foreach ($posts as $post) {
                if ($post->assess_date && $post->resolved_date) {
                    $assess = Carbon::parse($post->assess_date);
                    $resolved = Carbon::parse($post->resolved_date);
                    $diffMinutes = $assess->diffInMinutes($resolved);
                    $totalMinutes += $diffMinutes;
                    $validPosts++;
                }
            }

            $avgDays = $validPosts > 0 ? round($totalMinutes / 1440 / $validPosts, 2) : null;
            $avgResolveDays[$concern] = $avgDays;
        }

        return view('posts.headoffice.report_ho', [
            'loansCount'     => $concernCounts['Loans'],
            'depositCount'   => $concernCounts['Deposit'],
            'customerCount'  => $concernCounts['Customer Service'],
            'generalCount'   => $concernCounts['General'],
            'avgResolveDays' => $avgResolveDays,
            'areas'          => $areas,
        ]);
    }



    public function reportbm()
    {
        $token = session('token');
        $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $authenticatedUser = $response2->json();

        if ($response2->failed()) {
            return response()->json(['error' => 'Unable to fetch user data'], 500);
        }

        $userId = $authenticatedUser['user']['id'] ?? null;

        if (!$userId) {
            return response()->json(['error' => 'Invalid user data'], 500);
        }

        $archivedPosts = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->get();

        $grouped = $archivedPosts->groupBy('concern');

        $averageDays = [];

        foreach ($grouped as $concern => $posts) {
            $days = $posts->map(function ($post) {
                if ($post->endorsed_date && $post->resolved_date) {
                    return Carbon::parse($post->endorsed_date)->diffInDays(Carbon::parse($post->resolved_date));
                }
                return null;
            })->filter(); // removes nulls

            $averageDays[$concern] = $days->count() ? round($days->avg(), 2) : null;
        }

        return view('posts.bm.report_bm', [
            'loansCount' => $grouped->get('Loans', collect())->count(),
            'depositCount' => $grouped->get('Deposit', collect())->count(),
            'customerCount' => $grouped->get('Customer Service', collect())->count(),
            'generalCount' => $grouped->get('General', collect())->count(),

            'averageDays' => $averageDays,
        ]);
    }
    public function download($type)
    {
        //dd($type);

        return Excel::download(new PostExport($type), 'concerns.xlsx');
    }
    public function downloadho($type, $area = null)
    {

        return Excel::download(new PostExportHO($area, $type), 'concerns.xlsx');
    }
}
