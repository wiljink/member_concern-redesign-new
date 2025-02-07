<?php

namespace App\Exports;

use App\Models\Post;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PostExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    protected $concern;
    protected $loggedUser;

    public function __construct($concern)
    {

        $token = session("token");
        $getUserResponse = Http::withToken($token)
            ->get("https://loantracker.oicapp.com/api/v1/users/logged-user");
        $user = $getUserResponse->json();

        $this->loggedUser = $user['user']['id'];

        $this->concern = $concern;
    }

    public function collection()
    {


        $post = Post::select('id', 'name', 'branch', 'contact_number', 'concern', 'message', 'endorse_by', 'endorse_to', 'tasks', 'endorsed_date', 'resolved_date', 'resolved_days', 'resolve_by', 'assess', 'member_comments')
            ->where('endorse_to', $this->loggedUser)
            ->where('status', 'Archived')
            ->where('concern', $this->concern)
            ->get();
        return $post;
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
