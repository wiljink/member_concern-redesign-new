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
                    <th>Avg. Days to Resolve</th>
                    <th>Concern Count</th>
                    <th>Action</th> <!-- New Action Column -->
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Loans</td>
                    <td><span class="badge bg-secondary"> 2 </span></td>
                    <td>{{ $loansCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.report', ['type' => 'Loans']) }}" class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Deposit</td>
                    <td><span class="badge bg-secondary"> </span></td>
                    <td>{{ $depositCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.report', ['type' => 'Deposit']) }}" class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Customer Service</td>
                    <td><span class="badge bg-secondary"> 1 </span></td>
                    <td>{{ $customerCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.report', ['type' => 'Customer Service']) }}" class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>General</td>
                    <td><span class="badge bg-secondary"> 2 </span></td>
                    <td>{{ $generalCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.report', ['type' => 'general']) }}" class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</x-app-layout>
