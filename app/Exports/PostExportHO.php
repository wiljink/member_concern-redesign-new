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
        // Fetch all branches
        $response = Http::get('https://loantracker.oicapp.com/api/v1/branches');
        $apiResponse = $response->json();
        $branches = $apiResponse['branches'] ?? [];

        
        if (empty($branches)) {
            return collect(value: []);
        }

        // Map branch ID to names
        $branchMap = [];
        //dd($branchMap);
        foreach ($branches as $branch) {
            $branchMap[$branch['id']] = $branch['branch_name'];
            // dd($branchMap[$branch['id']]);
        }

        // Filter branches by selected area
        $areaBranches = [];
       

        if ($this->area !== 'all') {
            $areaBranches = array_column(
                array_filter($branches, fn($branch) => $branch['area'] == $this->area),
                'id'
            );
        }

        // Query posts based on branch and area
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


        // Fetch all endorse_by users in one API request
        $endorseByIds = $posts->pluck('endorse_by')->unique()->filter()->toArray();
        $userDetails = [];

        if (!empty($endorseByIds)) {
            $usersResponse = Http::withToken(session('token'))
                ->get("https://loantracker.oicapp.com/api/v1/users", ['ids' => implode(',', $endorseByIds)]);
            $usersData = $usersResponse->json()['users'] ?? [];

            foreach ($usersData as $user) {
                $userDetails[$user['id']] = $user['officer']['fullname'] ?? "Unknown User";
            }
        }

        // Process data for export
        foreach ($posts as $post) {
            $post->branch = $branchMap[$post->branch] ?? $post->branch;
            $post->endorse_to = $post->endorse_to == $this->loggedUser ? $this->branchManager : $post->endorse_to;
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
