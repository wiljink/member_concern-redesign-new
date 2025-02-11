<x-app-layout>
    <div class="container mt-5">
        <h3 class="mb-3 text-center" style="font-family: 'Poppins', sans-serif; font-weight: 700;">
            Average Concern All Branches
        </h3>

        <!-- Main Area Dropdown -->

        <div class="mb-3 text-center">
            <form method="get" action="{{ route('concerns.reportHeadOffice') }}">
                <label for="areaSelect" class="form-label fw-bold">Select Area:</label>
                <select id="areaSelect" name="area" class="form-select w-auto d-inline-block">
                    <option value="all" selected>All Areas</option>
                    <option value="1">Area 1</option>
                    <option value="2">Area 2</option>
                    <option value="3">Area 3</option>
                </select>
                <button type="submit" class="btn btn-success">Generate</button>
            </form>
        </div>

        <!-- Secondary Dropdowns for Specific Locations -->
        <div class="mb-3 text-center hidden" id="area1Dropdown">
            <label for="area1Select" class="form-label fw-bold">Select Branch (Area 1):</label>
            <select id="area1Select" class="form-select w-auto d-inline-block">
                <option value="" selected>Select Branch</option>
                <option value="yacapin">Yacapin</option>
                <option value="agora">Agora</option>
                <option value="carmen">Carmen</option>
            </select>
        </div>



        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Concern Type</th>
                    <th>Avg. Days to Resolve</th>
                    <th>Count</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td>Loans</td>
                    <td><span class="badge bg-secondary">Archived</span></td>
                    <td>{{ $loansCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.reportho', ['type' => 'Loans']) }}"
                            class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Deposit</td>
                    <td><span class="badge bg-secondary">Archived</span></td>
                    <td>{{ $depositCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.reportho', ['type' => 'Deposit']) }}"
                            class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>Customer Service</td>
                    <td><span class="badge bg-secondary">Archived</span></td>
                    <td>{{ $customerCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.reportho', ['type' => 'Customer Service']) }}"
                            class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>General</td>
                    <td><span class="badge bg-secondary">Archived</span></td>
                    <td>{{ $generalCount }}</td>
                    <td>
                        <a href="{{ route('concerns.download.reportho', ['type' => 'general']) }}"
                            class="btn btn-primary btn-sm">Download</a>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>




</x-app-layout>
