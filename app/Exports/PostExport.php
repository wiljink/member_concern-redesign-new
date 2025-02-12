<?php

namespace App\Exports;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PostExport implements FromCollection, WithHeadings
{
    protected $concern;
    protected $loggedUser;
    protected $branchManager;

    public function __construct($concern)
    {
        $token = session("token");
        $getUserResponse = Http::withToken($token)
            ->get("https://loantracker.oicapp.com/api/v1/users/logged-user");

        $user = $getUserResponse->json();
        $this->loggedUser = $user['user']['oid'] ?? null;

        $this->branchManager = $user['user']['fullname'] ?? null;
        $this->concern = $concern;
    }

    public function collection(): Collection
    {
        // Fetch branches
        $response1 = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $apiResponse = $response1->json();
        $branches = $apiResponse['branches'] ?? [];
    
        // Fetch posts
        $query = Post::select(
            'id', 'name', 'branch', 'contact_number', 'concern', 'message', 
            'endorse_by', 'endorse_to', 'tasks', 'endorsed_date', 'resolved_date', 
            'resolved_days', 'resolve_by', 'assess', 'member_comments'
        )
        ->where('endorse_to', $this->loggedUser)
        ->where('status', 'Archived');
    
        if ($this->concern) {
            $query->where('concern', $this->concern);
        }
    
        $posts = $query->get();
    
        // Map branch ID to branch name
        $branchMap = [];
        foreach ($branches as $branch) {
            $branchMap[$branch['id']] = $branch['branch_name'];
        }
    
        // Collect all `endorse_by` user IDs for batch processing
        $endorseByIds = $posts->pluck('endorse_by')->unique()->filter()->toArray();
        $userDetails = [];
    
        // Fetch details for each `endorse_by` user
        foreach ($endorseByIds as $userId) {
            $response3 = Http::withToken(session('token'))->get("https://loantracker.oicapp.com/api/v1/users/" . $userId);
            $userData = $response3->json();
            $userDetails[$userId] = $userData['user']['officer']['fullname'] ?? null;
        }
    
        // Process posts
        foreach ($posts as $post) {
            // Convert branch ID to branch name
            $post->branch = $branchMap[$post->branch] ?? $post->branch;
    
            // Convert `endorse_to` and `endorse_by` user details
            if ($post->endorse_to == $this->loggedUser) {
                $post->endorse_to = $this->branchManager;
            }
            if (isset($userDetails[$post->endorse_by])) {
                $post->endorse_by = $userDetails[$post->endorse_by];
            }
        }
    
        return collect($posts);
    }
    
    public function headings(): array
    {
        return [
            'ID',
            'NAME',
            'BRANCH',
            'CONTACT NUMBER',
            'CONCERN',
            'MESSAGE',
            'ENDORSE BY',
            'ENDORSE TO',
            'SOLUTION TO CONCERN',
            'ENDORSE DATE',
            'RESOLVE DATE',
            'RESOLVE DAYS',
            'RESOLVE BY',
            'ASSESS',
            'MEMBER COMMENTS'
        ];
    }
}
