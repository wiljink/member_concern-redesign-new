<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h2 class="text-lg font-semibold mb-4">Status Overview</h2>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Concern Type</th>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Last Updated</th> <!-- New Column for Last Update -->
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($concernTypes as $concern)
                                    @php
                                        // Get posts related to this concern
                                        $concernPosts = $posts->where('concern', $concern);
                                        // Get latest update for this concern
                                        $lastUpdated = $concernPosts->max('updated_at');
                                    @endphp

                                    @if($concernPosts->isEmpty())
                                        <tr>
                                            <td>{{ $concern }}</td>
                                            <td><span class="badge bg-secondary">No Data</span></td>
                                            <td>0</td>
                                            <td>-</td> <!-- No update available -->
                                        </tr>
                                    @else
                                        @foreach($concernPosts as $post)
                                            <tr>
                                                <td>{{ $post->concern }}</td>
                                                <td>
                                                    @if($post->status == 'Pending')
                                                        <span class="badge bg-warning">{{ $post->status }}</span>
                                                    @elseif($post->status == 'Validate')
                                                        <span class="badge bg-success">{{ $post->status }}</span>
                                                    @elseif($post->status == 'Endorsed')
                                                        <span class="badge bg-info">{{ $post->status }}</span>
                                                    @elseif($post->status == 'Resolved')
                                                        <span class="badge bg-danger">{{ $post->status }}</span>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $post->status }}</span>
                                                    @endif
                                                </td>
                                                <td>{{ $post->count }}</td>
                                                <td>{{ $post->last_updated ? \Carbon\Carbon::parse($post->last_updated)->format('Y-m-d H:i:s') : '-' }}</td>

                                            </tr>
                                        @endforeach
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
