<x-app-layout>
    <div class="container mt-5">
        <h3 class="mb-3 text-center" style="font-family: 'Poppins', sans-serif; font-weight: 700;">
            Branch Report for Resolved Concerns
        </h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Concern</th>
                <th>Avg. Time to Resolve</th>
                <th>Concern Count</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @php
                $categories = [
                    ['id' => 1, 'label' => 'Loans', 'count' => $loansCount],
                    ['id' => 2, 'label' => 'Deposit', 'count' => $depositCount],
                    ['id' => 3, 'label' => 'Customer Service', 'count' => $customerCount],
                    ['id' => 4, 'label' => 'General', 'count' => $generalCount],
                ];
            @endphp

            @foreach ($categories as $category)
                <tr>
                    <td>{{ $category['id'] }}</td>
                    <td>{{ $category['label'] }}</td>
                    <td>
                            <span class="badge bg-secondary">
                                @if (!empty($averageTimes[$category['label']]))
                                    {{ $averageTimes[$category['label']]['days'] }}d
                                    {{ $averageTimes[$category['label']]['hours'] }}h
                                    {{ $averageTimes[$category['label']]['minutes'] }}m
                                @else
                                    No data
                                @endif
                            </span>
                    </td>
                    <td>{{ $category['count'] }}</td>
                    <td>
                        <a href="{{ route('concerns.download.report', ['type' => $category['label']]) }}" class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
