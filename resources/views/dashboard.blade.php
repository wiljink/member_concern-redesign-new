<x-app-layout>
    <!-- <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot> -->

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
                            <tr>
                                <td><span class="badge bg-warning">Pending</span></td>
                                <td>10</td>
                                <td>2025-01-08</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-info">Endorsed</span></td>
                                <td>15</td>
                                <td>2025-01-07</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-success">Resolved</span></td>
                                <td>25</td>
                                <td>2025-01-06</td>
                            </tr>
                            <tr>
                                <td><span class="badge bg-danger">Unresolved</span></td>
                                <td>5</td>
                                <td>2025-01-05</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>




</x-app-layout>
