<?php

namespace App\Http\Controllers;

use App\Exports\PostExport;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log as FacadesLog;
use Illuminate\Support\facades\DB;
use Maatwebsite\Excel\Facades\Excel;

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
        $post = Post::findOrFail($validatedData['id']);

        // Update the assess and member_comments fields
        $post->update([
            'assess' => $validatedData['assess'],
            'member_comments' => $validatedData['member_comments'],
        ]);

        // Handle based on assess value
        if (in_array($validatedData['assess'], ['satisfied', 'unsatisfied'])) {
            // Archive the concern
            $post->update([
                'status' => 'Archived',
                'archived_at' => now(),
            ]);
        } elseif ($validatedData['assess'] === 'unresolved') {
            // Return to the manager who resolved the concern
            if ($post->resolve_by) {
                $manager = User::find($post->resolve_by); // Assuming User model holds managers

                if ($manager) {
                    // Update status to indicate reassignment
                    $post->update([
                        'status' => 'Reassigned',
                    ]);

                    // Log the reassignment for tracking
                    \Log::info('Concern reassigned to the manager who resolved it', [
                        'post_id' => $post->id,
                        'manager_id' => $manager->id,
                        'manager_name' => $manager->name,
                    ]);
                }
            }
        }

        // Return success response
        return back()->with('success', 'Concern has been validated and archived successfully.');
    }
    public function reportho(Request $request)
    {
        $branchRequest = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $branch = $branchRequest->json();

        $area = request()->area;
        
        if ($area == null) {
        } else {
            
        }

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

        // Check if the request is AJAX
        if ($request->ajax()) {
            // Retrieve filter parameters
            $selectedArea = $request->input('area');
            $selectedBranch = $request->input('branch');

            // Base query
            $query = Post::where('endorse_to', $userId)
                ->where('status', 'Archived');

            // Apply filters
            if (!empty($selectedArea) && $selectedArea !== 'all') {
                $query->where('area', $selectedArea);
            }

            if (!empty($selectedBranch)) {
                $query->where('branch', $selectedBranch);
            }

            // Fetch concern data with average resolution days
            $concerns = $query->select('concern', DB::raw('AVG(resolution_days) as avg_days'))
                ->groupBy('concern')
                ->get();

            return response()->json($concerns);
        }

        // Count concerns
        $loansCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'Loans')->count();

        $depositCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'Deposit')->count();

        $customerCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'Customer Service')->count();

        $generalCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'General')->count();



        // Return the view if not an AJAX request
        //return view('posts.headoffice.report_ho');
        return view('posts.headoffice.report_ho', compact('loansCount', 'depositCount', 'customerCount', 'generalCount'));
    }


    public function reportbm()
    {
        $token = session('token');
        $response2 = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $authenticatedUser = $response2->json();

        if ($response2->failed()) {
            return response()->json(['error' => 'Unable to fetch user data'], 500);
        }

        // Extract authenticated user ID
        $userId = $authenticatedUser['user']['id'] ?? null;

        if (!$userId) {
            return response()->json(['error' => 'Invalid user data'], 500);
        }

        $loansCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'Loans')->count();

        $depositCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'Deposit')->count();

        $customerCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'Customer Service')->count();

        $generalCount = Post::where('endorse_to', $userId)
            ->where('status', 'Archived')
            ->where('concern', 'General')->count();



        return view('posts.bm.report_bm', compact('loansCount', 'depositCount', 'customerCount', 'generalCount'));
    }
    public function download($type)
    {
        //dd($type);

        return Excel::download(new PostExport($type), 'concerns.xlsx');
    }
    public function downloadho($type)
    {
        //dd($type);

        return Excel::download(new PostExport($type), 'concerns.xlsx');
    }
}
