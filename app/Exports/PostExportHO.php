<?php

namespace App\Exports;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PostExportHO implements FromCollection, WithHeadings
{
    protected $area;
    protected $concern;
    protected $loggedUser;
    protected $branchManager;

    public function __construct( $area, $concern)
    {
        $token = session("token");

        // Fetch authenticated user details
        $userResponse = Http::withToken($token)->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $user = $userResponse->json();

        $this->loggedUser = $user['user']['oid'] ?? null;
        $this->branchManager = $user['user']['fullname'] ?? 'Unknown Manager';
        
        $this->area = $area;
        $this->concern = $concern;
    }

    public function collection(): Collection
    {
        // Fetch branches
        $response = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $apiResponse = $response->json();
        $branches = $apiResponse['branches'] ?? [];
    
        if (empty($branches)) {
            return collect([]);
        }
    
        // Map branch ID to branch names
        $branchMap = [];
        foreach ($branches as $branch) {
            $branchMap[$branch['id']] = $branch['branch_name'];
        }
    
        // Query archived posts
        $query = Post::select(
            'id', 'name', 'branch', 'contact_number', 'concern', 'message', 
            'endorse_by', 'endorse_to', 'tasks', 'endorsed_date', 'resolved_date', 
            'resolved_days', 'resolve_by', 'assess', 'member_comments'
        )
        ->where('status', 'Archived');
    
        if ($this->concern) {
            $query->where('concern', $this->concern);
        }
    
        $posts = $query->get();
    
        // Fetch all unique endorse_by and endorse_to user IDs
        $endorseByIds = $posts->pluck('endorse_by')->unique()->filter()->toArray();
        $endorseToIds = $posts->pluck('endorse_to')->unique()->filter()->toArray();
    
        $userDetails = [];
        $endorseToDetails = [];
    
        // Fetch endorse_by user details
        foreach ($endorseByIds as $userId) {
            $response = Http::withToken(session('token'))->get("https://loantracker.oicapp.com/api/v1/users/{$userId}");
            $userData = $response->json();
            $userDetails[$userId] = $userData['user']['officer']['fullname'] ?? "Unknown User";
        }
    
        // Fetch endorse_to user details
        foreach ($endorseToIds as $userId) {
            $response = Http::withToken(session('token'))->get("https://loantracker.oicapp.com/api/v1/users/{$userId}");
            $userData = $response->json();
            $endorseToDetails[$userId] = $userData['user']['officer']['fullname'] ?? "Unknown User";
        }
    
        // Process posts
        foreach ($posts as $post) {
            $post->branch = $branchMap[$post->branch] ?? $post->branch;
    
            if (!empty($post->endorse_to) && strval($post->endorse_to) === strval($this->loggedUser)) {
                $post->endorse_to = $this->branchManager;
            } else {
                $post->endorse_to = $endorseToDetails[$post->endorse_to] ?? "Unknown User";
            }
    
            $post->endorse_by = $userDetails[$post->endorse_by] ?? "Unknown User";
        }
    
        return collect($posts);
    }
    

    public function headings(): array
    {
        return [
            'ID', 'NAME', 'BRANCH', 'CONTACT NUMBER', 'CONCERN', 'MESSAGE',
            'ENDORSE BY', 'ENDORSE TO', 'SOLUTION TO CONCERN', 'ENDORSE DATE',
            'RESOLVE DATE', 'RESOLVE DAYS', 'RESOLVE BY', 'ASSESS', 'MEMBER COMMENTS'
        ];
    }
}
