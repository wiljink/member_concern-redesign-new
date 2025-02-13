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
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Last Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($posts as $post)
                                <tr>
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
                                    <td>{{ $post->count }}</td> <!-- Now it correctly displays the count per status -->
                                    <td>{{ \Carbon\Carbon::now()->format('Y-m-d') }}</td> <!-- Since `updated_at` isn't used, showing current date -->
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>