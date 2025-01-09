<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolve Concern</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Google Font: Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    h1, h2 {
        font-weight: 700; /* Bold */
        font-family: 'Poppins', sans-serif; /* Ensure Poppins font for both h1 and h2 */
        text-align: center;
    }

    .table th {
        background: linear-gradient(90deg, #006400, #228B22); /* Dark green shades */
        color: #fff;
    }

    .table td, .table th {
        font-weight: 600;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
    }

    .text-primary {
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .table-container {
        margin: 0 auto;
        max-width: 90%;
    }
</style>

</head>
<body>

<div class="container-fluid py-5">
    <h1>Average Resolved Concern</h1>

    <!-- Display Branch-wise Average Resolution Time -->
    @foreach ($averagesByBranch as $branchName => $concerns)
        @if (isset($concerns['branch_name']) && !empty($concerns)) <!-- Check if the branch has concerns -->
            <div class="text-center mb-5">
                <h2 class="text-primary">Branch: {{ ucfirst($concerns['branch_name']) }}</h2> <!-- Display branch name -->
            </div>
            <div class="table-container">
                <table class="table table-hover table-striped table-bordered text-center">
                    <thead>
                        <tr>
                            <th scope="col">Concern Type</th>
                            <th scope="col">Days</th>
                            <th scope="col">Hours</th>
                            <th scope="col">Minutes</th>
                            <th scope="col">Seconds</th> <!-- Add a column for Seconds if needed -->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($concerns as $concern => $timeData)
                            @if ($concern !== 'branch_name') <!-- Skip branch name from displaying as a concern -->
                                <tr>
                                    <td>{{ ucfirst($concern) }}</td>
                                    <td>{{ $timeData['days'] }}</td>
                                    <td>{{ $timeData['hours'] }}</td>
                                    <td>{{ $timeData['minutes'] }}</td>
                                    <td>{{ $timeData['seconds'] }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach
</div>

<!-- Display Resolved Posts (Filter by 'Resolved' status) -->
<div class="container">
    <h2 class="text-primary text-center mb-4">Resolved Posts</h2>
    <table class="table table-hover table-striped table-bordered text-center">
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">Name</th>
                <th scope="col">Branch</th>
                <th scope="col">Contact Number</th>
                <th scope="col">Concern Received Date</th>
                <th scope="col">Concern</th>
                <th scope="col">Message</th>
                <th scope="col">Resolved By</th>
                <th scope="col">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($posts as $post)
                @if($post->status === 'Resolved') <!-- Only show resolved posts -->
                    <tr>
                        <td>{{ $post->id }}</td>
                        <td>{{ $post->name }}</td>

                        <td>
                            @php 
                                // Find the branch name that matches the post's branch id
                                $branchName = collect($branches['branches'])->firstWhere('id', $post->branch)['branch_name'] ?? 'Unknown Branch';
                            @endphp
                            {{ $branchName }}
                        </td>

                        <td>{{ $post->contact_number }}</td>
                        <td>{{ $post->created_at->format('Y-m-d') }}</td>
                        <td>{{ $post->concern }}</td>
                        <td>{{ $post->message }}</td>
                        <td>{{ $post->resolve_by }}</td> 
                        <td>{{ $post->status }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>
</div>


    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>